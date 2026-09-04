<?php
declare(strict_types=1);

namespace App\Campaign\Controller;

use App\Campaign\Api\CampaignSchema;
use App\Campaign\Dto\CreateCampaignDto;
use App\Campaign\Dto\UpdateCampaignDto;
use App\Campaign\Filter\CampaignFilter;
use App\Campaign\Service\CampaignReadService;
use App\Campaign\Service\CampaignWriteService;
use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Campaigns')]
final class CampaignController {
    public function __construct(
        private CampaignReadService $reads,
        private CampaignWriteService $writes,
    ) {}

    #[Route('GET', '/campaigns', ['admin'])]
    #[OA\Get(
        path: '/campaigns',
        operationId: 'listCampaigns',
        summary: 'Lista campanhas',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Nome', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Status', schema: new OA\Schema(type: 'string', enum: ['active', 'closed'])),
            new OA\Parameter(name: 'running', in: 'query', description: 'Em execução', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'exhausted', in: 'query', description: 'Verba esgotada', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Ordenação', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'budget_total', 'budget_used', 'starts_at', 'ends_at', 'created_at'])),
            new OA\Parameter(name: 'order', in: 'query', description: 'Direção', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Página', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Itens por página', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de campanhas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: CampaignSchema::class)),
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
        $criteria = CampaignFilter::from($query);
        $page = $this->reads->list($criteria);
        $totals = $this->reads->totals();
        ApiResponse::collection($page, '/campaigns', $query->all(), $totals);
    }

    #[Route('POST', '/campaigns', ['admin'])]
    #[OA\Post(
        path: '/campaigns',
        operationId: 'createCampaign',
        summary: 'Cria uma campanha',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: CreateCampaignDto::class),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Campanha criada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: CampaignSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function create(array $body, array $actor): void {
        $campaign = $this->writes->create(CreateCampaignDto::fromArray($body), $actor);
        ApiResponse::item($campaign, 201);
    }

    #[Route('PUT', '/campaigns/{id}', ['admin'])]
    #[OA\Put(
        path: '/campaigns/{id}',
        operationId: 'updateCampaign',
        summary: 'Atualiza uma campanha',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: UpdateCampaignDto::class),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Campanha atualizada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: CampaignSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Campanha não encontrada'),
            new OA\Response(response: 409, description: 'Conflito de atualização'),
        ],
    )]
    public function update(int $id, array $body, array $actor): void {
        $campaign = $this->writes->update($id, UpdateCampaignDto::fromArray($body), $actor);
        ApiResponse::item($campaign);
    }

    #[Route('DELETE', '/campaigns/{id}', ['admin'])]
    #[OA\Delete(
        path: '/campaigns/{id}',
        operationId: 'closeCampaign',
        summary: 'Encerra uma campanha',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Campanha encerrada',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: CampaignSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Campanha não encontrada'),
            new OA\Response(response: 422, description: 'Campanha já encerrada'),
        ],
    )]
    public function close(int $id, array $actor): void {
        $campaign = $this->writes->close($id, $actor);
        ApiResponse::item($campaign);
    }
}
