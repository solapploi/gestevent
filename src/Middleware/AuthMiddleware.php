<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit;
        }
    }
}
