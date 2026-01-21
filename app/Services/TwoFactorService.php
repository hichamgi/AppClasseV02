<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Session;

class TwoFactorService
{
    public function generateCode(int $ttlSeconds = 300): string
    {
        $code = (string)random_int(100000, 999999);
        Session::set('_2fa_code', $code);
        Session::set('_2fa_exp', time() + $ttlSeconds);
        return $code;
    }

    public function verify(string $code): bool
    {
        $saved = (string)Session::get('_2fa_code', '');
        $exp = (int)Session::get('_2fa_exp', 0);
        if ($saved === '' || $exp < time()) return false;
        return hash_equals($saved, trim($code));
    }
}