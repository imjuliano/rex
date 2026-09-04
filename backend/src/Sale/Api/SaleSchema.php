<?php
declare(strict_types=1);

namespace App\Sale\Api;

use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'Sale',
    description: 'Venda lançada e creditada na campanha.',
    required: ['id', 'external_id', 'status', 'quantity', 'unit_value', 'total_value', 'points', 'campaign', 'seller', 'product', 'created_at'],
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'external_id', type: 'string'),
        new OA\Property(property: 'status', type: 'string', enum: ['approved', 'canceled']),
        new OA\Property(property: 'quantity', type: 'integer'),
        new OA\Property(property: 'unit_value', type: 'number', format: 'float'),
        new OA\Property(property: 'total_value', type: 'number', format: 'float'),
        new OA\Property(property: 'points', type: 'integer'),
        new OA\Property(property: 'campaign', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
        ]),
        new OA\Property(property: 'seller', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'email', type: 'string'),
        ]),
        new OA\Property(property: 'product', type: 'object', properties: [
            new OA\Property(property: 'id', type: 'integer'),
            new OA\Property(property: 'name', type: 'string'),
            new OA\Property(property: 'sku', type: 'string'),
            new OA\Property(property: 'points_per_unit', type: 'integer'),
        ]),
        new OA\Property(property: 'created_at', type: 'string', format: 'date-time'),
    ],
)]
final class SaleSchema {}
