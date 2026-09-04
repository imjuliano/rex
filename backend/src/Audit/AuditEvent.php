<?php
declare(strict_types=1);

namespace App\Audit;

final class AuditEvent {
    public function __construct(
        public readonly LogAction $action,
        public readonly LogEntity $entity,
        public readonly ?int $actorId,
        public readonly ?string $actorEmail,
        public readonly ?string $actorRole,
        public readonly ?string $entityId,
        public readonly array $payload,
        public readonly array $diff,
        public readonly ?string $ipAddress,
        public readonly ?string $userAgent,
        public readonly ?string $correlationId,
    ) {}

    public function table(): string {
        return $this->entity->tableName();
    }
}
