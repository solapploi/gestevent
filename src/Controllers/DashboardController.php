<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;

class DashboardController
{
    public function redirectAdmin(Request $request): void
    {
        header('Location: /admin');
        exit;
    }

    public function index(Request $request): void
    {
        // TODO: dashboard backoffice
    }
}
