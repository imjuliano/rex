<?php
declare(strict_types=1);

namespace App\Audit;

use App\Http\PaginatedCollection;
use App\Http\Criteria;
use PDO;
use PDOException;

final class MySqlAuditLogRepository implements AuditLogWriter {
    private const
        INSERT_SQL =
            'INSERT INTO %s (action, actor_id, actor_role, actor_email_encrypted, entity_id, payload, diff, ip_address, user_agent, correlation_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

    private const
        COUNT_SQL =
            'SELECT COUNT(*) FROM %s';

    private const
        SELECT_SQL =
            'SELECT * FROM %s';

    public function __construct(
        private PDO $pdo,
        private AuditEncryptor $encryptor,
    ) {}

    public function decryptEmail(?string $encrypted): ?string {
        return $this->encryptor->decrypt($encrypted);
    }

    public function write(AuditEvent $event): void {
        $table = $event->table();
        $sql = sprintf(self::INSERT_SQL, $table);

        $stmt = $this->pdo->prepare($sql);
        try {
            $stmt->execute([
                $event->action->value,
                $event->actorId,
                $event->actorRole,
                $event->actorEmail !== null ? $this->encryptor->encrypt($event->actorEmail) : null,
                $event->entityId,
                $event->payload === [] ? null : json_encode($event->payload, JSON_THROW_ON_ERROR),
                $event->diff === [] ? null : json_encode($event->diff, JSON_THROW_ON_ERROR),
                $event->ipAddress,
                $event->userAgent,
                $event->correlationId,
            ]);
        } catch (PDOException $e) {
            // Audit logs must never break the main transaction.
            error_log('Audit log write failed: ' . $e->getMessage());
        }
    }

    /**
     * @param callable(array<string, mixed>): array<string, mixed> $mapper
     */
    public function list(LogEntity $entity, Criteria $criteria, callable $mapper): PaginatedCollection {
        $table = $entity->tableName();

        $count = $this->pdo->prepare(sprintf(self::COUNT_SQL, $table) . $criteria->where());
        $count->execute($criteria->bindings());
        $total = (int) $count->fetchColumn();

        $rows = $this->pdo->prepare(
            sprintf(self::SELECT_SQL, $table) . $criteria->where() . $criteria->orderBy() . $criteria->limit()
        );
        $rows->execute($criteria->bindings());

        return new PaginatedCollection(
            array_map($mapper, $rows->fetchAll()),
            $total,
            $criteria->page(),
            $criteria->perPage()
        );
    }
}
