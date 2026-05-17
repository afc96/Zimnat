<?php

$envPath = dirname(__DIR__) . '/.env';
if (is_file($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

return [
    'app' => [
        'name' => 'PolicyPilot',
        'env' => getenv('APP_ENV') ?: 'local',
        'base_url' => getenv('APP_URL') ?: 'http://localhost:8000',
    ],
    'security' => [
        'secure_cookies' => filter_var(getenv('APP_SECURE_COOKIES') ?: 'false', FILTER_VALIDATE_BOOLEAN),
        'login_max_attempts' => (int) (getenv('LOGIN_MAX_ATTEMPTS') ?: 5),
        'login_decay_seconds' => (int) (getenv('LOGIN_DECAY_SECONDS') ?: 900),
        'rate_limit_path' => dirname(__DIR__) . '/storage/rate_limits',
    ],
    'database' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => getenv('DB_PORT') ?: '3306',
        'name' => getenv('DB_NAME') ?: 'zimnat_policy_renewal',
        'user' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') ?: '',
    ],
    'uploads' => [
        'path' => dirname(__DIR__) . '/storage/uploads',
        'max_bytes' => 5 * 1024 * 1024,
        'allowed_mime' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
        ],
    ],
    'notifications' => [
        'log_path' => dirname(__DIR__) . '/storage/logs/notifications.log',
        'email_from' => getenv('NOTIFICATION_EMAIL_FROM') ?: 'noreply@zimnat.test',
        'email_transport' => getenv('NOTIFICATION_EMAIL_TRANSPORT') ?: 'log',
        'sms_webhook_url' => getenv('SMS_WEBHOOK_URL') ?: '',
    ],
];
