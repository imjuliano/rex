<?php
declare(strict_types=1);

namespace App\Product\Service;

use App\Http\Criteria;
use App\Http\PaginatedCollection;
use App\Product\Dao\ProductDao;
use App\Product\Mapper\ProductMapper;

final class ProductReadService {
    public function __construct(
        private ProductDao $dao,
        private ProductMapper $mapper,
    ) {}

    public function summary(): array {
        $s = $this->dao->summary();
        return [
            'total_products' => (int) $s['total_products'],
            'active_products' => (int) $s['active_products'],
            'avg_points_per_unit_active' => round((float) $s['avg_points_per_unit_active'], 2),
        ];
    }

    public function list(Criteria $criteria): PaginatedCollection {
        $page = $this->dao->list($criteria);
        return new PaginatedCollection(
            array_map([$this->mapper, 'map'], $page->items),
            $page->total,
            $page->page,
            $page->perPage
        );
    }
}
