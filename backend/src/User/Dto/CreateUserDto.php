<?php
declare(strict_types=1);

namespace App\User\Dto;

use App\User\Exception\UserInvalidRoleException;
use App\User\Exception\UserPasswordTooShortException;
use App\User\UserRole;
use App\Validation\Assert;
use App\Validation\Limits;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'CreateUserRequest',
    required: ['name', 'email', 'password', 'role'],
    properties: [
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'seller']),
    ],
)]
final class CreateUserDto {
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $password,
        public readonly UserRole $role,
    ) {}

    public static function fromArray(array $body): self {
        Assert::requiredFields($body, ['name', 'email', 'password', 'role']);

        $name = Assert::nonEmptyString($body['name'], 'name', Limits::USER_NAME);
        $email = Assert::email($body['email']);
        $password = Assert::nonEmptyString($body['password'], 'password', Limits::PASSWORD);
        if (strlen($password) < 8) {
            throw new UserPasswordTooShortException(strlen($password));
        }

        $role = UserRole::tryFrom(is_string($body['role']) ? $body['role'] : '');
        if ($role === null) {
            throw new UserInvalidRoleException();
        }

        return new self($name, $email, $password, $role);
    }
}
