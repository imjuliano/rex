<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\ConflictException;
use App\Exception\ErrorCode;

final class SaleAlreadyExistsException extends ConflictException {
    public function __construct(string $externalId, int $saleId, string $status) {
        parent::__construct(
            ErrorCode::SALE_ALREADY_EXISTS,
            'This sale has already been recorded; no points were credited again.',
            [
                'external_id' => $externalId,
                'sale_id' => $saleId,
                'status' => $status,
            ]
        );
    }
}
