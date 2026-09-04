<?php
declare(strict_types=1);

namespace App\Auth\Exception;

use App\Exception\AuthenticationException;
use App\Exception\ErrorCode;
use Throwable;

final class InvalidTokenException extends AuthenticationException {
    public function __construct(?Throwable $previous = null) {
        parent::__construct(
            ErrorCode::INVALID_TOKEN,
            'Your session is invalid or has expired. Sign in again.',
            [],
            $previous
        );
    }
}
