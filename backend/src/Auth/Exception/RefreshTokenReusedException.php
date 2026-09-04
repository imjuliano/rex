<?php
declare(strict_types=1);

namespace App\Auth\Exception;

use App\Exception\AuthenticationException;
use App\Exception\ErrorCode;

/**
 * A refresh token that had already been spent came back a second time.
 *
 * With rotation there is no legitimate way for this to happen, so it is
 * treated as evidence that the chain leaked: the whole family is revoked
 * and every session descended from that login dies.
 */
final class RefreshTokenReusedException extends AuthenticationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::REFRESH_TOKEN_REUSED,
            'This session was revoked for security reasons. Sign in again.'
        );
    }
}
