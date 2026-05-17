<?php

use App\Core\Csrf;

$account = $account ?? [];
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Account settings</p>
        <h1 class="sr-only">Profile</h1>
    </div>
</section>

<section class="detail-grid account-grid">
    <article class="panel account-summary">
        <span class="avatar large"><?= e(strtoupper(substr($account['name'] ?? 'U', 0, 1))) ?></span>
        <div>
            <h2><?= e($account['name'] ?? 'User') ?></h2>
            <p><?= e($account['email'] ?? '') ?></p>
        </div>
        <span class="pill role-pill <?= e(role_tone($account['role'] ?? 'viewer')) ?>"><?= e(role_label($account['role'] ?? 'viewer')) ?></span>
        <div class="detail-list compact-detail">
            <div><span>Status</span><strong><?= !empty($account['is_active']) ? 'Active' : 'Inactive' ?></strong></div>
            <div><span>Account Created</span><strong><?= e(substr((string) ($account['created_at'] ?? ''), 0, 10)) ?></strong></div>
            <div class="span-2"><span>Last Updated</span><strong><?= e(substr((string) ($account['updated_at'] ?? ''), 0, 10)) ?></strong></div>
        </div>
    </article>

    <article class="panel">
        <div class="panel-header">
            <div>
                <h2>Profile details</h2>
                <p>Update your name, email, and password.</p>
            </div>
        </div>
        <form method="post" action="?action=account_update" data-validate>
            <?= Csrf::field() ?>
            <div class="form-grid">
                <label>
                    <span>Name</span>
                    <input name="name" value="<?= e($account['name'] ?? '') ?>" required>
                    <?php if (isset($errors['name'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Email</span>
                    <input type="email" name="email" value="<?= e($account['email'] ?? '') ?>" required>
                    <?php if (isset($errors['email'])): ?><small class="error"><?= e($errors['email']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Current Password</span>
                    <input type="password" name="current_password" autocomplete="current-password" placeholder="Required to change password">
                    <?php if (isset($errors['current_password'])): ?><small class="error"><?= e($errors['current_password']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>New Password</span>
                    <input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current">
                    <?php if (isset($errors['password'])): ?><small class="error"><?= e($errors['password']) ?></small><?php endif; ?>
                </label>
                <label>
                    <span>Confirm New Password</span>
                    <input type="password" name="password_confirmation" autocomplete="new-password" placeholder="Repeat new password">
                    <?php if (isset($errors['password_confirmation'])): ?><small class="error"><?= e($errors['password_confirmation']) ?></small><?php endif; ?>
                </label>
            </div>
            <div class="form-actions">
                <button class="button primary" type="submit">Save account</button>
            </div>
        </form>
    </article>
</section>
