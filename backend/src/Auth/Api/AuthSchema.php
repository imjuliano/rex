<?php
declare(strict_types=1);

namespace App\Auth\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'Auth',
    description: 'Sessão autenticada (login ou refresh).',
    required: ['token', 'token_type', 'expires_at', 'expires_in', 'refresh_expires_at', 'user'],
    properties: [
        new OA\Property(property: 'token', type: 'string', description: 'JWT de acesso.'),
        new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'expires_in', type: 'integer', example: 900),
        new OA\Property(property: 'refresh_expires_at', type: 'string', format: 'date-time'),
        new OA\Property(property: 'user', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'email', type: 'string', format: 'email'),
            new OA\Property(property: 'role', type: 'string', enum: ['admin', 'seller']),
        ]),
    ],
)]
final class AuthSchema {}
