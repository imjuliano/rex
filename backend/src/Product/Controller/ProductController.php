<?php
declare(strict_types=1);

namespace App\Product\Controller;

use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Product\Api\ProductSchema;
use App\Product\Dto\CreateProductDto;
use App\Product\Dto\UpdateProductDto;
use App\Product\Filter\ProductFilter;
use App\Product\Service\ProductReadService;
use App\Product\Service\ProductWriteService;
use App\Route;
use OpenApi\Attributes as OA;

final class ProductController {
    public function __construct(
        private ProductReadService $reads,
        private ProductWriteService $writes,
    ) {}

    #[Route('GET', '/products', ['admin', 'seller'])]
    #[OA\Get(
        path: '/products',
        operationId: 'listProducts',
        summary: 'Lista produtos',
        description: 'Lista paginada do catálogo, com suporte a busca, filtros e ordenação.',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Nome ou SKU', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Status', schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Coluna de ordenação', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'sku', 'points_per_unit', 'created_at'])),
            new OA\Parameter(name: 'order', in: 'query', description: 'Direção da ordenação', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Página', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Itens por página', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
            new OA\Parameter(name: 'min_points', in: 'query', description: 'Pontos mínimos', schema: new OA\Schema(type: 'integer', minimum: 0)),
            new OA\Parameter(name: 'max_points', in: 'query', description: 'Pontos máximos', schema: new OA\Schema(type: 'integer', minimum: 0)),
            new OA\Parameter(name: 'with_deleted', in: 'query', description: 'Incluir excluídos', schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de produtos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: ProductSchema::class)),
                        new OA\Property(property: 'meta', type: 'object', additionalProperties: true),
                        new OA\Property(property: 'links', type: 'object', additionalProperties: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function list(QueryParams $query): void {
        $criteria = ProductFilter::from($query);
        $page = $this->reads->list($criteria);
        $summary = $this->reads->summary();
        ApiResponse::collection($page, '/products', $query->all(), $summary);
    }

    #[Route('POST', '/products', ['admin'])]
    #[OA\Post(
        path: '/products',
        operationId: 'createProduct',
        summary: 'Cria um produto',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: CreateProductDto::class),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Produto criado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: ProductSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 409, description: 'SKU duplicado'),
        ],
    )]
    public function create(array $body, array $actor): void {
        $product = $this->writes->create(CreateProductDto::fromArray($body), $actor);
        ApiResponse::item($product, 201);
    }

    #[Route('PUT', '/products/{id}', ['admin'])]
    #[OA\Put(
        path: '/products/{id}',
        operationId: 'updateProduct',
        summary: 'Atualiza um produto',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: UpdateProductDto::class),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produto atualizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: ProductSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Produto não encontrado'),
            new OA\Response(response: 409, description: 'SKU duplicado'),
        ],
    )]
    public function update(int $id, array $body, array $actor): void {
        $product = $this->writes->update($id, UpdateProductDto::fromArray($body), $actor);
        ApiResponse::item($product);
    }

    #[Route('DELETE', '/products/{id}', ['admin'])]
    #[OA\Delete(
        path: '/products/{id}',
        operationId: 'deactivateProduct',
        summary: 'Inativa um produto',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produto inativado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: ProductSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Produto não encontrado'),
        ],
    )]
    public function deactivate(int $id, array $actor): void {
        $product = $this->writes->deactivate($id, $actor);
        ApiResponse::item($product);
    }

    #[Route('POST', '/products/{id}/delete', ['admin'])]
    #[OA\Post(
        path: '/products/{id}/delete',
        operationId: 'softDeleteProduct',
        summary: 'Exclui permanentemente um produto',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Produto excluído',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: ProductSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Produto não encontrado'),
        ],
    )]
    public function softDelete(int $id, array $actor): void {
        $product = $this->writes->softDelete($id, $actor);
        ApiResponse::item($product);
    }
}
