<?php
declare(strict_types=1);

namespace App\Audit\Controller;

use App\Audit\Api\AuditLogSchema;
use App\Audit\Filter\AuditLogFilter;
use App\Audit\LogAction;
use App\Audit\LogEntity;
use App\Audit\Mapper\AuditLogMapper;
use App\Audit\MySqlAuditLogRepository;
use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Route;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Audit')]
final class AuditController {
    public function __construct(
        private MySqlAuditLogRepository $auditRepo,
        private AuditLogMapper $mapper,
    ) {}

    #[Route('GET', '/audit/products', ['admin'])]
    #[OA\Get(
        path: '/audit/products',
        operationId: 'listProductAudit',
        summary: 'Auditoria de produtos',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'action', in: 'query', description: 'ID da ação', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'actor_id', in: 'query', description: 'ID do ator', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'entity_id', in: 'query', description: 'ID da entidade', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', description: 'entity_id parcial', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', description: 'Início', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', description: 'Fim', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Ordenação', schema: new OA\Schema(type: 'string', enum: ['id', 'occurred_at', 'action'])),
            new OA\Parameter(name: 'order', in: 'query', description: 'Direção', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Página', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Itens por página', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logs de auditoria de produtos',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: AuditLogSchema::class)),
                        new OA\Property(property: 'meta', type: 'object', additionalProperties: true),
                        new OA\Property(property: 'links', type: 'object', additionalProperties: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function listProducts(QueryParams $query): void {
        $this->list($query, LogEntity::PRODUCT);
    }

    #[Route('GET', '/audit/campaigns', ['admin'])]
    #[OA\Get(
        path: '/audit/campaigns',
        operationId: 'listCampaignAudit',
        summary: 'Auditoria de campanhas',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'actor_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'entity_id', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'occurred_at', 'action'])),
            new OA\Parameter(name: 'order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logs de auditoria de campanhas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: AuditLogSchema::class)),
                        new OA\Property(property: 'meta', type: 'object', additionalProperties: true),
                        new OA\Property(property: 'links', type: 'object', additionalProperties: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function listCampaigns(QueryParams $query): void {
        $this->list($query, LogEntity::CAMPAIGN);
    }

    #[Route('GET', '/audit/sales', ['admin'])]
    #[OA\Get(
        path: '/audit/sales',
        operationId: 'listSaleAudit',
        summary: 'Auditoria de vendas',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'actor_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'entity_id', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'occurred_at', 'action'])),
            new OA\Parameter(name: 'order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logs de auditoria de vendas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: AuditLogSchema::class)),
                        new OA\Property(property: 'meta', type: 'object', additionalProperties: true),
                        new OA\Property(property: 'links', type: 'object', additionalProperties: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function listSales(QueryParams $query): void {
        $this->list($query, LogEntity::SALE);
    }

    #[Route('GET', '/audit/users', ['admin'])]
    #[OA\Get(
        path: '/audit/users',
        operationId: 'listUserAudit',
        summary: 'Auditoria de usuários',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'actor_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'entity_id', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'occurred_at', 'action'])),
            new OA\Parameter(name: 'order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logs de auditoria de usuários',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: AuditLogSchema::class)),
                        new OA\Property(property: 'meta', type: 'object', additionalProperties: true),
                        new OA\Property(property: 'links', type: 'object', additionalProperties: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function listUsers(QueryParams $query): void {
        $this->list($query, LogEntity::USER);
    }

    #[Route('GET', '/audit/auth', ['admin'])]
    #[OA\Get(
        path: '/audit/auth',
        operationId: 'listAuthAudit',
        summary: 'Auditoria de autenticação',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'action', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'actor_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'entity_id', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'search', in: 'query', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'from', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'to', in: 'query', schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'sort', in: 'query', schema: new OA\Schema(type: 'string', enum: ['id', 'occurred_at', 'action'])),
            new OA\Parameter(name: 'order', in: 'query', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'desc')),
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logs de auditoria de autenticação',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: AuditLogSchema::class)),
                        new OA\Property(property: 'meta', type: 'object', additionalProperties: true),
                        new OA\Property(property: 'links', type: 'object', additionalProperties: true),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
        ],
    )]
    public function listAuth(QueryParams $query): void {
        $this->list($query, LogEntity::AUTH);
    }

    private function list(QueryParams $query, LogEntity $entity): void {
        $criteria = AuditLogFilter::from($query);
        $page = $this->auditRepo->list($entity, $criteria, fn(array $row) => $this->mapper->map($row));

        ApiResponse::collection($page, '/audit/' . $entity->value, $query->all(), [
            'actions' => array_map(fn(LogAction $a) => ['value' => $a->value, 'label' => $a->label()], LogAction::cases()),
        ]);
    }
}
