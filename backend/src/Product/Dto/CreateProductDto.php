<?php
declare(strict_types=1);

namespace App\Product\Dto;

use App\Validation\Assert;
use App\Validation\Limits;

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
