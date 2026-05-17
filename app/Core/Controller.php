<?php

namespace App\Core;

class Controller
{
    protected function view(string $view, array $params = []): void
    {
        View::render($view, $params);
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function requireAuth(): void
    {
        if (!Auth::check()) {
            $this->redirect('?page=login');
        }
    }

    protected function requireRole(array|string $roles): void
    {
        $this->requireAuth();
        // UI controls are hidden by role, but authorization is enforced here.
        if (!Auth::hasRole($roles)) {
            http_response_code(403);
            View::render('errors/403', []);
            exit;
        }
    }

    protected function requirePermission(string $permission): void
    {
        $this->requireAuth();
        if (!Auth::can($permission)) {
            http_response_code(403);
            View::render('errors/403', []);
            exit;
        }
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }
}
