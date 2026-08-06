<?php

declare(strict_types=1);

namespace App\Config;

use mysqli;
use RuntimeException;

final class Database
{
    private static ?mysqli $connection = null;

    public static function connection(): mysqli
    {
        if (self::$connection instanceof mysqli) {
            return self::$connection;
        }

        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

        try {
            $connection = new mysqli(
                getenv('DB_HOST') ?: '127.0.0.1',
                getenv('DB_USER') ?: 'root',
                getenv('DB_PASSWORD') ?: '',
                getenv('DB_DATABASE') ?: 'MicroFinance',
                (int) (getenv('DB_PORT') ?: 3306)
            );
            $connection->set_charset('utf8mb4');
            self::$connection = $connection;
        } catch (\Throwable $exception) {
            throw new RuntimeException('Database connection failed.', 0, $exception);
        }

        return self::$connection;
    }
}
