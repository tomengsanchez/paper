<?php
namespace Core;

abstract class Controller
{
    protected function view(string $view, array $data = []): void
    {
        extract($data);
        $viewPath = dirname(__DIR__) . "/App/Views/{$view}.php";
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

    protected function requireCapability(string $capability): void
    {
        $this->requireAuth();
        if (!Auth::can($capability)) {
            $this->redirect('/');
        }
    }

    /** Validate CSRF for POST requests. Call at the start of any action that accepts POST. Redirects with error if invalid. */
    protected function validateCsrf(): void
    {
        if (!\Core\Csrf::validate()) {
            $this->redirect($this->csrfRedirectUrl(), 403);
        }
    }

    /** Override in subclass to set redirect target when CSRF validation fails (default /). */
    protected function csrfRedirectUrl(): string
    {
        return '/?error=csrf';
    }
}
