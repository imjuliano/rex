<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * The request could not be understood: malformed body, missing or
 * badly typed fields. Always the client's fault. HTTP 400.
 */
class ValidationException extends AbstractDomainException {
    /** @param array<string, mixed> $details */
    public function __construct(
        ErrorCode $errorCode,
        string $message,
        array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, $message, 400, $details, $previous);
    }

    public static function missingField(string $field): self {
        return new self(
            ErrorCode::MISSING_FIELD,
            sprintf("Field '%s' is required.", $field),
            ['field' => $field]
        );
    }

    public static function invalidField(string $field, string $expected): self {
        return new self(
            ErrorCode::INVALID_FIELD,
            sprintf("Field '%s' is invalid: expected %s.", $field, $expected),
            ['field' => $field, 'expected' => $expected]
        );
    }

    public static function tooLong(string $field, int $maxLength, int $actual): self {
        return new self(
            ErrorCode::VALUE_TOO_LONG,
            sprintf("Field '%s' must be at most %d characters, got %d.", $field, $maxLength, $actual),
            ['field' => $field, 'max_length' => $maxLength, 'actual_length' => $actual]
        );
    }

    /**
     * Raised before the value reaches the database, so an out-of-range number
     * is reported as the client error it is instead of surfacing as a driver
     * failure with a 500.
     */
    public static function outOfRange(string $field, int|float $min, int|float $max): self {
        return new self(
            ErrorCode::VALUE_OUT_OF_RANGE,
            sprintf("Field '%s' must be between %s and %s.", $field, self::num($min), self::num($max)),
            ['field' => $field, 'min' => $min, 'max' => $max]
        );
    }

    private static function num(int|float $value): string {
        return is_int($value) ? (string) $value : rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    public static function invalidJsonBody(): self {
        return new self(
            ErrorCode::INVALID_JSON_BODY,
            'Request body must be a valid JSON object.'
        );
    }
}
