<?php
declare(strict_types=1);

namespace App\Sale\Dto;

use App\Validation\Assert;
use App\Validation\Limits;
use OpenApi\Attributes as OA;

#[OA\Schema(
    title: 'CreateSaleRequest',
    required: ['external_id', 'campaign_id', 'seller_id', 'product_id', 'quantity', 'unit_value'],
    properties: [
        new OA\Property(property: 'external_id', type: 'string'),
        new OA\Property(property: 'campaign_id', type: 'integer'),
        new OA\Property(property: 'seller_id', type: 'integer'),
        new OA\Property(property: 'product_id', type: 'integer'),
        new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
        new OA\Property(property: 'unit_value', type: 'number', format: 'float', minimum: 0),
    ],
)]
final class CreateSaleDto {
    public function __construct(
        public readonly string $externalId,
        public readonly int $campaignId,
        public readonly int $sellerId,
        public readonly int $productId,
        public readonly int $quantity,
        public readonly float $unitValue,
    ) {}

    public static function fromArray(array $row): self {
        Assert::requiredFields($row, ['external_id', 'campaign_id', 'seller_id', 'product_id', 'quantity', 'unit_value']);

        return new self(
            externalId: Assert::nonEmptyString($row['external_id'], 'external_id', Limits::SALE_EXTERNAL_ID),
            campaignId: Assert::positiveInt($row['campaign_id'], 'campaign_id'),
            sellerId: Assert::positiveInt($row['seller_id'], 'seller_id'),
            productId: Assert::positiveInt($row['product_id'], 'product_id'),
            quantity: Assert::positiveInt($row['quantity'], 'quantity', Limits::QUANTITY_MAX),
            unitValue: Assert::nonNegativeNumber($row['unit_value'], 'unit_value', Limits::UNIT_VALUE_MAX),
        );
    }
}
