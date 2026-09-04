<?php
declare(strict_types=1);

namespace App\Wallet\Dao;

use App\Http\Criteria;
use App\Http\PaginatedCollection;
use App\Wallet\WalletEntryType;
use PDO;

final class WalletDao {
    private const
        LIST_COUNT_SQL =
            'SELECT COUNT(*) FROM wallet_entries';

    private const
        LIST_SELECT_SQL =
            'SELECT id, seller_id, campaign_id, sale_id, type, points, description, created_at FROM wallet_entries';

    private const
        SUMMARY_SQL =
            'SELECT COALESCE(SUM(CASE WHEN type = "credit" THEN points ELSE -points END), 0) AS balance, COALESCE(SUM(CASE WHEN type = "credit" THEN points ELSE 0 END), 0) AS credited, COALESCE(SUM(CASE WHEN type = "debit"  THEN points ELSE 0 END), 0) AS debited, COALESCE(AVG(CASE WHEN type = "credit" THEN points END), 0) AS avg_points_per_credit, SUM(type = "credit") AS credit_entries, SUM(type = "debit")  AS debit_entries, COUNT(*) AS total_entries FROM wallet_entries WHERE seller_id = ?';

    private const
        INSERT_SQL =
            'INSERT INTO wallet_entries (seller_id, campaign_id, sale_id, type, points, description) VALUES (?, ?, ?, ?, ?, ?)';

    private const
        FIND_CREDIT_SQL =
            'SELECT points FROM wallet_entries WHERE sale_id = ? AND type = "credit" FOR UPDATE';

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

    public function summary(int $sellerId): array {
        $stmt = $this->pdo->prepare(self::SUMMARY_SQL);
        $stmt->execute([$sellerId]);
        return $stmt->fetch();
    }

    public function insert(int $sellerId, ?int $campaignId, int $saleId, WalletEntryType $type, int $points, string $description): void {
        $stmt = $this->pdo->prepare(self::INSERT_SQL);
        $stmt->execute([$sellerId, $campaignId, $saleId, $type->value, $points, $description]);
    }

    public function findCreditBySaleId(int $saleId): ?int {
        $stmt = $this->pdo->prepare(self::FIND_CREDIT_SQL);
        $stmt->execute([$saleId]);
        $value = $stmt->fetchColumn();
        return $value === false ? null : (int) $value;
    }
}
