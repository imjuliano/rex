<?php
declare(strict_types=1);

namespace App\Campaign\Exception;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;

final class CampaignNoFieldsToUpdateException extends ValidationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::NO_FIELDS_TO_UPDATE,
            'Provide at least one field to update.'
        );
    }
}
