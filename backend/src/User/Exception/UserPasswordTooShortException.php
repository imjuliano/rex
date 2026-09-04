<?php
declare(strict_types=1);

namespace App\User\Exception;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;

final class UserPasswordTooShortException extends ValidationException {
    public function __construct(int $actual) {
        parent::__construct(
            ErrorCode::INVALID_FIELD,
            "Field 'password' must be at least 8 characters.",
            ['field' => 'password', 'min_length' => 8, 'actual_length' => $actual]
        );
    }
}
