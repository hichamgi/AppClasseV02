<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

class Auth
{
    public static function check(): bool
    {
        $cfg = require dirname(__DIR__) . '/config/auth.php';
        return (int)Session::get($cfg['session_key'], 0) > 0;
    }

    public static function id(): int
    {
        $cfg = require dirname(__DIR__) . '/config/auth.php';
        return (int)Session::get($cfg['session_key'], 0);
    }

    public static function user(): ?array
    {
        $id = self::id();
        return $id > 0 ? User::find($id) : null;
    }

    public static function login(int $userId): void
    {
        $cfg = require dirname(__DIR__) . '/config/auth.php';
        Session::set($cfg['session_key'], $userId);
    }

    public static function logout(): void
    {
        $cfg = require dirname(__DIR__) . '/config/auth.php';
        Session::forget($cfg['session_key']);
        Session::forget('_2fa_pending');
        Session::forget('_2fa_code');
        Session::forget('_2fa_exp');
    }

    public static function isAdmin(): bool
    {
        $u = self::user();
        if (!$u) return false;

        $cfg = require dirname(__DIR__) . '/config/auth.php';
        $admins = $cfg['admin_usernames'] ?? [];
        return in_array($u['username'] ?? '', $admins, true);
    }
}