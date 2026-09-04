<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\BusinessException;
use App\Exception\ErrorCode;

final class SaleNegativeBudgetException extends BusinessException {
    public function __construct(int $campaignId, int $points, int $budgetUsed) {
        parent::__construct(
            ErrorCode::NEGATIVE_BUDGET,
            'Cancelling this sale would drive the campaign budget negative.',
            [
                'campaign_id' => $campaignId,
                'points' => $points,
                'budget_used' => $budgetUsed,
            ]
        );
    }
}
