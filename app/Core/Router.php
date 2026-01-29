<?php
declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $baseUrl;
    private ?Container $container;

    public function __construct(string $baseUrl = '', ?Container $container = null)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->container = $container;
    }

    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->map('GET', $path, $handler, $middleware);
    }

    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->map('POST', $path, $handler, $middleware);
    }

    private function map(string $method, string $path, string $handler, array $middleware): void
    {
        $path = '/' . ltrim($path, '/');
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // retirer base_url si nécessaire
        if ($this->baseUrl !== '' && str_starts_with($uri, $this->baseUrl)) {
            $uri = substr($uri, strlen($this->baseUrl)) ?: '/';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) continue;
            $params = [];
            $pattern = $this->toRegex($route['path'], $params);
            if (preg_match($pattern, $uri, $matches)) {
                $args = [];
                foreach ($params as $p) {
                    $args[$p] = $matches[$p] ?? null;
                }

                foreach ($route['middleware'] as $mwClass) {
                    $mw = new $mwClass();
                    $mw->handle();
                }

                [$class, $action] = explode('@', $route['handler'], 2);

                $controller = $this->container
                    ? $this->container->get($class)
                    : new $class();

                $controller->$action($args);
                return;
            }
        }

        http_response_code(404);
        echo "404 Not Found";
    }

    private function toRegex(string $path, ?array &$params): string
    {
        $params = [];
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) use (&$params) {
            $params[] = $m[1];
            return '(?P<' . $m[1] . '>[0-9]+)';
        }, $path);

        return '#^' . $regex . '$#';
    }
}
