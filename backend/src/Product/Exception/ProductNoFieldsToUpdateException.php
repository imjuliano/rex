<?php
declare(strict_types=1);

namespace App\Product\Exception;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;

final class ProductNoFieldsToUpdateException extends ValidationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::NO_FIELDS_TO_UPDATE,
            'Provide at least one field to update.'
        );
    }
}
