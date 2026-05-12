<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;

class DashboardController
{
    public function redirectAdmin(Request $request): void
    {
        header('Location: /admin');
        exit;
    }

    public function index(Request $request): void
    {
        Response::render('dashboard/index', [
            'user_name' => $_SESSION['user_name'] ?? '',
            'user_role' => $_SESSION['user_role'] ?? '',
        ]);
    }
}
