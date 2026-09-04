<?php
declare(strict_types=1);

namespace App\Campaign;

enum CampaignStatus: string {
    case ACTIVE = 'active';
    case CLOSED = 'closed';

    public function acceptsSales(): bool {
        return $this === self::ACTIVE;
    }
}
