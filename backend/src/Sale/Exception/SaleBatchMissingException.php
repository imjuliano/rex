<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;

final class SaleBatchMissingException extends ValidationException {
    public function __construct() {
        parent::__construct(
            ErrorCode::INVALID_FIELD,
            "Field 'sales' must be a non-empty array.",
            ['field' => 'sales']
        );
    }
}
