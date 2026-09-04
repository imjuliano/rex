<?php
declare(strict_types=1);

namespace App\Product\Exception;

use App\Exception\ErrorCode;
use App\Exception\NotFoundException;

final class ProductNotFoundException extends NotFoundException {
    public function __construct(int $id) {
        parent::__construct(
            ErrorCode::PRODUCT_NOT_FOUND,
            'Product not found.',
            ['product_id' => $id]
        );
    }
}
