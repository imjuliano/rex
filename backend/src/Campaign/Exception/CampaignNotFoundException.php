<?php
declare(strict_types=1);

namespace App\Campaign\Exception;

use App\Exception\ErrorCode;
use App\Exception\NotFoundException;

final class CampaignNotFoundException extends NotFoundException {
    public function __construct(int $id) {
        parent::__construct(
            ErrorCode::CAMPAIGN_NOT_FOUND,
            'Campaign not found.',
            ['campaign_id' => $id]
        );
    }
}
