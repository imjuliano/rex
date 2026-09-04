<?php
declare(strict_types=1);

namespace App\Product\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'Product',
    description: 'Recurso de produto do catálogo.',
    required: ['id', 'name', 'sku', 'points_per_unit', 'status', 'active', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Smartphone X'),
        new OA\Property(property: 'sku', type: 'string', example: 'PHONE-001'),
        new OA\Property(property: 'points_per_unit', type: 'integer', example: 50),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive']),
        new OA\Property(property: 'active', type: 'boolean'),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
final class ProductSchema {}
