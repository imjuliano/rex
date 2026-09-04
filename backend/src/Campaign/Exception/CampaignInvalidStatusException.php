<?php
declare(strict_types=1);

namespace App\Campaign\Exception;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;

final class CampaignInvalidStatusException extends ValidationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::INVALID_FIELD,
            "Field 'status' is invalid: expected one of: active|closed.",
            ['field' => 'status']
        );
    }
}
