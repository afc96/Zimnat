<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Document;

$isEdit = !empty($policy['id']);
$canEdit = Auth::can('policy.update') || (!$isEdit && Auth::can('policy.create'));
$action = $isEdit ? '?action=policy_update&id=' . e((string) $policy['id']) : '?action=policy_store';
$staff = $staff ?? [];
$clients = $clients ?? [];
$clientTypes = ['Individual', 'Corporate', 'SME', 'Group'];
$clientSegments = ['Retail', 'VIP', 'SME', 'Corporate'];
$contactMethods = ['Phone', 'Email', 'SMS', 'WhatsApp'];
$clientStatuses = ['Active', 'Inactive', 'Watchlist'];
$selectedClientId = (string) ($policy['client_id'] ?? '');
$clientJson = htmlspecialchars(json_encode(array_column($clients, null, 'id'), JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow"><?= $isEdit ? 'Policy detail' : 'Create policy' ?></p>
        <h1 class="compact-title"><?= $isEdit ? e($policy['policy_number']) : 'New policy' ?></h1>
    </div>
    <div class="page-actions">
        <?php if ($isEdit): ?>
            <a class="button utility-button" href="?page=clients&search=<?= e(urlencode((string) ($policy['client_name'] ?? ''))) ?>">Client profile</a>
            <a class="button ghost" href="?page=documents&policy_id=<?= e((string) $policy['id']) ?>">Documents</a>
            <a class="button ghost" href="?page=reminders&policy_id=<?= e((string) $policy['id']) ?>&renewal=">Reminder</a>
        <?php endif; ?>
        <a class="button quiet" href="?page=policies">Back to policies</a>
    </div>
</section>

<section class="detail-grid">
    <form class="panel form-panel" method="post" action="<?= $action ?>" data-validate>
        <?= Csrf::field() ?>
        <?php if (!$canEdit): ?>
            <div class="notice">Your role is read-only. Policy data cannot be changed.</div>
        <?php endif; ?>

        <div class="form-grid">
            <div class="form-section-title span-2">
                <strong>Client</strong>
                <span>Select an existing client or capture the profile details for a new one.</span>
            </div>
            <label class="span-2">
                <span>Client</span>
                <select name="client_id" data-client-selector data-clients="<?= $clientJson ?>" <?= !$canEdit ? 'disabled' : '' ?>>
                    <option value="">Create a new client from the details below</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= e((string) $client['id']) ?>" <?= $selectedClientId === (string) $client['id'] ? 'selected' : '' ?>>
                            <?= e($client['client_name']) ?> · <?= e($client['client_code']) ?><?= $client['client_email'] ? ' · ' . e($client['client_email']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="field-hint">Select an existing client to avoid re-entering profile details, or leave blank to create a new client.</small>
            </label>
            <label>
                <span>Policy Number</span>
                <input name="policy_number" value="<?= e($policy['policy_number'] ?? '') ?>" required <?= !$canEdit ? 'disabled' : '' ?>>
                <?php if (isset($errors['policy_number'])): ?><small class="error"><?= e($errors['policy_number']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Client Name</span>
                <input name="client_name" value="<?= e($policy['client_name'] ?? '') ?>" required <?= !$canEdit ? 'disabled' : '' ?>>
                <?php if (isset($errors['client_name'])): ?><small class="error"><?= e($errors['client_name']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Client Email</span>
                <input type="email" name="client_email" value="<?= e($policy['client_email'] ?? '') ?>" placeholder="client@example.com" <?= !$canEdit ? 'disabled' : '' ?>>
                <?php if (isset($errors['client_email'])): ?><small class="error"><?= e($errors['client_email']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Client Phone</span>
                <input type="tel" name="client_phone" value="<?= e($policy['client_phone'] ?? '') ?>" placeholder="+263 ..." <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <label>
                <span>Client Type</span>
                <select name="client_type" <?= !$canEdit ? 'disabled' : '' ?>>
                    <?php foreach ($clientTypes as $type): ?>
                        <option value="<?= e($type) ?>" <?= ($policy['client_type'] ?? 'Individual') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Client Segment</span>
                <select name="segment" <?= !$canEdit ? 'disabled' : '' ?>>
                    <?php foreach ($clientSegments as $segment): ?>
                        <option value="<?= e($segment) ?>" <?= ($policy['segment'] ?? 'Retail') === $segment ? 'selected' : '' ?>><?= e($segment) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Preferred Contact</span>
                <select name="preferred_contact" <?= !$canEdit ? 'disabled' : '' ?>>
                    <?php foreach ($contactMethods as $method): ?>
                        <option value="<?= e($method) ?>" <?= ($policy['preferred_contact'] ?? 'Phone') === $method ? 'selected' : '' ?>><?= e($method) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Client Status</span>
                <select name="client_status" <?= !$canEdit ? 'disabled' : '' ?>>
                    <?php foreach ($clientStatuses as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($policy['client_status'] ?? 'Active') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                <span>Alternate Phone</span>
                <input type="tel" name="alternate_phone" value="<?= e($policy['alternate_phone'] ?? '') ?>" placeholder="+263 ..." <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <label>
                <span>National ID</span>
                <input name="national_id" value="<?= e($policy['national_id'] ?? '') ?>" placeholder="63-000000-X-00" <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <label>
                <span>Tax Number</span>
                <input name="tax_number" value="<?= e($policy['tax_number'] ?? '') ?>" placeholder="Optional" <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <label>
                <span>City</span>
                <input name="city" value="<?= e($policy['city'] ?? 'Harare') ?>" <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <label>
                <span>Province</span>
                <input name="province" value="<?= e($policy['province'] ?? 'Harare') ?>" <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <label>
                <span>Country</span>
                <input name="country" value="<?= e($policy['country'] ?? 'Zimbabwe') ?>" <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <label>
                <span>Suburb</span>
                <input name="suburb" value="<?= e($policy['suburb'] ?? '') ?>" <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <label>
                <span>Address</span>
                <input name="address_line1" value="<?= e($policy['address_line1'] ?? '') ?>" <?= !$canEdit ? 'disabled' : '' ?>>
            </label>
            <div class="form-section-title span-2">
                <strong>Policy</strong>
                <span>Core cover, premium, and renewal information.</span>
            </div>
            <label>
                <span>Insurance Type</span>
                <select name="insurance_type" required <?= !$canEdit ? 'disabled' : '' ?>>
                    <?php foreach (['Life Assurance', 'Funeral Cover', 'Education Plan', 'Retirement Annuity', 'Group Life'] as $type): ?>
                        <option value="<?= e($type) ?>" <?= ($policy['insurance_type'] ?? '') === $type ? 'selected' : '' ?>><?= e($type) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['insurance_type'])): ?><small class="error"><?= e($errors['insurance_type']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Premium Amount</span>
                <input type="number" step="0.01" min="0" name="premium_amount" value="<?= e($policy['premium_amount'] ?? '') ?>" required <?= !$canEdit ? 'disabled' : '' ?>>
                <?php if (isset($errors['premium_amount'])): ?><small class="error"><?= e($errors['premium_amount']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Payment Frequency</span>
                <select name="payment_frequency" required <?= !$canEdit ? 'disabled' : '' ?>>
                    <?php foreach (['Monthly', 'Quarterly', 'Annually'] as $frequency): ?>
                        <option value="<?= e($frequency) ?>" <?= ($policy['payment_frequency'] ?? 'Monthly') === $frequency ? 'selected' : '' ?>><?= e($frequency) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['payment_frequency'])): ?><small class="error"><?= e($errors['payment_frequency']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Start Date</span>
                <input type="date" name="start_date" value="<?= e($policy['start_date'] ?? '') ?>" required <?= !$canEdit ? 'disabled' : '' ?>>
                <?php if (isset($errors['start_date'])): ?><small class="error"><?= e($errors['start_date']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Renewal Date</span>
                <input type="date" name="renewal_date" value="<?= e($policy['renewal_date'] ?? '') ?>" required <?= !$canEdit ? 'disabled' : '' ?>>
                <?php if (isset($errors['renewal_date'])): ?><small class="error"><?= e($errors['renewal_date']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Reminder Lead Time</span>
                <input type="number" min="1" max="365" name="reminder_days" value="<?= e($policy['reminder_days'] ?? '30') ?>" required <?= !$canEdit ? 'disabled' : '' ?>>
                <?php if (isset($errors['reminder_days'])): ?><small class="error"><?= e($errors['reminder_days']) ?></small><?php endif; ?>
            </label>
            <div class="form-section-title span-2">
                <strong>Ownership</strong>
                <span>Assign responsibility and record operational notes.</span>
            </div>
            <label>
                <span>Policy Status</span>
                <select name="status" required <?= !$canEdit ? 'disabled' : '' ?>>
                    <?php foreach (['Active', 'Pending Renewal', 'Expired'] as $status): ?>
                        <option value="<?= e($status) ?>" <?= ($policy['status'] ?? 'Active') === $status ? 'selected' : '' ?>><?= e($status) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (isset($errors['status'])): ?><small class="error"><?= e($errors['status']) ?></small><?php endif; ?>
            </label>
            <label>
                <span>Assigned Officer</span>
                <select name="assigned_to" <?= !$canEdit ? 'disabled' : '' ?>>
                    <option value="">Unassigned</option>
                    <?php foreach ($staff as $member): ?>
                        <option value="<?= e((string) $member['id']) ?>" <?= (string) ($policy['assigned_to'] ?? '') === (string) $member['id'] ? 'selected' : '' ?>>
                            <?= e($member['name']) ?> · <?= e(role_label($member['role'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="span-2">
                <span>Notes</span>
                <textarea name="notes" rows="4" <?= !$canEdit ? 'disabled' : '' ?>><?= e($policy['notes'] ?? '') ?></textarea>
            </label>
            <label class="span-2">
                <span>Client Notes</span>
                <textarea name="client_notes" rows="3" <?= !$canEdit ? 'disabled' : '' ?>><?= e($policy['client_notes'] ?? '') ?></textarea>
            </label>
        </div>

        <?php if ($canEdit): ?>
            <div class="form-actions">
                <button class="button primary" type="submit"><?= $isEdit ? 'Save changes' : 'Create policy' ?></button>
            </div>
        <?php endif; ?>
    </form>

    <aside class="panel upload-panel">
        <div class="panel-header">
            <div>
                <h2>Supporting Documents</h2>
                <p>JPG, PNG, or PDF. Maximum 5 MB.</p>
            </div>
        </div>

        <?php if (!$isEdit): ?>
            <div class="notice">Create the policy before uploading documents.</div>
        <?php elseif ($canEdit): ?>
            <form class="dropzone" method="post" enctype="multipart/form-data" action="?action=document_upload&policy_id=<?= e((string) $policy['id']) ?>" data-dropzone>
                <?= Csrf::field() ?>
                <input id="document" type="file" name="document" accept=".jpg,.jpeg,.png,.pdf" required>
                <label class="document-type-field">
                    <span>Document Type</span>
                    <select name="document_type" required>
                        <?php foreach (array_merge(array_column(Document::types(), 'name'), ['Other']) as $type): ?>
                            <option value="<?= e($type) ?>"><?= e($type) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="drop-target" for="document">
                    <span>⇧</span>
                    <strong>Select a document to upload</strong>
                    <small>or drag and drop it here</small>
                </label>
                <div class="selected-file" data-selected-file hidden>
                    <span class="file-icon">FILE</span>
                    <div>
                        <strong data-selected-file-name>No file selected</strong>
                        <small data-selected-file-meta>Choose a JPG, PNG, or PDF up to 5 MB.</small>
                    </div>
                </div>
                <button class="button primary full" type="submit" data-upload-submit disabled>Upload document</button>
            </form>
        <?php endif; ?>

        <div class="document-list">
            <?php if ($isEdit && $checklist): ?>
                <div class="checklist">
                    <?php foreach ($checklist as $item): ?>
                        <span class="checklist-item <?= $item['uploaded'] ? 'complete' : 'missing' ?>">
                            <span><?= $item['uploaded'] ? '✓' : 'REQ' ?></span>
                            <strong><?= e($item['type']) ?></strong>
                            <small><?= $item['uploaded'] ? 'Uploaded' : 'Required' ?></small>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?php foreach ($documents as $document): ?>
                <?php $previewable = in_array($document['mime_type'], ['application/pdf', 'image/jpeg', 'image/png'], true); ?>
                <div class="document-row">
                    <span class="file-icon"><?= str_contains($document['mime_type'], 'pdf') ? 'PDF' : 'IMG' ?></span>
                    <div class="document-main">
                        <strong><?= e($document['original_name']) ?></strong>
                        <small><?= e($document['document_type']) ?> · <?= e(number_format($document['size_bytes'] / 1024, 1)) ?> KB · <?= e($document['created_at']) ?></small>
                    </div>
                    <div class="document-actions">
                        <?php if ($previewable): ?>
                            <button class="button small" type="button" data-dialog-open="document-preview-<?= e((string) $document['id']) ?>">View</button>
                        <?php endif; ?>
                        <a class="icon-button" href="?action=document_download&id=<?= e((string) $document['id']) ?>" aria-label="Download document">↓</a>
                        <?php if ($canEdit): ?>
                            <form method="post" action="?action=document_delete&id=<?= e((string) $document['id']) ?>" data-confirm="Delete this document?">
                                <?= Csrf::field() ?>
                                <button class="icon-button danger-text" type="submit" aria-label="Delete document">×</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php if ($previewable): ?>
                    <dialog class="modal document-preview-modal" id="document-preview-<?= e((string) $document['id']) ?>">
                        <div class="modal-card preview-card">
                            <div class="modal-header">
                                <div>
                                    <p class="eyebrow"><?= e($document['document_type']) ?></p>
                                    <h2><?= e($document['original_name']) ?></h2>
                                </div>
                                <button class="icon-button" type="button" data-dialog-close aria-label="Close preview">×</button>
                            </div>
                            <div class="document-preview-frame">
                                <?php if (str_starts_with($document['mime_type'], 'image/')): ?>
                                    <img src="?action=document_preview&id=<?= e((string) $document['id']) ?>" alt="<?= e($document['original_name']) ?>">
                                <?php else: ?>
                                    <iframe src="?action=document_preview&id=<?= e((string) $document['id']) ?>" title="<?= e($document['original_name']) ?>"></iframe>
                                <?php endif; ?>
                            </div>
                            <div class="modal-actions">
                                <a class="button ghost" href="?action=document_download&id=<?= e((string) $document['id']) ?>">Download</a>
                            </div>
                        </div>
                    </dialog>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if ($isEdit && !$documents): ?>
                <div class="empty-card">No supporting documents uploaded yet.</div>
            <?php endif; ?>
        </div>

        <?php if ($isEdit): ?>
            <div class="audit-card">
                <h2>Audit trail</h2>
                <div class="detail-list single-column">
                    <div><span>Created By</span><strong><?= e($policy['created_name'] ?? 'Unknown') ?> · <?= e($policy['created_at'] ?? '') ?></strong></div>
                    <div><span>Last Edited</span><strong><?= e($policy['updated_name'] ?? 'Unknown') ?> · <?= e($policy['updated_at'] ?? '') ?></strong></div>
                    <div><span>Last Reminder Sent</span><strong><?= e($policy['reminder_last_contacted_at'] ?? 'Not yet sent') ?></strong></div>
                    <div><span>Reminder State</span><strong><?= e($policy['reminder_status'] ?? 'Pending') ?></strong></div>
                </div>
            </div>
            <div class="audit-card">
                <h2>Policy timeline</h2>
                <div class="timeline-list">
                    <div>
                        <span class="timeline-dot"></span>
                        <p><strong>Policy created</strong><small><?= e(($policy['created_name'] ?? 'Unknown') . ' · ' . ($policy['created_at'] ?? '')) ?></small></p>
                    </div>
                    <?php foreach ($timeline as $event): ?>
                        <div>
                            <span class="timeline-dot"></span>
                            <p><strong><?= e($event['description']) ?></strong><small><?= e(($event['name'] ?? 'System') . ' · ' . $event['created_at']) ?></small></p>
                        </div>
                    <?php endforeach; ?>
                    <div>
                        <span class="timeline-dot"></span>
                        <p><strong>Last edited</strong><small><?= e(($policy['updated_name'] ?? 'Unknown') . ' · ' . ($policy['updated_at'] ?? '')) ?></small></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </aside>
</section>
