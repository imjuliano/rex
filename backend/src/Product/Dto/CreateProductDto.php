<?php
declare(strict_types=1);

namespace App\Product\Dto;

use App\Validation\Assert;
use App\Validation\Limits;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'CreateProductRequest',
    description: 'Payload para criação de um produto no catálogo.',
    required: ['name', 'sku', 'points_per_unit'],
    properties: [
        new OA\Property(property: 'name', type: 'string', maxLength: 100, example: 'Smartphone X'),
        new OA\Property(property: 'sku', type: 'string', maxLength: 100, example: 'PHONE-001'),
        new OA\Property(property: 'points_per_unit', type: 'integer', minimum: 0, example: 50),
        new OA\Property(property: 'active', type: 'boolean', example: true),
    ],
)]
final class CreateProductDto {
    public function __construct(
        public readonly string $name,
        public readonly string $sku,
        public readonly int $pointsPerUnit,
        public readonly bool $active,
    ) {}

    public static function fromArray(array $body): self {
        return new self(
            name: Assert::nonEmptyString($body['name'] ?? null, 'name', Limits::PRODUCT_NAME),
            sku: Assert::nonEmptyString($body['sku'] ?? null, 'sku', Limits::PRODUCT_SKU),
            pointsPerUnit: Assert::nonNegativeInt($body['points_per_unit'] ?? null, 'points_per_unit', Limits::POINTS_PER_UNIT_MAX),
            active: Assert::boolean($body['active'] ?? true, 'active'),
        );
    }
}
