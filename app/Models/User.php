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
}