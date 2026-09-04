<?php
declare(strict_types=1);

namespace App\Validation;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;
use DateTimeImmutable;

/**
 * Guard clauses for request input.
 *
 * Every method either returns the coerced value or throws a
 * ValidationException naming the offending field, so handlers stay
 * free of defensive branching.
 */
final class Assert {
    private function __construct() {}

    /**
     * @param array<string, mixed> $body
     * @param list<string> $fields
     */
    public static function requiredFields(array $body, array $fields): void {
        $missing = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $body) || $body[$field] === '' || $body[$field] === null) {
                $missing[] = $field;
            }
        }
        if ($missing !== []) {
            throw new ValidationException(
                ErrorCode::MISSING_FIELD,
                count($missing) === 1
                    ? sprintf("Field '%s' is required.", $missing[0])
                    : sprintf('Fields %s are required.', "'" . implode("', '", $missing) . "'"),
                ['fields' => $missing]
            );
        }
    }

    public static function nonEmptyString(
        mixed $value,
        string $field,
        int $maxLength = Limits::PRODUCT_NAME
    ): string {
        if (!is_string($value)) {
            throw ValidationException::invalidField($field, 'a string');
        }
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw ValidationException::invalidField($field, 'a non-empty string');
        }
        $len = mb_strlen($trimmed);
        if ($len > $maxLength) {
            throw ValidationException::tooLong($field, $maxLength, $len);
        }
        return $trimmed;
    }

    /**
     * Accepts an integer that is at least 1 and at most $max.
     * Rejects floats, booleans and non-digit strings, which is what
     * the MySQL INT columns require.
     */
    public static function positiveInt(mixed $value, string $field, int $max = Limits::INT_MAX): int {
        if (!self::looksLikeInteger($value)) {
            throw ValidationException::invalidField($field, 'a positive integer');
        }
        $int = (int) $value;
        if ($int <= 0 || $int > $max) {
            throw ValidationException::outOfRange($field, 1, $max);
        }
        return $int;
    }

    /**
     * Accepts an integer that is at least 0 and at most $max.
     * Useful for point counts, budget totals and counters.
     */
    public static function nonNegativeInt(mixed $value, string $field, int $max = Limits::INT_MAX): int {
        if (!self::looksLikeInteger($value)) {
            throw ValidationException::invalidField($field, 'a non-negative integer');
        }
        $int = (int) $value;
        if ($int < 0 || $int > $max) {
            throw ValidationException::outOfRange($field, 0, $max);
        }
        return $int;
    }

    /**
     * Accepts a non-negative decimal number up to $max. Rejects
     * scientific notation and strings that are "numeric" but contain
     * stray characters (e.g. "1e9", "10abc").
     */
    public static function nonNegativeNumber(
        mixed $value,
        string $field,
        float $max = Limits::UNIT_VALUE_MAX
    ): float {
        if (!is_string($value) && !is_int($value) && !is_float($value)) {
            throw ValidationException::invalidField($field, 'a number');
        }
        $str = is_string($value) ? $value : (string) $value;
        if ($str === '' || !preg_match('/^-?(\d+)(\.\d+)?$/', $str)) {
            throw ValidationException::invalidField($field, 'a non-negative number');
        }
        $float = (float) $str;
        if ($float < 0 || $float > $max) {
            throw ValidationException::outOfRange($field, 0, $max);
        }
        // Coerce to two decimals before returning, so later rounding matches
        // the DECIMAL(10,2) storage.
        return round($float, 2);
    }

    public static function boolean(mixed $value, string $field): bool {
        $parsed = filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        if ($parsed === null) {
            throw ValidationException::invalidField($field, 'a boolean');
        }
        return $parsed;
    }

    public static function email(mixed $value, string $field = 'email'): string {
        if (!is_string($value) || !filter_var(trim($value), FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::invalidField($field, 'a valid e-mail address');
        }
        return trim($value);
    }

    /**
     * Parses the ISO-ish formats the API accepts. Uses '!' in the formats
     * so missing time components are reset to 00:00:00, then checks
     * DateTimeImmutable::getLastErrors() to reject rollovers such as
     * "2026-13-45" which would otherwise be silently converted.
     */
    public static function dateTime(mixed $value, string $field): DateTimeImmutable {
        if (!is_string($value)) {
            throw ValidationException::invalidField($field, 'a date string');
        }
        $value = trim($value);
        foreach (['!Y-m-d H:i:s', '!Y-m-d\TH:i:s', '!Y-m-d\TH:i', '!Y-m-d'] as $format) {
            $dt = DateTimeImmutable::createFromFormat($format, $value);
            if ($dt === false) {
                continue;
            }
            $errors = DateTimeImmutable::getLastErrors() ?: ['warning_count' => 0, 'error_count' => 0];
            if ((int) ($errors['warning_count'] ?? 0) === 0 && (int) ($errors['error_count'] ?? 0) === 0) {
                return $dt;
            }
        }
        throw ValidationException::invalidField($field, 'a valid date');
    }

    public static function isBefore(DateTimeImmutable $earlier, DateTimeImmutable $later, string $field): void {
        if ($earlier >= $later) {
            throw new ValidationException(
                ErrorCode::INVALID_FIELD,
                sprintf("Field '%s' must be after the start date.", $field),
                ['field' => $field]
            );
        }
    }

    /**
     * Rejects a date that is already over. Used for campaign ends_at on
     * creation — a campaign whose entire window is in the past is almost
     * always a user typo and not a deliberate back-dating.
     */
    public static function notInPast(DateTimeImmutable $value, string $field, DateTimeImmutable $now): void {
        if ($value <= $now) {
            throw new ValidationException(
                ErrorCode::DATE_IN_THE_PAST,
                sprintf("Field '%s' must be in the future.", $field),
                ['field' => $field, 'now' => $now->format('Y-m-d H:i:s')]
            );
        }
    }

    /**
     * @param array<string, mixed> $fields
     */
    public static function notEmptyUpdate(array $fields): void {
        if ($fields === []) {
            throw new ValidationException(
                ErrorCode::NO_FIELDS_TO_UPDATE,
                'Provide at least one field to update.'
            );
        }
    }

    private static function looksLikeInteger(mixed $value): bool {
        if (is_int($value)) {
            return true;
        }
        if (!is_string($value) || $value === '') {
            return false;
        }
        // Matches an optional minus sign followed by digits. No plus sign,
        // no leading/trailing spaces, no decimal points, no underscores.
        return (bool) preg_match('/^-?\d+$/', trim($value));
    }
}
