<?php
declare(strict_types=1);

namespace App\Campaign\Mapper;

use App\Campaign\CampaignStatus;
use DateTimeImmutable;

final class CampaignMapper {
    public function map(array $r): array {
        $total = (int) $r['budget_total'];
        $used = (int) $r['budget_used'];
        $status = CampaignStatus::from($r['status']);
        $startsAt = new DateTimeImmutable($r['starts_at']);
        $endsAt = new DateTimeImmutable($r['ends_at']);
        $now = new DateTimeImmutable();

        return [
            'id' => (int) $r['id'],
            'name' => $r['name'],
            'budget' => [
                'total' => $total,
                'used' => $used,
                'remaining' => $total - $used,
                'usage_pct' => $total > 0 ? round(($used / $total) * 100, 2) : 0.0,
                'exhausted' => $used >= $total,
            ],
            'period' => [
                'starts_at' => $startsAt->format(\DateTimeInterface::ATOM),
                'ends_at' => $endsAt->format(\DateTimeInterface::ATOM),
                'days_remaining' => max(0, $now->diff($endsAt)->days),
            ],
            'status' => $status->value,
            'accepting_sales' => $status->acceptsSales() && $now >= $startsAt && $now <= $endsAt && $used < $total,
            'created_at' => (new DateTimeImmutable($r['created_at']))->format(\DateTimeInterface::ATOM),
        ];
    }
}
