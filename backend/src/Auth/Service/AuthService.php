<?php
declare(strict_types=1);

namespace App\Auth\Service;

use App\Audit\AuditEvent;
use App\Audit\AuditLogDispatcher;
use App\Audit\LogAction;
use App\Audit\LogEntity;
use App\Auth\Dto\LoginDto;
use App\Auth\Exception\ForbiddenRoleException;
use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Exception\InvalidRefreshTokenException;
use App\Auth\Exception\InvalidTokenException;
use App\Auth\Exception\MissingRefreshTokenException;
use App\Auth\Exception\MissingTokenException;
use App\Auth\Exception\RefreshTokenReusedException;
use App\Auth\RefreshTokenStore;
use App\Auth\TokenPair;
use App\TransactionRunner;
use App\User\Dao\UserDao;
use App\User\Exception\UserNotFoundException;
use DateTimeImmutable;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Throwable;

/**
 * Access tokens are short-lived and stateless; refresh tokens are long-lived
 * and stateful. That split is what makes revocation possible: a JWT cannot be
 * un-issued, but the row backing a refresh token can be revoked at any time,
 * which caps how long a leaked session survives at ACCESS_TTL_SECONDS.
 */
final class AuthService {
    private const ISSUER = 'rex';
    private const ACCESS_TTL_SECONDS = 900;       // 15 minutes
    private const REFRESH_TTL_SECONDS = 604800;   // 7 days
    private const REFRESH_TOKEN_BYTES = 32;       // 256 bits of CSPRNG entropy

    /**
     * Two tabs sharing one cookie can fire /auth/refresh at nearly the same
     * instant. Both send the same token, so the loser would look like a replay
     * and get the whole family revoked — the user is logged out for being
     * ordinary. A replay this soon after the original is treated as that race
     * instead of theft. A stolen token surfacing later still trips detection,
     * which is what the mechanism is actually for.
     */
    private const REPLAY_GRACE_SECONDS = 10;

    private ?array $user = null;
    private bool $resolved = false;

    public function __construct(
        private string $secret,
        private UserDao $userDao,
        private RefreshTokenStore $refreshTokens,
        private TransactionRunner $tx,
        private AuditLogDispatcher $audit,
    ) {}

    public function login(LoginDto $dto): TokenPair {
        $user = $this->userDao->findByEmail($dto->email);

        // Constant-time failure: verify against a dummy hash when the user is absent
        // so the response time does not reveal whether the e-mail exists.
        $hash = $user['password_hash'] ?? '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidin';

        if (!password_verify($dto->password, $hash) || !$user) {
            $this->auditLoginFailed($dto->email);
            throw new InvalidCredentialsException();
        }

        $userId = (int) $user['id'];
        $pair = $this->tx->run(function () use ($userId, $user): TokenPair {
            $this->refreshTokens->deleteExpiredForUser($userId);
            return $this->issuePair($user, $this->newFamilyId());
        });

        $this->auditLoginSuccess($pair);

        return $pair;
    }

    /**
     * Exchanges a refresh token for a brand new pair, invalidating the one
     * that was presented.
     */
    public function refresh(?string $rawRefreshToken): TokenPair {
        if ($rawRefreshToken === null || trim($rawRefreshToken) === '') {
            throw new MissingRefreshTokenException();
        }

        $hash = $this->hashRefreshToken($rawRefreshToken);
        $outcome = $this->tx->run(fn(): array => $this->rotate($hash));

        // Reuse is reported only after the transaction committed. Throwing from
        // inside would roll the revocation back and leave the leaked chain live.
        if ($outcome['reused']) {
            $this->auditReuseDetected($outcome['userId'], $outcome['revoked']);
            throw new RefreshTokenReusedException();
        }

        $this->auditRefreshed($outcome['pair']);

        return $outcome['pair'];
    }

    /** Idempotent: an unknown or already-revoked token is not an error. */
    public function logout(?string $rawRefreshToken): void {
        if ($rawRefreshToken === null || trim($rawRefreshToken) === '') {
            return;
        }

        $hash = $this->hashRefreshToken($rawRefreshToken);
        $result = $this->tx->run(function () use ($hash): array {
            $row = $this->refreshTokens->findByHashForUpdate($hash);
            if ($row === null) {
                return ['userId' => null, 'revoked' => 0];
            }
            return [
                'userId' => (int) $row['user_id'],
                'revoked' => $this->refreshTokens->revokeFamily((string) $row['family_id']),
            ];
        });

        if ($result['userId'] !== null) {
            $this->auditLogout($result['userId'], $result['revoked']);
        }
    }

    /**
     * Kills every session of a user at once. Called when an account is removed
     * or its password changes, so stolen tokens stop working immediately.
     */
    public function revokeAllSessions(int $userId): int {
        return $this->tx->run(fn(): int => $this->refreshTokens->revokeAllForUser($userId));
    }

    public function currentUser(): ?array {
        if ($this->resolved) {
            return $this->user;
        }
        $this->resolved = true;

        $header = $this->authorizationHeader();
        if ($header === '') {
            return $this->user = null;
        }
        if (!str_starts_with($header, 'Bearer ')) {
            throw new InvalidTokenException();
        }

        try {
            $claims = (array) JWT::decode(substr($header, 7), new Key($this->secret, 'HS256'));
        } catch (Throwable $e) {
            throw new InvalidTokenException($e);
        }

        if (!isset($claims['sub'], $claims['role'])) {
            throw new InvalidTokenException();
        }

        return $this->user = [
            'id' => (int) $claims['sub'],
            'email' => (string) ($claims['email'] ?? ''),
            'role' => (string) $claims['role'],
        ];
    }

    public function requireUser(): array {
        $user = $this->currentUser();
        if ($user === null) {
            throw new MissingTokenException();
        }
        return $user;
    }

    /**
     * @param list<string> $roles
     * @throws AuthenticationException|AuthorizationException
     */
    public function requireRole(array $roles): array {
        $user = $this->requireUser();
        if ($roles !== [] && !in_array($user['role'], $roles, true)) {
            throw new ForbiddenRoleException($roles);
        }
        return $user;
    }

    public function claims(string $token): array {
        return (array) JWT::decode($token, new Key($this->secret, 'HS256'));
    }

    // ------------------------------------------------------------- rotation

    /**
     * Runs inside a transaction with the token row locked, so two concurrent
     * refreshes cannot both spend the same token.
     *
     * @return array{reused: bool, userId: int, revoked: int, pair: ?TokenPair}
     */
    private function rotate(string $hash): array {
        $row = $this->refreshTokens->findByHashForUpdate($hash);
        if ($row === null) {
            throw new InvalidRefreshTokenException();
        }

        $alreadySpent = $row['used_at'] !== null || $row['revoked_at'] !== null;

        if ($alreadySpent && !$this->isWithinReplayGrace($row)) {
            return [
                'reused' => true,
                'userId' => (int) $row['user_id'],
                'revoked' => $this->refreshTokens->revokeFamily((string) $row['family_id']),
                'pair' => null,
            ];
        }

        if ($this->isExpired((string) $row['expires_at'])) {
            throw new InvalidRefreshTokenException();
        }

        $user = $this->findActiveUser((int) $row['user_id']);
        if (!$alreadySpent) {
            $this->refreshTokens->markUsed((int) $row['id']);
        }

        return [
            'reused' => false,
            'userId' => (int) $row['user_id'],
            'revoked' => 0,
            'pair' => $this->issuePair($user, (string) $row['family_id']),
        ];
    }

    /**
     * Only an explicit logout or a detected replay sets revoked_at without
     * used_at, and neither should be forgiven — so the grace window applies
     * strictly to tokens spent by a normal rotation moments ago.
     */
    private function isWithinReplayGrace(array $row): bool {
        if ($row['used_at'] === null) {
            return false;
        }
        $usedAt = new DateTimeImmutable((string) $row['used_at']);
        return $usedAt->getTimestamp() >= time() - self::REPLAY_GRACE_SECONDS;
    }

    /** @param array<string, mixed> $user */
    private function issuePair(array $user, string $familyId): TokenPair {
        $now = time();
        $accessExpiresAt = $now + self::ACCESS_TTL_SECONDS;
        $refreshExpiresAt = $now + self::REFRESH_TTL_SECONDS;

        $userId = (int) $user['id'];
        $email = (string) $user['email'];
        $role = (string) $user['role'];

        $refreshToken = bin2hex(random_bytes(self::REFRESH_TOKEN_BYTES));
        $this->refreshTokens->create(
            $userId,
            $this->hashRefreshToken($refreshToken),
            $familyId,
            (new DateTimeImmutable())->setTimestamp($refreshExpiresAt)
        );

        return new TokenPair(
            accessToken: $this->encodeAccessToken($userId, $email, $role, $now, $accessExpiresAt),
            accessExpiresAt: $accessExpiresAt,
            accessIssuedAt: $now,
            refreshToken: $refreshToken,
            refreshExpiresAt: $refreshExpiresAt,
            userId: $userId,
            userEmail: $email,
            userRole: $role,
        );
    }

    /**
     * `jti` is what makes two tokens minted in the same second distinguishable.
     * Without it the claims are byte-identical, so a rotation would hand back a
     * token indistinguishable from the one it replaced and there would be
     * nothing to key a denylist on later.
     */
    private function encodeAccessToken(int $userId, string $email, string $role, int $issuedAt, int $expiresAt): string {
        return JWT::encode([
            'iss' => self::ISSUER,
            'iat' => $issuedAt,
            'exp' => $expiresAt,
            'jti' => bin2hex(random_bytes(16)),
            'sub' => $userId,
            'email' => $email,
            'role'  => $role,
        ], $this->secret, 'HS256');
    }

    /**
     * A soft-deleted user must not be able to refresh, but saying "user not
     * found" would turn a dead session into a 404. It is an invalid session.
     */
    private function findActiveUser(int $userId): array {
        try {
            return $this->userDao->findById($userId);
        } catch (UserNotFoundException $e) {
            throw new InvalidRefreshTokenException();
        }
    }

    private function isExpired(string $expiresAt): bool {
        return new DateTimeImmutable($expiresAt) <= new DateTimeImmutable();
    }

    /**
     * SHA-256 rather than password_hash: the token is 256 bits of CSPRNG
     * output, so it is not brute-forceable and a slow KDF would only add
     * latency. Hashing at all is what keeps a stolen dump useless, and a
     * plain digest is what allows the indexed unique lookup.
     */
    private function hashRefreshToken(string $rawToken): string {
        return hash('sha256', $rawToken);
    }

    private function newFamilyId(): string {
        return bin2hex(random_bytes(16));
    }

    private function authorizationHeader(): string {
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                foreach ($headers as $name => $value) {
                    if (strcasecmp($name, 'Authorization') === 0) {
                        return (string) $value;
                    }
                }
            }
        }
        return (string) ($_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '');
    }

    // ---------------------------------------------------------------- audit

    private function auditLoginFailed(string $email): void {
        $this->audit->dispatch(new AuditEvent(
            action: LogAction::AUTH_LOGIN_FAILED,
            entity: LogEntity::AUTH,
            actorId: null,
            actorEmail: $email,
            actorRole: null,
            entityId: null,
            payload: ['reason' => 'invalid_credentials'],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));
    }

    private function auditLoginSuccess(TokenPair $pair): void {
        $this->audit->dispatch(new AuditEvent(
            action: LogAction::AUTH_LOGIN_SUCCESS,
            entity: LogEntity::AUTH,
            actorId: $pair->userId,
            actorEmail: $pair->userEmail,
            actorRole: $pair->userRole,
            entityId: null,
            payload: [
                'expires_at' => date('c', $pair->accessExpiresAt),
                'refresh_expires_at' => date('c', $pair->refreshExpiresAt),
            ],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));
    }

    private function auditRefreshed(TokenPair $pair): void {
        $this->audit->dispatch(new AuditEvent(
            action: LogAction::AUTH_TOKEN_REFRESHED,
            entity: LogEntity::AUTH,
            actorId: $pair->userId,
            actorEmail: $pair->userEmail,
            actorRole: $pair->userRole,
            entityId: null,
            payload: [
                'expires_at' => date('c', $pair->accessExpiresAt),
                'refresh_expires_at' => date('c', $pair->refreshExpiresAt),
            ],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));
    }

    private function auditReuseDetected(int $userId, int $revokedCount): void {
        $this->audit->dispatch(new AuditEvent(
            action: LogAction::AUTH_REFRESH_REUSE_DETECTED,
            entity: LogEntity::AUTH,
            actorId: $userId,
            actorEmail: null,
            actorRole: null,
            entityId: null,
            payload: [
                'reason' => 'refresh_token_replayed',
                'revoked_tokens' => $revokedCount,
            ],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));
    }

    private function auditLogout(int $userId, int $revokedCount): void {
        $this->audit->dispatch(new AuditEvent(
            action: LogAction::AUTH_LOGOUT,
            entity: LogEntity::AUTH,
            actorId: $userId,
            actorEmail: null,
            actorRole: null,
            entityId: null,
            payload: ['revoked_tokens' => $revokedCount],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));
    }
}
