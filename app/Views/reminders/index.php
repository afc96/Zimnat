<?php

use App\Core\Auth;
use App\Core\Csrf;

function reminder_sort_link(string $label, string $column, array $filters): string
{
    $active = ($filters['sort'] ?? '') === $column;
    $direction = (($filters['sort'] ?? '') === $column && ($filters['direction'] ?? 'ASC') === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'page' => 'reminders',
        'search' => $filters['search'] ?? '',
        'status' => $filters['status'] ?? '',
        'reminder_status' => $filters['reminder_status'] ?? '',
        'renewal' => $filters['renewal'] ?? '',
        'client_id' => $filters['client_id'] ?? '',
        'policy_id' => $filters['policy_id'] ?? '',
        'sort' => $column,
        'direction' => $direction,
    ], fn ($value) => $value !== '');
    return '<a class="sort-link ' . ($active ? 'active' : '') . '" href="?' . http_build_query($params) . '">' . e($label) . sort_icon($active, $filters['direction'] ?? 'ASC') . '</a>';
}

$canEdit = Auth::can('reminder.manage');
$hasActiveFilters = ($filters['search'] ?? '') !== ''
    || ($filters['status'] ?? '') !== ''
    || ($filters['reminder_status'] ?? '') !== ''
    || ($filters['renewal'] ?? 'soon') !== 'soon'
    || !empty($filters['client_id'])
    || !empty($filters['policy_id']);
$exportParams = array_filter([
    'action' => 'reminders_export',
    'search' => $filters['search'] ?? '',
    'status' => $filters['status'] ?? '',
    'reminder_status' => $filters['reminder_status'] ?? '',
    'renewal' => $filters['renewal'] ?? '',
    'client_id' => $filters['client_id'] ?? '',
    'policy_id' => $filters['policy_id'] ?? '',
    'sort' => $filters['sort'] ?? 'renewal_date',
    'direction' => $filters['direction'] ?? 'ASC',
], fn ($value) => $value !== '');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Renewals · full register</p>
        <h1 class="sr-only">Reminders</h1>
    </div>
    <div class="filter-menu">
        <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Export</button>
        <div class="filter-popover">
            <a class="menu-item" href="?<?= e(http_build_query($exportParams)) ?>">Current view CSV</a>
            <a class="menu-item" href="?action=reminders_export&renewal=">All reminders CSV</a>
            <button class="menu-item" type="button" data-print-page>Print current view</button>
            <?php if ($canEdit): ?>
                <button class="menu-item" type="submit" form="reminders-bulk-form" name="bulk_action" value="export" data-requires-selection>Selected rows CSV</button>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="panel">
    <form class="toolbar compact-toolbar" method="get" data-server-filter>
        <input type="hidden" name="page" value="reminders">
        <?php if (!empty($filters['client_id'])): ?><input type="hidden" name="client_id" value="<?= e((string) $filters['client_id']) ?>"><?php endif; ?>
        <?php if (!empty($filters['policy_id'])): ?><input type="hidden" name="policy_id" value="<?= e((string) $filters['policy_id']) ?>"><?php endif; ?>
        <label class="search-field">
            <span class="sr-only">Search reminders</span>
            <input type="search" name="search" placeholder="Search client, policy number, email, phone..." value="<?= e($filters['search']) ?>">
        </label>
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Filters</button>
            <div class="filter-popover" data-filter-popover>
                <label>
                    <span>Reminder Scope</span>
                    <select name="renewal" aria-label="Filter reminder scope">
                        <option value="soon" <?= $filters['renewal'] === 'soon' ? 'selected' : '' ?>>Next 30 days</option>
                        <option value="expired" <?= $filters['renewal'] === 'expired' ? 'selected' : '' ?>>Overdue renewals</option>
                        <option value="" <?= $filters['renewal'] === '' ? 'selected' : '' ?>>All policies</option>
                    </select>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status" aria-label="Filter by status">
                        <option value="">All statuses</option>
                        <?php foreach (['Active', 'Pending Renewal', 'Expired'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Reminder Status</span>
                    <select name="reminder_status" aria-label="Filter by reminder status">
                        <option value="">All reminder statuses</option>
                        <?php foreach (['Pending', 'Contacted', 'Snoozed', 'Failed', 'Resolved'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= ($filters['reminder_status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions">
                    <button class="button primary" type="submit">Apply</button>
                    <?php if ($hasActiveFilters): ?><a class="button quiet" href="?page=reminders">Reset</a><?php endif; ?>
                </div>
            </div>
        </div>
    </form>

    <?php if ($canEdit): ?>
        <form id="reminders-bulk-form" method="post" action="?action=reminder_bulk" data-bulk-form>
            <?= Csrf::field() ?>
            <input type="hidden" name="return_to" value="?page=reminders">
            <div class="bulk-bar">
                <span data-selected-count>0 selected</span>
                <select name="reminder_status" aria-label="Bulk reminder status">
                    <option value="">Set reminder...</option>
                    <?php foreach (['Contacted', 'Snoozed', 'Resolved', 'Failed'] as $status): ?>
                        <option value="<?= e($status) ?>"><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="date" name="reminder_snoozed_until" aria-label="Snooze until">
                <input name="reminder_note" placeholder="Bulk note">
                <button class="button quiet small" name="bulk_action" value="update" type="submit">Apply</button>
            </div>
    <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <?php if ($canEdit): ?><th><input type="checkbox" data-select-all aria-label="Select all reminders"></th><?php endif; ?>
                <th><?= reminder_sort_link('Client', 'client_name', $filters) ?></th>
                <th><?= reminder_sort_link('Policy Number', 'policy_number', $filters) ?></th>
                <th><?= reminder_sort_link('Renewal Date', 'renewal_date', $filters) ?></th>
                <th><?= reminder_sort_link('Lead Time', 'reminder_days', $filters) ?></th>
                <th><?= reminder_sort_link('Contact', 'client_phone', $filters) ?></th>
                <th><?= reminder_sort_link('Assigned', 'assigned_name', $filters) ?></th>
                <th><?= reminder_sort_link('Reminder', 'reminder_status', $filters) ?></th>
                <th><?= reminder_sort_link('Last Contact', 'reminder_last_contacted_at', $filters) ?></th>
                <th><?= reminder_sort_link('Status', 'status', $filters) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result['items'] as $policy): ?>
                <?php $badge = renewal_badge($policy['renewal_date'], $policy['status']); ?>
                <tr class="clickable-row" tabindex="0" data-dialog-open="reminder-dialog-<?= e((string) $policy['id']) ?>">
                    <?php if ($canEdit): ?><td><input type="checkbox" name="ids[]" value="<?= e((string) $policy['id']) ?>" data-row-select aria-label="Select <?= e($policy['policy_number']) ?>"></td><?php endif; ?>
                    <td><strong><?= e($policy['client_name']) ?></strong><small><?= e($policy['client_email'] ?? 'No email') ?></small></td>
                    <td><?= e($policy['policy_number']) ?></td>
                    <td><span class="badge <?= e($badge['tone']) ?>"><?= e($badge['label']) ?></span><small><?= e($policy['renewal_date']) ?></small></td>
                    <td><?= e((string) $policy['reminder_days']) ?> days</td>
                    <td><?= e($policy['client_phone'] ?? 'No phone') ?></td>
                    <td><?= e($policy['assigned_name'] ?? 'Unassigned') ?></td>
                    <td><span class="badge <?= e(reminder_tone($policy['reminder_status'])) ?>"><?= e($policy['reminder_status']) ?></span></td>
                    <td><?= e($policy['reminder_last_contacted_at'] ?? 'Not contacted') ?></td>
                    <td><span class="status-dot <?= e(strtolower(str_replace(' ', '-', $policy['status']))) ?>"><?= e($policy['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['items']): ?>
                <tr>
                    <td colspan="<?= $canEdit ? '10' : '9' ?>" class="empty-cell">
                        <div class="empty-state">
                            <strong>No reminders match the current filters.</strong>
                            <span>Reset filters to view the full renewal queue.</span>
                            <?php if ($hasActiveFilters): ?><a class="button quiet small" href="?page=reminders">Reset filters</a><?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($canEdit): ?>
        </form>
    <?php endif; ?>

    <div class="pagination">
        <span>Showing <?= e((string) (count($result['items']) ? (($result['page'] - 1) * $result['per_page'] + 1) : 0)) ?>-<?= e((string) min($result['total'], $result['page'] * $result['per_page'])) ?> of <?= e((string) $result['total']) ?></span>
        <?php
        $base = [
            'page' => 'reminders',
            'search' => $filters['search'],
            'status' => $filters['status'],
            'reminder_status' => $filters['reminder_status'] ?? '',
            'renewal' => $filters['renewal'],
            'client_id' => $filters['client_id'] ?? '',
            'policy_id' => $filters['policy_id'] ?? '',
            'sort' => $filters['sort'],
            'direction' => $filters['direction'],
        ];
        ?>
        <div>
            <?php if ($result['page'] > 1): ?>
                <a class="button quiet" href="?<?= e(http_build_query($base + ['p' => $result['page'] - 1])) ?>">Previous</a>
            <?php endif; ?>
            <span>Page <?= e((string) $result['page']) ?> of <?= e((string) $result['pages']) ?></span>
            <?php if ($result['page'] < $result['pages']): ?>
                <a class="button quiet" href="?<?= e(http_build_query($base + ['p' => $result['page'] + 1])) ?>">Next</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php foreach ($result['items'] as $policy): ?>
    <?php $reminderFormId = 'reminder-action-form-' . (string) $policy['id']; ?>
    <dialog class="modal workflow-modal" id="reminder-dialog-<?= e((string) $policy['id']) ?>">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="eyebrow"><?= e($policy['policy_number']) ?></p>
                    <h2><?= e($policy['client_name']) ?></h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button>
            </div>
            <div class="detail-list">
                <div><span>Renewal Date</span><strong><?= e($policy['renewal_date']) ?></strong></div>
                <div><span>Reminder Lead Time</span><strong><?= e((string) $policy['reminder_days']) ?> days</strong></div>
                <div><span>Email</span><strong><?= e($policy['client_email'] ?? 'Not captured') ?></strong></div>
                <div><span>Phone</span><strong><?= e($policy['client_phone'] ?? 'Not captured') ?></strong></div>
                <div><span>Assigned Officer</span><strong><?= e($policy['assigned_name'] ?? 'Unassigned') ?></strong></div>
                <div><span>Status</span><strong><?= e($policy['status']) ?></strong></div>
                <div><span>Reminder Status</span><strong><?= e($policy['reminder_status']) ?></strong></div>
                <div><span>Last Reminder Sent</span><strong><?= e($policy['reminder_last_contacted_at'] ?? 'Not yet sent') ?></strong></div>
                <div><span>Snoozed Until</span><strong><?= e($policy['reminder_snoozed_until'] ?? 'Not snoozed') ?></strong></div>
                <div><span>Reminder Note</span><strong><?= e($policy['reminder_note'] ?? 'No reminder note') ?></strong></div>
            </div>
            <?php if ($canEdit): ?>
                <form id="<?= e($reminderFormId) ?>" class="reminder-action-form" method="post" action="?action=reminder_update&id=<?= e((string) $policy['id']) ?>" data-validate>
                    <?= Csrf::field() ?>
                    <input type="hidden" name="return_to" value="?page=reminders">
                    <label>
                        <span>Follow-up note</span>
                        <textarea name="reminder_note" rows="2" placeholder="Call outcome, next step, or reason for delay"><?= e($policy['reminder_note'] ?? '') ?></textarea>
                    </label>
                    <label>
                        <span>Snooze Until</span>
                        <input type="date" name="reminder_snoozed_until" value="<?= e($policy['reminder_snoozed_until'] ?? '') ?>">
                    </label>
                </form>
            <?php endif; ?>
            <div class="modal-actions">
                <div class="modal-action-group">
                    <a class="button ghost" href="?page=policy_edit&id=<?= e((string) $policy['id']) ?>">Open policy</a>
                    <a class="button ghost" href="?page=documents&policy_id=<?= e((string) $policy['id']) ?>">View documents</a>
                    <a class="button ghost" href="?page=clients&search=<?= e(urlencode($policy['client_name'])) ?>">Open client</a>
                    <button class="button quiet" type="button" data-print-summary>Print reminder</button>
                </div>
                <?php if ($canEdit): ?>
                    <div class="modal-action-group primary-actions">
                        <button class="button ghost small" form="<?= e($reminderFormId) ?>" name="reminder_status" value="Contacted" type="submit">Contacted</button>
                        <button class="button quiet small" form="<?= e($reminderFormId) ?>" name="reminder_status" value="Snoozed" type="submit">Snooze</button>
                        <button class="button quiet small" form="<?= e($reminderFormId) ?>" name="reminder_status" value="Resolved" type="submit">Resolved</button>
                        <button class="button danger-button small" form="<?= e($reminderFormId) ?>" name="reminder_status" value="Failed" type="submit">Failed</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </dialog>
<?php endforeach; ?>
