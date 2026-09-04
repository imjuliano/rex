<?php
declare(strict_types=1);

namespace App\Http;

/**
 * One page of results plus the totals needed to build meta and links.
 */
final class PaginatedCollection {
    /** @param list<array<string, mixed>> $items */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $page,
        public readonly int $perPage,
    ) {}

    public function totalPages(): int {
        return $this->perPage > 0 ? (int) ceil($this->total / $this->perPage) : 0;
    }

    public function count(): int {
        return count($this->items);
    }
}
