<?php
declare(strict_types=1);

namespace App\Audit\Mapper;

use App\Audit\LogAction;
use App\Audit\MySqlAuditLogRepository;

final class AuditLogMapper {
    public function __construct(private MySqlAuditLogRepository $auditRepo) {}

    public function map(array $r): array {
        $action = LogAction::from((int) $r['action']);
        $payload = $r['payload'] !== null ? json_decode((string) $r['payload'], true) : null;
        $diff = $r['diff'] !== null ? json_decode((string) $r['diff'], true) : null;

        return [
            'id' => (int) $r['id'],
            'action' => ['value' => $action->value, 'label' => $action->label()],
            'actor_id' => $r['actor_id'] !== null ? (int) $r['actor_id'] : null,
            'actor_role' => $r['actor_role'],
            'actor_email' => $r['actor_email_encrypted'] !== null ? $this->auditRepo->decryptEmail($r['actor_email_encrypted']) : null,
            'entity_id' => $r['entity_id'],
            'payload' => $payload,
            'diff' => $diff,
            'ip_address' => $r['ip_address'],
            'user_agent' => $r['user_agent'],
            'correlation_id' => $r['correlation_id'],
            'occurred_at' => (new \DateTimeImmutable($r['occurred_at']))->format(\DateTimeInterface::ATOM),
        ];
    }
}
