<?php
declare(strict_types=1);

namespace App\Product\Exception;

use App\Exception\ConflictException;
use App\Exception\ErrorCode;
use Throwable;

final class DuplicateSkuException extends ConflictException {
    public function __construct(string $sku, ?Throwable $previous = null) {
        parent::__construct(
            ErrorCode::DUPLICATE_SKU,
            'A product with this SKU already exists.',
            ['sku' => $sku],
            $previous
        );
    }
}
