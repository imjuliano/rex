<?php
declare(strict_types=1);

namespace App\Product\Exception;

use App\Exception\BusinessException;
use App\Exception\ErrorCode;

final class ProductInactiveException extends BusinessException {
    public function __construct(int $productId) {
        parent::__construct(
            ErrorCode::PRODUCT_INACTIVE,
            'The product is inactive and cannot be sold.',
            ['product_id' => $productId]
        );
    }
}
