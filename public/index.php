<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/config/config.php';

session_start([
    'cookie_httponly' => true,
    'cookie_secure' => (bool) $config['security']['secure_cookies'],
    'cookie_samesite' => 'Lax',
]);

require dirname(__DIR__) . '/app/Support/helpers.php';

// Small front controller: every request enters here, then routes to a controller.
// This keeps public exposure limited to /public and avoids scattering request logic.
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $path = dirname(__DIR__) . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\DocumentController;
use App\Controllers\OperationsController;
use App\Controllers\PolicyController;
use App\Controllers\SettingsController;
use App\Controllers\UserController;
use App\Core\SecurityHeaders;
use App\Core\View;

SecurityHeaders::apply($config);

$page = $_GET['page'] ?? null;
$action = $_GET['action'] ?? null;

try {
    // State-changing actions are POST-only and each controller verifies CSRF.
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        match ($action) {
            'login' => (new AuthController())->authenticate(),
            'logout' => (new AuthController())->logout(),
            'policy_store' => (new PolicyController())->store(),
            'policy_update' => (new PolicyController())->update(),
            'policy_delete' => (new PolicyController())->destroy(),
            'policy_bulk' => (new PolicyController())->bulk(),
            'reminder_update' => (new OperationsController())->updateReminder(),
            'reminder_bulk' => (new OperationsController())->bulkReminders(),
            'client_store' => (new OperationsController())->storeClient(),
            'client_update' => (new OperationsController())->updateClient(),
            'client_delete' => (new OperationsController())->deleteClient(),
            'document_upload' => (new DocumentController())->upload(),
            'document_delete' => (new DocumentController())->destroy(),
            'user_store' => (new UserController())->store(),
            'user_update' => (new UserController())->update(),
            'user_toggle' => (new UserController())->toggle(),
            'user_delete' => (new UserController())->destroy(),
            'account_update' => (new UserController())->updateProfile(),
            'settings_user_store' => (new SettingsController())->userStore(),
            'settings_user_update' => (new SettingsController())->userUpdate(),
            'settings_role_store' => (new SettingsController())->roleStore(),
            'settings_role_update' => (new SettingsController())->roleUpdate(),
            'settings_role_delete' => (new SettingsController())->roleDelete(),
            'settings_reminder_rules' => (new SettingsController())->reminderRules(),
            'settings_document_type_store' => (new SettingsController())->documentTypeStore(),
            'settings_document_type_delete' => (new SettingsController())->documentTypeDelete(),
            default => View::render('errors/404'),
        };
        exit;
    }

    if ($action === 'document_download') {
        (new DocumentController())->download();
        exit;
    }

    if ($action === 'document_preview') {
        (new DocumentController())->preview();
        exit;
    }

    if ($action === 'policies_export') {
        (new PolicyController())->export();
        exit;
    }

    if ($action === 'reminders_export') {
        (new OperationsController())->exportReminders();
        exit;
    }

    if ($action === 'clients_export') {
        (new OperationsController())->exportClients();
        exit;
    }

    if ($action === 'documents_export') {
        (new DocumentController())->export();
        exit;
    }

    if ($page === 'users') {
        header('Location: ?page=settings&tab=users', true, 302);
        exit;
    }

    match ($page ?? 'dashboard') {
        'login' => (new AuthController())->login(),
        'dashboard' => (new DashboardController())->index(),
        'account' => (new UserController())->profile(),
        'my_tasks' => (new OperationsController())->myTasks(),
        'policies' => (new PolicyController())->index(),
        'policy_new' => (new PolicyController())->create(),
        'policy_edit' => (new PolicyController())->edit(),
        'documents' => (new DocumentController())->index(),
        'reminders' => (new OperationsController())->reminders(),
        'clients' => (new OperationsController())->clients(),
        'settings' => (new SettingsController())->index(),
        default => View::render('errors/404'),
    };
} catch (Throwable $exception) {
    if ($config['app']['env'] === 'local') {
        throw $exception;
    }
    http_response_code(500);
    View::render('errors/500');
}
