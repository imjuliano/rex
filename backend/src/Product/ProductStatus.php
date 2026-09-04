<?php
declare(strict_types=1);

namespace App\Product;

enum ProductStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';

    public static function fromFlag(bool $active): self {
        return $active ? self::ACTIVE : self::INACTIVE;
    }

    public function toFlag(): int {
        return $this === self::ACTIVE ? 1 : 0;
    }
}
