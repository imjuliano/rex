<?php
declare(strict_types=1);

namespace App\Auth\Dao;

use App\Auth\RefreshTokenStore;
use DateTimeImmutable;
use PDO;

/**
 * Persistence for the refresh-token chain.
 *
 * Only SHA-256 hashes are stored, so the raw token exists nowhere but in the
 * client cookie. Lookups therefore always go through the hash.
 */
final class RefreshTokenDao implements RefreshTokenStore {
    private const
        INSERT_SQL =
            'INSERT INTO refresh_tokens (user_id, token_hash, family_id, expires_at) VALUES (?, ?, ?, ?)';

    private const
        FIND_BY_HASH_FOR_UPDATE_SQL =
            'SELECT id, user_id, token_hash, family_id, expires_at, used_at, revoked_at
             FROM refresh_tokens WHERE token_hash = ? FOR UPDATE';

    private const
        MARK_USED_SQL =
            'UPDATE refresh_tokens SET used_at = NOW(), revoked_at = NOW() WHERE id = ?';

    private const
        REVOKE_FAMILY_SQL =
            'UPDATE refresh_tokens SET revoked_at = NOW() WHERE family_id = ? AND revoked_at IS NULL';

    private const
        REVOKE_FOR_USER_SQL =
            'UPDATE refresh_tokens SET revoked_at = NOW() WHERE user_id = ? AND revoked_at IS NULL';

    private const
        DELETE_EXPIRED_FOR_USER_SQL =
            'DELETE FROM refresh_tokens WHERE user_id = ? AND expires_at < NOW()';

    public function __construct(private PDO $pdo) {}

    public function create(int $userId, string $tokenHash, string $familyId, DateTimeImmutable $expiresAt): int {
        $stmt = $this->pdo->prepare(self::INSERT_SQL);
        $stmt->execute([$userId, $tokenHash, $familyId, $expiresAt->format('Y-m-d H:i:s')]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Locks the row so two concurrent refreshes with the same token cannot
     * both pass the reuse check.
     */
    public function findByHashForUpdate(string $tokenHash): ?array {
        $stmt = $this->pdo->prepare(self::FIND_BY_HASH_FOR_UPDATE_SQL);
        $stmt->execute([$tokenHash]);
        return $stmt->fetch() ?: null;
    }

    /** Spends a token: it is simultaneously marked used and revoked. */
    public function markUsed(int $id): void {
        $stmt = $this->pdo->prepare(self::MARK_USED_SQL);
        $stmt->execute([$id]);
    }

    public function revokeFamily(string $familyId): int {
        $stmt = $this->pdo->prepare(self::REVOKE_FAMILY_SQL);
        $stmt->execute([$familyId]);
        return $stmt->rowCount();
    }

    public function revokeAllForUser(int $userId): int {
        $stmt = $this->pdo->prepare(self::REVOKE_FOR_USER_SQL);
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }

    /** Housekeeping, called on login so the table cannot grow unbounded. */
    public function deleteExpiredForUser(int $userId): int {
        $stmt = $this->pdo->prepare(self::DELETE_EXPIRED_FOR_USER_SQL);
        $stmt->execute([$userId]);
        return $stmt->rowCount();
    }
}
