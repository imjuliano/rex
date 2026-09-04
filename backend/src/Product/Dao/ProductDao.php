<?php
declare(strict_types=1);

namespace App\Product\Dao;

use App\Exception\PdoExceptionTranslator;
use App\Http\Criteria;
use App\Product\Exception\ProductNotFoundException;
use App\Http\PaginatedCollection;
use App\Product\Dto\CreateProductDto;
use App\Product\Dto\UpdateProductDto;
use PDO;
use PDOException;

final class ProductDao {
    private const
        INSERT_SQL =
            'INSERT INTO products (name, sku, points_per_unit, active) VALUES (?, ?, ?, ?)';

    private const
        FIND_SQL =
            'SELECT id, name, sku, points_per_unit, active, created_at FROM products WHERE id = ? AND deleted_at IS NULL';

    private const
        LIST_COUNT_SQL =
            'SELECT COUNT(*) FROM products';

    private const
        LIST_SELECT_SQL =
            'SELECT id, name, sku, points_per_unit, active, created_at FROM products';

    private const
        SUMMARY_SQL =
            'SELECT COUNT(*) AS total_products, SUM(active = 1) AS active_products, COALESCE(AVG(CASE WHEN active = 1 THEN points_per_unit END), 0) AS avg_points_per_unit_active FROM products WHERE deleted_at IS NULL';

    private const
        UPDATE_SQL_PREFIX =
            'UPDATE products SET ';

    private const
        UPDATE_SQL_SUFFIX =
            ' WHERE id = ?';

    private const
        DEACTIVATE_SQL =
            'UPDATE products SET active = 0 WHERE id = ?';

    private const
        SOFT_DELETE_SQL =
            'UPDATE products SET deleted_at = NOW() WHERE id = ?';

    public function __construct(private PDO $pdo) {}

    public function create(CreateProductDto $dto): int {
        $stmt = $this->pdo->prepare(self::INSERT_SQL);
        try {
            $stmt->execute([$dto->name, $dto->sku, $dto->pointsPerUnit, $dto->active ? 1 : 0]);
        } catch (PDOException $e) {
            throw PdoExceptionTranslator::translate($e, ['sku' => $dto->sku]);
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function find(int $id): array {
        $stmt = $this->pdo->prepare(self::FIND_SQL);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new ProductNotFoundException($id);
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

        return new PaginatedCollection(
            $stmt->fetchAll(),
            $total,
            $criteria->page(),
            $criteria->perPage()
        );
    }

    public function summary(): array {
        return $this->pdo->query(self::SUMMARY_SQL)->fetch();
    }

    public function update(int $id, UpdateProductDto $dto): void {
        $columns = [];
        $values = [];

        if ($dto->name !== null) {
            $columns[] = 'name = ?';
            $values[] = $dto->name;
        }
        if ($dto->sku !== null) {
            $columns[] = 'sku = ?';
            $values[] = $dto->sku;
        }
        if ($dto->pointsPerUnit !== null) {
            $columns[] = 'points_per_unit = ?';
            $values[] = $dto->pointsPerUnit;
        }
        if ($dto->active !== null) {
            $columns[] = 'active = ?';
            $values[] = $dto->active ? 1 : 0;
        }

        $values[] = $id;
        try {
            $stmt = $this->pdo->prepare(self::UPDATE_SQL_PREFIX . implode(', ', $columns) . self::UPDATE_SQL_SUFFIX);
            $stmt->execute($values);
        } catch (PDOException $e) {
            throw PdoExceptionTranslator::translate($e, ['sku' => $dto->sku]);
        }
    }

    public function deactivate(int $id): void {
        $stmt = $this->pdo->prepare(self::DEACTIVATE_SQL);
        $stmt->execute([$id]);
    }

    public function softDelete(int $id): void {
        $stmt = $this->pdo->prepare(self::SOFT_DELETE_SQL);
        $stmt->execute([$id]);
    }
}
