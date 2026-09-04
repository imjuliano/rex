<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * The caller could not be identified: no token, malformed token,
 * expired token or wrong credentials. HTTP 401.
 *
 * Messages are intentionally vague so they cannot be used to probe
 * which e-mails exist in the database.
 */
class AuthenticationException extends AbstractDomainException {
    /** @param array<string, mixed> $details */
    public function __construct(
        ErrorCode $errorCode,
        string $message,
        array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, $message, 401, $details, $previous);
    }
}
