<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

class Database
{
    private static ?PDO $pdo = null;

    public static function init(array $config): void
    {
        if (self::$pdo) return;

        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            $config['driver'],
            $config['host'],
            (int)$config['port'],
            $config['database'],
            $config['charset']
        );

        self::$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options'] ?? []);
    }

    public static function pdo(): PDO
    {
        if (!self::$pdo) {
            throw new \RuntimeException("Database not initialized");
        }
        return self::$pdo;
    }
}
