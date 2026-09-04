<?php
declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Dto\LoginDto;
use App\Auth\RefreshTokenCookie;
use App\Auth\Service\AuthService;
use App\Auth\TokenPair;
use App\Http\ApiResponse;

final class AuthController {
    public function __construct(private AuthService $auth) {}

    public function login(array $body): void {
        $this->respondWithPair($this->auth->login(LoginDto::fromArray($body)));
    }

    /**
     * Rotates the session. The refresh token arrives in the HttpOnly cookie,
     * never in the body, so this endpoint needs no Authorization header —
     * by design it has to work when the access token is already expired.
     */
    public function refresh(): void {
        $this->respondWithPair($this->auth->refresh(RefreshTokenCookie::read()));
    }

    public function logout(): void {
        $this->auth->logout(RefreshTokenCookie::read());
        RefreshTokenCookie::clear();
        http_response_code(204);
    }

    private function respondWithPair(TokenPair $pair): void {
        RefreshTokenCookie::send($pair->refreshToken, $pair->refreshExpiresAt);
        ApiResponse::item($pair->toResponse());
    }
}
