<?php
declare(strict_types=1);

namespace App\Tests\Unit\User;

use App\Audit\AuditLogDispatcher;
use App\Tests\Support\InMemoryRefreshTokenStore;
use App\Tests\Support\RecordingAuditLogWriter;
use App\User\Dao\UserDao;
use App\User\Dto\UpdateUserDto;
use App\User\Mapper\UserMapper;
use App\User\Service\UserWriteService;
use DateTimeImmutable;
use PDO;
use PHPUnit\Framework\TestCase;

/**
 * Revocation is the whole reason refresh tokens are stateful, so the paths that
 * ought to end a session are worth pinning down.
 */
final class UserWriteServiceSessionRevocationTest extends TestCase {
    private const USER_ID = 1;
    private const OTHER_USER_ID = 2;

    private PDO $pdo;
    private InMemoryRefreshTokenStore $store;
    private UserWriteService $service;

    protected function setUp(): void {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        // UserDao soft-deletes with NOW(). Teaching SQLite the function keeps
        // the production SQL untouched.
        $this->pdo->sqliteCreateFunction('NOW', fn(): string => date('Y-m-d H:i:s'), 0);
        $this->pdo->exec(
            'CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                role TEXT NOT NULL,
                deleted_at TEXT DEFAULT NULL,
                created_at TEXT DEFAULT "2026-01-01 00:00:00"
            )'
        );
        $this->pdo->exec(
            "INSERT INTO users (name, email, password_hash, role) VALUES
             ('Target', 'target@rex.test', 'hash', 'seller'),
             ('Other', 'other@rex.test', 'hash', 'seller')"
        );

        $this->store = new InMemoryRefreshTokenStore();
        $this->service = new UserWriteService(
            new UserDao($this->pdo),
            new UserMapper(),
            new AuditLogDispatcher(new RecordingAuditLogWriter()),
            $this->store
        );
    }

    public function test_changing_a_password_ends_every_session_of_that_user(): void {
        $this->giveSession(self::USER_ID);
        $this->giveSession(self::USER_ID);
        $this->giveSession(self::OTHER_USER_ID);

        $this->service->update(self::USER_ID, new UpdateUserDto(password: 'a-brand-new-password'), $this->actor());

        // Only the target's sessions die; the other user keeps working.
        $this->assertSame(1, $this->store->liveCount());
    }

    public function test_renaming_a_user_leaves_sessions_alone(): void {
        $this->giveSession(self::USER_ID);

        $this->service->update(self::USER_ID, new UpdateUserDto(name: 'Renamed'), $this->actor());

        $this->assertSame(1, $this->store->liveCount());
    }

    public function test_deleting_a_user_ends_their_sessions(): void {
        $this->giveSession(self::USER_ID);
        $this->giveSession(self::OTHER_USER_ID);

        $this->service->softDelete(self::USER_ID, $this->actor());

        $this->assertSame(1, $this->store->liveCount());
    }

    private function giveSession(int $userId): void {
        $this->store->create(
            $userId,
            hash('sha256', bin2hex(random_bytes(8))),
            bin2hex(random_bytes(16)),
            (new DateTimeImmutable())->modify('+7 days')
        );
    }

    /** @return array<string, mixed> */
    private function actor(): array {
        return ['id' => 99, 'email' => 'admin@rex.test', 'role' => 'admin'];
    }
}
