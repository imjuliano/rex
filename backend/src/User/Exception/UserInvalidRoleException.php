<?php
declare(strict_types=1);

namespace App\User\Exception;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;

final class UserInvalidRoleException extends ValidationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::INVALID_FIELD,
            "Field 'role' is invalid: expected one of: admin|seller.",
            ['field' => 'role']
        );
    }
}
