<?php
declare(strict_types=1);

namespace App\Http\Exception;

use App\Exception\ErrorCode;
use App\Exception\ValidationException;

final class InvalidQuerySortException extends ValidationException {
    /** @param list<string> $allowed */
    public function __construct(array $allowed) {
        parent::__construct(
            ErrorCode::INVALID_FIELD,
            "Field 'sort' is invalid: expected one of: " . implode('|', $allowed) . '.',
            ['field' => 'sort', 'allowed' => $allowed]
        );
    }
}
