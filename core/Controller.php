<?php
namespace Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = dirname(__DIR__) . "/app/Views/{$view}.php";
        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            die("View not found: {$view}");
        }
    }

    protected function redirect(string $url, int $code = 302): void
    {
        header("Location: {$url}", true, $code);
        exit;
    }

    protected function json($data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function auth(): ?object
    {
        return Auth::user();
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('/login');
        }
    }
}
