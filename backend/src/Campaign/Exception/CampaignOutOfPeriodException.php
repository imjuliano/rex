<?php
declare(strict_types=1);

namespace App\Campaign\Exception;

use App\Exception\BusinessException;
use App\Exception\ErrorCode;

final class CampaignOutOfPeriodException extends BusinessException {
    public function __construct(int $campaignId, string $startsAt, string $endsAt) {
        parent::__construct(
            ErrorCode::CAMPAIGN_OUT_OF_PERIOD,
            'The current date is outside the campaign period.',
            ['campaign_id' => $campaignId, 'starts_at' => $startsAt, 'ends_at' => $endsAt]
        );
    }
}
