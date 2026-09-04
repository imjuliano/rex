<?php
declare(strict_types=1);

namespace App\Campaign\Exception;

use App\Exception\BusinessException;
use App\Exception\ErrorCode;

final class CampaignNotActiveException extends BusinessException {
    public function __construct(int $campaignId) {
        parent::__construct(
            ErrorCode::CAMPAIGN_NOT_ACTIVE,
            'The campaign is closed and cannot receive new sales.',
            ['campaign_id' => $campaignId]
        );
    }
}
