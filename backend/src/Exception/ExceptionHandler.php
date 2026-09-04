<?php
declare(strict_types=1);

namespace App\Exception;

use PDOException;
use Throwable;

/**
 * Single exit point for every failure in the request lifecycle.
 *
 * Responsibilities:
 *  - normalise any Throwable into an AbstractDomainException
 *  - emit one consistent JSON envelope
 *  - log server faults with a correlation id, never leaking internals
 */
final class ExceptionHandler {
    private bool $debug;

    public function __construct(bool $debug = false) {
        $this->debug = $debug;
    }

    public static function fromEnv(): self {
        $env = strtolower((string) (getenv('APP_ENV') ?: 'production'));
        return new self(in_array($env, ['dev', 'develop', 'development', 'local'], true));
    }

    /**
     * @param array<string, mixed> $requestContext
     */
    public function handle(Throwable $e, array $requestContext = []): void {
        $domain = $this->normalize($e);
        $traceId = $this->traceId();

        if ($domain->shouldLog()) {
            $this->log($domain, $traceId, $requestContext);
        }

        $this->respond($domain, $traceId);
    }

    private function normalize(Throwable $e): AbstractDomainException {
        if ($e instanceof AbstractDomainException) {
            return $e;
        }
        if ($e instanceof PDOException) {
            return PdoExceptionTranslator::translate($e);
        }
        return new InfrastructureException(
            ErrorCode::INTERNAL_ERROR,
            'An unexpected error occurred.',
            500,
            [],
            $e
        );
    }

    /**
     * @param array<string, mixed> $requestContext
     */
    private function log(AbstractDomainException $domain, string $traceId, array $requestContext): void {
        $cause = $domain->getPrevious() ?? $domain;
        error_log(sprintf(
            '[%s] %s %s | %s: %s | at %s:%d | ctx=%s',
            $traceId,
            $requestContext['method'] ?? '-',
            $requestContext['path'] ?? '-',
            $domain->errorCode()->value,
            $cause->getMessage(),
            $cause->getFile(),
            $cause->getLine(),
            json_encode($requestContext, JSON_UNESCAPED_SLASHES)
        ));
    }

    private function respond(AbstractDomainException $domain, string $traceId): void {
        if (!headers_sent()) {
            http_response_code($domain->statusCode());
            header('Content-Type: application/json; charset=utf-8');
            header('X-Trace-Id: ' . $traceId);
        }

        $payload = [
            'error' => $domain->errorCode()->value,
            'message' => $domain->isMessagePublic() || $this->debug
                ? $domain->getMessage()
                : 'An unexpected error occurred.',
            'status' => $domain->statusCode(),
            'trace_id' => $traceId,
        ];

        if ($domain->details() !== []) {
            $payload['details'] = $domain->details();
        }

        if ($this->debug) {
            $cause = $domain->getPrevious();
            $payload['debug'] = [
                'exception' => $domain::class,
                'file' => $domain->getFile(),
                'line' => $domain->getLine(),
                'previous' => $cause ? [
                    'exception' => $cause::class,
                    'message' => $cause->getMessage(),
                    'file' => $cause->getFile(),
                    'line' => $cause->getLine(),
                ] : null,
            ];
        }

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function traceId(): string {
        try {
            return bin2hex(random_bytes(6));
        } catch (Throwable) {
            return uniqid('t', true);
        }
    }
}
