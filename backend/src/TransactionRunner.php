<?php
declare(strict_types=1);

namespace App;

use App\Exception\AbstractDomainException;
use App\Exception\InfrastructureException;
use App\Exception\PdoExceptionTranslator;
use PDO;
use PDOException;
use Throwable;

final class TransactionRunner {
    public function __construct(private PDO $pdo) {}

    public function run(callable $work): mixed {
        $this->pdo->beginTransaction();
        try {
            $result = $work();
            $this->pdo->commit();
            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $this->translateException($e);
        }
    }

    private function translateException(Throwable $e): Throwable {
        if ($e instanceof AbstractDomainException) {
            return $e;
        }
        if ($e instanceof PDOException) {
            return PdoExceptionTranslator::translate($e);
        }
        return InfrastructureException::databaseError($e);
    }
}
