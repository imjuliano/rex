<?php
declare(strict_types=1);

namespace App\Wallet\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'WalletEntry',
    description: 'Movimentação de pontos na carteira do vendedor.',
    required: ['id', 'campaign_id', 'sale_id', 'type', 'points', 'signed_points', 'description', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'campaign_id', type: 'integer', nullable: true),
        new OA\Property(property: 'sale_id', type: 'integer', nullable: true),
        new OA\Property(property: 'type', type: 'string', enum: ['credit', 'debit']),
        new OA\Property(property: 'points', type: 'integer'),
        new OA\Property(property: 'signed_points', type: 'integer'),
        new OA\Property(property: 'description', type: 'string'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
final class WalletSchema {}
