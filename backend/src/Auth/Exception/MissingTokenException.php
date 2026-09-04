<?php
declare(strict_types=1);

namespace App\Auth\Exception;

use App\Exception\AuthenticationException;
use App\Exception\ErrorCode;

final class MissingTokenException extends AuthenticationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::MISSING_TOKEN,
            'Authentication required. Send a Bearer token in the Authorization header.'
        );
    }
}
