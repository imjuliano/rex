<?php
declare(strict_types=1);

namespace App\Sale\Mapper;

use App\Sale\SaleStatus;

final class SaleMapper {
    public function map(array $r): array {
        $quantity = (int) $r['quantity'];
        $unitValue = (float) $r['unit_value'];

        return [
            'id' => (int) $r['id'],
            'external_id' => $r['external_id'],
            'status' => SaleStatus::from($r['status'])->value,
            'quantity' => $quantity,
            'unit_value' => round($unitValue, 2),
            'total_value' => round($quantity * $unitValue, 2),
            'points' => $r['points_credited'] === null ? 0 : (int) $r['points_credited'],
            'campaign' => [
                'id' => (int) $r['campaign_id'],
                'name' => $r['campaign_name'],
            ],
            'seller' => [
                'id' => (int) $r['seller_id'],
                'name' => $r['seller_name'],
                'email' => $r['seller_email'],
            ],
            'product' => [
                'id' => (int) $r['product_id'],
                'name' => $r['product_name'],
                'sku' => $r['product_sku'],
                'points_per_unit' => (int) $r['points_per_unit'],
            ],
            'created_at' => (new \DateTimeImmutable($r['created_at']))->format(\DateTimeInterface::ATOM),
        ];
    }

    public function mapCreated(
        int $saleId,
        string $externalId,
        string $status,
        int $quantity,
        float $unitValue,
        int $points,
        array $campaign,
        int $sellerId,
        int $productId,
        int $pointsPerUnit
    ): array {
        return [
            'id' => $saleId,
            'external_id' => $externalId,
            'status' => $status,
            'quantity' => $quantity,
            'unit_value' => round($unitValue, 2),
            'total_value' => round($quantity * $unitValue, 2),
            'points' => $points,
            'campaign' => [
                'id' => (int) $campaign['id'],
                'name' => $campaign['name'],
                'budget_remaining' => $campaign['budget_remaining'],
            ],
            'seller' => ['id' => $sellerId],
            'product' => [
                'id' => $productId,
                'points_per_unit' => $pointsPerUnit,
            ],
        ];
    }

    public function mapCanceled(
        int $saleId,
        string $externalId,
        string $status,
        int $pointsReversed,
        int $campaignId,
        string $campaignName,
        int $budgetRemaining,
        int $sellerId
    ): array {
        return [
            'id' => $saleId,
            'external_id' => $externalId,
            'status' => $status,
            'points_reversed' => $pointsReversed,
            'campaign' => [
                'id' => $campaignId,
                'name' => $campaignName,
                'budget_remaining' => $budgetRemaining,
            ],
            'seller' => ['id' => $sellerId],
        ];
    }
}
