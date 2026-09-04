<?php
declare(strict_types=1);

namespace App\User\Controller;

use App\Http\ApiResponse;
use App\Http\QueryParams;
use App\User\Dto\CreateUserDto;
use App\User\Dto\UpdateUserDto;
use App\User\Filter\UserFilter;
use App\User\Service\UserReadService;
use App\User\Service\UserWriteService;

final class UserController {
    public function __construct(
        private UserReadService $reads,
        private UserWriteService $writes,
    ) {}

    public function list(QueryParams $query): void {
        $criteria = UserFilter::from($query);
        $page = $this->reads->list($criteria);
        $totals = $this->reads->totals($criteria);
        ApiResponse::collection($page, '/users', $query->all(), $totals);
    }

    public function create(array $body, array $actor): void {
        $user = $this->writes->create(CreateUserDto::fromArray($body), $actor);
        ApiResponse::item($user, 201);
    }

    public function update(int $id, array $body, array $actor): void {
        $user = $this->writes->update($id, UpdateUserDto::fromArray($body), $actor);
        ApiResponse::item($user);
    }

    public function softDelete(int $id, array $actor): void {
        $user = $this->writes->softDelete($id, $actor);
        ApiResponse::item($user);
    }
}
