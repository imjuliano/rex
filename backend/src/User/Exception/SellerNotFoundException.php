<?php
declare(strict_types=1);

namespace App\User\Exception;

use App\Exception\ErrorCode;
use App\Exception\NotFoundException;

final class SellerNotFoundException extends NotFoundException {
    public function __construct(int $sellerId) {
        parent::__construct(
            ErrorCode::SELLER_NOT_FOUND,
            'Seller not found.',
            ['seller_id' => $sellerId]
        );
    }
}
