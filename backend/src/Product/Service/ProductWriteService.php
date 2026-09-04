<?php
declare(strict_types=1);

namespace App\Product\Service;

use App\Audit\AuditEvent;
use App\Audit\AuditLogDispatcher;
use App\Audit\LogAction;
use App\Audit\LogEntity;
use App\Product\Dao\ProductDao;
use App\Product\Dto\CreateProductDto;
use App\Product\Dto\UpdateProductDto;
use App\Product\Mapper\ProductMapper;

final class ProductWriteService {
    public function __construct(
        private ProductDao $dao,
        private ProductMapper $mapper,
        private AuditLogDispatcher $audit,
    ) {}

    public function create(CreateProductDto $dto, array $actor): array {
        $id = $this->dao->create($dto);
        $product = $this->mapper->map($this->dao->find($id));

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::PRODUCT_CREATED,
            entity: LogEntity::PRODUCT,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $product['name'], 'sku' => $product['sku'], 'points_per_unit' => $product['points_per_unit'], 'active' => $product['active']],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $product;
    }

    public function update(int $id, UpdateProductDto $dto, array $actor): array {
        $before = $this->mapper->map($this->dao->find($id));
        $this->dao->update($id, $dto);
        $after = $this->mapper->map($this->dao->find($id));

        $action = LogAction::PRODUCT_UPDATED;
        if ($dto->active !== null) {
            if (!$before['active'] && $after['active']) {
                $action = LogAction::PRODUCT_ACTIVATED;
            } elseif ($before['active'] && !$after['active']) {
                $action = LogAction::PRODUCT_DEACTIVATED;
            }
        }

        $this->audit->dispatch(new AuditEvent(
            action: $action,
            entity: LogEntity::PRODUCT,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $after['name'], 'sku' => $after['sku'], 'points_per_unit' => $after['points_per_unit'], 'active' => $after['active']],
            diff: ['before' => $before, 'after' => $after],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $after;
    }

    public function deactivate(int $id, array $actor): array {
        $before = $this->mapper->map($this->dao->find($id));
        $this->dao->deactivate($id);
        $after = $this->mapper->map($this->dao->find($id));

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::PRODUCT_DEACTIVATED,
            entity: LogEntity::PRODUCT,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $after['name'], 'sku' => $after['sku'], 'active' => false],
            diff: ['before' => $before, 'after' => $after],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $after;
    }

    public function softDelete(int $id, array $actor): array {
        $product = $this->mapper->map($this->dao->find($id));
        $this->dao->softDelete($id);

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::PRODUCT_DELETED,
            entity: LogEntity::PRODUCT,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $product['name'], 'sku' => $product['sku']],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $product;
    }
}
