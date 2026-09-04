<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\ConflictException;
use App\Exception\ErrorCode;

final class SaleAlreadyCanceledException extends ConflictException {
    public function __construct(string $externalId, int $saleId) {
        parent::__construct(
            ErrorCode::SALE_ALREADY_CANCELED,
            'This sale is already cancelled; no points were reversed again.',
            ['external_id' => $externalId, 'sale_id' => $saleId]
        );
    }
}
