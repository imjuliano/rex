<?php
declare(strict_types=1);

namespace App\Auth\Exception;

use App\Exception\AuthorizationException;
use App\Exception\ErrorCode;

final class ForbiddenRoleException extends AuthorizationException {
    /** @param list<string> $requiredRoles */
    public function __construct(array $requiredRoles) {
        parent::__construct(
            ErrorCode::FORBIDDEN_ROLE,
            'You do not have permission to perform this action.',
            ['required_roles' => $requiredRoles]
        );
    }
}
