<?php
declare(strict_types=1);

namespace App\Core;

class Controller
{
    /**
     * Render a view with an optional layout.
     * - $layout = 'main' (default): wraps view inside Views/layouts/main.php
     * - $layout = 'auth': wraps view inside Views/layouts/auth.php
     * - $layout = '' or null: renders view only (useful for Bootstrap modals / partials)
     */
    protected function view(string $view, array $data = [], string|null $layout = 'main'): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . trim($view, '/') . '.php';
        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: $viewFile");
        }

        extract($data, EXTR_SKIP);

        // Render view
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // No layout => partial response
        if ($layout === null || $layout === '') {
            echo $content;
            return;
        }

        $layoutFile = dirname(__DIR__) . '/Views/layouts/' . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \RuntimeException("Layout not found: $layoutFile");
        }

        require $layoutFile;
    }

    protected function redirect(string $path): void
    {
        $baseUrl = (require dirname(__DIR__) . '/config/app.php')['base_url'] ?? '';
        header('Location: ' . rtrim($baseUrl, '/') . '/' . ltrim($path, '/'));
        exit;
    }

    protected function json(array $payload, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function e(?string $s): string
    {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    }

    protected function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . $this->e(Csrf::token()) . '">';
    }

    protected function requireCsrf(): void
    {
        $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!Csrf::check((string)$token)) {
            http_response_code(419);
            echo "CSRF token mismatch";
            exit;
        }
    }

    protected function inputJson(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw ?: '', true);
        return is_array($data) ? $data : [];
    }
}