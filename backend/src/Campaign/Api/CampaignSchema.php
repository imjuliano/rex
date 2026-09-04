<?php
declare(strict_types=1);

namespace App\Campaign\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'Campaign',
    description: 'Campanha de incentivo com verba e período.',
    required: ['id', 'name', 'budget', 'period', 'status', 'accepting_sales', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'budget', type: 'object', properties: [
            new OA\Property(property: 'total', type: 'integer'),
            new OA\Property(property: 'used', type: 'integer'),
            new OA\Property(property: 'remaining', type: 'integer'),
            new OA\Property(property: 'usage_pct', type: 'number', format: 'float'),
            new OA\Property(property: 'exhausted', type: 'boolean'),
        ]),
        new OA\Property(property: 'period', type: 'object', properties: [
            new OA\Property(property: 'starts_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'ends_at', type: 'string', format: 'date-time'),
            new OA\Property(property: 'days_remaining', type: 'integer'),
        ]),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'closed']),
        new OA\Property(property: 'accepting_sales', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
final class CampaignSchema {}
