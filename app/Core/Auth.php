<?php

namespace App\Core;

use App\Models\User;
use App\Models\Role;

class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }

        return User::find((int) $_SESSION['user_id']);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function attempt(string $email, string $password): bool
    {
        $user = User::findByEmail($email);

        if (!$user || !(int) $user['is_active']) {
            return false;
        }

        // Passwords are stored as one-way hashes; plain text is never persisted.
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }

        // Regenerate the session after login to prevent session fixation.
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function hasRole(array|string $roles): bool
    {
        $user = self::user();
        $allowed = (array) $roles;
        return $user && in_array($user['role'], $allowed, true);
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        return $user && Role::userCan((int) $user['id'], $permission);
    }
}
