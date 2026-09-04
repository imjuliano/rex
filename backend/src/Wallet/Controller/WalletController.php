<?php
declare(strict_types=1);

namespace App\Wallet\Controller;

use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Route;
use App\Wallet\Api\WalletSchema;
use App\Wallet\Filter\WalletFilter;
use App\Wallet\Service\WalletReadService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Wallet')]
final class WalletController {
    public function __construct(private WalletReadService $reads) {}

    #[Route('GET', '/me/wallet', ['seller'])]
    #[OA\Get(
        path: '/me/wallet',
        operationId: 'listWallet',
        summary: 'Carteira do vendedor logado',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Descrição', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'type', in: 'query', description: 'Tipo', schema: new OA\Schema(type: 'string', enum: ['credit', 'debit'])),
            new OA\Parameter(name: 'campaign_id', in: 'query', description: 'ID da campanha', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'sale_id', in: 'query', description: 'ID da venda', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'from', in: 'query', description: 'Início', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', description: 'Fim', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Ordenação', schema: new OA\Schema(type: 'string', enum: ['id', 'points', 'created_at'])),
            new OA\Parameter(name: 'order', in: 'query', description: 'Direção', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Página', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Itens por página', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Movimentações da carteira',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: WalletSchema::class)),
                        new OA\Property(property: 'meta', type: 'object', additionalProperties: true),
                        new OA\Property(property: 'links', type: 'object', additionalProperties: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function list(QueryParams $query, array $actor): void {
        $sellerId = (int) $actor['id'];
        $criteria = WalletFilter::from($query, $sellerId);
        $page = $this->reads->list($criteria);
        $summary = $this->reads->summary($sellerId);
        ApiResponse::collection($page, '/me/wallet', $query->all(), $summary);
    }
}
