<?php
declare(strict_types=1);

namespace App\Sale;

enum SaleStatus: string {
    case APPROVED = 'approved';
    case CANCELED = 'canceled';

    public function isReversible(): bool {
        return $this === self::APPROVED;
    }
}
