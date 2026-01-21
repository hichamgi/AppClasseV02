<?php
declare(strict_types=1);

namespace App\Core;

class Csrf
{
    private const KEY = '_csrf';

    public static function token(): string
    {
        $token = Session::get(self::KEY);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::KEY, $token);
        }
        return (string)$token;
    }

    public static function check(string $token): bool
    {
        $sess = (string)Session::get(self::KEY, '');
        return $sess !== '' && hash_equals($sess, $token);
    }
}