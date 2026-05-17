<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Client;

function client_sort_link(string $label, string $column, array $filters): string
{
    $active = ($filters['sort'] ?? '') === $column;
    $direction = (($filters['sort'] ?? '') === $column && ($filters['direction'] ?? 'ASC') === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'page' => 'clients',
        'search' => $filters['search'] ?? '',
        'status' => $filters['status'] ?? '',
        'renewal' => $filters['renewal'] ?? '',
        'type' => $filters['type'] ?? '',
        'docs' => $filters['docs'] ?? '',
        'contact' => $filters['contact'] ?? '',
        'segment' => $filters['segment'] ?? '',
        'city' => $filters['city'] ?? '',
        'client_status' => $filters['client_status'] ?? '',
        'sort' => $column,
        'direction' => $direction,
    ], fn ($value) => $value !== '');
    return '<a class="sort-link ' . ($active ? 'active' : '') . '" href="?' . http_build_query($params) . '">' . e($label) . sort_icon($active, $filters['direction'] ?? 'ASC') . '</a>';
}

$clients = $result['items'] ?? $clients ?? [];
$hasActiveFilters = trim((string) ($filters['search'] ?? '')) !== ''
    || ($filters['status'] ?? '') !== ''
    || ($filters['renewal'] ?? '') !== ''
    || ($filters['type'] ?? '') !== ''
    || ($filters['docs'] ?? '') !== ''
    || ($filters['contact'] ?? '') !== ''
    || ($filters['segment'] ?? '') !== ''
    || ($filters['city'] ?? '') !== ''
    || ($filters['client_status'] ?? '') !== '';
$canManageClients = Auth::can('policy.update');
$clientTypes = ['Individual', 'Corporate', 'SME', 'Group'];
$clientSegments = ['Retail', 'VIP', 'SME', 'Corporate'];
$contactMethods = ['Phone', 'Email', 'SMS', 'WhatsApp'];
$clientStatuses = ['Active', 'Inactive', 'Watchlist'];
$documentTypeCount = max(1, (int) ($documentTypeCount ?? 1));
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Client directory</p>
        <h1 class="sr-only">Clients</h1>
    </div>
    <?php $exportParams = array_filter(['action' => 'clients_export', 'search' => $filters['search'] ?? '', 'status' => $filters['status'] ?? '', 'renewal' => $filters['renewal'] ?? '', 'type' => $filters['type'] ?? '', 'docs' => $filters['docs'] ?? '', 'contact' => $filters['contact'] ?? '', 'segment' => $filters['segment'] ?? '', 'city' => $filters['city'] ?? '', 'client_status' => $filters['client_status'] ?? '', 'sort' => $filters['sort'] ?? 'client_name', 'direction' => $filters['direction'] ?? 'ASC'], fn ($value) => $value !== ''); ?>
    <div class="page-actions">
        <?php if ($canManageClients): ?><button class="button primary" type="button" data-dialog-open="client-create-dialog">+ New Client</button><?php endif; ?>
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Export</button>
            <div class="filter-popover">
                <a class="menu-item" href="?<?= e(http_build_query($exportParams)) ?>">Current view CSV</a>
                <a class="menu-item" href="?action=clients_export">All clients CSV</a>
                <button class="menu-item" type="button" data-print-page>Print current view</button>
            </div>
        </div>
    </div>
</section>

<section class="panel">
    <form class="toolbar compact-toolbar" method="get" data-server-filter>
        <input type="hidden" name="page" value="clients">
        <label class="search-field">
            <span class="sr-only">Search clients</span>
            <input type="search" name="search" placeholder="Search client, code, ID, city, email, phone..." value="<?= e($filters['search'] ?? '') ?>">
        </label>
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Filters</button>
            <div class="filter-popover" data-filter-popover>
                <label>
                    <span>Client Segment</span>
                    <select name="segment" aria-label="Filter by client segment">
                        <option value="">Any segment</option>
                        <?php foreach (['Retail', 'SME', 'Corporate', 'VIP'] as $segment): ?>
                            <option value="<?= e($segment) ?>" <?= ($filters['segment'] ?? '') === $segment ? 'selected' : '' ?>><?= e($segment) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Client Status</span>
                    <select name="client_status" aria-label="Filter by client status">
                        <option value="">Any client status</option>
                        <?php foreach ($clientStatuses as $status): ?>
                            <option value="<?= e($status) ?>" <?= ($filters['client_status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>City</span>
                    <select name="city" aria-label="Filter by city">
                        <option value="">Any city</option>
                        <?php foreach (['Harare', 'Bulawayo', 'Mutare', 'Gweru', 'Masvingo'] as $city): ?>
                            <option value="<?= e($city) ?>" <?= ($filters['city'] ?? '') === $city ? 'selected' : '' ?>><?= e($city) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Portfolio Status</span>
                    <select name="status" aria-label="Filter by policy status">
                        <option value="">Any status</option>
                        <?php foreach (['Active', 'Pending Renewal', 'Expired'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Renewal Pressure</span>
                    <select name="renewal" aria-label="Filter by renewal pressure">
                        <option value="">Any renewal</option>
                        <option value="soon" <?= ($filters['renewal'] ?? '') === 'soon' ? 'selected' : '' ?>>Renewal in 30 days</option>
                        <option value="expired" <?= ($filters['renewal'] ?? '') === 'expired' ? 'selected' : '' ?>>Overdue renewal</option>
                    </select>
                </label>
                <label>
                    <span>Insurance Type</span>
                    <select name="type" aria-label="Filter by insurance type">
                        <option value="">Any type</option>
                        <?php foreach (['Life Assurance', 'Funeral Cover', 'Education Plan', 'Retirement Annuity', 'Group Life'] as $type): ?>
                            <option value="<?= e($type) ?>" <?= ($filters['type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Documents</span>
                    <select name="docs" aria-label="Filter by document state">
                        <option value="">Any document state</option>
                        <option value="missing" <?= ($filters['docs'] ?? '') === 'missing' ? 'selected' : '' ?>>Has missing documents</option>
                    </select>
                </label>
                <label>
                    <span>Contact Details</span>
                    <select name="contact" aria-label="Filter by contact completeness">
                        <option value="">Any contact state</option>
                        <option value="complete" <?= ($filters['contact'] ?? '') === 'complete' ? 'selected' : '' ?>>Email and phone captured</option>
                        <option value="missing" <?= ($filters['contact'] ?? '') === 'missing' ? 'selected' : '' ?>>Missing email or phone</option>
                    </select>
                </label>
                <div class="filter-actions">
                    <button class="button primary" type="submit">Apply</button>
                    <?php if ($hasActiveFilters): ?><a class="button quiet" href="?page=clients">Reset</a><?php endif; ?>
                </div>
            </div>
        </div>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th><?= client_sort_link('Client', 'client_name', $filters ?? []) ?></th>
                <th><?= client_sort_link('Segment', 'segment', $filters ?? []) ?></th>
                <th><?= client_sort_link('City', 'city', $filters ?? []) ?></th>
                <th><?= client_sort_link('Contact', 'client_phone', $filters ?? []) ?></th>
                <th><?= client_sort_link('Policies', 'policy_count', $filters ?? []) ?></th>
                <th><?= client_sort_link('Needs', 'renewal_soon_count', $filters ?? []) ?></th>
                <th><?= client_sort_link('Missing Docs', 'missing_docs_count', $filters ?? []) ?></th>
                <th><?= client_sort_link('Next Renewal', 'next_renewal', $filters ?? []) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($clients as $index => $client): ?>
                <tr class="clickable-row" tabindex="0" data-dialog-open="client-dialog-<?= e((string) $index) ?>">
                    <td><strong><?= e($client['client_name']) ?></strong><small><?= e($client['client_code']) ?> · <?= e($client['client_type']) ?></small></td>
                    <td><span class="pill"><?= e($client['segment']) ?></span></td>
                    <td><?= e($client['city'] ?? 'Not captured') ?></td>
                    <td><?= e($client['client_phone'] ?? 'Not captured') ?><small><?= e($client['client_email'] ?? 'No email') ?></small></td>
                    <td><?= e((string) $client['policy_count']) ?></td>
                    <td><?= e((string) ((int) $client['renewal_soon_count'] + (int) $client['expired_count'])) ?></td>
                    <td><?= e((string) $client['missing_docs_count']) ?></td>
                    <td><?= e($client['next_renewal'] ?? 'None') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$clients): ?>
                <tr>
                    <td colspan="8" class="empty-cell">
                        <div class="empty-state">
                            <strong>No clients match the current search or filters.</strong>
                            <span>Reset filters to view the full directory, or create a new client if this is a new account.</span>
                            <span class="empty-actions">
                                <?php if ($hasActiveFilters): ?><a class="button quiet small" href="?page=clients">Reset filters</a><?php endif; ?>
                                <?php if ($canManageClients): ?><button class="button primary small" type="button" data-dialog-open="client-create-dialog">New client</button><?php endif; ?>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (isset($result)): ?>
        <div class="pagination">
            <span>Showing <?= e((string) (count($clients) ? (($result['page'] - 1) * $result['per_page'] + 1) : 0)) ?>-<?= e((string) min($result['total'], $result['page'] * $result['per_page'])) ?> of <?= e((string) $result['total']) ?></span>
            <?php $base = ['page' => 'clients', 'search' => $filters['search'], 'status' => $filters['status'], 'renewal' => $filters['renewal'], 'type' => $filters['type'], 'docs' => $filters['docs'], 'contact' => $filters['contact'], 'segment' => $filters['segment'], 'city' => $filters['city'], 'client_status' => $filters['client_status'], 'sort' => $filters['sort'], 'direction' => $filters['direction']]; ?>
            <div>
                <?php if ($result['page'] > 1): ?><a class="button quiet" href="?<?= e(http_build_query($base + ['p' => $result['page'] - 1])) ?>">Previous</a><?php endif; ?>
                <span>Page <?= e((string) $result['page']) ?> of <?= e((string) $result['pages']) ?></span>
                <?php if ($result['page'] < $result['pages']): ?><a class="button quiet" href="?<?= e(http_build_query($base + ['p' => $result['page'] + 1])) ?>">Next</a><?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</section>

<?php foreach ($clients as $index => $client): ?>
    <dialog class="modal" id="client-dialog-<?= e((string) $index) ?>">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="eyebrow">Client profile</p>
                    <h2><?= e($client['client_name']) ?></h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button>
            </div>
            <?php $policies = $clientPolicies[Client::key($client)] ?? []; ?>
            <div class="modal-tabs" data-tabs>
                <div class="tab-list" role="tablist" aria-label="Client profile sections">
                    <button class="active" type="button" data-tab="overview">Overview</button>
                    <button type="button" data-tab="policies">Policies</button>
                    <?php if ($canManageClients): ?><button type="button" data-tab="edit">Edit</button><?php endif; ?>
                </div>
                <div class="tab-panel active" data-tab-panel="overview">
                    <div class="detail-list">
                        <div><span>Email</span><strong><?= e($client['client_email'] ?? 'Not captured') ?></strong></div>
                        <div><span>Phone</span><strong><?= e($client['client_phone'] ?? 'Not captured') ?></strong></div>
                        <div><span>Client Code</span><strong><?= e($client['client_code']) ?></strong></div>
                        <div><span>Type / Segment</span><strong><?= e($client['client_type']) ?> · <?= e($client['segment']) ?></strong></div>
                        <div><span>Preferred Contact</span><strong><?= e($client['preferred_contact']) ?></strong></div>
                        <div><span>ID / Tax Number</span><strong><?= e($client['national_id'] ?? $client['tax_number'] ?? 'Not captured') ?></strong></div>
                        <div><span>Total Policies</span><strong><?= e((string) $client['policy_count']) ?></strong></div>
                        <div><span>Active Policies</span><strong><?= e((string) $client['active_count']) ?></strong></div>
                        <div class="span-2"><span>Address</span><strong><?= e(trim(($client['address_line1'] ?? '') . ', ' . ($client['suburb'] ?? '') . ', ' . ($client['city'] ?? '') . ', ' . ($client['province'] ?? ''), ', ')) ?></strong></div>
                        <div class="span-2"><span>Next Renewal</span><strong><?= e($client['next_renewal'] ?? 'None') ?></strong></div>
                    </div>
                </div>
                <div class="tab-panel" data-tab-panel="policies">
                    <div class="client-360">
                        <h3>Portfolio snapshot</h3>
                        <div class="mini-stats">
                            <span><strong><?= e(money(array_sum(array_map(fn ($policy) => (float) $policy['premium_amount'], $policies)))) ?></strong> Total premium</span>
                            <span><strong><?= e((string) array_sum(array_map(fn ($policy) => (int) $policy['document_count'], $policies))) ?></strong> Documents uploaded</span>
                            <span><strong><?= e((string) count(array_filter($policies, fn ($policy) => $policy['reminder_status'] !== 'Resolved'))) ?></strong> Open reminders</span>
                        </div>
                        <?php if (!$policies): ?>
                            <div class="empty-card">No policies linked to this client yet.</div>
                        <?php endif; ?>
                        <div class="table-wrap compact-table">
                            <table>
                                <thead>
                                <tr>
                                    <th>Policy Number</th>
                                    <th>Type</th>
                                    <th>Renewal</th>
                                    <th>Reminder</th>
                                    <th>Docs</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($policies as $policy): ?>
                                    <?php $badge = renewal_badge($policy['renewal_date'], $policy['status']); ?>
                                    <tr>
                                        <td><a href="?page=policy_edit&id=<?= e((string) $policy['id']) ?>"><?= e($policy['policy_number']) ?></a></td>
                                        <td><?= e($policy['insurance_type']) ?></td>
                                        <td><span class="badge <?= e($badge['tone']) ?>"><?= e($badge['label']) ?></span></td>
                                        <td><span class="badge <?= e(reminder_tone($policy['reminder_status'])) ?>"><?= e($policy['reminder_status']) ?></span></td>
                                        <td><?= e((string) $policy['document_count']) ?>/<?= e((string) $documentTypeCount) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php if ($canManageClients): ?>
                    <div class="tab-panel" data-tab-panel="edit">
                    <form class="form-grid" method="post" action="?action=client_update&id=<?= e((string) $client['id']) ?>" data-validate>
                        <?= Csrf::field() ?>
                        <input type="hidden" name="return_to" value="?page=clients">
                        <label><span>Client Name</span><input name="client_name" value="<?= e($client['client_name']) ?>" required></label>
                        <label><span>Email</span><input type="email" name="client_email" value="<?= e($client['client_email'] ?? '') ?>"></label>
                        <label><span>Phone</span><input name="client_phone" value="<?= e($client['client_phone'] ?? '') ?>"></label>
                        <label><span>Alternate Phone</span><input name="alternate_phone" value="<?= e($client['alternate_phone'] ?? '') ?>"></label>
                        <label><span>Client Type</span><select name="client_type"><?php foreach ($clientTypes as $type): ?><option value="<?= e($type) ?>" <?= $client['client_type'] === $type ? 'selected' : '' ?>><?= e($type) ?></option><?php endforeach; ?></select></label>
                        <label><span>Segment</span><select name="segment"><?php foreach ($clientSegments as $segment): ?><option value="<?= e($segment) ?>" <?= $client['segment'] === $segment ? 'selected' : '' ?>><?= e($segment) ?></option><?php endforeach; ?></select></label>
                        <label><span>Status</span><select name="client_status"><?php foreach ($clientStatuses as $status): ?><option value="<?= e($status) ?>" <?= $client['client_status'] === $status ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select></label>
                        <label><span>Preferred Contact</span><select name="preferred_contact"><?php foreach ($contactMethods as $method): ?><option value="<?= e($method) ?>" <?= $client['preferred_contact'] === $method ? 'selected' : '' ?>><?= e($method) ?></option><?php endforeach; ?></select></label>
                        <label><span>National ID</span><input name="national_id" value="<?= e($client['national_id'] ?? '') ?>"></label>
                        <label><span>Tax Number</span><input name="tax_number" value="<?= e($client['tax_number'] ?? '') ?>"></label>
                        <label><span>City</span><input name="city" value="<?= e($client['city'] ?? '') ?>"></label>
                        <label><span>Province</span><input name="province" value="<?= e($client['province'] ?? '') ?>"></label>
                        <label><span>Country</span><input name="country" value="<?= e($client['country'] ?? 'Zimbabwe') ?>"></label>
                        <label><span>Suburb</span><input name="suburb" value="<?= e($client['suburb'] ?? '') ?>"></label>
                        <label class="span-2"><span>Address</span><input name="address_line1" value="<?= e($client['address_line1'] ?? '') ?>"></label>
                        <label class="span-2"><span>Client Notes</span><textarea name="client_notes" rows="3"><?= e($client['client_notes'] ?? '') ?></textarea></label>
                        <div class="form-actions span-2">
                            <button class="button primary" type="submit">Save client</button>
                        </div>
                    </form>
                    </div>
                <?php endif; ?>
            </div>
            <div class="modal-actions">
                <?php if ($canManageClients): ?>
                    <a class="button primary" href="?page=policy_new&client_id=<?= e((string) $client['id']) ?>">Add policy</a>
                <?php endif; ?>
                <a class="button ghost" href="?page=policies&search=<?= e(urlencode((string) ($client['client_email'] ?: $client['client_phone'] ?: $client['client_name']))) ?>">View policies</a>
                <a class="button ghost" href="?page=documents&client_id=<?= e((string) $client['id']) ?>">View documents</a>
                <button class="button quiet" type="button" data-print-summary>Print profile</button>
                <?php if ($canManageClients && (int) $client['policy_count'] === 0): ?>
                    <form method="post" action="?action=client_delete&id=<?= e((string) $client['id']) ?>" data-confirm="Archive this client?">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="return_to" value="?page=clients">
                        <button class="button danger-button" type="submit">Archive client</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </dialog>
<?php endforeach; ?>

<?php if ($canManageClients): ?>
    <dialog class="modal" id="client-create-dialog">
        <form class="modal-card" method="post" action="?action=client_store" data-validate>
            <?= Csrf::field() ?>
            <input type="hidden" name="return_to" value="?page=clients">
            <div class="modal-header">
                <div>
                    <p class="eyebrow">Client management</p>
                    <h2>New client</h2>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button>
            </div>
            <div class="form-grid">
                <label><span>Client Name</span><input name="client_name" required></label>
                <label><span>Email</span><input type="email" name="client_email"></label>
                <label><span>Phone</span><input name="client_phone"></label>
                <label><span>Alternate Phone</span><input name="alternate_phone"></label>
                <label><span>Client Type</span><select name="client_type"><?php foreach ($clientTypes as $type): ?><option value="<?= e($type) ?>"><?= e($type) ?></option><?php endforeach; ?></select></label>
                <label><span>Segment</span><select name="segment"><?php foreach ($clientSegments as $segment): ?><option value="<?= e($segment) ?>"><?= e($segment) ?></option><?php endforeach; ?></select></label>
                <label><span>Status</span><select name="client_status"><?php foreach ($clientStatuses as $status): ?><option value="<?= e($status) ?>"><?= e($status) ?></option><?php endforeach; ?></select></label>
                <label><span>Preferred Contact</span><select name="preferred_contact"><?php foreach ($contactMethods as $method): ?><option value="<?= e($method) ?>"><?= e($method) ?></option><?php endforeach; ?></select></label>
                <label><span>National ID</span><input name="national_id"></label>
                <label><span>Tax Number</span><input name="tax_number"></label>
                <label><span>City</span><input name="city" value="Harare"></label>
                <label><span>Province</span><input name="province" value="Harare"></label>
                <label><span>Country</span><input name="country" value="Zimbabwe"></label>
                <label><span>Suburb</span><input name="suburb"></label>
                <label class="span-2"><span>Address</span><input name="address_line1"></label>
                <label class="span-2"><span>Client Notes</span><textarea name="client_notes" rows="3"></textarea></label>
            </div>
            <div class="modal-actions">
                <button class="button primary" type="submit">Create client</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>
