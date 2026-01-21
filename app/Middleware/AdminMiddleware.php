<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;

class AdminMiddleware
{
    public function handle(): void
    {
        if (!Auth::isAdmin()) {
            http_response_code(403);
            echo "403 Forbidden";
            exit;
        }
    }
}