<?php
declare(strict_types=1);

namespace App\User\Controller;

use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\Route;
use App\User\Api\UserSchema;
use App\User\Dto\CreateUserDto;
use App\User\Dto\UpdateUserDto;
use App\User\Filter\UserFilter;
use App\User\Service\UserReadService;
use App\User\Service\UserWriteService;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Users')]
final class UserController {
    public function __construct(
        private UserReadService $reads,
        private UserWriteService $writes,
    ) {}

    #[Route('GET', '/users', ['admin'])]
    #[OA\Get(
        path: '/users',
        operationId: 'listUsers',
        summary: 'Lista usuários',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'search', in: 'query', description: 'Nome ou e-mail', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'role', in: 'query', description: 'Papel', schema: new OA\Schema(type: 'string', enum: ['admin', 'seller'])),
            new OA\Parameter(name: 'with_deleted', in: 'query', description: 'Incluir excluídos', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'sort', in: 'query', description: 'Ordenação', schema: new OA\Schema(type: 'string', enum: ['id', 'name', 'email', 'created_at'])),
            new OA\Parameter(name: 'order', in: 'query', description: 'Direção', schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'], default: 'asc')),
            new OA\Parameter(name: 'page', in: 'query', description: 'Página', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'per_page', in: 'query', description: 'Itens por página', schema: new OA\Schema(type: 'integer', default: 20, maximum: 100)),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de usuários',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: UserSchema::class)),
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
        $criteria = UserFilter::from($query);
        $page = $this->reads->list($criteria);
        $totals = $this->reads->totals($criteria);
        ApiResponse::collection($page, '/users', $query->all(), $totals);
    }

    #[Route('POST', '/users', ['admin'])]
    #[OA\Post(
        path: '/users',
        operationId: 'createUser',
        summary: 'Cria um usuário',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: CreateUserDto::class),
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuário criado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: UserSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 409, description: 'E-mail duplicado'),
        ],
    )]
    public function create(array $body, array $actor): void {
        $user = $this->writes->create(CreateUserDto::fromArray($body), $actor);
        ApiResponse::item($user, 201);
    }

    #[Route('PUT', '/users/{id}', ['admin'])]
    #[OA\Put(
        path: '/users/{id}',
        operationId: 'updateUser',
        summary: 'Atualiza um usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: UpdateUserDto::class),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuário atualizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: UserSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 400, description: 'Payload inválido'),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Usuário não encontrado'),
            new OA\Response(response: 409, description: 'E-mail duplicado'),
        ],
    )]
    public function update(int $id, array $body, array $actor): void {
        $user = $this->writes->update($id, UpdateUserDto::fromArray($body), $actor);
        ApiResponse::item($user);
    }

    #[Route('DELETE', '/users/{id}', ['admin'])]
    #[OA\Delete(
        path: '/users/{id}',
        operationId: 'softDeleteUser',
        summary: 'Exclui um usuário',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuário excluído',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: UserSchema::class),
                    ],
                ),
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Sem permissão'),
            new OA\Response(response: 404, description: 'Usuário não encontrado'),
            new OA\Response(response: 422, description: 'Auto-exclusão não permitida'),
        ],
    )]
    public function softDelete(int $id, array $actor): void {
        $user = $this->writes->softDelete($id, $actor);
        ApiResponse::item($user);
    }
}
