<?php
declare(strict_types=1);

namespace App\Exception;

use App\Product\Exception\DuplicateSkuException;
use App\User\Exception\DuplicateEmailException;
use PDOException;

/**
 * Turns opaque driver failures into meaningful domain exceptions.
 *
 * Keeping this in one place means handlers never have to inspect
 * SQLSTATE codes or grep driver message strings.
 */
final class PdoExceptionTranslator {
    private const INTEGRITY_CONSTRAINT_VIOLATION = '23000';
    private const DEADLOCK = '40001';
    private const LOCK_WAIT_TIMEOUT = 'HY000';

    /**
     * @param array<string, string> $uniqueKeyMap Maps an index name fragment to an ErrorCode-bearing factory hint.
     */
    public static function translate(PDOException $e, array $context = []): AbstractDomainException {
        $sqlState = $e->getCode();
        $message = $e->getMessage();

        if ($sqlState === self::DEADLOCK || str_contains($message, 'Deadlock found')) {
            return ConcurrencyException::deadlock($e);
        }

        if (str_contains($message, 'Lock wait timeout')) {
            return ConcurrencyException::deadlock($e);
        }

        if ($sqlState === self::INTEGRITY_CONSTRAINT_VIOLATION || str_contains($message, 'Duplicate entry')) {
            if (str_contains($message, 'Duplicate entry')) {
                if (isset($context['sku'])) {
                    return new DuplicateSkuException((string) $context['sku'], $e);
                }
                if (isset($context['email'])) {
                    return new DuplicateEmailException((string) $context['email'], $e);
                }
                return new DuplicateEntryException($e);
            }
            // Foreign key violations and NOT NULL breaches land here.
            return new ValidationException(
                ErrorCode::INVALID_FIELD,
                'A referenced record does not exist or a required value is missing.',
                [],
                $e
            );
        }

        return InfrastructureException::databaseError($e);
    }
}
