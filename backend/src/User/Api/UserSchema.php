<?php
declare(strict_types=1);

namespace App\User\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'User',
    description: 'Usuário da plataforma.',
    required: ['id', 'name', 'email', 'role', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string', format: 'email'),
        new OA\Property(property: 'role', type: 'string', enum: ['admin', 'seller']),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
final class UserSchema {}
