<?php

use App\Core\Auth;
use App\Core\Csrf;

function sort_link(string $label, string $column, array $filters): string
{
    $active = ($filters['sort'] ?? '') === $column;
    $direction = (($filters['sort'] ?? '') === $column && ($filters['direction'] ?? 'ASC') === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'page' => 'policies',
        'search' => $filters['search'] ?? '',
        'status' => $filters['status'] ?? '',
        'reminder_status' => $filters['reminder_status'] ?? '',
        'renewal' => $filters['renewal'] ?? '',
        'type' => $filters['type'] ?? '',
        'docs' => $filters['docs'] ?? '',
        'client_id' => $filters['client_id'] ?? '',
        'policy_id' => $filters['policy_id'] ?? '',
        'sort' => $column,
        'direction' => $direction,
    ], fn ($value) => $value !== '');
    return '<a class="sort-link ' . ($active ? 'active' : '') . '" href="?' . http_build_query($params) . '">' . e($label) . sort_icon($active, $filters['direction'] ?? 'ASC') . '</a>';
}

$canCreate = Auth::can('policy.create');
$canEdit = Auth::can('policy.update');
$staff = $staff ?? [];
$hasActiveFilters = ($filters['search'] ?? '') !== ''
    || ($filters['status'] ?? '') !== ''
    || ($filters['reminder_status'] ?? '') !== ''
    || ($filters['renewal'] ?? '') !== ''
    || ($filters['type'] ?? '') !== ''
    || ($filters['docs'] ?? '') !== ''
    || !empty($filters['client_id'])
    || !empty($filters['policy_id']);
$exportParams = array_filter([
    'action' => 'policies_export',
    'search' => $filters['search'] ?? '',
    'status' => $filters['status'] ?? '',
    'reminder_status' => $filters['reminder_status'] ?? '',
    'renewal' => $filters['renewal'] ?? '',
    'type' => $filters['type'] ?? '',
    'docs' => $filters['docs'] ?? '',
    'client_id' => $filters['client_id'] ?? '',
    'policy_id' => $filters['policy_id'] ?? '',
    'sort' => $filters['sort'] ?? 'renewal_date',
    'direction' => $filters['direction'] ?? 'ASC',
], fn ($value) => $value !== '');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Policy register</p>
        <h1 class="sr-only">Policies</h1>
    </div>
    <div class="page-actions">
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Export</button>
            <div class="filter-popover">
                <a class="menu-item" href="?<?= e(http_build_query($exportParams)) ?>">Current view CSV</a>
                <a class="menu-item" href="?action=policies_export">All policies CSV</a>
                <button class="menu-item" type="button" data-print-page>Print current view</button>
                <?php if ($canEdit): ?>
                    <button class="menu-item" type="submit" form="policies-bulk-form" name="bulk_action" value="export" data-requires-selection>Selected rows CSV</button>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($canCreate): ?>
            <a class="button primary" href="?page=policy_new">+ New Policy</a>
        <?php endif; ?>
    </div>
</section>

<section class="panel">
    <form class="toolbar compact-toolbar" method="get" data-server-filter>
        <input type="hidden" name="page" value="policies">
        <?php if (!empty($filters['client_id'])): ?><input type="hidden" name="client_id" value="<?= e((string) $filters['client_id']) ?>"><?php endif; ?>
        <?php if (!empty($filters['policy_id'])): ?><input type="hidden" name="policy_id" value="<?= e((string) $filters['policy_id']) ?>"><?php endif; ?>
        <label class="search-field">
            <span class="sr-only">Search policies</span>
            <input type="search" name="search" placeholder="Search client, policy number, email, phone, type..." value="<?= e($filters['search']) ?>">
        </label>
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Filters</button>
            <div class="filter-popover" data-filter-popover>
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
                <label>
                    <span>Renewal Date</span>
                    <select name="renewal" aria-label="Filter by renewal date">
                        <option value="">All renewal dates</option>
                        <option value="soon" <?= $filters['renewal'] === 'soon' ? 'selected' : '' ?>>Next 30 days</option>
                        <option value="expired" <?= $filters['renewal'] === 'expired' ? 'selected' : '' ?>>Expired dates</option>
                    </select>
                </label>
                <label>
                    <span>Documents</span>
                    <select name="docs" aria-label="Filter by document completeness">
                        <option value="">All document states</option>
                        <option value="missing" <?= ($filters['docs'] ?? '') === 'missing' ? 'selected' : '' ?>>Missing documents</option>
                    </select>
                </label>
                <label>
                    <span>Insurance Type</span>
                    <select name="type" aria-label="Filter by insurance type">
                        <option value="">All types</option>
                        <?php foreach (['Life Assurance', 'Funeral Cover', 'Education Plan', 'Retirement Annuity', 'Group Life'] as $type): ?>
                            <option value="<?= e($type) ?>" <?= $filters['type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <div class="filter-actions">
                    <button class="button primary" type="submit">Apply</button>
                    <?php if ($hasActiveFilters): ?><a class="button quiet" href="?page=policies">Reset</a><?php endif; ?>
                </div>
            </div>
        </div>
    </form>

    <form id="policies-bulk-form" method="post" action="?action=policy_bulk" data-bulk-form>
        <?= Csrf::field() ?>
        <?php if ($canEdit): ?>
            <div class="bulk-bar">
                <span data-selected-count>0 selected</span>
                <select name="bulk_status" aria-label="Bulk policy status">
                    <option value="">Set status...</option>
                    <?php foreach (['Active', 'Pending Renewal', 'Expired'] as $status): ?>
                        <option value="<?= e($status) ?>"><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button quiet small" name="bulk_action" value="status" type="submit">Apply status</button>
                <select name="bulk_assigned_to" aria-label="Bulk assigned officer">
                    <option value="">Unassigned</option>
                    <?php foreach ($staff as $member): ?>
                        <option value="<?= e((string) $member['id']) ?>"><?= e($member['name']) ?></option>
                <?php endforeach; ?>
                </select>
                <button class="button quiet small" name="bulk_action" value="assign" type="submit">Assign</button>
            </div>
        <?php endif; ?>

    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <?php if ($canEdit): ?><th><input type="checkbox" data-select-all aria-label="Select all policies"></th><?php endif; ?>
                <th><?= sort_link('Client', 'client_name', $filters) ?></th>
                <th><?= sort_link('Policy Number', 'policy_number', $filters) ?></th>
                <th><?= sort_link('Contact', 'client_phone', $filters) ?></th>
                <th><?= sort_link('Renewal', 'renewal_date', $filters) ?></th>
                <th><?= sort_link('Type', 'insurance_type', $filters) ?></th>
                <th><?= sort_link('Premium', 'premium_amount', $filters) ?></th>
                <th><?= sort_link('Docs', 'document_count', $filters) ?></th>
                <th><?= sort_link('Assigned', 'assigned_name', $filters) ?></th>
                <th><?= sort_link('Reminder', 'reminder_status', $filters) ?></th>
                <th><?= sort_link('Status', 'status', $filters) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result['items'] as $policy): ?>
                <?php $badge = renewal_badge($policy['renewal_date'], $policy['status']); ?>
                <tr class="clickable-row" tabindex="0" data-dialog-open="policy-dialog-<?= e((string) $policy['id']) ?>">
                    <?php if ($canEdit): ?><td><input type="checkbox" name="ids[]" value="<?= e((string) $policy['id']) ?>" data-row-select aria-label="Select <?= e($policy['policy_number']) ?>"></td><?php endif; ?>
                    <td><strong><?= e($policy['client_name']) ?></strong><small><?= e($policy['client_email'] ?? 'No email') ?></small></td>
                    <td><?= e($policy['policy_number']) ?></td>
                    <td><?= e($policy['client_phone'] ?? 'No phone') ?></td>
                    <td><span class="badge <?= e($badge['tone']) ?>"><?= e($badge['label']) ?></span><small><?= e($policy['renewal_date']) ?></small></td>
                    <td><?= e($policy['insurance_type']) ?></td>
                    <td><?= e(money($policy['premium_amount'])) ?><small><?= e($policy['payment_frequency']) ?></small></td>
                    <td><span class="badge <?= (int) $policy['document_count'] > 0 ? 'success' : 'warning' ?>"><?= e((string) $policy['document_count']) ?></span></td>
                    <td><?= e($policy['assigned_name'] ?? 'Unassigned') ?></td>
                    <td><span class="badge <?= e(reminder_tone($policy['reminder_status'])) ?>"><?= e($policy['reminder_status']) ?></span></td>
                    <td><span class="status-dot <?= e(strtolower(str_replace(' ', '-', $policy['status']))) ?>"><?= e($policy['status']) ?></span></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['items']): ?>
                <tr>
                    <td colspan="<?= $canEdit ? '11' : '10' ?>" class="empty-cell">
                        <div class="empty-state">
                            <strong>No policies match the current filters.</strong>
                            <span>Reset filters to return to the full register, or create a new policy if this is new business.</span>
                            <span class="empty-actions">
                                <?php if ($hasActiveFilters): ?><a class="button quiet small" href="?page=policies">Reset filters</a><?php endif; ?>
                                <?php if ($canCreate): ?><a class="button primary small" href="?page=policy_new">New policy</a><?php endif; ?>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    </form>

    <div class="pagination">
        <span>Showing <?= e((string) (count($result['items']) ? (($result['page'] - 1) * $result['per_page'] + 1) : 0)) ?>-<?= e((string) min($result['total'], $result['page'] * $result['per_page'])) ?> of <?= e((string) $result['total']) ?></span>
        <?php
        $base = [
            'page' => 'policies',
            'search' => $filters['search'],
            'status' => $filters['status'],
            'reminder_status' => $filters['reminder_status'] ?? '',
            'renewal' => $filters['renewal'],
            'type' => $filters['type'],
            'docs' => $filters['docs'] ?? '',
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
    <?php $badge = renewal_badge($policy['renewal_date'], $policy['status']); ?>
    <dialog class="modal" id="policy-dialog-<?= e((string) $policy['id']) ?>">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="eyebrow"><?= e($policy['policy_number']) ?></p>
                    <h2><?= e($policy['client_name']) ?></h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button>
            </div>

            <div class="detail-list">
                <div><span>Policy status</span><strong><?= e($policy['status']) ?></strong></div>
                <div><span>Renewal position</span><strong><?= e($policy['renewal_date']) ?> · <?= e($badge['label']) ?></strong></div>
                <div><span>Insurance Type</span><strong><?= e($policy['insurance_type']) ?></strong></div>
                <div><span>Premium</span><strong><?= e(money($policy['premium_amount'])) ?> / <?= e($policy['payment_frequency']) ?></strong></div>
                <div><span>Email</span><strong><?= e($policy['client_email'] ?? 'Not captured') ?></strong></div>
                <div><span>Phone</span><strong><?= e($policy['client_phone'] ?? 'Not captured') ?></strong></div>
                <div><span>Assigned Officer</span><strong><?= e($policy['assigned_name'] ?? 'Unassigned') ?></strong></div>
                <div><span>Supporting documents</span><strong><?= e((string) $policy['document_count']) ?></strong></div>
                <div><span>Reminder status</span><strong><?= e($policy['reminder_status']) ?></strong></div>
                <div><span>Last reminder sent</span><strong><?= e($policy['reminder_last_contacted_at'] ?? 'Not yet sent') ?></strong></div>
                <div><span>Created by</span><strong><?= e($policy['created_name'] ?? 'Unknown') ?></strong></div>
                <div><span>Last edited</span><strong><?= e(($policy['updated_name'] ?? 'Unknown') . ' · ' . $policy['updated_at']) ?></strong></div>
                <div class="span-2"><span>Notes</span><strong><?= e($policy['notes'] ?? 'No notes recorded') ?></strong></div>
            </div>

            <div class="modal-actions">
                <a class="button ghost" href="?page=policy_edit&id=<?= e((string) $policy['id']) ?>"><?= $canEdit ? 'Modify policy' : 'View full policy' ?></a>
                <a class="button ghost" href="?page=documents&policy_id=<?= e((string) $policy['id']) ?>">Documents</a>
                <a class="button ghost" href="?page=reminders&policy_id=<?= e((string) $policy['id']) ?>&renewal=">Reminder</a>
                <a class="button ghost" href="?page=clients&search=<?= e(urlencode($policy['client_name'])) ?>">Client</a>
                <button class="button quiet" type="button" data-print-summary>Print summary</button>
                <?php if ($canEdit): ?>
                    <form method="post" action="?action=policy_delete&id=<?= e((string) $policy['id']) ?>" data-confirm="Remove this policy and its documents?">
                        <?= Csrf::field() ?>
                        <button class="button danger-button" type="submit">Delete policy</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </dialog>
<?php endforeach; ?>
