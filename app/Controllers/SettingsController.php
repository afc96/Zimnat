<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ActivityLog;
use App\Models\AppSetting;
use App\Models\Document;
use App\Models\Role;
use App\Models\User;
use App\Services\UserService;
use App\Support\Validator;
use PDOException;

class SettingsController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('settings.manage');
        $userFilters = [
            'search' => trim((string) ($_GET['user_search'] ?? '')),
            'status' => trim((string) ($_GET['user_status'] ?? '')),
            'role' => trim((string) ($_GET['user_role'] ?? '')),
            'sort' => trim((string) ($_GET['user_sort'] ?? 'name')),
            'direction' => trim((string) ($_GET['user_direction'] ?? 'ASC')),
            'page' => (int) ($_GET['user_p'] ?? 1),
        ];
        $activityFilters = [
            'search' => trim((string) ($_GET['audit_search'] ?? '')),
            'action' => trim((string) ($_GET['audit_action'] ?? '')),
            'sort' => trim((string) ($_GET['audit_sort'] ?? 'created_at')),
            'direction' => trim((string) ($_GET['audit_direction'] ?? 'DESC')),
        ];
        $userResult = User::paginate($userFilters);
        $activity = ActivityLog::paginate((int) ($_GET['audit_p'] ?? 1), 20, $activityFilters);

        $this->view('settings/index', [
            'tab' => $_GET['tab'] ?? 'users',
            'users' => $userResult['items'],
            'userResult' => $userResult,
            'userFilters' => $userFilters,
            'roles' => Role::all(),
            'roleOptions' => Role::options(),
            'permissions' => Role::permissions(),
            'permissionMap' => Role::permissionMap(),
            'activity' => $activity,
            'activityFilters' => $activityFilters,
            'settings' => AppSetting::all(),
            'documentTypes' => Document::types(),
            'errors' => [],
            'editing' => null,
        ]);
    }

    public function userStore(): void
    {
        $this->requirePermission('settings.manage');
        Csrf::verify();
        $errors = Validator::user($_POST);
        if ($errors) {
            $this->settingsWithErrors($errors, $_POST);
            return;
        }

        try {
            (new UserService())->create($_POST, (int) Auth::user()['id']);
            $this->flash('success', 'User created.');
        } catch (PDOException) {
            $this->flash('danger', 'A user with this email already exists.');
        }
        $this->redirect('?page=settings&tab=users');
    }

    public function userUpdate(): void
    {
        $this->requirePermission('settings.manage');
        Csrf::verify();
        $id = (int) ($_GET['id'] ?? 0);
        $errors = Validator::user($_POST, false);
        if (!empty($_POST['password']) && strlen((string) $_POST['password']) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }
        if ($errors) {
            $this->settingsWithErrors($errors, $_POST + ['id' => $id]);
            return;
        }

        try {
            (new UserService())->update($id, $_POST, (int) Auth::user()['id']);
            $this->flash('success', 'User updated.');
        } catch (PDOException) {
            $this->settingsWithErrors(['email' => 'A user with this email already exists.'], $_POST + ['id' => $id]);
            return;
        }
        $this->redirect('?page=settings&tab=users');
    }

    public function roleStore(): void
    {
        $this->requirePermission('settings.manage');
        Csrf::verify();
        $errors = Validator::role($_POST);
        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('?page=settings&tab=roles');
        }

        try {
            Role::create($_POST);
            ActivityLog::record((int) Auth::user()['id'], 'role_created', 'Created role ' . $_POST['name']);
            $this->flash('success', 'Role created.');
        } catch (PDOException) {
            $this->flash('danger', 'A role with this name already exists.');
        }
        $this->redirect('?page=settings&tab=roles');
    }

    public function roleUpdate(): void
    {
        $this->requirePermission('settings.manage');
        Csrf::verify();
        $errors = Validator::role($_POST);
        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('?page=settings&tab=roles');
        }

        try {
            Role::update((int) ($_GET['id'] ?? 0), $_POST);
            Role::syncPermissions((int) ($_GET['id'] ?? 0), $_POST['permissions'] ?? []);
            ActivityLog::record((int) Auth::user()['id'], 'role_updated', 'Updated role permissions');
            $this->flash('success', 'Role updated.');
        } catch (PDOException) {
            $this->flash('danger', 'Role could not be updated. Check the role name and permissions.');
        }
        $this->redirect('?page=settings&tab=roles');
    }

    public function roleDelete(): void
    {
        $this->requirePermission('settings.manage');
        Csrf::verify();
        $deleted = Role::delete((int) ($_GET['id'] ?? 0));
        $this->flash($deleted ? 'success' : 'danger', $deleted ? 'Role deleted.' : 'System roles and roles assigned to users cannot be deleted.');
        $this->redirect('?page=settings&tab=roles');
    }

    public function reminderRules(): void
    {
        $this->requirePermission('settings.manage');
        Csrf::verify();
        AppSetting::updateMany([
            'default_reminder_days' => max(1, (int) ($_POST['default_reminder_days'] ?? 30)),
            'renewal_window_days' => max(1, (int) ($_POST['renewal_window_days'] ?? 30)),
            'default_snooze_days' => max(1, (int) ($_POST['default_snooze_days'] ?? 7)),
            'escalation_days' => max(1, (int) ($_POST['escalation_days'] ?? 5)),
        ]);
        ActivityLog::record((int) Auth::user()['id'], 'settings_updated', 'Updated reminder rules');
        $this->flash('success', 'Reminder rules updated.');
        $this->redirect('?page=settings&tab=reminders');
    }

    public function documentTypeStore(): void
    {
        $this->requirePermission('settings.manage');
        Csrf::verify();
        $errors = Validator::documentType($_POST);
        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('?page=settings&tab=documents');
        }

        try {
            Document::createType($_POST);
            ActivityLog::record((int) Auth::user()['id'], 'document_type_created', 'Created document type ' . $_POST['name']);
            $this->flash('success', 'Document type added.');
        } catch (PDOException) {
            $this->flash('danger', 'That document type already exists.');
        }
        $this->redirect('?page=settings&tab=documents');
    }

    public function documentTypeDelete(): void
    {
        $this->requirePermission('settings.manage');
        Csrf::verify();
        Document::deleteType((int) ($_GET['id'] ?? 0));
        $this->flash('success', 'Document type deleted.');
        $this->redirect('?page=settings&tab=documents');
    }

    private function settingsWithErrors(array $errors, array $editing): void
    {
        $userFilters = [
            'search' => trim((string) ($_GET['user_search'] ?? '')),
            'status' => trim((string) ($_GET['user_status'] ?? '')),
            'role' => trim((string) ($_GET['user_role'] ?? '')),
            'sort' => trim((string) ($_GET['user_sort'] ?? 'name')),
            'direction' => trim((string) ($_GET['user_direction'] ?? 'ASC')),
            'page' => (int) ($_GET['user_p'] ?? 1),
        ];
        $activityFilters = [
            'search' => trim((string) ($_GET['audit_search'] ?? '')),
            'action' => trim((string) ($_GET['audit_action'] ?? '')),
            'sort' => trim((string) ($_GET['audit_sort'] ?? 'created_at')),
            'direction' => trim((string) ($_GET['audit_direction'] ?? 'DESC')),
        ];
        $userResult = User::paginate($userFilters);
        $activity = ActivityLog::paginate(1, 20, $activityFilters);

        $this->view('settings/index', [
            'tab' => 'users',
            'users' => $userResult['items'],
            'userResult' => $userResult,
            'userFilters' => $userFilters,
            'roles' => Role::all(),
            'roleOptions' => Role::options(),
            'permissions' => Role::permissions(),
            'permissionMap' => Role::permissionMap(),
            'activity' => $activity,
            'activityFilters' => $activityFilters,
            'settings' => AppSetting::all(),
            'documentTypes' => Document::types(),
            'errors' => $errors,
            'editing' => $editing,
        ]);
    }
}
