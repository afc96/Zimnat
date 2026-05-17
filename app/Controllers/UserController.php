<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ActivityLog;
use App\Models\User;
use App\Services\UserService;
use App\Support\Validator;
use PDOException;

class UserController extends Controller
{
    public function profile(): void
    {
        $this->requireAuth();
        $this->view('account/index', [
            'account' => Auth::user(),
            'errors' => [],
        ]);
    }

    public function updateProfile(): void
    {
        $this->requireAuth();
        Csrf::verify();
        $user = Auth::user();
        $errors = Validator::profile($_POST);

        if (!empty($_POST['password']) && !password_verify((string) ($_POST['current_password'] ?? ''), (string) $user['password_hash'])) {
            $errors['current_password'] = 'Current password is incorrect.';
        }

        if ($errors) {
            $this->view('account/index', [
                'account' => $user + ['name' => $_POST['name'] ?? $user['name'], 'email' => $_POST['email'] ?? $user['email']],
                'errors' => $errors,
            ]);
            return;
        }

        try {
            User::updateProfile((int) $user['id'], $_POST);
            ActivityLog::record((int) $user['id'], 'account_updated', 'Updated own account settings');
            $this->flash('success', 'Account settings updated.');
            $this->redirect('?page=account');
        } catch (PDOException) {
            $errors['email'] = 'A user with this email already exists.';
            $this->view('account/index', [
                'account' => $user + ['name' => $_POST['name'] ?? $user['name'], 'email' => $_POST['email'] ?? $user['email']],
                'errors' => $errors,
            ]);
        }
    }

    public function index(): void
    {
        $this->requireRole('admin');
        $this->redirect('?page=settings&tab=users');
    }

    public function store(): void
    {
        $this->requireRole('admin');
        Csrf::verify();
        $errors = Validator::user($_POST);
        if ($errors) {
            $this->flash('danger', reset($errors) ?: 'Please correct the highlighted user details.');
            $this->redirect('?page=settings&tab=users');
        }

        try {
            (new UserService())->create($_POST, (int) Auth::user()['id']);
            $this->flash('success', 'User created.');
            $this->redirect('?page=settings&tab=users');
        } catch (PDOException $exception) {
            $this->flash('danger', 'A user with this email already exists.');
            $this->redirect('?page=settings&tab=users');
        }
    }

    public function update(): void
    {
        $this->requireRole('admin');
        Csrf::verify();
        $id = (int) ($_GET['id'] ?? 0);
        $errors = Validator::user($_POST, false);
        if (!empty($_POST['password']) && strlen((string) $_POST['password']) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }
        if ($errors) {
            $this->flash('danger', reset($errors) ?: 'Please correct the highlighted user details.');
            $this->redirect('?page=settings&tab=users');
        }

        try {
            (new UserService())->update($id, $_POST, (int) Auth::user()['id']);
            $this->flash('success', 'User updated.');
            $this->redirect('?page=settings&tab=users');
        } catch (PDOException $exception) {
            $this->flash('danger', 'A user with this email already exists.');
            $this->redirect('?page=settings&tab=users');
        }
    }

    public function toggle(): void
    {
        $this->requireRole('admin');
        Csrf::verify();
        $id = (int) ($_GET['id'] ?? 0);
        if ($id !== (int) Auth::user()['id']) {
            (new UserService())->setActive($id, ($_POST['active'] ?? '0') === '1', (int) Auth::user()['id']);
        }
        $this->redirect(str_starts_with((string) ($_POST['return_to'] ?? ''), '?page=settings') ? (string) $_POST['return_to'] : '?page=settings&tab=users');
    }

    public function destroy(): void
    {
        $this->requireRole('admin');
        Csrf::verify();
        $id = (int) ($_GET['id'] ?? 0);
        if ($id !== (int) Auth::user()['id']) {
            (new UserService())->delete($id, (int) Auth::user()['id']);
            $this->flash('success', 'User deleted.');
        }
        $this->redirect(str_starts_with((string) ($_POST['return_to'] ?? ''), '?page=settings') ? (string) $_POST['return_to'] : '?page=settings&tab=users');
    }
}
