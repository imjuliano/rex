<?php
declare(strict_types=1);

namespace App\Exception;

use Throwable;

/**
 * Two writers raced for the same rows. The loser gets this so the
 * client can safely retry the whole operation. HTTP 409.
 */
class ConcurrencyException extends BusinessException {
    /** @param array<string, mixed> $details */
    public function __construct(
        string $message = 'The record was modified by another request. Retry the operation.',
        array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct(ErrorCode::CONCURRENT_UPDATE, $message, $details, 409, $previous);
    }

    public static function deadlock(?Throwable $previous = null): self {
        return new self(
            'The operation collided with a concurrent request. Retry it.',
            ['retryable' => true],
            $previous
        );
    }
}
