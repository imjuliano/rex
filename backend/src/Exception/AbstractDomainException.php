<?php
declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Throwable;

/**
 * Base class for every error this application throws on purpose.
 *
 * Carries the three things an HTTP boundary needs to build a response:
 * the status code, a stable machine-readable code and structured details.
 */
abstract class AbstractDomainException extends RuntimeException {
    protected ErrorCode $errorCode;
    protected int $statusCode;
    /** @var array<string, mixed> */
    protected array $details;

    /**
     * @param array<string, mixed> $details
     */
    public function __construct(
        ErrorCode $errorCode,
        string $message,
        int $statusCode,
        array $details = [],
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->details = $details;
    }

    public function errorCode(): ErrorCode {
        return $this->errorCode;
    }

    public function statusCode(): int {
        return $this->statusCode;
    }

    /** @return array<string, mixed> */
    public function details(): array {
        return $this->details;
    }

    /**
     * Whether the underlying cause should be written to the error log.
     * Client mistakes (4xx) are noise; server faults are not.
     */
    public function shouldLog(): bool {
        return $this->statusCode >= 500;
    }

    /**
     * Whether the message is safe to expose verbatim to the client.
     * Infrastructure failures override this to hide internals.
     */
    public function isMessagePublic(): bool {
        return true;
    }
}
