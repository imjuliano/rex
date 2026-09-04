<?php
declare(strict_types=1);

namespace App\Http;

/**
 * Accumulated WHERE fragments, bindings and pagination for one query.
 *
 * Fragments are always parameterised; only LIMIT/OFFSET are inlined, and
 * those come from QueryParams which has already proven they are integers.
 */
final class Criteria {
    /** @var list<string> */
    private array $conditions = [];
    /** @var list<mixed> */
    private array $bindings = [];

    public function __construct(
        private string $sortColumn,
        private string $order,
        private int $page,
        private int $perPage,
    ) {}

    /** @param list<mixed> $bindings */
    public function add(string $condition, array $bindings = []): self {
        $this->conditions[] = $condition;
        foreach ($bindings as $binding) {
            $this->bindings[] = $binding;
        }
        return $this;
    }

    public function addIfNotNull(string $condition, mixed $value): self {
        return $value === null ? $this : $this->add($condition, [$value]);
    }

    public function where(): string {
        return $this->conditions === [] ? '' : ' WHERE ' . implode(' AND ', $this->conditions);
    }

    /** @return list<mixed> */
    public function bindings(): array {
        return $this->bindings;
    }

    public function orderBy(): string {
        // Tie-break on id so pages never overlap when the sort key repeats.
        return sprintf(' ORDER BY %s %s, id %s', $this->sortColumn, strtoupper($this->order), strtoupper($this->order));
    }

    public function limit(): string {
        return sprintf(' LIMIT %d OFFSET %d', $this->perPage, ($this->page - 1) * $this->perPage);
    }

    public function page(): int {
        return $this->page;
    }

    public function perPage(): int {
        return $this->perPage;
    }
}
