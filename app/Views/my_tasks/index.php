<?php

use App\Core\Csrf;

function my_task_sort_link(string $label, string $column, array $filters): string
{
    $active = ($filters['sort'] ?? '') === $column;
    $direction = (($filters['sort'] ?? '') === $column && ($filters['direction'] ?? 'ASC') === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'page' => 'my_tasks',
        'search' => $filters['search'] ?? '',
        'status' => $filters['status'] ?? '',
        'reminder_status' => $filters['reminder_status'] ?? '',
        'renewal' => $filters['renewal'] ?? '',
        'type' => $filters['type'] ?? '',
        'docs' => $filters['docs'] ?? '',
        'sort' => $column,
        'direction' => $direction,
    ], fn ($value) => $value !== '');
    return '<a class="sort-link ' . ($active ? 'active' : '') . '" href="?' . http_build_query($params) . '">' . e($label) . sort_icon($active, $filters['direction'] ?? 'ASC') . '</a>';
}

$items = $result['items'];
$summaryItems = $summaryItems ?? $items;
$documentTypeCount = max(1, (int) ($documentTypeCount ?? 1));
$dueSoon = array_filter($summaryItems, fn ($policy) => renewal_badge($policy['renewal_date'], $policy['status'])['tone'] === 'warning');
$overdue = array_filter($summaryItems, fn ($policy) => renewal_badge($policy['renewal_date'], $policy['status'])['tone'] === 'danger');
$missingDocs = array_filter($summaryItems, fn ($policy) => (int) $policy['document_count'] === 0);
$snoozed = array_filter($summaryItems, fn ($policy) => $policy['reminder_status'] === 'Snoozed');
$hasActiveFilters = ($filters['search'] ?? '') !== ''
    || ($filters['status'] ?? '') !== ''
    || ($filters['reminder_status'] ?? '') !== ''
    || ($filters['renewal'] ?? '') !== ''
    || ($filters['type'] ?? '') !== ''
    || ($filters['docs'] ?? '') !== '';
$exportParams = array_filter([
    'action' => 'reminders_export',
    'mine' => '1',
    'search' => $filters['search'] ?? '',
    'status' => $filters['status'] ?? '',
    'reminder_status' => $filters['reminder_status'] ?? '',
    'renewal' => $filters['renewal'] ?? '',
    'type' => $filters['type'] ?? '',
    'docs' => $filters['docs'] ?? '',
    'sort' => $filters['sort'] ?? 'renewal_date',
    'direction' => $filters['direction'] ?? 'ASC',
], fn ($value) => $value !== '');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Renewals · personal queue</p>
        <h1 class="sr-only">My Tasks</h1>
    </div>
    <div class="page-actions">
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Export</button>
            <div class="filter-popover">
                <a class="menu-item" href="?<?= e(http_build_query($exportParams)) ?>">Current view CSV</a>
                <button class="menu-item" type="submit" form="my-tasks-bulk-form" name="bulk_action" value="export" data-requires-selection>Selected rows CSV</button>
                <button class="menu-item" type="button" data-print-page>Print current view</button>
            </div>
        </div>
        <a class="button quiet" href="?page=reminders">Full reminder register</a>
    </div>
</section>

<section class="kpi-grid queue-summary" aria-label="My renewal workload">
    <a class="metric-card" href="?page=my_tasks&renewal=expired">
        <span class="metric-icon danger">!</span>
        <h2>Overdue</h2>
        <strong><?= e((string) count($overdue)) ?></strong>
        <p>Renewal date already passed</p>
    </a>
    <a class="metric-card" href="?page=my_tasks&renewal=soon">
        <span class="metric-icon warning">!</span>
        <h2>Due Soon</h2>
        <strong><?= e((string) count($dueSoon)) ?></strong>
        <p>Assigned renewals within 30 days</p>
    </a>
    <a class="metric-card" href="?page=my_tasks&docs=missing">
        <span class="metric-icon warning">△</span>
        <h2>Missing Docs</h2>
        <strong><?= e((string) count($missingDocs)) ?></strong>
        <p>Policies needing support files</p>
    </a>
    <a class="metric-card" href="?page=my_tasks&reminder_status=Snoozed">
        <span class="metric-icon muted">i</span>
        <h2>Snoozed</h2>
        <strong><?= e((string) count($snoozed)) ?></strong>
        <p>Waiting for next follow-up date</p>
    </a>
</section>

<section class="panel">
    <form class="toolbar compact-toolbar" method="get" data-server-filter>
        <input type="hidden" name="page" value="my_tasks">
        <label class="search-field">
            <span class="sr-only">Search assigned work</span>
            <input type="search" name="search" placeholder="Search client, policy number, email, phone..." value="<?= e($filters['search'] ?? '') ?>">
        </label>
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Filters</button>
            <div class="filter-popover">
                <label>Renewal
                    <select name="renewal">
                        <option value="" <?= ($filters['renewal'] ?? '') === '' ? 'selected' : '' ?>>Any renewal</option>
                        <option value="soon" <?= ($filters['renewal'] ?? '') === 'soon' ? 'selected' : '' ?>>Due in 30 days</option>
                        <option value="expired" <?= ($filters['renewal'] ?? '') === 'expired' ? 'selected' : '' ?>>Overdue</option>
                    </select>
                </label>
                <label>Reminder
                    <select name="reminder_status">
                        <option value="">Any reminder</option>
                        <?php foreach (['Pending', 'Contacted', 'Snoozed', 'Failed', 'Resolved'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= ($filters['reminder_status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Policy status
                    <select name="status">
                        <option value="">Any status</option>
                        <?php foreach (['Active', 'Pending Renewal', 'Expired'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Documents
                    <select name="docs">
                        <option value="">Any document state</option>
                        <option value="missing" <?= ($filters['docs'] ?? '') === 'missing' ? 'selected' : '' ?>>Missing documents</option>
                    </select>
                </label>
                <button class="button primary small" type="submit">Apply filters</button>
            </div>
        </div>
        <?php if ($hasActiveFilters): ?><a class="button quiet" href="?page=my_tasks">Reset</a><?php endif; ?>
    </form>
    <form id="my-tasks-bulk-form" method="post" action="?action=reminder_bulk" data-bulk-form>
        <?= Csrf::field() ?>
        <input type="hidden" name="return_to" value="?page=my_tasks">
        <div class="bulk-bar">
            <span data-selected-count>0 selected</span>
            <select name="reminder_status" aria-label="Bulk reminder status">
                <option value="">Set reminder...</option>
                <?php foreach (['Contacted', 'Snoozed', 'Resolved', 'Failed'] as $status): ?>
                    <option value="<?= e($status) ?>"><?= e($status) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="reminder_snoozed_until" aria-label="Snooze until">
            <input name="reminder_note" placeholder="Follow-up note">
            <button class="button quiet small" name="bulk_action" value="update" type="submit">Apply</button>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th><input type="checkbox" data-select-all aria-label="Select all tasks"></th>
                    <th><?= my_task_sort_link('Client', 'client_name', $filters ?? []) ?></th>
                    <th><?= my_task_sort_link('Policy', 'policy_number', $filters ?? []) ?></th>
                    <th><?= my_task_sort_link('Renewal', 'renewal_date', $filters ?? []) ?></th>
                    <th><?= my_task_sort_link('Reminder', 'reminder_status', $filters ?? []) ?></th>
                    <th><?= my_task_sort_link('Last Contact', 'reminder_last_contacted_at', $filters ?? []) ?></th>
                    <th><?= my_task_sort_link('Documents', 'document_count', $filters ?? []) ?></th>
                    <th><?= my_task_sort_link('Status', 'status', $filters ?? []) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($items as $policy): ?>
                    <?php $badge = renewal_badge($policy['renewal_date'], $policy['status']); ?>
                    <tr class="clickable-row" tabindex="0" data-dialog-open="task-dialog-<?= e((string) $policy['id']) ?>">
                        <td><input type="checkbox" name="ids[]" value="<?= e((string) $policy['id']) ?>" data-row-select aria-label="Select <?= e($policy['policy_number']) ?>"></td>
                        <td><strong><?= e($policy['client_name']) ?></strong><small><?= e($policy['client_phone'] ?? 'No phone') ?></small></td>
                        <td><?= e($policy['policy_number']) ?></td>
                        <td><span class="badge <?= e($badge['tone']) ?>"><?= e($badge['label']) ?></span><small><?= e($policy['renewal_date']) ?></small></td>
                        <td><span class="badge <?= e(reminder_tone($policy['reminder_status'])) ?>"><?= e($policy['reminder_status']) ?></span></td>
                        <td><?= e($policy['reminder_last_contacted_at'] ?? 'Not contacted') ?></td>
                        <td><?= e((string) $policy['document_count']) ?>/<?= e((string) $documentTypeCount) ?></td>
                        <td><span class="status-dot <?= e(strtolower(str_replace(' ', '-', $policy['status']))) ?>"><?= e($policy['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?>
                    <tr>
                        <td colspan="8" class="empty-cell">
                            <div class="empty-state">
                                <strong><?= $hasActiveFilters ? 'No tasks match the current filters.' : 'You have no assigned renewal work right now.' ?></strong>
                                <span><?= $hasActiveFilters ? 'Clear filters to return to your full queue.' : 'Assigned renewals, missing documents, and overdue follow-ups will appear here.' ?></span>
                                <?php if ($hasActiveFilters): ?><a class="button quiet small" href="?page=my_tasks">Reset filters</a><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
    <div class="pagination">
        <span>Showing <?= e((string) (count($items) ? (($result['page'] - 1) * $result['per_page'] + 1) : 0)) ?>-<?= e((string) min($result['total'], $result['page'] * $result['per_page'])) ?> of <?= e((string) $result['total']) ?></span>
        <?php $base = ['page' => 'my_tasks', 'search' => $filters['search'], 'status' => $filters['status'], 'reminder_status' => $filters['reminder_status'], 'renewal' => $filters['renewal'], 'type' => $filters['type'], 'docs' => $filters['docs'] ?? '', 'sort' => $filters['sort'], 'direction' => $filters['direction']]; ?>
        <div>
            <?php if ($result['page'] > 1): ?><a class="button quiet" href="?<?= e(http_build_query($base + ['p' => $result['page'] - 1])) ?>">Previous</a><?php endif; ?>
            <span>Page <?= e((string) $result['page']) ?> of <?= e((string) $result['pages']) ?></span>
            <?php if ($result['page'] < $result['pages']): ?><a class="button quiet" href="?<?= e(http_build_query($base + ['p' => $result['page'] + 1])) ?>">Next</a><?php endif; ?>
        </div>
    </div>
</section>

<?php foreach ($items as $policy): ?>
    <?php $badge = renewal_badge($policy['renewal_date'], $policy['status']); ?>
    <?php $taskFormId = 'task-action-form-' . (string) $policy['id']; ?>
    <dialog class="modal workflow-modal" id="task-dialog-<?= e((string) $policy['id']) ?>">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="eyebrow"><?= e($policy['policy_number']) ?></p>
                    <h2><?= e($policy['client_name']) ?></h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button>
            </div>
            <div class="detail-list">
                <div><span>Renewal</span><strong><?= e($policy['renewal_date']) ?> · <?= e($badge['label']) ?></strong></div>
                <div><span>Reminder</span><strong><?= e($policy['reminder_status']) ?></strong></div>
                <div><span>Phone</span><strong><?= e($policy['client_phone'] ?? 'Not captured') ?></strong></div>
                <div><span>Email</span><strong><?= e($policy['client_email'] ?? 'Not captured') ?></strong></div>
                <div><span>Documents</span><strong><?= e((string) $policy['document_count']) ?>/<?= e((string) $documentTypeCount) ?> uploaded</strong></div>
                <div><span>Last Contact</span><strong><?= e($policy['reminder_last_contacted_at'] ?? 'Not contacted') ?></strong></div>
                <div class="span-2"><span>Reminder Note</span><strong><?= e($policy['reminder_note'] ?? 'No reminder note') ?></strong></div>
            </div>
            <form id="<?= e($taskFormId) ?>" class="reminder-action-form single-field" method="post" action="?action=reminder_update&id=<?= e((string) $policy['id']) ?>" data-validate>
                <?= Csrf::field() ?>
                <input type="hidden" name="return_to" value="?page=my_tasks">
                <label>
                    <span>Follow-up note</span>
                    <textarea name="reminder_note" rows="2"><?= e($policy['reminder_note'] ?? '') ?></textarea>
                </label>
            </form>
            <div class="modal-actions">
                <div class="modal-action-group">
                    <a class="button ghost" href="?page=policy_edit&id=<?= e((string) $policy['id']) ?>">Open policy</a>
                    <button class="button quiet" type="button" data-print-summary>Print task</button>
                </div>
                <div class="modal-action-group primary-actions">
                    <button class="button ghost small" form="<?= e($taskFormId) ?>" name="reminder_status" value="Contacted" type="submit">Contacted</button>
                    <button class="button quiet small" form="<?= e($taskFormId) ?>" name="reminder_status" value="Resolved" type="submit">Resolved</button>
                    <button class="button danger-button small" form="<?= e($taskFormId) ?>" name="reminder_status" value="Failed" type="submit">Failed</button>
                </div>
            </div>
        </div>
    </dialog>
<?php endforeach; ?>
