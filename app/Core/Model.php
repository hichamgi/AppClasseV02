<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    protected static function pdo(): PDO
    {
        return Database::pdo();
    }

    public static function find(int $id): ?array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id LIMIT 1";
        $st = self::pdo()->prepare($sql);
        $st->execute(['id' => $id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function all(int $limit = 200, int $offset = 0): array
    {
        $sql = "SELECT * FROM " . static::$table . " LIMIT :o, :l";
        $st = self::pdo()->prepare($sql);
        $st->bindValue(':o', $offset, PDO::PARAM_INT);
        $st->bindValue(':l', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function where(string $whereSql, array $params = [], int $limit = 200): array
    {
        $sql = "SELECT * FROM " . static::$table . " WHERE $whereSql LIMIT :l";
        $st = self::pdo()->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue(is_int($k) ? $k + 1 : ':' . $k, $v);
        $st->bindValue(':l', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll();
    }

    public static function insert(array $data): int
    {
        $cols = array_keys($data);
        $place = array_map(fn($c) => ':' . $c, $cols);

        $sql = "INSERT INTO " . static::$table .
            " (" . implode(',', $cols) . ") VALUES (" . implode(',', $place) . ")";
        $st = self::pdo()->prepare($sql);
        $st->execute($data);
        return (int)self::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $sets = [];
        foreach ($data as $k => $_) $sets[] = "$k=:$k";
        $data[static::$primaryKey] = $id;

        $sql = "UPDATE " . static::$table . " SET " . implode(',', $sets) .
            " WHERE " . static::$primaryKey . " = :" . static::$primaryKey;
        $st = self::pdo()->prepare($sql);
        return $st->execute($data);
    }

    public static function delete(int $id): bool
    {
        $sql = "DELETE FROM " . static::$table . " WHERE " . static::$primaryKey . " = :id";
        $st = self::pdo()->prepare($sql);
        return $st->execute(['id' => $id]);
    }
}
