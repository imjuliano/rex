<?php
declare(strict_types=1);

namespace App\Tests\Unit\Auth;

use App\Audit\AuditLogDispatcher;
use App\Audit\LogAction;
use App\Auth\Dto\LoginDto;
use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Exception\InvalidRefreshTokenException;
use App\Auth\Exception\MissingRefreshTokenException;
use App\Auth\Exception\RefreshTokenReusedException;
use App\Auth\Service\AuthService;
use App\Auth\TokenPair;
use App\Tests\Support\InMemoryRefreshTokenStore;
use App\Tests\Support\RecordingAuditLogWriter;
use App\TransactionRunner;
use App\User\Dao\UserDao;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase {
    private const SECRET = 'test-secret-not-used-anywhere-else';
    private const PASSWORD = 'correct-horse-battery-staple';

    private PDO $pdo;
    private InMemoryRefreshTokenStore $store;
    private RecordingAuditLogWriter $auditWriter;
    private AuthService $auth;

    protected function setUp(): void {
        // SQLite is enough here: UserDao's queries are portable, and the parts
        // that are not (FOR UPDATE, NOW()) live behind RefreshTokenStore.
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL,
                deleted_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            )'
        );

        $this->store = new InMemoryRefreshTokenStore();
        $this->auditWriter = new RecordingAuditLogWriter();
        $this->auth = new AuthService(
            self::SECRET,
            new UserDao($this->pdo),
            $this->store,
            new TransactionRunner($this->pdo),
            new AuditLogDispatcher($this->auditWriter)
        );

        $this->seedUser('seller@rex.test', 'seller');
    }

    // ----------------------------------------------------------------- login

    public function test_login_issues_an_access_token_and_a_refresh_token(): void {
        $pair = $this->login();

        $this->assertNotSame('', $pair->accessToken);
        $this->assertNotSame('', $pair->refreshToken);
        $this->assertSame('seller@rex.test', $pair->userEmail);
        $this->assertSame('seller', $pair->userRole);
        $this->assertSame(1, $this->store->liveCount());
    }

    public function test_access_token_expires_in_fifteen_minutes(): void {
        $pair = $this->login();

        $this->assertSame(900, $pair->expiresIn());

        $claims = (array) JWT::decode($pair->accessToken, new Key(self::SECRET, 'HS256'));
        $this->assertSame(900, $claims['exp'] - $claims['iat']);
        $this->assertSame('rex', $claims['iss']);
        $this->assertSame('seller', $claims['role']);
    }

    public function test_refresh_token_expires_in_seven_days(): void {
        $pair = $this->login();

        $this->assertSame(604800, $pair->refreshExpiresAt - $pair->accessIssuedAt);
    }

    public function test_refresh_token_is_never_stored_in_plain_text(): void {
        $pair = $this->login();

        $stored = $this->store->rows()[0]['token_hash'];
        $this->assertNotSame($pair->refreshToken, $stored);
        $this->assertSame(hash('sha256', $pair->refreshToken), $stored);
    }

    public function test_wrong_password_is_rejected_and_audited(): void {
        $this->expectException(InvalidCredentialsException::class);

        try {
            $this->auth->login(new LoginDto('seller@rex.test', 'wrong-password'));
        } finally {
            $this->assertTrue($this->auditWriter->has(LogAction::AUTH_LOGIN_FAILED));
            $this->assertSame(0, $this->store->liveCount());
        }
    }

    public function test_unknown_email_is_rejected(): void {
        $this->expectException(InvalidCredentialsException::class);

        $this->auth->login(new LoginDto('nobody@rex.test', self::PASSWORD));
    }

    // --------------------------------------------------------------- refresh

    public function test_refresh_returns_a_new_pair(): void {
        $first = $this->login();
        $second = $this->auth->refresh($first->refreshToken);

        $this->assertNotSame($first->refreshToken, $second->refreshToken);
        $this->assertNotSame($first->accessToken, $second->accessToken);
        $this->assertSame($first->userId, $second->userId);
        $this->assertTrue($this->auditWriter->has(LogAction::AUTH_TOKEN_REFRESHED));
    }

    public function test_rotation_leaves_exactly_one_live_token(): void {
        $pair = $this->login();
        $this->auth->refresh($pair->refreshToken);

        $this->assertSame(1, $this->store->liveCount());
        $this->assertCount(2, $this->store->rows());
    }

    public function test_rotated_token_stays_in_the_same_family(): void {
        $pair = $this->login();
        $this->auth->refresh($pair->refreshToken);

        $families = array_unique(array_column($this->store->rows(), 'family_id'));
        $this->assertCount(1, $families);
    }

    public function test_refresh_chain_can_be_followed_repeatedly(): void {
        $pair = $this->login();

        for ($i = 0; $i < 5; $i++) {
            $pair = $this->auth->refresh($pair->refreshToken);
        }

        $this->assertSame(1, $this->store->liveCount());
    }

    public function test_missing_refresh_token_is_rejected(): void {
        $this->expectException(MissingRefreshTokenException::class);

        $this->auth->refresh(null);
    }

    public function test_blank_refresh_token_is_rejected(): void {
        $this->expectException(MissingRefreshTokenException::class);

        $this->auth->refresh('   ');
    }

    public function test_unknown_refresh_token_is_rejected(): void {
        $this->expectException(InvalidRefreshTokenException::class);

        $this->auth->refresh('a-token-that-was-never-issued');
    }

    public function test_expired_refresh_token_is_rejected(): void {
        $pair = $this->login();
        $this->store->expire(hash('sha256', $pair->refreshToken));

        $this->expectException(InvalidRefreshTokenException::class);

        $this->auth->refresh($pair->refreshToken);
    }

    public function test_soft_deleted_user_cannot_refresh(): void {
        $pair = $this->login();
        $this->pdo->exec("UPDATE users SET deleted_at = '2020-01-01 00:00:00' WHERE id = {$pair->userId}");

        $this->expectException(InvalidRefreshTokenException::class);

        $this->auth->refresh($pair->refreshToken);
    }

    // ------------------------------------------------------- reuse detection

    public function test_replaying_an_old_token_revokes_the_whole_family(): void {
        $first = $this->login();
        $second = $this->auth->refresh($first->refreshToken);

        // Age the spent token so it is no longer covered by the grace window.
        $this->store->backdateUsedAt(hash('sha256', $first->refreshToken), 60);

        try {
            $this->auth->refresh($first->refreshToken);
            $this->fail('Expected the replay to be rejected.');
        } catch (RefreshTokenReusedException $e) {
            $this->assertSame('REFRESH_TOKEN_REUSED', $e->errorCode()->value);
        }

        // The still-unused successor must die with the rest of the chain.
        $this->assertSame(0, $this->store->liveCount());
        $this->assertTrue($this->auditWriter->has(LogAction::AUTH_REFRESH_REUSE_DETECTED));

        $this->expectException(RefreshTokenReusedException::class);
        $this->auth->refresh($second->refreshToken);
    }

    public function test_revocation_survives_the_failed_refresh(): void {
        $first = $this->login();
        $this->auth->refresh($first->refreshToken);
        $this->store->backdateUsedAt(hash('sha256', $first->refreshToken), 60);

        try {
            $this->auth->refresh($first->refreshToken);
        } catch (RefreshTokenReusedException) {
            // The throw happens after the transaction commits; if it happened
            // inside, the rollback would resurrect the leaked chain.
        }

        $this->assertSame(0, $this->store->liveCount());
    }

    public function test_near_simultaneous_replay_is_tolerated(): void {
        $first = $this->login();
        $this->auth->refresh($first->refreshToken);

        // Two tabs sharing one cookie: the second call arrives within the grace
        // window and must not be mistaken for a stolen token.
        $retry = $this->auth->refresh($first->refreshToken);

        $this->assertInstanceOf(TokenPair::class, $retry);
        $this->assertFalse($this->auditWriter->has(LogAction::AUTH_REFRESH_REUSE_DETECTED));
    }

    // ---------------------------------------------------------------- logout

    public function test_logout_revokes_the_family_and_is_audited(): void {
        $pair = $this->login();
        $this->auth->logout($pair->refreshToken);

        $this->assertSame(0, $this->store->liveCount());
        $this->assertTrue($this->auditWriter->has(LogAction::AUTH_LOGOUT));
    }

    public function test_refresh_after_logout_fails(): void {
        $pair = $this->login();
        $this->auth->logout($pair->refreshToken);

        $this->expectException(RefreshTokenReusedException::class);

        $this->auth->refresh($pair->refreshToken);
    }

    public function test_logout_without_a_token_is_a_no_op(): void {
        $this->auth->logout(null);

        $this->assertSame([], $this->auditWriter->actions());
    }

    public function test_logout_with_an_unknown_token_is_silent(): void {
        $this->auth->logout('never-issued');

        $this->assertSame([], $this->auditWriter->actions());
    }

    public function test_revoking_all_sessions_kills_every_family(): void {
        $first = $this->login();
        $second = $this->login();

        $revoked = $this->auth->revokeAllSessions($first->userId);

        $this->assertSame(2, $revoked);
        $this->assertSame(0, $this->store->liveCount());

        $this->expectException(RefreshTokenReusedException::class);
        $this->auth->refresh($second->refreshToken);
    }

    // --------------------------------------------------------------- helpers

    private function login(): TokenPair {
        return $this->auth->login(new LoginDto('seller@rex.test', self::PASSWORD));
    }

    private function seedUser(string $email, string $role): void {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute(['Test User', $email, password_hash(self::PASSWORD, PASSWORD_DEFAULT), $role]);
    }
}
