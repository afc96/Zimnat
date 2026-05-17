<?php

use App\Core\Auth;
use App\Core\Csrf;

$currentPage = $_GET['page'] ?? 'dashboard';
$flashType = $flash['type'] ?? 'success';
$flashIcon = match ($flashType) {
    'danger' => '!',
    'warning' => '!',
    'info' => 'i',
    default => '✓',
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($config['app']['name']) ?></title>
    <link rel="stylesheet" href="assets/css/app.css">
    <script src="assets/js/app.js" defer></script>
</head>
<body>
<?php if ($user): ?>
    <header class="topbar">
        <a class="brand" href="?page=dashboard" aria-label="PolicyPilot dashboard">
            <span class="brand-mark">P</span>
            <span>Policy<span>Pilot</span></span>
        </a>
        <nav class="nav" aria-label="Primary navigation">
            <?php if (Auth::can('dashboard.view')): ?>
                <a class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>" href="?page=dashboard">Dashboard</a>
            <?php endif; ?>
            <?php if (Auth::can('reminder.manage')): ?>
                <a class="<?= in_array($currentPage, ['my_tasks', 'reminders'], true) ? 'active' : '' ?>" href="?page=my_tasks">Renewals</a>
            <?php endif; ?>
            <?php if (Auth::can('policy.view')): ?>
                <a class="<?= str_starts_with($currentPage, 'polic') ? 'active' : '' ?>" href="?page=policies">Policies</a>
            <?php endif; ?>
            <?php if (Auth::can('document.view')): ?>
                <a class="<?= $currentPage === 'documents' ? 'active' : '' ?>" href="?page=documents">Documents</a>
            <?php endif; ?>
            <?php if (Auth::can('client.view')): ?>
                <a class="<?= $currentPage === 'clients' ? 'active' : '' ?>" href="?page=clients">Clients</a>
            <?php endif; ?>
        </nav>
        <div class="profile-menu">
            <button class="user-chip profile-trigger" type="button" data-profile-menu aria-haspopup="true" aria-expanded="false">
                <span class="avatar"><?= e(strtoupper(substr($user['name'], 0, 1))) ?></span>
                <span>
                    <strong><?= e($user['name']) ?></strong>
                    <small><?= e(role_label($user['role'])) ?></small>
                </span>
            </button>
            <div class="profile-dropdown" data-profile-dropdown>
                <a class="menu-item" href="?page=account">
                    <span aria-hidden="true">◎</span>
                    Account settings
                </a>
                <button class="menu-item" type="button" data-theme-toggle>
                    <span aria-hidden="true">◐</span>
                    Toggle night mode
                </button>
                <?php if (Auth::can('settings.manage')): ?>
                    <a class="menu-item" href="?page=settings">
                        <span aria-hidden="true">⚙</span>
                        Admin settings
                    </a>
                <?php endif; ?>
                <form method="post" action="?action=logout">
                    <?= Csrf::field() ?>
                    <button class="menu-item danger-text" type="submit">
                        <span aria-hidden="true">-&gt;</span>
                        Sign out
                    </button>
                </form>
            </div>
        </div>
    </header>
<?php endif; ?>

<main class="<?= $user ? 'shell' : 'auth-shell' ?>">
    <?php if ($flash): ?>
        <div class="flash <?= e($flash['type']) ?>" role="status">
            <span class="flash-icon" aria-hidden="true"><?= e($flashIcon) ?></span>
            <span class="flash-message"><?= e($flash['message']) ?></span>
            <button type="button" data-dismiss aria-label="Dismiss message">×</button>
            <span class="flash-progress" aria-hidden="true"></span>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>
</body>
</html>
