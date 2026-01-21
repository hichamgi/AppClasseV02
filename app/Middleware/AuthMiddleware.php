<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            $baseUrl = (require dirname(__DIR__) . '/config/app.php')['base_url'] ?? '';
            header('Location: ' . rtrim($baseUrl, '/') . '/login');
            exit;
        }
    }
}
