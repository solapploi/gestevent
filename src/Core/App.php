<?php

declare(strict_types=1);

namespace App\Core;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;

class App
{
    private Router $router;

    public function __construct()
    {
        date_default_timezone_set('Europe/Paris');
        $this->setSecurityHeaders();
        $this->initSession();
        $this->router = new Router();
        $this->registerRoutes();
    }

    public function run(): void
    {
        $request = new Request();
        $this->router->dispatch($request);
    }

    private function registerRoutes(): void
    {
        $auth = [AuthMiddleware::class];
        $csrf = [CsrfMiddleware::class];
        $authCsrf = [AuthMiddleware::class, CsrfMiddleware::class];

        // Auth
        $this->router->get('/login',  'AuthController', 'loginForm');
        $this->router->post('/login', 'AuthController', 'login', $csrf);
        $this->router->get('/logout', 'AuthController', 'logout', $auth);

        // Public invité
        $this->router->get('/inscription/{slug}',         'RegistrationController', 'form');
        $this->router->post('/inscription/{slug}',        'RegistrationController', 'submit', $csrf);
        $this->router->get('/reponse/{access_token}',     'ResponseController',     'form');
        $this->router->post('/reponse/{access_token}',    'ResponseController',     'submit', $csrf);
        $this->router->get('/invitation/{access_token}',  'InvitationController',   'show');

        // Backoffice
        $this->router->get('/',                                'DashboardController',    'redirectAdmin');
        $this->router->get('/admin',                           'DashboardController',    'index',   $auth);
        $this->router->get('/admin/events',                    'EventController',        'list',    $auth);
        $this->router->post('/admin/events',                   'EventController',        'create',  $authCsrf);
        $this->router->get('/admin/events/{id}',               'EventController',        'show',    $auth);
        $this->router->post('/admin/events/{id}/admins',       'EventAdminController',   'assign',  $authCsrf);
        $this->router->get('/admin/events/{id}/guests',        'GuestController',        'list',    $auth);
        $this->router->post('/admin/guests/{id}/approve',      'GuestController',        'approve', $authCsrf);
        $this->router->post('/admin/guests/{id}/reject',       'GuestController',        'reject',  $authCsrf);
        $this->router->post('/admin/events/{id}/import',       'GuestImportController',  'import',  $authCsrf);
        $this->router->get('/admin/events/{id}/export',        'GuestExportController',  'export',  $auth);
        $this->router->get('/admin/events/{id}/stats',         'StatsController',        'index',   $auth);
        $this->router->get('/admin/users',                     'UserController',         'list',    $auth);
        $this->router->post('/admin/users',                    'UserController',         'create',  $authCsrf);

        // Scanner
        $this->router->get('/scanner/{event_id}',  'ScannerController', 'index',    $auth);
        $this->router->post('/api/scan',           'ScanApiController', 'validate', $auth);
    }

    private function setSecurityHeaders(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:;");
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(self)');
    }

    private function initSession(): void
    {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');

        if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
            ini_set('session.cookie_secure', '1');
        }

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
