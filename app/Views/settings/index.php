<?php

use App\Core\Auth;
use App\Core\Csrf;

function settings_user_sort_link(string $label, string $column, array $filters): string
{
    $active = ($filters['sort'] ?? '') === $column;
    $direction = (($filters['sort'] ?? '') === $column && ($filters['direction'] ?? 'ASC') === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'page' => 'settings',
        'tab' => 'users',
        'user_search' => $filters['search'] ?? '',
        'user_status' => $filters['status'] ?? '',
        'user_role' => $filters['role'] ?? '',
        'user_sort' => $column,
        'user_direction' => $direction,
    ], fn ($value) => $value !== '');
    return '<a class="sort-link ' . ($active ? 'active' : '') . '" href="?' . http_build_query($params) . '">' . e($label) . sort_icon($active, $filters['direction'] ?? 'ASC') . '</a>';
}

function settings_audit_sort_link(string $label, string $column, array $filters): string
{
    $active = ($filters['sort'] ?? '') === $column;
    $direction = (($filters['sort'] ?? '') === $column && ($filters['direction'] ?? 'DESC') === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'page' => 'settings',
        'tab' => 'audit',
        'audit_search' => $filters['search'] ?? '',
        'audit_action' => $filters['action'] ?? '',
        'audit_sort' => $column,
        'audit_direction' => $direction,
    ], fn ($value) => $value !== '');
    return '<a class="sort-link ' . ($active ? 'active' : '') . '" href="?' . http_build_query($params) . '">' . e($label) . sort_icon($active, $filters['direction'] ?? 'DESC') . '</a>';
}

$tab = $tab ?? 'users';
$hasUserFilters = ($userFilters['search'] ?? '') !== ''
    || ($userFilters['status'] ?? '') !== ''
    || ($userFilters['role'] ?? '') !== '';
$hasActivityFilters = ($activityFilters['search'] ?? '') !== ''
    || ($activityFilters['action'] ?? '') !== '';
$tabs = [
    'users' => 'Users',
    'roles' => 'Roles & Permissions',
    'audit' => 'System Activity',
    'reminders' => 'Reminder Rules',
    'documents' => 'Document Checklist',
];
$users = $userResult['items'] ?? $users ?? [];
?>

<section class="page-heading">
    <div>
        <div class="settings-title-row">
            <p class="eyebrow">Administration settings</p>
            <nav class="settings-tabs inline-tabs" aria-label="Settings sections">
                <?php foreach ($tabs as $key => $label): ?>
                    <a class="<?= $tab === $key ? 'active' : '' ?>" href="?page=settings&tab=<?= e($key) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</section>

<section>
    <div class="panel settings-panel">
        <?php if ($tab === 'users'): ?>
            <div class="panel-header">
                <div><h2>System users</h2></div>
                <button class="button primary" type="button" data-dialog-open="create-user-dialog">+ Create User</button>
            </div>
            <form class="toolbar compact-toolbar" method="get" data-server-filter>
                <input type="hidden" name="page" value="settings">
                <input type="hidden" name="tab" value="users">
                <label class="search-field">
                    <span class="sr-only">Search users</span>
                    <input type="search" name="user_search" placeholder="Search name, email, role..." value="<?= e($userFilters['search'] ?? '') ?>">
                </label>
                <div class="filter-menu">
                    <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Filters</button>
                    <div class="filter-popover">
                        <label>Status
                            <select name="user_status">
                                <option value="">Any status</option>
                                <option value="active" <?= ($userFilters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($userFilters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </label>
                        <label>Role
                            <select name="user_role">
                                <option value="">Any role</option>
                                <?php foreach ($roleOptions as $role): ?>
                                    <option value="<?= e($role['slug']) ?>" <?= ($userFilters['role'] ?? '') === $role['slug'] ? 'selected' : '' ?>><?= e($role['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <button class="button primary small" type="submit">Apply filters</button>
                    </div>
                </div>
                <?php if ($hasUserFilters): ?><a class="button quiet" href="?page=settings&tab=users">Reset</a><?php endif; ?>
            </form>
            <div class="table-wrap">
                <table class="users-table">
                    <thead><tr><th><?= settings_user_sort_link('User', 'name', $userFilters ?? []) ?></th><th><?= settings_user_sort_link('Email', 'email', $userFilters ?? []) ?></th><th><?= settings_user_sort_link('Role', 'role', $userFilters ?? []) ?></th><th><?= settings_user_sort_link('Created', 'created_at', $userFilters ?? []) ?></th><th><?= settings_user_sort_link('Last Updated', 'updated_at', $userFilters ?? []) ?></th><th><?= settings_user_sort_link('Status', 'is_active', $userFilters ?? []) ?></th></tr></thead>
                    <tbody>
                    <?php foreach ($users as $staff): ?>
                        <tr class="clickable-row" tabindex="0" data-dialog-open="user-dialog-<?= e((string) $staff['id']) ?>">
                            <td><span class="identity-cell"><span class="avatar"><?= e(strtoupper(substr($staff['name'], 0, 1))) ?></span><strong><?= e($staff['name']) ?></strong></span></td>
                            <td><?= e($staff['email']) ?></td>
                            <td><span class="pill role-pill <?= e(role_tone($staff['role'])) ?>"><?= e($staff['role_name'] ?? role_label($staff['role'])) ?></span></td>
                            <td><?= e(substr($staff['created_at'], 0, 10)) ?></td>
                            <td><?= e(substr($staff['updated_at'] ?? $staff['created_at'], 0, 10)) ?></td>
                            <td><span class="badge <?= (int) $staff['is_active'] ? 'success' : 'danger' ?>"><?= (int) $staff['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$users): ?>
                        <tr><td colspan="6" class="empty-cell">No users match the current filters.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (isset($userResult)): ?>
                <div class="pagination">
                    <span>Showing <?= e((string) (count($users) ? (($userResult['page'] - 1) * $userResult['per_page'] + 1) : 0)) ?>-<?= e((string) min($userResult['total'], $userResult['page'] * $userResult['per_page'])) ?> of <?= e((string) $userResult['total']) ?></span>
                    <?php $userBase = ['page' => 'settings', 'tab' => 'users', 'user_search' => $userFilters['search'], 'user_status' => $userFilters['status'], 'user_role' => $userFilters['role'], 'user_sort' => $userFilters['sort'], 'user_direction' => $userFilters['direction']]; ?>
                    <div>
                        <?php if ($userResult['page'] > 1): ?><a class="button quiet" href="?<?= e(http_build_query($userBase + ['user_p' => $userResult['page'] - 1])) ?>">Previous</a><?php endif; ?>
                        <span>Page <?= e((string) $userResult['page']) ?> of <?= e((string) $userResult['pages']) ?></span>
                        <?php if ($userResult['page'] < $userResult['pages']): ?><a class="button quiet" href="?<?= e(http_build_query($userBase + ['user_p' => $userResult['page'] + 1])) ?>">Next</a><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php elseif ($tab === 'roles'): ?>
            <div class="panel-header">
                <div><h2>Roles & permissions</h2><p>Create custom roles and attach system permissions.</p></div>
            </div>
            <form class="toolbar" method="post" action="?action=settings_role_store" data-validate>
                <?= Csrf::field() ?>
                <input name="name" placeholder="Role name" required>
                <input name="description" placeholder="Role description">
                <button class="button primary" type="submit">Add role</button>
            </form>
            <div class="role-grid">
                <?php foreach ($roles as $role): ?>
                    <?php $assigned = $permissionMap[(int) $role['id']] ?? []; ?>
                    <details class="role-card">
                        <summary>
                            <span>
                                <strong><?= e($role['name']) ?></strong>
                                <small><?= e($role['description'] ?? 'No description') ?></small>
                            </span>
                            <span class="summary-meta">
                                <?= e((string) count($assigned)) ?> permissions
                                <?php if ((int) $role['is_system']): ?><span class="badge muted">System</span><?php endif; ?>
                            </span>
                        </summary>
                        <form method="post" action="?action=settings_role_update&id=<?= e((string) $role['id']) ?>">
                            <?= Csrf::field() ?>
                            <div class="role-card-header">
                                <div>
                                    <input name="name" value="<?= e($role['name']) ?>" required>
                                    <input name="description" value="<?= e($role['description'] ?? '') ?>" placeholder="Description">
                                </div>
                            </div>
                            <div class="permission-grid">
                                <?php foreach ($permissions as $permission): ?>
                                    <label class="permission-item">
                                        <input type="checkbox" name="permissions[]" value="<?= e($permission['slug']) ?>" <?= in_array($permission['slug'], $assigned, true) ? 'checked' : '' ?>>
                                        <span><?= e($permission['name']) ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                            <div class="form-actions">
                                <?php if (!(int) $role['is_system']): ?>
                                    <button class="button danger-button" formaction="?action=settings_role_delete&id=<?= e((string) $role['id']) ?>" data-confirm="Delete this role?" type="submit">Delete</button>
                                <?php endif; ?>
                                <button class="button primary" type="submit">Save role</button>
                            </div>
                        </form>
                    </details>
                <?php endforeach; ?>
            </div>
        <?php elseif ($tab === 'audit'): ?>
            <div class="panel-header">
                <div><h2>System activity</h2><p>Full audit record across users, policies, documents, reminders, and settings.</p></div>
            </div>
            <form class="toolbar compact-toolbar" method="get" data-server-filter>
                <input type="hidden" name="page" value="settings">
                <input type="hidden" name="tab" value="audit">
                <label class="search-field">
                    <span class="sr-only">Search activity</span>
                    <input type="search" name="audit_search" placeholder="Search user, action, policy, detail..." value="<?= e($activityFilters['search'] ?? '') ?>">
                </label>
                <div class="filter-menu">
                    <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Filters</button>
                    <div class="filter-popover">
                        <label>Action
                            <input name="audit_action" placeholder="e.g. policy_updated" value="<?= e($activityFilters['action'] ?? '') ?>">
                        </label>
                        <button class="button primary small" type="submit">Apply filters</button>
                    </div>
                </div>
                <?php if ($hasActivityFilters): ?><a class="button quiet" href="?page=settings&tab=audit">Reset</a><?php endif; ?>
            </form>
            <div class="table-wrap">
                <table>
                    <thead><tr><th><?= settings_audit_sort_link('Time', 'created_at', $activityFilters ?? []) ?></th><th><?= settings_audit_sort_link('User', 'user', $activityFilters ?? []) ?></th><th><?= settings_audit_sort_link('Policy', 'policy', $activityFilters ?? []) ?></th><th><?= settings_audit_sort_link('Action', 'action', $activityFilters ?? []) ?></th><th>Detail</th></tr></thead>
                    <tbody>
                    <?php foreach ($activity['items'] as $item): ?>
                        <tr>
                            <td><?= e($item['created_at']) ?></td>
                            <td><?= e($item['name'] ?? 'System') ?></td>
                            <td><?= e($item['policy_number'] ?? '-') ?></td>
                            <td><span class="pill"><?= e($item['action']) ?></span></td>
                            <td><?= e($item['description']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$activity['items']): ?>
                        <tr><td colspan="5" class="empty-cell">No audit entries match the current filters.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <span>Showing <?= e((string) (count($activity['items']) ? (($activity['page'] - 1) * $activity['per_page'] + 1) : 0)) ?>-<?= e((string) min($activity['total'], $activity['page'] * $activity['per_page'])) ?> of <?= e((string) $activity['total']) ?></span>
                <?php $auditBase = ['page' => 'settings', 'tab' => 'audit', 'audit_search' => $activityFilters['search'], 'audit_action' => $activityFilters['action'], 'audit_sort' => $activityFilters['sort'], 'audit_direction' => $activityFilters['direction']]; ?>
                <div>
                    <?php if ($activity['page'] > 1): ?><a class="button quiet" href="?<?= e(http_build_query($auditBase + ['audit_p' => $activity['page'] - 1])) ?>">Previous</a><?php endif; ?>
                    <span>Page <?= e((string) $activity['page']) ?> of <?= e((string) $activity['pages']) ?></span>
                    <?php if ($activity['page'] < $activity['pages']): ?><a class="button quiet" href="?<?= e(http_build_query($auditBase + ['audit_p' => $activity['page'] + 1])) ?>">Next</a><?php endif; ?>
                </div>
            </div>
        <?php elseif ($tab === 'reminders'): ?>
            <div class="panel-header">
                <div><h2>Reminder rules</h2><p>Default operational rules used by renewal workflows.</p></div>
            </div>
            <form method="post" action="?action=settings_reminder_rules" data-validate>
                <?= Csrf::field() ?>
                <div class="form-grid">
                    <label><span>Default Lead Time</span><input type="number" name="default_reminder_days" min="1" value="<?= e($settings['default_reminder_days'] ?? '30') ?>"></label>
                    <label><span>Renewal Window</span><input type="number" name="renewal_window_days" min="1" value="<?= e($settings['renewal_window_days'] ?? '30') ?>"></label>
                    <label><span>Default Snooze Days</span><input type="number" name="default_snooze_days" min="1" value="<?= e($settings['default_snooze_days'] ?? '7') ?>"></label>
                    <label><span>Escalation After Days</span><input type="number" name="escalation_days" min="1" value="<?= e($settings['escalation_days'] ?? '5') ?>"></label>
                </div>
                <div class="form-actions"><button class="button primary" type="submit">Save rules</button></div>
            </form>
        <?php elseif ($tab === 'documents'): ?>
            <div class="panel-header">
                <div><h2>Document checklist</h2><p>Configure document types expected during renewal review.</p></div>
            </div>
            <form class="toolbar" method="post" action="?action=settings_document_type_store" data-validate>
                <?= Csrf::field() ?>
                <input name="name" placeholder="Document type" required>
                <input type="number" name="sort_order" placeholder="Order" value="100">
                <label class="inline-check"><input type="checkbox" name="is_required" checked> Required</label>
                <button class="button primary" type="submit">Add type</button>
            </form>
            <div class="document-list">
                <?php foreach ($documentTypes as $type): ?>
                    <div class="document-row">
                        <span class="file-icon"><?= (int) $type['is_required'] ? 'REQ' : 'OPT' ?></span>
                        <div class="document-copy"><strong><?= e($type['name']) ?></strong><small><?= (int) $type['is_required'] ? 'Required' : 'Optional' ?></small></div>
                        <?php if (!empty($type['id'])): ?>
                            <form method="post" action="?action=settings_document_type_delete&id=<?= e((string) $type['id']) ?>" data-confirm="Delete this document type?">
                                <?= Csrf::field() ?>
                                <button class="icon-button danger-text" type="submit">×</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<dialog class="modal" id="create-user-dialog">
    <form class="modal-card" method="post" action="?action=settings_user_store" data-validate>
        <?= Csrf::field() ?>
        <div class="modal-header"><div><p class="eyebrow">New account</p><h2>Create user</h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button></div>
        <div class="form-grid">
            <label><span>Name</span><input name="name" value="<?= e($editing['name'] ?? '') ?>" required></label>
            <label><span>Email</span><input type="email" name="email" value="<?= e($editing['email'] ?? '') ?>" required></label>
            <label><span>Role</span><select name="role" required><?php foreach ($roleOptions as $role): ?><option value="<?= e($role['slug']) ?>"><?= e($role['name']) ?></option><?php endforeach; ?></select></label>
            <label><span>Password</span><input type="password" name="password" required autocomplete="new-password"></label>
        </div>
        <div class="form-actions"><button class="button quiet" type="button" data-dialog-close>Cancel</button><button class="button primary" type="submit">Create user</button></div>
    </form>
</dialog>

<?php foreach ($users as $staff): ?>
    <?php $isSelf = (int) $staff['id'] === (int) Auth::user()['id']; ?>
    <dialog class="modal" id="user-dialog-<?= e((string) $staff['id']) ?>">
        <div class="modal-card">
            <div class="modal-header"><div><p class="eyebrow">Account detail</p><h2><?= e($staff['name']) ?></h2></div><button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button></div>
            <form method="post" action="?action=settings_user_update&id=<?= e((string) $staff['id']) ?>" data-validate>
                <?= Csrf::field() ?>
                <div class="form-grid">
                    <label><span>Name</span><input name="name" value="<?= e($staff['name']) ?>" required></label>
                    <label><span>Email</span><input type="email" name="email" value="<?= e($staff['email']) ?>" required></label>
                    <label><span>Role</span><select name="role" required><?php foreach ($roleOptions as $role): ?><option value="<?= e($role['slug']) ?>" <?= $staff['role'] === $role['slug'] ? 'selected' : '' ?>><?= e($role['name']) ?></option><?php endforeach; ?></select></label>
                    <label><span>New Password</span><input type="password" name="password" autocomplete="new-password" placeholder="Leave blank to keep current"></label>
                </div>
                <div class="form-actions"><button class="button primary" type="submit">Save changes</button></div>
            </form>
            <div class="modal-actions">
                <form method="post" action="?action=user_toggle&id=<?= e((string) $staff['id']) ?>">
                    <?= Csrf::field() ?><input type="hidden" name="return_to" value="?page=settings&tab=users"><input type="hidden" name="active" value="<?= (int) $staff['is_active'] ? '0' : '1' ?>">
                    <button class="button ghost" type="submit" <?= $isSelf ? 'disabled' : '' ?>><?= (int) $staff['is_active'] ? 'Deactivate account' : 'Activate account' ?></button>
                </form>
                <form method="post" action="?action=user_delete&id=<?= e((string) $staff['id']) ?>" data-confirm="Delete this user account? This cannot be undone.">
                    <?= Csrf::field() ?><input type="hidden" name="return_to" value="?page=settings&tab=users">
                    <button class="button danger-button" type="submit" <?= $isSelf ? 'disabled' : '' ?>>Delete user</button>
                </form>
            </div>
        </div>
    </dialog>
<?php endforeach; ?>
