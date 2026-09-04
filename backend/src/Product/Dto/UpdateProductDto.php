<?php
declare(strict_types=1);

namespace App\Product\Dto;

use App\Product\Exception\ProductNoFieldsToUpdateException;
use App\Validation\Assert;
use App\Validation\Limits;

final class UpdateProductDto {
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $sku = null,
        public readonly ?int $pointsPerUnit = null,
        public readonly ?bool $active = null,
    ) {}

    public static function fromArray(array $body): self {
        $dto = new self(
            name: array_key_exists('name', $body) ? Assert::nonEmptyString($body['name'], 'name', Limits::PRODUCT_NAME) : null,
            sku: array_key_exists('sku', $body) ? Assert::nonEmptyString($body['sku'], 'sku', Limits::PRODUCT_SKU) : null,
            pointsPerUnit: array_key_exists('points_per_unit', $body) ? Assert::nonNegativeInt($body['points_per_unit'], 'points_per_unit', Limits::POINTS_PER_UNIT_MAX) : null,
            active: array_key_exists('active', $body) ? Assert::boolean($body['active'], 'active') : null,
        );

        if ($dto->name === null && $dto->sku === null && $dto->pointsPerUnit === null && $dto->active === null) {
            throw new ProductNoFieldsToUpdateException();
        }

        return $dto;
    }
}
