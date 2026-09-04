<?php
declare(strict_types=1);

namespace App\Audit\Controller;

use App\Audit\Filter\AuditLogFilter;
use App\Audit\LogAction;
use App\Audit\LogEntity;
use App\Audit\Mapper\AuditLogMapper;
use App\Audit\MySqlAuditLogRepository;
use App\Http\ApiResponse;
use App\Http\QueryParams;

final class AuditController {
    public function __construct(
        private MySqlAuditLogRepository $auditRepo,
        private AuditLogMapper $mapper,
    ) {}

    public function list(QueryParams $query, LogEntity $entity): void {
        $criteria = AuditLogFilter::from($query);
        $page = $this->auditRepo->list($entity, $criteria, fn(array $row) => $this->mapper->map($row));

        ApiResponse::collection($page, '/audit/' . $entity->value, $query->all(), [
            'actions' => array_map(fn(LogAction $a) => ['value' => $a->value, 'label' => $a->label()], LogAction::cases()),
        ]);
    }
}
