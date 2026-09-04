<?php
declare(strict_types=1);

namespace App\Sale\Exception;

use App\Exception\ErrorCode;
use App\Exception\NotFoundException;

final class SaleNotFoundException extends NotFoundException {
    public function __construct(string $externalId) {
        parent::__construct(
            ErrorCode::SALE_NOT_FOUND,
            'Sale not found.',
            ['external_id' => $externalId]
        );
    }
}
