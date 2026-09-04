<?php
declare(strict_types=1);

namespace App\User\Service;

use App\Audit\AuditEvent;
use App\Audit\AuditLogDispatcher;
use App\Audit\LogAction;
use App\Audit\LogEntity;
use App\Auth\RefreshTokenStore;
use App\User\Dao\UserDao;
use App\User\Dto\CreateUserDto;
use App\User\Dto\UpdateUserDto;
use App\User\Exception\UserSelfDeletionException;
use App\User\Mapper\UserMapper;

final class UserWriteService {
    public function __construct(
        private UserDao $dao,
        private UserMapper $mapper,
        private AuditLogDispatcher $audit,
        private RefreshTokenStore $refreshTokens,
    ) {}

    public function create(CreateUserDto $dto, array $actor): array {
        $id = $this->dao->create($dto);
        $user = $this->mapper->map($this->dao->findById($id));

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::USER_CREATED,
            entity: LogEntity::USER,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $user;
    }

    public function update(int $id, UpdateUserDto $dto, array $actor): array {
        $before = $this->mapper->map($this->dao->findById($id));
        $this->dao->update($id, $dto);
        $after = $this->mapper->map($this->dao->findById($id));

        // A password change has to end every session opened with the old one,
        // otherwise rotating a leaked credential would not lock the thief out.
        if ($dto->password !== null) {
            $this->refreshTokens->revokeAllForUser($id);
        }

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::USER_UPDATED,
            entity: LogEntity::USER,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $after['name'], 'email' => $after['email'], 'role' => $after['role']],
            diff: ['before' => $before, 'after' => $after],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $after;
    }

    public function softDelete(int $id, array $actor): array {
        if ($actor['id'] === $id) {
            throw new UserSelfDeletionException();
        }

        $user = $this->mapper->map($this->dao->findById($id));
        $this->dao->softDelete($id);
        $this->refreshTokens->revokeAllForUser($id);

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::USER_DELETED,
            entity: LogEntity::USER,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $user['name'], 'email' => $user['email'], 'role' => $user['role']],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $user;
    }
}
