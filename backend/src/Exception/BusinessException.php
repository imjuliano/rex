<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * The request is well formed but violates a domain rule. HTTP 422.
 *
 * These carry rich details on purpose: the client can render exactly
 * why the scoring engine refused the operation.
 */
class BusinessException extends AbstractDomainException {
    /** @param array<string, mixed> $details */
    public function __construct(
        ErrorCode $errorCode,
        string $message,
        array $details = [],
        int $statusCode = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($errorCode, $message, $statusCode, $details, $previous);
    }
}
