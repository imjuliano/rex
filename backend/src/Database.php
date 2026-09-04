<?php
declare(strict_types=1);

namespace App;

use App\Exception\InfrastructureException;
use PDO;
use PDOException;

class Database {
    public static function connect(): PDO {
        return self::pdo(getenv('DB_NAME') ?: 'rex');
    }

    public static function logsConnect(): PDO {
        return self::pdo(getenv('LOG_DB_NAME') ?: 'rex_logs');
    }

    private static function pdo(string $db): PDO {
        $host = getenv('DB_HOST') ?: 'db';
        $user = getenv('DB_USER') ?: 'rex';
        $pass = getenv('DB_PASSWORD') ?: 'rex';

        try {
            return new PDO(
                "mysql:host={$host};dbname={$db};charset=utf8mb4",
                $user,
                $pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw InfrastructureException::databaseUnavailable($e);
        }
    }
}
