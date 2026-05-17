<?php

namespace App\Core;

class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(): void
    {
        $posted = $_POST['_csrf'] ?? '';
        // hash_equals avoids timing leaks when comparing user-submitted tokens.
        if (!$posted || !hash_equals($_SESSION['_csrf'] ?? '', $posted)) {
            http_response_code(419);
            exit('Security token expired. Go back and try again.');
        }
    }
}
