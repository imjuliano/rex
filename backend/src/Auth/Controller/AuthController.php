<?php
declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Api\AuthSchema;
use App\Auth\Dto\LoginDto;
use App\Auth\RefreshTokenCookie;
use App\Auth\Service\AuthService;
use App\Auth\TokenPair;
use App\Http\ApiResponse;
use App\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Auth')]
final class AuthController {
    public function __construct(private AuthService $auth) {}

    #[Route('POST', '/auth/login', null)]
    #[OA\Post(
        path: '/auth/login',
        operationId: 'login',
        summary: 'Autenticação',
        description: 'Troca e-mail e senha por um access token (body) e refresh token (cookie HttpOnly).',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: LoginDto::class),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Autenticado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: AuthSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Credenciais inválidas'),
        ],
    )]
    public function login(array $body): void {
        $this->respondWithPair($this->auth->login(LoginDto::fromArray($body)));
    }

    #[Route('POST', '/auth/refresh', null)]
    #[OA\Post(
        path: '/auth/refresh',
        operationId: 'refresh',
        summary: 'Renova a sessão',
        description: 'Gira o refresh token. Requer o cookie de refresh previamente setado.',
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sessão renovada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: AuthSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Refresh token inválido ou ausente'),
        ],
    )]
    public function refresh(): void {
        $this->respondWithPair($this->auth->refresh(RefreshTokenCookie::read()));
    }

    #[Route('POST', '/auth/logout', null)]
    #[OA\Post(
        path: '/auth/logout',
        operationId: 'logout',
        summary: 'Encerra a sessão',
        description: 'Revoga a família de refresh tokens e limpa o cookie.',
        responses: [
            new OA\Response(response: 204, description: 'Sessão encerrada'),
            new OA\Response(response: 401, description: 'Refresh token inválido ou ausente'),
        ],
    )]
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
