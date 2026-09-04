<?php
declare(strict_types=1);

namespace App\User\Exception;

use App\Exception\ConflictException;
use App\Exception\ErrorCode;
use Throwable;

final class DuplicateEmailException extends ConflictException {
    public function __construct(string $email, ?Throwable $previous = null) {
        parent::__construct(
            ErrorCode::DUPLICATE_EMAIL,
            'A user with this e-mail already exists.',
            ['email' => $email],
            $previous
        );
    }
}
