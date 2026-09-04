<?php
declare(strict_types=1);

namespace App\Auth;

use DateTimeImmutable;

/**
 * Storage contract for the refresh-token chain.
 *
 * Mirrors AuditLogWriter: the service depends on the behaviour, not on MySQL,
 * so the rotation rules can be exercised without a database.
 */
interface RefreshTokenStore {
    public function create(int $userId, string $tokenHash, string $familyId, DateTimeImmutable $expiresAt): int;

    /**
     * Must lock the row for the remainder of the transaction so two concurrent
     * refreshes cannot both spend the same token.
     *
     * @return array<string, mixed>|null
     */
    public function findByHashForUpdate(string $tokenHash): ?array;

    /** Spends a token: marked used and revoked at once. */
    public function markUsed(int $id): void;

    public function revokeFamily(string $familyId): int;

    public function revokeAllForUser(int $userId): int;

    public function deleteExpiredForUser(int $userId): int;
}
