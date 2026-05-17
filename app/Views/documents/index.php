<?php

use App\Core\Auth;
use App\Core\Csrf;

function document_sort_link(string $label, string $column, array $filters): string
{
    $active = ($filters['sort'] ?? '') === $column;
    $direction = (($filters['sort'] ?? '') === $column && ($filters['direction'] ?? 'DESC') === 'ASC') ? 'DESC' : 'ASC';
    $params = array_filter([
        'page' => 'documents',
        'search' => $filters['search'] ?? '',
        'document_type' => $filters['document_type'] ?? '',
        'file_type' => $filters['file_type'] ?? '',
        'policy_status' => $filters['policy_status'] ?? '',
        'uploaded_from' => $filters['uploaded_from'] ?? '',
        'uploaded_to' => $filters['uploaded_to'] ?? '',
        'client_id' => $filters['client_id'] ?? '',
        'policy_id' => $filters['policy_id'] ?? '',
        'sort' => $column,
        'direction' => $direction,
    ], fn ($value) => $value !== '');
    return '<a class="sort-link ' . ($active ? 'active' : '') . '" href="?' . http_build_query($params) . '">' . e($label) . sort_icon($active, $filters['direction'] ?? 'DESC') . '</a>';
}

$canEdit = Auth::can('document.delete');
$documentTypes = $documentTypes ?? [];
$hasActiveFilters = trim((string) ($filters['search'] ?? $search ?? '')) !== ''
    || ($filters['document_type'] ?? '') !== ''
    || ($filters['file_type'] ?? '') !== ''
    || ($filters['policy_status'] ?? '') !== ''
    || ($filters['uploaded_from'] ?? '') !== ''
    || ($filters['uploaded_to'] ?? '') !== ''
    || !empty($filters['client_id'])
    || !empty($filters['policy_id']);
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Document register</p>
        <h1 class="sr-only">Documents</h1>
    </div>
    <?php $exportParams = array_filter(['action' => 'documents_export', 'search' => $filters['search'] ?? '', 'document_type' => $filters['document_type'] ?? '', 'file_type' => $filters['file_type'] ?? '', 'policy_status' => $filters['policy_status'] ?? '', 'uploaded_from' => $filters['uploaded_from'] ?? '', 'uploaded_to' => $filters['uploaded_to'] ?? '', 'client_id' => $filters['client_id'] ?? '', 'policy_id' => $filters['policy_id'] ?? '', 'sort' => $filters['sort'] ?? 'created_at', 'direction' => $filters['direction'] ?? 'DESC'], fn ($value) => $value !== '' && $value !== 0); ?>
    <div class="page-actions">
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Export</button>
            <div class="filter-popover">
                <a class="menu-item" href="?<?= e(http_build_query($exportParams)) ?>">Current view CSV</a>
                <a class="menu-item" href="?action=documents_export">All documents CSV</a>
                <button class="menu-item" type="button" data-print-page>Print current view</button>
            </div>
        </div>
    </div>
</section>

<section class="panel">
    <form class="toolbar compact-toolbar" method="get" data-server-filter>
        <input type="hidden" name="page" value="documents">
        <?php if (!empty($filters['client_id'])): ?><input type="hidden" name="client_id" value="<?= e((string) $filters['client_id']) ?>"><?php endif; ?>
        <?php if (!empty($filters['policy_id'])): ?><input type="hidden" name="policy_id" value="<?= e((string) $filters['policy_id']) ?>"><?php endif; ?>
        <label class="search-field">
            <span class="sr-only">Search documents</span>
            <input type="search" name="search" placeholder="Search file, policy ID, client..." value="<?= e($filters['search'] ?? $search) ?>">
        </label>
        <div class="filter-menu">
            <button class="button utility-button" type="button" data-filter-menu aria-expanded="false">Filters</button>
            <div class="filter-popover" data-filter-popover>
                <label>
                    <span>Document Type</span>
                    <select name="document_type" aria-label="Filter by document type">
                        <option value="">Any document type</option>
                        <?php foreach ($documentTypes as $type): ?>
                            <option value="<?= e($type['name']) ?>" <?= ($filters['document_type'] ?? '') === $type['name'] ? 'selected' : '' ?>><?= e($type['name']) ?></option>
                        <?php endforeach; ?>
                        <option value="Other" <?= ($filters['document_type'] ?? '') === 'Other' ? 'selected' : '' ?>>Other</option>
                    </select>
                </label>
                <label>
                    <span>File Type</span>
                    <select name="file_type" aria-label="Filter by file type">
                        <option value="">Any file type</option>
                        <option value="pdf" <?= ($filters['file_type'] ?? '') === 'pdf' ? 'selected' : '' ?>>PDF</option>
                        <option value="image" <?= ($filters['file_type'] ?? '') === 'image' ? 'selected' : '' ?>>Image</option>
                    </select>
                </label>
                <label>
                    <span>Policy Status</span>
                    <select name="policy_status" aria-label="Filter by policy status">
                        <option value="">Any policy status</option>
                        <?php foreach (['Active', 'Pending Renewal', 'Expired'] as $status): ?>
                            <option value="<?= e($status) ?>" <?= ($filters['policy_status'] ?? '') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Uploaded From</span>
                    <input type="date" name="uploaded_from" value="<?= e($filters['uploaded_from'] ?? '') ?>">
                </label>
                <label>
                    <span>Uploaded To</span>
                    <input type="date" name="uploaded_to" value="<?= e($filters['uploaded_to'] ?? '') ?>">
                </label>
                <div class="filter-actions">
                    <button class="button primary" type="submit">Apply</button>
                    <?php if ($hasActiveFilters): ?><a class="button quiet" href="?page=documents">Reset</a><?php endif; ?>
                </div>
            </div>
        </div>
    </form>

    <div class="table-wrap">
        <table class="document-register-table">
            <thead>
            <tr>
                <th><?= document_sort_link('File', 'original_name', $filters ?? []) ?></th>
                <th><?= document_sort_link('Type', 'document_type', $filters ?? []) ?></th>
                <th><?= document_sort_link('Policy', 'policy_number', $filters ?? []) ?></th>
                <th><?= document_sort_link('Client', 'client_name', $filters ?? []) ?></th>
                <th><?= document_sort_link('Policy Status', 'policy_status', $filters ?? []) ?></th>
                <th><?= document_sort_link('Uploaded By', 'uploaded_by', $filters ?? []) ?></th>
                <th><?= document_sort_link('Size', 'size_bytes', $filters ?? []) ?></th>
                <th><?= document_sort_link('Date', 'created_at', $filters ?? []) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result['items'] as $document): ?>
                <tr class="clickable-row" tabindex="0" data-dialog-open="document-dialog-<?= e((string) $document['id']) ?>">
                    <td class="document-file-cell">
                        <strong class="document-file-name" title="<?= e($document['original_name']) ?>"><?= e($document['original_name']) ?></strong>
                        <small class="document-file-meta" title="<?= e($document['mime_type']) ?>"><?= e($document['mime_type']) ?></small>
                    </td>
                    <td><span class="pill"><?= e($document['document_type']) ?></span></td>
                    <td><?= e($document['policy_number']) ?></td>
                    <td><?= e($document['client_name']) ?></td>
                    <td><span class="status-dot <?= e(strtolower(str_replace(' ', '-', $document['policy_status']))) ?>"><?= e($document['policy_status']) ?></span></td>
                    <td><?= e($document['uploaded_by_name'] ?? 'Unknown') ?></td>
                    <td><?= e(number_format($document['size_bytes'] / 1024, 1)) ?> KB</td>
                    <td><?= e($document['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['items']): ?>
                <tr>
                    <td colspan="8" class="empty-cell">
                        <div class="empty-state">
                            <strong>No documents match the current search or filters.</strong>
                            <span>Reset filters to view every uploaded file, or open a policy to upload supporting documents.</span>
                            <span class="empty-actions">
                                <?php if ($hasActiveFilters): ?><a class="button quiet small" href="?page=documents">Reset filters</a><?php endif; ?>
                                <a class="button ghost small" href="?page=policies&docs=missing">Find policies missing documents</a>
                            </span>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="pagination">
        <span>Showing <?= e((string) (count($result['items']) ? (($result['page'] - 1) * $result['per_page'] + 1) : 0)) ?>-<?= e((string) min($result['total'], $result['page'] * $result['per_page'])) ?> of <?= e((string) $result['total']) ?></span>
        <?php $base = ['page' => 'documents', 'search' => $filters['search'], 'document_type' => $filters['document_type'], 'file_type' => $filters['file_type'], 'policy_status' => $filters['policy_status'], 'uploaded_from' => $filters['uploaded_from'], 'uploaded_to' => $filters['uploaded_to'], 'client_id' => $filters['client_id'] ?? '', 'policy_id' => $filters['policy_id'] ?? '', 'sort' => $filters['sort'], 'direction' => $filters['direction']]; ?>
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

<?php foreach ($result['items'] as $document): ?>
    <?php $previewable = in_array($document['mime_type'], ['application/pdf', 'image/jpeg', 'image/png'], true); ?>
    <dialog class="modal" id="document-dialog-<?= e((string) $document['id']) ?>">
        <div class="modal-card">
            <div class="modal-header">
                <div>
                    <p class="eyebrow"><?= e($document['policy_number']) ?></p>
                    <h2>Document details</h2>
                    <p class="modal-subtitle" title="<?= e($document['original_name']) ?>"><?= e($document['original_name']) ?></p>
                </div>
                <button class="icon-button" type="button" data-dialog-close aria-label="Close dialog">×</button>
            </div>
            <div class="detail-list">
                <div class="span-2"><span>File Name</span><strong><?= e($document['original_name']) ?></strong></div>
                <div><span>Client</span><strong><?= e($document['client_name']) ?></strong></div>
                <div><span>Document Type</span><strong><?= e($document['document_type']) ?></strong></div>
                <div><span>Policy Status</span><strong><?= e($document['policy_status']) ?></strong></div>
                <div><span>MIME Type</span><strong><?= e($document['mime_type']) ?></strong></div>
                <div><span>Size</span><strong><?= e(number_format($document['size_bytes'] / 1024, 1)) ?> KB</strong></div>
                <div><span>Uploaded By</span><strong><?= e($document['uploaded_by_name'] ?? 'Unknown') ?></strong></div>
                <div class="span-2"><span>Uploaded At</span><strong><?= e($document['created_at']) ?></strong></div>
            </div>
            <div class="modal-actions">
                <a class="button ghost" href="?page=policy_edit&id=<?= e((string) $document['policy_id']) ?>">Open policy</a>
                <a class="button ghost" href="?page=clients&search=<?= e(urlencode($document['client_name'])) ?>">Open client</a>
                <?php if ($previewable): ?>
                    <a class="button ghost" href="?action=document_preview&id=<?= e((string) $document['id']) ?>" target="_blank" rel="noopener">Preview</a>
                <?php endif; ?>
                <a class="button ghost" href="?action=document_download&id=<?= e((string) $document['id']) ?>">Download document</a>
                <button class="button quiet" type="button" data-print-summary>Print details</button>
                <?php if ($canEdit): ?>
                    <form method="post" action="?action=document_delete&id=<?= e((string) $document['id']) ?>" data-confirm="Delete this document?">
                        <?= Csrf::field() ?>
                        <button class="button danger-button" type="submit">Delete document</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </dialog>
<?php endforeach; ?>
