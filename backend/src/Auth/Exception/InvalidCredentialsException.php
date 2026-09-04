<?php
declare(strict_types=1);

namespace App\Auth\Exception;

use App\Exception\AuthenticationException;
use App\Exception\ErrorCode;

final class InvalidCredentialsException extends AuthenticationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::INVALID_CREDENTIALS,
            'Invalid e-mail or password.'
        );
    }
}
