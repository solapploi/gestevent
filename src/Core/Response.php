<?php

declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        require ROOT_PATH . '/views/' . $view . '.php';
    }

    public static function redirect(string $url, int $code = 302): never
    {
        http_response_code($code);
        header('Location: ' . $url);
        exit;
    }

    public static function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
