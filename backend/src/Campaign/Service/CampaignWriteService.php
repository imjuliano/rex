<?php
declare(strict_types=1);

namespace App\Campaign\Service;

use App\Audit\AuditEvent;
use App\Audit\AuditLogDispatcher;
use App\Audit\LogAction;
use App\Audit\LogEntity;
use App\Campaign\CampaignStatus;
use App\Campaign\Dao\CampaignDao;
use App\Campaign\Dto\CreateCampaignDto;
use App\Campaign\Dto\UpdateCampaignDto;
use App\Campaign\Exception\CampaignBudgetBelowCommittedException;
use App\Campaign\Mapper\CampaignMapper;

final class CampaignWriteService {
    public function __construct(
        private CampaignDao $dao,
        private CampaignMapper $mapper,
        private AuditLogDispatcher $audit,
    ) {}

    public function create(CreateCampaignDto $dto, array $actor): array {
        $id = $this->dao->create($dto);
        $campaign = $this->mapper->map($this->dao->find($id));

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::CAMPAIGN_CREATED,
            entity: LogEntity::CAMPAIGN,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $campaign['name'], 'budget_total' => $campaign['budget']['total'], 'starts_at' => $campaign['period']['starts_at'], 'ends_at' => $campaign['period']['ends_at']],
            diff: [],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $campaign;
    }

    public function update(int $id, UpdateCampaignDto $dto, array $actor): array {
        $before = $this->dao->find($id);
        $this->assertBudgetInvariant($before, $dto);
        $this->dao->update($id, $dto);
        $after = $this->mapper->map($this->dao->find($id));

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::CAMPAIGN_UPDATED,
            entity: LogEntity::CAMPAIGN,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $after['name'], 'budget_total' => $after['budget']['total'], 'starts_at' => $after['period']['starts_at'], 'ends_at' => $after['period']['ends_at'], 'status' => $after['status']],
            diff: ['before' => $this->mapper->map($before), 'after' => $after],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $after;
    }

    public function close(int $id, array $actor): array {
        $before = $this->mapper->map($this->dao->find($id));
        $this->dao->close($id);
        $after = $this->mapper->map($this->dao->find($id));

        $this->audit->dispatch(new AuditEvent(
            action: LogAction::CAMPAIGN_CLOSED,
            entity: LogEntity::CAMPAIGN,
            actorId: $actor['id'],
            actorEmail: $actor['email'],
            actorRole: $actor['role'],
            entityId: (string) $id,
            payload: ['name' => $after['name'], 'budget_total' => $after['budget']['total'], 'status' => $after['status']],
            diff: ['before' => $before, 'after' => $after],
            ipAddress: null,
            userAgent: null,
            correlationId: null,
        ));

        return $after;
    }

    public function lockById(int $id): array {
        return $this->dao->lockById($id);
    }

    public function incrementBudgetUsed(int $id, int $points): void {
        $this->dao->incrementBudgetUsed($id, $points);
    }

    public function decrementBudgetUsed(int $id, int $points): void {
        $this->dao->decrementBudgetUsed($id, $points);
    }

    private function assertBudgetInvariant(array $before, UpdateCampaignDto $dto): void {
        $newTotal = $dto->budgetTotal ?? (int) $before['budget_total'];
        $used = (int) $before['budget_used'];
        if ($newTotal < $used) {
            throw new CampaignBudgetBelowCommittedException($before['id'], $newTotal, $used);
        }
    }
}
