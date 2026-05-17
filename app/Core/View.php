<?php

namespace App\Core;

class View
{
    public static function render(string $view, array $params = []): void
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $user = Auth::user();
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        extract($params, EXTR_SKIP);

        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';
        if (!is_file($viewPath)) {
            http_response_code(500);
            exit('View not found.');
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        require dirname(__DIR__) . '/Views/layout.php';
    }
}
