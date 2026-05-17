<?php

namespace App\Core;

class SecurityHeaders
{
    public static function apply(array $config): void
    {
        if (headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');

        $baseUrl = (string) ($config['app']['base_url'] ?? '');
        $connectSources = ["'self'"];
        if (str_starts_with($baseUrl, 'https://')) {
            $connectSources[] = $baseUrl;
        }

        header(
            "Content-Security-Policy: default-src 'self'; "
            . "base-uri 'self'; "
            . "form-action 'self'; "
            . "frame-ancestors 'none'; "
            . "object-src 'none'; "
            . "img-src 'self' data: blob:; "
            . "style-src 'self' 'unsafe-inline'; "
            . "script-src 'self'; "
            . "frame-src 'self'; "
            . 'connect-src ' . implode(' ', array_unique($connectSources))
        );
    }
}
