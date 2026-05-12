<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Request;

class CsrfMiddleware
{
    public function handle(Request $request): void
    {
    }
}
