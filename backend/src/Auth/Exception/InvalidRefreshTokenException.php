<?php
declare(strict_types=1);

namespace App\Auth\Exception;

use App\Exception\AuthenticationException;
use App\Exception\ErrorCode;

/**
 * The refresh token is unknown, already revoked or past its expiry.
 *
 * The message stays deliberately vague: telling a caller whether a token
 * "existed but expired" versus "never existed" is free reconnaissance.
 */
final class InvalidRefreshTokenException extends AuthenticationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::INVALID_REFRESH_TOKEN,
            'Your session has expired. Sign in again.'
        );
    }
}
