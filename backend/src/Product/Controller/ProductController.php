<?php
declare(strict_types=1);

namespace App\Product\Controller;

use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Product\Dto\CreateProductDto;
use App\Product\Dto\UpdateProductDto;
use App\Product\Filter\ProductFilter;
use App\Product\Service\ProductReadService;
use App\Product\Service\ProductWriteService;

final class ProductController {
    public function __construct(
        private ProductReadService $reads,
        private ProductWriteService $writes,
    ) {}

    public function list(QueryParams $query): void {
        $criteria = ProductFilter::from($query);
        $page = $this->reads->list($criteria);
        $summary = $this->reads->summary();
        ApiResponse::collection($page, '/products', $query->all(), $summary);
    }

    public function create(array $body, array $actor): void {
        $product = $this->writes->create(CreateProductDto::fromArray($body), $actor);
        ApiResponse::item($product, 201);
    }

    public function update(int $id, array $body, array $actor): void {
        $product = $this->writes->update($id, UpdateProductDto::fromArray($body), $actor);
        ApiResponse::item($product);
    }

    public function deactivate(int $id, array $actor): void {
        $product = $this->writes->deactivate($id, $actor);
        ApiResponse::item($product);
    }

    public function softDelete(int $id, array $actor): void {
        $product = $this->writes->softDelete($id, $actor);
        ApiResponse::item($product);
    }
}
