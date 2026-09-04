<?php
declare(strict_types=1);

namespace App\Http;

use App\Exception\ValidationException;
use App\Http\Exception\InvalidQuerySortException;

/**
 * Typed, validated access to the query string.
 *
 * An unknown sort column or a bogus enum value is a client error, so this
 * throws ValidationException instead of silently falling back to a default —
 * silent fallbacks hide broken integrations.
 */
final class QueryParams {
    public const DEFAULT_PER_PAGE = 20;
    public const MAX_PER_PAGE = 100;

    /** @param array<string, mixed> $query */
    public function __construct(private array $query) {}

    public static function fromGlobals(): self {
        return new self($_GET ?? []);
    }

    public function has(string $key): bool {
        return isset($this->query[$key]) && $this->query[$key] !== '';
    }

    public function string(string $key, int $maxLength = 255): ?string {
        if (!$this->has($key)) {
            return null;
        }
        $value = $this->query[$key];
        if (!is_string($value)) {
            throw ValidationException::invalidField($key, 'a string');
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }
        if (mb_strlen($trimmed) > $maxLength) {
            throw ValidationException::invalidField($key, "at most $maxLength characters");
        }
        return $trimmed;
    }

    public function int(string $key, int $min = 1, int $max = PHP_INT_MAX): ?int {
        if (!$this->has($key)) {
            return null;
        }
        $value = $this->query[$key];
        if (!is_numeric($value) || (int) $value != $value) {
            throw ValidationException::invalidField($key, 'an integer');
        }
        $int = (int) $value;
        if ($int < $min || $int > $max) {
            throw ValidationException::invalidField(
                $key,
                $max === PHP_INT_MAX ? "an integer >= $min" : "an integer between $min and $max"
            );
        }
        return $int;
    }

    public function bool(string $key): ?bool {
        if (!$this->has($key)) {
            return null;
        }
        $parsed = filter_var($this->query[$key], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw ValidationException::invalidField($key, 'true or false');
        }
        return $parsed;
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enum
     * @return T|null
     */
    public function enum(string $key, string $enum): ?object {
        $raw = $this->string($key, 40);
        if ($raw === null) {
            return null;
        }
        $case = $enum::tryFrom($raw);
        if ($case === null) {
            $allowed = implode('|', array_column($enum::cases(), 'value'));
            throw ValidationException::invalidField($key, "one of: $allowed");
        }
        return $case;
    }

    public function page(): int {
        return $this->int('page', 1) ?? 1;
    }

    public function perPage(): int {
        return $this->int('per_page', 1, self::MAX_PER_PAGE) ?? self::DEFAULT_PER_PAGE;
    }

    /**
     * @param array<string, string> $allowed Maps the public field name to its SQL expression.
     */
    public function sort(array $allowed, string $default): string {
        $field = $this->string('sort', 40) ?? $default;
        if (!isset($allowed[$field])) {
            throw new InvalidQuerySortException(array_keys($allowed));
        }
        return $field;
    }

    public function order(string $default = 'desc'): string {
        $order = strtolower($this->string('order', 4) ?? $default);
        if (!in_array($order, ['asc', 'desc'], true)) {
            throw ValidationException::invalidField('order', 'asc or desc');
        }
        return $order;
    }

    /** @return array<string, mixed> Echoed back into pagination links. */
    public function all(): array {
        return $this->query;
    }
}
