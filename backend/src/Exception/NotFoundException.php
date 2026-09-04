<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * The addressed resource does not exist. HTTP 404.
 */
class NotFoundException extends AbstractDomainException {
    /** @param array<string, mixed> $details */
    public function __construct(
        ErrorCode $errorCode,
        string $message,
        array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, $message, 404, $details, $previous);
    }
}
