<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Something outside the domain failed: database, network, encoding.
 *
 * The real cause is always logged but never returned to the client,
 * because driver messages leak schema, credentials and file paths.
 */
class InfrastructureException extends AbstractDomainException {
    /** @param array<string, mixed> $details */
    public function __construct(
        ErrorCode $errorCode,
        string $message,
        int $statusCode = 500,
        array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, $message, $statusCode, $details, $previous);
    }

    public function isMessagePublic(): bool {
        return false;
    }

    public static function databaseUnavailable(?Throwable $previous = null): self {
        return new self(
            ErrorCode::DATABASE_UNAVAILABLE,
            'The database is unavailable.',
            503,
            [],
            $previous
        );
    }

    public static function databaseError(?Throwable $previous = null): self {
        return new self(
            ErrorCode::DATABASE_ERROR,
            'The database rejected the operation.',
            500,
            [],
            $previous
        );
    }
}
