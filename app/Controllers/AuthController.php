<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\RateLimiter;

class AuthController extends Controller
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirect('?page=dashboard');
        }
        $this->view('auth/login', []);
    }

    public function authenticate(): void
    {
        Csrf::verify();
        $email = trim(strtolower((string) ($_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $key = 'login|' . ($_SERVER['REMOTE_ADDR'] ?? 'local') . '|' . $email;
        $maxAttempts = max(1, (int) $config['security']['login_max_attempts']);
        $decaySeconds = max(60, (int) $config['security']['login_decay_seconds']);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts, $decaySeconds)) {
            $minutes = max(1, (int) ceil(RateLimiter::availableIn($key, $decaySeconds) / 60));
            $this->flash('danger', "Too many login attempts. Try again in {$minutes} minutes.");
            $this->redirect('?page=login');
        }

        if (Auth::attempt($email, $password)) {
            RateLimiter::clear($key);
            $this->redirect('?page=dashboard');
        }

        RateLimiter::hit($key, $decaySeconds);
        $this->flash('danger', 'Login failed. Check your email, password, and active status.');
        $this->redirect('?page=login');
    }

    public function logout(): void
    {
        Csrf::verify();
        Auth::logout();
        session_start();
        $this->flash('success', 'You have been signed out.');
        $this->redirect('?page=login');
    }
}
