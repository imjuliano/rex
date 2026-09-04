<?php
declare(strict_types=1);

namespace App\User\Dao;

use App\Exception\PdoExceptionTranslator;
use App\Http\Criteria;
use App\User\Exception\UserNotFoundException;
use App\Http\PaginatedCollection;
use App\User\Dto\CreateUserDto;
use App\User\Dto\UpdateUserDto;
use PDO;
use PDOException;

final class UserDao {
    private const
        INSERT_SQL =
            'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)';

    private const
        FIND_BY_ID_SQL =
            'SELECT id, name, email, role, created_at FROM users WHERE id = ? AND deleted_at IS NULL';

    private const
        FIND_BY_EMAIL_SQL =
            'SELECT id, email, role, password_hash FROM users WHERE email = ? AND deleted_at IS NULL';

    private const
        LIST_COUNT_SQL =
            'SELECT COUNT(*) FROM users';

    private const
        LIST_SELECT_SQL =
            'SELECT id, name, email, role, created_at FROM users';

    private const
        UPDATE_SQL_PREFIX =
            'UPDATE users SET ';

    private const
        UPDATE_SQL_SUFFIX =
            ' WHERE id = ?';

    private const
        SOFT_DELETE_SQL =
            'UPDATE users SET deleted_at = NOW() WHERE id = ?';

    private const
        TOTALS_SQL =
            'SELECT COUNT(*) AS total, SUM(role = "seller") AS sellers, SUM(role = "admin") AS admins FROM users';

    public function __construct(private PDO $pdo) {}

    public function create(CreateUserDto $dto): int {
        $hash = password_hash($dto->password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare(self::INSERT_SQL);
        try {
            $stmt->execute([$dto->name, $dto->email, $hash, $dto->role->value]);
        } catch (PDOException $e) {
            throw PdoExceptionTranslator::translate($e, ['email' => $dto->email]);
        }
        return (int) $this->pdo->lastInsertId();
    }

    public function findById(int $id): array {
        $stmt = $this->pdo->prepare(self::FIND_BY_ID_SQL);
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new UserNotFoundException($id);
        }
        return $row;
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare(self::FIND_BY_EMAIL_SQL);
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
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

    public function update(int $id, UpdateUserDto $dto): void {
        $columns = [];
        $values = [];

        if ($dto->name !== null) {
            $columns[] = 'name = ?';
            $values[] = $dto->name;
        }
        if ($dto->email !== null) {
            $columns[] = 'email = ?';
            $values[] = $dto->email;
        }
        if ($dto->password !== null) {
            $columns[] = 'password_hash = ?';
            $values[] = password_hash($dto->password, PASSWORD_DEFAULT);
        }
        if ($dto->role !== null) {
            $columns[] = 'role = ?';
            $values[] = $dto->role->value;
        }

        $values[] = $id;
        try {
            $stmt = $this->pdo->prepare(self::UPDATE_SQL_PREFIX . implode(', ', $columns) . self::UPDATE_SQL_SUFFIX);
            $stmt->execute($values);
        } catch (PDOException $e) {
            throw PdoExceptionTranslator::translate($e, ['email' => $dto->email]);
        }
    }

    public function softDelete(int $id): void {
        $stmt = $this->pdo->prepare(self::SOFT_DELETE_SQL);
        $stmt->execute([$id]);
    }

    public function totals(Criteria $criteria): array {
        $stmt = $this->pdo->prepare(self::TOTALS_SQL . $criteria->where());
        $stmt->execute($criteria->bindings());
        return $stmt->fetch();
    }
}
