<?php
declare(strict_types=1);

namespace App\Campaign\Exception;

use App\Exception\BusinessException;
use App\Exception\ErrorCode;

final class CampaignBudgetBelowCommittedException extends BusinessException {
    public function __construct(int $campaignId, int $requested, int $committed) {
        parent::__construct(
            ErrorCode::BUDGET_BELOW_COMMITTED,
            'The new budget is lower than the points already credited for this campaign.',
            [
                'campaign_id' => $campaignId,
                'requested_budget' => $requested,
                'already_committed' => $committed,
                'minimum_allowed' => $committed,
            ]
        );
    }
}
