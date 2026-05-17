<?php

use App\Core\Auth;
use App\Core\Csrf;
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Administration</p>
        <h1 class="sr-only">Users</h1>
    </div>
    <button class="button primary" type="button" data-dialog-open="create-user-dialog">+ Create User</button>
</section>

<section class="panel users-panel">
    <div class="panel-header">
        <div>
            <h2>System users</h2>
        </div>
        <span class="panel-meta"><?= e((string) count($users)) ?> accounts</span>
    </div>

    <div class="table-wrap">
        <table class="users-table">
            <thead>
            <tr>
                <th>User</th>
                <th>Email</th>
                <th>Role</th>
                <th>Created</th>
                <th>Last Updated</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $staff): ?>
                <tr class="clickable-row" tabindex="0" data-dialog-open="user-dialog-<?= e((string) $staff['id']) ?>">
                    <td>
                        <span class="identity-cell">
                            <span class="avatar"><?= e(strtoupper(substr($staff['name'], 0, 1))) ?></span>
                            <strong><?= e($staff['name']) ?></strong>
                        </span>
                    </td>
                    <td><?= e($staff['email']) ?></td>
                    <td><span class="pill role-pill <?= e(role_tone($staff['role'])) ?>"><?= e(role_label($staff['role'])) ?></span></td>
                    <td><?= e(substr($staff['created_at'], 0, 10)) ?></td>
                    <td><?= e(substr($staff['updated_at'] ?? $staff['created_at'], 0, 10)) ?></td>
                    <td><span class="badge <?= (int) $staff['is_active'] ? 'success' : 'danger' ?>"><?= (int) $staff['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$users): ?>
                <tr><td colspan="6" class="empty-cell">No system users are available yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<dialog class="modal" id="create-user-dialog">
    <form class="modal-card" method="post" action="?action=user_store" data-validate>
        <?= Csrf::field() ?>
        <div class="modal-header">
            <div>
                <p class="eyebrow">New account</p>
                <h2>Create user</h2>
            </div>
            <button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button>
        </div>
        <div class="form-grid">
            <label>
                <span>Name</span>
                <input name="name" value="<?= e($editing['name'] ?? '') ?>" required>
                <?php if (isset($errors['name']) && empty($editing['id'])): ?><small class="error"><?= e($errors['name']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= e($editing['email'] ?? '') ?>" required>
                <?php if (isset($errors['email']) && empty($editing['id'])): ?><small class="error"><?= e($errors['email']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Role</span>
                <select name="role" required>
                    <?php foreach (['viewer' => 'Viewer', 'policy_officer' => 'Policy Officer', 'admin' => 'Admin'] as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($editing['role'] ?? 'viewer') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required autocomplete="new-password">
                <?php if (isset($errors['password']) && empty($editing['id'])): ?><small class="error"><?= e($errors['password']) ?></small><?php endif; ?>
            </label>
        </div>
        <div class="form-actions">
            <button class="button quiet" type="button" data-dialog-close>Cancel</button>
            <button class="button primary" type="submit">Create user</button>
        </div>
    </form>
</dialog>

<?php foreach ($users as $staff): ?>
    <?php $isSelf = (int) $staff['id'] === (int) Auth::user()['id']; ?>
    <dialog class="modal" id="user-dialog-<?= e((string) $staff['id']) ?>">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="eyebrow">Account detail</p>
                    <h2><?= e($staff['name']) ?></h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button>
            </div>

            <form method="post" action="?action=user_update&id=<?= e((string) $staff['id']) ?>" data-validate>
                <?= Csrf::field() ?>
                <div class="form-grid">
                    <label>
                        <span>Name</span>
                        <input name="name" value="<?= e($staff['name']) ?>" required>
                    </label>
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="<?= e($staff['email']) ?>" required>
                    </label>
                    <label>
                        <span>Role</span>
                        <select name="role" required>
                            <?php foreach (['viewer' => 'Viewer', 'policy_officer' => 'Policy Officer', 'admin' => 'Admin'] as $value => $label): ?>
                                <option value="<?= e($value) ?>" <?= $staff['role'] === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>
                        <span>New Password</span>
                        <input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current">
                    </label>
                </div>
                <div class="form-actions">
                    <button class="button primary" type="submit">Save changes</button>
                </div>
            </form>

            <div class="modal-actions">
                <form method="post" action="?action=user_toggle&id=<?= e((string) $staff['id']) ?>">
                    <?= Csrf::field() ?>
                    <input type="hidden" name="active" value="<?= (int) $staff['is_active'] ? '0' : '1' ?>">
                    <button class="button ghost" type="submit" <?= $isSelf ? 'disabled' : '' ?>>
                        <?= (int) $staff['is_active'] ? 'Deactivate account' : 'Activate account' ?>
                    </button>
                </form>
                <form method="post" action="?action=user_delete&id=<?= e((string) $staff['id']) ?>" data-confirm="Delete this user account? This cannot be undone.">
                    <?= Csrf::field() ?>
                    <button class="button danger-button" type="submit" <?= $isSelf ? 'disabled' : '' ?>>Delete user</button>
                </form>
            </div>
            <?php if ($isSelf): ?>
                <p class="hint">You cannot deactivate or delete your own active session.</p>
            <?php endif; ?>
        </div>
    </dialog>
<?php endforeach; ?>

<?php if ($errors): ?>
    <script>
        window.__openDialogOnLoad = <?= json_encode(!empty($editing['id']) ? 'user-dialog-' . $editing['id'] : 'create-user-dialog') ?>;
    </script>
<?php endif; ?>
