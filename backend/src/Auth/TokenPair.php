<?php
declare(strict_types=1);

namespace App\Auth;

/**
 * What a successful login or refresh hands back.
 *
 * The access token is meant for the response body; the refresh token never
 * reaches JavaScript and is written straight into an HttpOnly cookie.
 */
final class TokenPair {
    public function __construct(
        public readonly string $accessToken,
        public readonly int $accessExpiresAt,
        public readonly int $accessIssuedAt,
        public readonly string $refreshToken,
        public readonly int $refreshExpiresAt,
        public readonly int $userId,
        public readonly string $userEmail,
        public readonly string $userRole,
    ) {}

    public function expiresIn(): int {
        return $this->accessExpiresAt - $this->accessIssuedAt;
    }

    /** @return array<string, mixed> */
    public function toResponse(): array {
        return [
            'token' => $this->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => date('c', $this->accessExpiresAt),
            'expires_in' => $this->expiresIn(),
            'refresh_expires_at' => date('c', $this->refreshExpiresAt),
            'user' => [
                'id' => $this->userId,
                'email' => $this->userEmail,
                'role' => $this->userRole,
            ],
        ];
    }
}
