<?php
declare(strict_types=1);

namespace App\Wallet\Mapper;

use App\Wallet\WalletEntryType;

final class WalletMapper {
    public function map(array $r): array {
        $type = WalletEntryType::from($r['type']);
        $points = (int) $r['points'];

        return [
            'id' => (int) $r['id'],
            'campaign_id' => $r['campaign_id'] !== null ? (int) $r['campaign_id'] : null,
            'sale_id' => $r['sale_id'] !== null ? (int) $r['sale_id'] : null,
            'type' => $type->value,
            'points' => $points,
            'signed_points' => $points * $type->sign(),
            'description' => $r['description'],
            'created_at' => (new \DateTimeImmutable($r['created_at']))->format(\DateTimeInterface::ATOM),
        ];
    }
}
