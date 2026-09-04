<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * The caller is known but not allowed to perform this action. HTTP 403.
 */
class AuthorizationException extends AbstractDomainException {
    /** @param array<string, mixed> $details */
    public function __construct(
        ErrorCode $errorCode,
        string $message,
        array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, $message, 403, $details, $previous);
    }
}
