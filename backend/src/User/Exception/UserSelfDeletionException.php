<?php
declare(strict_types=1);

namespace App\User\Exception;

use App\Exception\ConflictException;
use App\Exception\ErrorCode;

final class UserSelfDeletionException extends ConflictException {
    public function __construct() {
        parent::__construct(
            ErrorCode::SELF_DELETION,
            'You cannot delete your own account.'
        );
    }
}
