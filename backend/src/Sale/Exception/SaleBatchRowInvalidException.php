<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;

final class SaleBatchRowInvalidException extends ValidationException {
    public function __construct(int $idx) {
        parent::__construct(
            ErrorCode::INVALID_FIELD,
            "Row $idx must be an object.",
            ['field' => "sales[$idx]"]
        );
    }
}
