<?php
declare(strict_types=1);

namespace App\Auth\Exception;

use App\Exception\AuthenticationException;
use App\Exception\ErrorCode;

final class MissingRefreshTokenException extends AuthenticationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::MISSING_REFRESH_TOKEN,
            'No refresh token was sent. Sign in again.'
        );
    }
}
