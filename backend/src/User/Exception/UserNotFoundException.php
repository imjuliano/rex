<?php
declare(strict_types=1);

namespace App\User\Exception;

use App\Exception\ErrorCode;
use App\Exception\NotFoundException;

final class UserNotFoundException extends NotFoundException {
    public function __construct(int $id) {
        parent::__construct(
            ErrorCode::USER_NOT_FOUND,
            'User not found.',
            ['user_id' => $id]
        );
    }
}
