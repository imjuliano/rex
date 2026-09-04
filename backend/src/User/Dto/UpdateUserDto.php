<?php
declare(strict_types=1);

namespace App\User\Dto;

use App\User\Exception\UserInvalidRoleException;
use App\User\Exception\UserNoFieldsToUpdateException;
use App\User\Exception\UserPasswordTooShortException;
use App\User\UserRole;
use App\Validation\Assert;
use App\Validation\Limits;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'UpdateUserRequest',
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'seller']),
    ],
)]
final class UpdateUserDto {
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $email = null,
        public readonly ?string $password = null,
        public readonly ?UserRole $role = null,
    ) {}

    public static function fromArray(array $body): self {
        $name = array_key_exists('name', $body) ? Assert::nonEmptyString($body['name'], 'name', Limits::USER_NAME) : null;
        $email = array_key_exists('email', $body) ? Assert::email($body['email']) : null;

        $password = null;
        if (array_key_exists('password', $body) && $body['password'] !== null && $body['password'] !== '') {
            $password = Assert::nonEmptyString($body['password'], 'password', Limits::PASSWORD);
            if (strlen($password) < 8) {
                throw new UserPasswordTooShortException(strlen($password));
            }
        }

        $role = null;
        if (array_key_exists('role', $body) && $body['role'] !== null) {
            $role = UserRole::tryFrom(is_string($body['role']) ? $body['role'] : '');
            if ($role === null) {
                throw new UserInvalidRoleException();
            }
        }

        if ($name === null && $email === null && $password === null && $role === null) {
            throw new UserNoFieldsToUpdateException();
        }

        return new self($name, $email, $password, $role);
    }
}
