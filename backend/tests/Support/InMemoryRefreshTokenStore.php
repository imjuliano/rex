<?php
declare(strict_types=1);

namespace App\Tests\Support;

use App\Auth\RefreshTokenStore;
use DateTimeImmutable;

/**
 * In-memory stand-in for RefreshTokenDao.
 *
 * The MySQL DAO leans on FOR UPDATE and NOW(), neither of which SQLite offers,
 * and weakening that SQL to suit a test would remove the very lock that makes
 * rotation safe. The rules under test live in AuthService, so the storage is
 * faked instead.
 */
final class InMemoryRefreshTokenStore implements RefreshTokenStore {
    private int $nextId = 1;

    /** @var array<int, array<string, mixed>> */
    private array $rows = [];

    public function create(int $userId, string $tokenHash, string $familyId, DateTimeImmutable $expiresAt): int {
        $id = $this->nextId++;
        $this->rows[$id] = [
            'id' => $id,
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'family_id' => $familyId,
            'expires_at' => $expiresAt->format('Y-m-d H:i:s'),
            'used_at' => null,
            'revoked_at' => null,
        ];
        return $id;
    }

    public function findByHashForUpdate(string $tokenHash): ?array {
        foreach ($this->rows as $row) {
            if (hash_equals((string) $row['token_hash'], $tokenHash)) {
                return $row;
            }
        }
        return null;
    }

    public function markUsed(int $id): void {
        $now = date('Y-m-d H:i:s');
        $this->rows[$id]['used_at'] = $now;
        $this->rows[$id]['revoked_at'] = $now;
    }

    public function revokeFamily(string $familyId): int {
        return $this->revokeWhere(fn(array $row): bool => $row['family_id'] === $familyId);
    }

    public function revokeAllForUser(int $userId): int {
        return $this->revokeWhere(fn(array $row): bool => (int) $row['user_id'] === $userId);
    }

    public function deleteExpiredForUser(int $userId): int {
        $deleted = 0;
        $now = new DateTimeImmutable();
        foreach ($this->rows as $id => $row) {
            if ((int) $row['user_id'] === $userId && new DateTimeImmutable((string) $row['expires_at']) < $now) {
                unset($this->rows[$id]);
                $deleted++;
            }
        }
        return $deleted;
    }

    // ------------------------------------------------------------- helpers

    /** @return list<array<string, mixed>> */
    public function rows(): array {
        return array_values($this->rows);
    }

    public function liveCount(): int {
        return count(array_filter($this->rows, fn(array $row): bool => $row['revoked_at'] === null));
    }

    /** Ages a spent token so it falls outside the replay grace window. */
    public function backdateUsedAt(string $tokenHash, int $secondsAgo): void {
        foreach ($this->rows as $id => $row) {
            if (hash_equals((string) $row['token_hash'], $tokenHash)) {
                $this->rows[$id]['used_at'] = date('Y-m-d H:i:s', time() - $secondsAgo);
                return;
            }
        }
    }

    /** Forces a token past its expiry without waiting for it. */
    public function expire(string $tokenHash): void {
        foreach ($this->rows as $id => $row) {
            if (hash_equals((string) $row['token_hash'], $tokenHash)) {
                $this->rows[$id]['expires_at'] = date('Y-m-d H:i:s', time() - 60);
                return;
            }
        }
    }

    public function contains(string $tokenHash): bool {
        return $this->findByHashForUpdate($tokenHash) !== null;
    }

    /** @param callable(array<string, mixed>): bool $matches */
    private function revokeWhere(callable $matches): int {
        $count = 0;
        foreach ($this->rows as $id => $row) {
            if ($matches($row) && $row['revoked_at'] === null) {
                $this->rows[$id]['revoked_at'] = date('Y-m-d H:i:s');
                $count++;
            }
        }
        return $count;
    }
}
