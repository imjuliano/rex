<?php
declare(strict_types=1);

namespace App\Sale\Dao;

use App\Http\Criteria;
use App\Http\PaginatedCollection;
use PDO;

final class SaleDao {
    private const
        LIST_COUNT_SQL =
            'SELECT COUNT(*) FROM sales s JOIN campaigns c ON c.id = s.campaign_id JOIN users u ON u.id = s.seller_id JOIN products p ON p.id = s.product_id';

    private const
        LIST_SELECT_SQL =
            'SELECT s.id, s.external_id, s.quantity, s.unit_value, s.status, s.created_at, s.campaign_id, c.name AS campaign_name, s.seller_id, u.name AS seller_name, u.email AS seller_email, s.product_id, p.name AS product_name, p.sku AS product_sku, p.points_per_unit, (SELECT w.points FROM wallet_entries w WHERE w.sale_id = s.id AND w.type = "credit" LIMIT 1) AS points_credited FROM sales s JOIN campaigns c ON c.id = s.campaign_id JOIN users u ON u.id = s.seller_id JOIN products p ON p.id = s.product_id';

    private const
        TOTALS_SQL =
            'SELECT COUNT(*) AS total, SUM(s.status = "approved") AS approved_count, SUM(s.status = "canceled") AS canceled_count, COALESCE(SUM(s.quantity * s.unit_value), 0) AS gross_value FROM sales s JOIN campaigns c ON c.id = s.campaign_id JOIN users u ON u.id = s.seller_id JOIN products p ON p.id = s.product_id';

    private const
        EXPORT_SQL =
            'SELECT s.external_id, s.campaign_id, s.seller_id, s.product_id, s.quantity, s.unit_value FROM sales s';

    private const
        FIND_BY_EXTERNAL_SQL =
            'SELECT id, seller_id, campaign_id, status, quantity, unit_value FROM sales WHERE external_id = ? FOR UPDATE';

    private const
        INSERT_SQL =
            'INSERT INTO sales (external_id, campaign_id, seller_id, product_id, quantity, unit_value, status) VALUES (?, ?, ?, ?, ?, ?, ?)';

    private const
        CANCEL_SQL =
            'UPDATE sales SET status = ? WHERE id = ?';

    public function __construct(private PDO $pdo) {}

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

    public function export(Criteria $criteria): array {
        $stmt = $this->pdo->prepare(
            self::EXPORT_SQL .
            $criteria->where() . $criteria->orderBy()
        );
        $stmt->execute($criteria->bindings());
        return $stmt->fetchAll();
    }

    public function totals(Criteria $criteria): array {
        $stmt = $this->pdo->prepare(self::TOTALS_SQL . $criteria->where());
        $stmt->execute($criteria->bindings());
        return $stmt->fetch();
    }

    public function findByExternalIdForUpdate(string $externalId): ?array {
        $stmt = $this->pdo->prepare(self::FIND_BY_EXTERNAL_SQL);
        $stmt->execute([$externalId]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $externalId, int $campaignId, int $sellerId, int $productId, int $quantity, float $unitValue, string $status): int {
        $stmt = $this->pdo->prepare(self::INSERT_SQL);
        $stmt->execute([$externalId, $campaignId, $sellerId, $productId, $quantity, $unitValue, $status]);
        return (int) $this->pdo->lastInsertId();
    }

    public function cancel(int $id): void {
        $stmt = $this->pdo->prepare(self::CANCEL_SQL);
        $stmt->execute(['canceled', $id]);
    }
}
