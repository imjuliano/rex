<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * The request collides with the current state of the resource,
 * typically a uniqueness constraint. HTTP 409.
 */
class ConflictException extends AbstractDomainException {
    /** @param array<string, mixed> $details */
    public function __construct(
        ErrorCode $errorCode,
        string $message,
        array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, $message, 409, $details, $previous);
    }
}
