<?php
declare(strict_types=1);

namespace App\Sale\Controller;

use App\Exception\ValidationException;
use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Route;
use App\Sale\Api\SaleSchema;
use App\Sale\Dto\BatchSalesDto;
use App\Sale\Dto\CreateSaleDto;
use App\Sale\Filter\SaleFilter;
use App\Sale\Service\SaleReadService;
use App\Sale\Service\SaleWriteService;
use App\Validation\Assert;
use App\Validation\Limits;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Sales')]
final class SaleController {
    public function __construct(
        private SaleReadService $reads,
        private SaleWriteService $writes,
    ) {}

    #[Route('GET', '/sales', ['admin'])]
    #[OA\Get(
        path: '/sales',
        operationId: 'listSales',
        summary: 'Lista vendas',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'external_id, produto ou vendedor', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Status', schema: new OA\Schema(type: 'string', enum: ['approved', 'canceled'])),
            new OA\Parameter(name: 'campaign_id', in: 'query', description: 'ID da campanha', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'seller_id', in: 'query', description: 'ID do vendedor', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'product_id', in: 'query', description: 'ID do produto', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', description: 'Início do período', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', description: 'Fim do período', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Ordenação', schema: new OA\Schema(type: 'string', enum: ['id', 'external_id', 'quantity', 'unit_value', 'created_at'])),
            new OA\Parameter(name: 'order', in: 'query', description: 'Direção', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Página', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Itens por página', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de vendas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: SaleSchema::class)),
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
        $criteria = SaleFilter::from($query);
        $page = $this->reads->list($criteria);
        $totals = $this->reads->totals($criteria);
        ApiResponse::collection($page, '/sales', $query->all(), $totals);
    }

    #[Route('GET', '/sales/export', ['admin'])]
    #[OA\Get(
        path: '/sales/export',
        operationId: 'exportSales',
        summary: 'Exporta vendas em CSV',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'seller_id', in: 'query', required: true, description: 'Obrigatório', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'campaign_id', in: 'query', description: 'ID da campanha', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'product_id', in: 'query', description: 'ID do produto', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', description: 'Início', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', description: 'Fim', schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'CSV de vendas',
                content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string', format: 'binary')),
            ),
            new OA\Response(response: 400, description: 'seller_id ausente'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function export(QueryParams $query): void {
        if ($query->int('seller_id') === null) {
            throw ValidationException::missingField('seller_id');
        }

        $criteria = SaleFilter::from($query);
        $csv = $this->reads->export($criteria);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sales.csv"');
        echo $csv;
    }

    #[Route('POST', '/sales', ['admin'])]
    #[OA\Post(
        path: '/sales',
        operationId: 'createSale',
        summary: 'Cria uma venda',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: CreateSaleDto::class),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Venda criada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: SaleSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 409, description: 'Venda já existe'),
            new OA\Response(response: 422, description: 'Regra de negócio violada'),
        ],
    )]
    public function create(array $body, array $actor): void {
        $sale = $this->writes->create(CreateSaleDto::fromArray($body), $actor);
        ApiResponse::item($sale, 201);
    }

    #[Route('POST', '/sales/batch', ['admin'])]
    #[OA\Post(
        path: '/sales/batch',
        operationId: 'batchSales',
        summary: 'Importa vendas em lote',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: BatchSalesDto::class),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Importação concluída',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', properties: [
                            new OA\Property(property: 'results', type: 'array', items: new OA\Items(ref: SaleSchema::class)),
                        ]),
                        new OA\Property(property: 'meta', type: 'object', additionalProperties: true),
                    ],
                ),
            ),
            new OA\Response(response: 207, description: 'Importação parcial'),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function batch(array $body, array $actor): void {
        $summary = $this->writes->batch(BatchSalesDto::fromArray($body), $actor);
        $status = $summary['created'] === $summary['submitted'] ? 200 : 207;

        ApiResponse::item(
            ['results' => $summary['results']],
            $status,
            [
                'submitted' => $summary['submitted'],
                'created' => $summary['created'],
                'skipped' => $summary['skipped'],
                'errors' => $summary['errors'],
                'points_credited' => $summary['points_credited'],
            ]
        );
    }

    #[Route('POST', '/sales/{external_id}/cancel', ['admin'])]
    #[OA\Post(
        path: '/sales/{external_id}/cancel',
        operationId: 'cancelSale',
        summary: 'Cancela uma venda',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'external_id', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Venda cancelada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: SaleSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Venda não encontrada'),
            new OA\Response(response: 409, description: 'Venda já cancelada'),
            new OA\Response(response: 422, description: 'Regra de negócio violada'),
        ],
    )]
    public function cancel(string $externalId, array $actor): void {
        $externalId = Assert::nonEmptyString($externalId, 'external_id', Limits::SALE_EXTERNAL_ID);
        $sale = $this->writes->cancel($externalId, $actor);
        ApiResponse::item($sale);
    }
}
