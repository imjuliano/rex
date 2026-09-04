<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\BusinessException;
use App\Exception\ErrorCode;

final class SaleInsufficientBudgetException extends BusinessException {
    public function __construct(int $campaignId, int $requestedPoints, int $availablePoints) {
        parent::__construct(
            ErrorCode::INSUFFICIENT_BUDGET,
            'The sale exceeds the remaining campaign budget and was rejected in full.',
            [
                'campaign_id' => $campaignId,
                'requested_points' => $requestedPoints,
                'available_points' => $availablePoints,
                'policy' => 'reject_whole_sale',
            ]
        );
    }
}
