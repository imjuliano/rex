<?php
declare(strict_types=1);

namespace App\Campaign\Dao;

use App\Campaign\CampaignStatus;
use App\Campaign\Dto\CreateCampaignDto;
use App\Campaign\Dto\UpdateCampaignDto;
use App\Campaign\Exception\CampaignNotFoundException;
use App\Exception\PdoExceptionTranslator;
use App\Http\Criteria;
use App\Http\PaginatedCollection;
use PDO;
use PDOException;

final class CampaignDao {
    private const
        INSERT_SQL =
            'INSERT INTO campaigns (name, budget_total, budget_used, starts_at, ends_at, status) VALUES (?, ?, 0, ?, ?, ?)';

    private const
        FIND_SQL =
            'SELECT id, name, budget_total, budget_used, starts_at, ends_at, status, created_at FROM campaigns WHERE id = ?';

    private const
        LOCK_BY_ID_SQL =
            'SELECT id, name, budget_total, budget_used, starts_at, ends_at, status, created_at FROM campaigns WHERE id = ? FOR UPDATE';

    private const
        LIST_COUNT_SQL =
            'SELECT COUNT(*) FROM campaigns';

    private const
        LIST_SELECT_SQL =
            'SELECT id, name, budget_total, budget_used, starts_at, ends_at, status, created_at FROM campaigns';

    private const
        TOTALS_SQL =
            'SELECT COUNT(*) AS total, SUM(status = "active" AND NOW() BETWEEN starts_at AND ends_at) AS active_running, SUM(budget_used >= budget_total) AS exhausted, COALESCE(SUM(budget_total), 0) AS budget_total_sum, COALESCE(SUM(budget_used), 0) AS budget_used_sum FROM campaigns';

    private const
        UPDATE_SQL_PREFIX =
            'UPDATE campaigns SET ';

    private const
        UPDATE_SQL_SUFFIX =
            ' WHERE id = ?';

    private const
        CLOSE_SQL =
            'UPDATE campaigns SET status = ? WHERE id = ?';

    private const
        INCREMENT_BUDGET_SQL =
            'UPDATE campaigns SET budget_used = budget_used + ? WHERE id = ?';

    private const
        DECREMENT_BUDGET_SQL =
            'UPDATE campaigns SET budget_used = budget_used - ? WHERE id = ?';

    public function __construct(private PDO $pdo) {}

    public function create(CreateCampaignDto $dto): int {
        $stmt = $this->pdo->prepare(self::INSERT_SQL);
        $stmt->execute([
            $dto->name,
            $dto->budgetTotal,
            $dto->startsAt->format('Y-m-d H:i:s'),
            $dto->endsAt->format('Y-m-d H:i:s'),
            CampaignStatus::ACTIVE->value,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): array {
        $stmt = $this->pdo->prepare(self::FIND_SQL);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new CampaignNotFoundException($id);
        }
        return $row;
    }

    public function lockById(int $id): array {
        $stmt = $this->pdo->prepare(self::LOCK_BY_ID_SQL);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new CampaignNotFoundException($id);
        }
        return $row;
    }

    public function list(Criteria $criteria): PaginatedCollection {
        $count = $this->pdo->prepare(self::LIST_COUNT_SQL . $criteria->where());
        $count->execute($criteria->bindings());
        $total = (int) $count->fetchColumn();

        $stmt = $this->pdo->prepare(
            self::LIST_SELECT_SQL .
            $criteria->where() . $criteria->orderBy() . $criteria->limit()
        );
        $stmt->execute($criteria->bindings());

        return new PaginatedCollection($stmt->fetchAll(), $total, $criteria->page(), $criteria->perPage());
    }

    public function totals(): array {
        return $this->pdo->query(self::TOTALS_SQL)->fetch();
    }

    public function update(int $id, UpdateCampaignDto $dto): void {
        $columns = [];
        $values = [];

        if ($dto->name !== null) {
            $columns[] = 'name = ?';
            $values[] = $dto->name;
        }
        if ($dto->budgetTotal !== null) {
            $columns[] = 'budget_total = ?';
            $values[] = $dto->budgetTotal;
        }
        if ($dto->startsAt !== null) {
            $columns[] = 'starts_at = ?';
            $values[] = $dto->startsAt->format('Y-m-d H:i:s');
        }
        if ($dto->endsAt !== null) {
            $columns[] = 'ends_at = ?';
            $values[] = $dto->endsAt->format('Y-m-d H:i:s');
        }
        if ($dto->status !== null) {
            $columns[] = 'status = ?';
            $values[] = $dto->status->value;
        }

        $values[] = $id;
        try {
            $stmt = $this->pdo->prepare(self::UPDATE_SQL_PREFIX . implode(', ', $columns) . self::UPDATE_SQL_SUFFIX);
            $stmt->execute($values);
        } catch (PDOException $e) {
            throw PdoExceptionTranslator::translate($e, []);
        }
    }

    public function close(int $id): void {
        $stmt = $this->pdo->prepare(self::CLOSE_SQL);
        $stmt->execute([CampaignStatus::CLOSED->value, $id]);
    }

    public function incrementBudgetUsed(int $id, int $points): void {
        $stmt = $this->pdo->prepare(self::INCREMENT_BUDGET_SQL);
        $stmt->execute([$points, $id]);
    }

    public function decrementBudgetUsed(int $id, int $points): void {
        $stmt = $this->pdo->prepare(self::DECREMENT_BUDGET_SQL);
        $stmt->execute([$points, $id]);
    }
}
