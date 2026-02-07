<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByUsername(string $username): ?array
    {
        $rows = self::where('username = :u AND deleted_at IS NULL', ['u' => $username], 1);
        return $rows[0] ?? null;
    }

    /**
     * Retourne 1 si l'utilisateur est en mode Ramadan, sinon 0
     */
    public static function isRamadan(int $userId): int
    {
        $rows = self::where('id = :i AND deleted_at IS NULL', ['i' => $userId], 1);
        if (empty($rows)) return 0;
        return (int)($rows[0]['ramadan'] ?? 0);
    }

    /**
     * Nom du prof (users.profname) sinon fallback username
     */
    public static function profName(int $userId): string
    {
        $rows = self::where('id = :i AND deleted_at IS NULL', ['i' => $userId], 1);
        if (empty($rows)) return '';
        $r = $rows[0];

        $name = trim((string)($r['profname'] ?? ''));
        if ($name !== '') return $name;

        return trim((string)($r['username'] ?? ''));
    }
}
