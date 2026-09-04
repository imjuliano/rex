<?php
declare(strict_types=1);

namespace App\Auth\Dto;

use App\Validation\Assert;

final class LoginDto {
    public function __construct(
        public readonly string $email,
        public readonly string $password,
    ) {}

    public static function fromArray(array $body): self {
        Assert::requiredFields($body, ['email', 'password']);
        return new self(
            email: Assert::email($body['email']),
            password: Assert::nonEmptyString($body['password'], 'password', 1024),
        );
    }
}
