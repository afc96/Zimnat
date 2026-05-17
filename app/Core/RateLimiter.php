<?php

namespace App\Core;

class RateLimiter
{
    public static function tooManyAttempts(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        return count(self::attempts($key, $decaySeconds)) >= $maxAttempts;
    }

    public static function hit(string $key, int $decaySeconds): void
    {
        $attempts = self::attempts($key, $decaySeconds);
        $attempts[] = time();
        self::write($key, $attempts);
    }

    public static function clear(string $key): void
    {
        $path = self::path($key);
        if (is_file($path)) {
            unlink($path);
        }
    }

    public static function availableIn(string $key, int $decaySeconds): int
    {
        $attempts = self::attempts($key, $decaySeconds);
        if (!$attempts) {
            return 0;
        }

        return max(0, ($attempts[0] + $decaySeconds) - time());
    }

    private static function attempts(string $key, int $decaySeconds): array
    {
        $path = self::path($key);
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [];
        }

        $cutoff = time() - $decaySeconds;
        $attempts = array_values(array_filter($decoded, static fn ($timestamp): bool => is_int($timestamp) && $timestamp >= $cutoff));
        self::write($key, $attempts);

        return $attempts;
    }

    private static function write(string $key, array $attempts): void
    {
        $directory = self::directory();
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents(self::path($key), json_encode($attempts, JSON_THROW_ON_ERROR), LOCK_EX);
    }

    private static function path(string $key): string
    {
        return self::directory() . '/' . hash('sha256', $key) . '.json';
    }

    private static function directory(): string
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        return (string) $config['security']['rate_limit_path'];
    }
}
