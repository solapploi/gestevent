<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    /** @var array<int, array{method: string, pattern: string, controller: string, action: string, middleware: list<string>}> */
    private array $routes = [];

    public function get(string $pattern, string $controller, string $action, array $middleware = []): void
    {
        $this->add('GET', $pattern, $controller, $action, $middleware);
    }

    public function post(string $pattern, string $controller, string $action, array $middleware = []): void
    {
        $this->add('POST', $pattern, $controller, $action, $middleware);
    }

    public function add(string $method, string $pattern, string $controller, string $action, array $middleware = []): void
    {
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $this->compilePattern($pattern),
            'controller' => $controller,
            'action'     => $action,
            'middleware' => $middleware,
        ];
    }

    public function dispatch(Request $request): void
    {
        $method = $request->getMethod();
        $uri    = $request->getUri();

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $uri, $matches)) {
                continue;
            }

            // Extract named URL parameters (string keys only)
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $request->setRouteParams($params);

            // Run middleware stack; each middleware may throw or redirect to abort
            foreach ($route['middleware'] as $middlewareClass) {
                (new $middlewareClass())->handle($request);
            }

            $controllerClass = 'App\\Controllers\\' . $route['controller'];
            $action          = $route['action'];

            if (!class_exists($controllerClass)) {
                $this->abort(500, "Contrôleur introuvable : {$route['controller']}");
                return;
            }

            $controller = new $controllerClass();

            if (!method_exists($controller, $action)) {
                $this->abort(500, "Action introuvable : {$action} dans {$route['controller']}");
                return;
            }

            $controller->$action($request);
            return;
        }

        $this->abort(404, 'Page introuvable.');
    }

    // Converts {param} placeholders to named regex capture groups
    private function compilePattern(string $pattern): string
    {
        $regex = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $pattern);

        return '#^' . $regex . '$#';
    }

    private function abort(int $code, string $message): void
    {
        http_response_code($code);
        echo htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
