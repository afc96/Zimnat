<?php

use App\Core\Auth;

$docTotal = max(1, $stats['total']);
$docCompliance = (int) round(($stats['policies_with_documents'] / $docTotal) * 100);
$isOperator = Auth::can('reminder.manage');
?>

<section class="page-heading">
    <div>
        <p class="eyebrow">Renewal operations</p>
        <h1 class="sr-only">Dashboard</h1>
        <small class="dashboard-stamp">Last updated <?= e((new DateTimeImmutable())->format('Y-m-d H:i')) ?></small>
    </div>
    <?php if (Auth::can('policy.create')): ?>
        <div class="page-actions">
            <a class="button primary" href="?page=policy_new">+ New Policy</a>
        </div>
    <?php endif; ?>
</section>

<section class="kpi-grid" aria-label="Policy summary">
    <a class="metric-card" href="?page=policies&status=Active">
        <span class="metric-icon success">✓</span>
        <h2>Active Policies</h2>
        <strong><?= e((string) $stats['active']) ?></strong>
        <p>Currently in good standing</p>
    </a>
    <a class="metric-card" href="<?= Auth::can('reminder.manage') ? '?page=reminders&renewal=soon' : '?page=policies&renewal=soon' ?>">
        <span class="metric-icon danger">!</span>
        <h2>Expiring Soon</h2>
        <strong><?= e((string) $stats['soon']) ?></strong>
        <p>Renewal date within 30 days</p>
    </a>
    <a class="metric-card" href="?page=policies&docs=missing">
        <span class="metric-icon warning">△</span>
        <h2>Missing Documents</h2>
        <strong><?= e((string) $stats['missing_documents']) ?></strong>
        <p>Policies without uploads</p>
    </a>
    <a class="metric-card" href="<?= Auth::can('reminder.manage') ? '?page=reminders&renewal=expired' : '?page=policies&renewal=expired' ?>">
        <span class="metric-icon muted">i</span>
        <h2>Expired Policies</h2>
        <strong><?= e((string) $stats['expired']) ?></strong>
        <p>Requires staff follow-up</p>
    </a>
</section>

<section class="dashboard-grid">
    <article class="panel main-panel">
        <div class="panel-header">
            <div>
                <h2>Last policies</h2>
                <p>Newest changes across the policy register.</p>
            </div>
            <a class="button quiet small" href="?page=policies">View all</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Client</th>
                    <th>Policy Number</th>
                    <th>Renewal</th>
                    <th>Type</th>
                    <th>Documents</th>
                    <th>Status</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($latestPolicies as $policy): ?>
                    <?php $badge = renewal_badge($policy['renewal_date'], $policy['status']); ?>
                    <tr>
                        <td><a href="?page=clients&search=<?= e(urlencode($policy['client_name'])) ?>"><strong><?= e($policy['client_name']) ?></strong></a></td>
                        <td><a href="?page=policy_edit&id=<?= e((string) $policy['id']) ?>"><?= e($policy['policy_number']) ?></a></td>
                        <td><span class="badge <?= e($badge['tone']) ?>"><?= e($badge['label']) ?></span></td>
                        <td><?= e($policy['insurance_type']) ?></td>
                        <td><?= e((string) $policy['document_count']) ?></td>
                        <td><span class="status-dot <?= e(strtolower(str_replace(' ', '-', $policy['status']))) ?>"><?= e($policy['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$latestPolicies): ?>
                    <tr><td colspan="6" class="empty-cell">No policy activity yet. Create the first policy to populate this register.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </article>

    <?php if (!empty($showAdminInsights)): ?>
        <aside class="panel side-panel">
            <div class="panel-header">
                <div>
                    <h2>Performance Review</h2>
                    <p>Officer workload and renewal pressure.</p>
                </div>
                <?php if (Auth::can('settings.manage')): ?>
                    <a class="button quiet small" href="?page=settings&tab=users">Manage</a>
                <?php endif; ?>
            </div>
            <div class="stack-list">
                <?php foreach ($officerLoad as $officer): ?>
                    <div class="person-row">
                        <span class="avatar"><?= e(strtoupper(substr($officer['name'], 0, 1))) ?></span>
                        <span class="pill role-pill <?= e(role_tone($officer['role'])) ?>"><?= e(role_label($officer['role'])) ?></span>
                        <div class="person-main">
                            <strong><?= e($officer['name']) ?></strong>
                            <small><?= e((string) $officer['policy_count']) ?> policies</small>
                        </div>
                        <div class="person-meta">
                            <span>Need Renewal</span>
                            <small><?= e((string) $officer['renewal_count']) ?> policies</small>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (!$officerLoad): ?>
                    <div class="empty-card">No active officers available for workload review.</div>
                <?php endif; ?>
            </div>
        </aside>
    <?php else: ?>
        <aside class="panel side-panel focus-panel">
            <div class="panel-header">
                <div>
                    <h2><?= $isOperator ? 'Renewal focus' : 'Policy overview' ?></h2>
                    <p><?= $isOperator ? 'Operational work that needs attention now.' : 'Read-only snapshot of policy health.' ?></p>
                </div>
                <a class="button quiet small" href="<?= $isOperator ? '?page=my_tasks' : '?page=policies&renewal=soon' ?>"><?= $isOperator ? 'Open tasks' : 'Review' ?></a>
            </div>
            <div class="focus-list">
                <a href="<?= $isOperator ? '?page=reminders&renewal=soon' : '?page=policies&renewal=soon' ?>">
                    <span class="focus-icon warning">!</span>
                    <span><strong><?= e((string) $stats['soon']) ?></strong><small>Renewals in the next 30 days</small></span>
                </a>
                <a href="?page=policies&docs=missing">
                    <span class="focus-icon danger">△</span>
                    <span><strong><?= e((string) $stats['missing_documents']) ?></strong><small>Policies missing supporting documents</small></span>
                </a>
                <a href="<?= $isOperator ? '?page=reminders&renewal=expired' : '?page=policies&renewal=expired' ?>">
                    <span class="focus-icon muted">i</span>
                    <span><strong><?= e((string) $stats['expired']) ?></strong><small>Expired policies requiring follow-up</small></span>
                </a>
            </div>
        </aside>
    <?php endif; ?>

    <article class="panel compliance-panel">
        <div class="panel-header compliance-head">
            <div>
                <h2>Document Compliance</h2>
                <p>Policies with at least one supporting file.</p>
            </div>
            <div class="compliance-ring" style="--value: <?= e((string) $docCompliance) ?>" aria-label="Document compliance <?= e((string) $docCompliance) ?> percent">
                <strong><?= e((string) $docCompliance) ?>%</strong>
            </div>
        </div>
        <div class="compliance-bar" aria-hidden="true">
            <span style="width: <?= e((string) $docCompliance) ?>%"></span>
        </div>
        <div class="mini-stats">
            <a href="?page=documents"><strong><?= e((string) $stats['policies_with_documents']) ?></strong><span>Policies with docs</span></a>
            <a href="?page=policies&docs=missing"><strong><?= e((string) $stats['missing_documents']) ?></strong><span>Missing docs</span></a>
            <a href="<?= $isOperator ? '?page=reminders&status=Pending+Renewal&renewal=' : '?page=policies&status=Pending+Renewal' ?>"><strong><?= e((string) $stats['pending']) ?></strong><span>Pending renewal</span></a>
        </div>
    </article>

    <article class="queue-panel">
        <div class="panel-header">
            <div>
                <h2>Renewal Queue</h2>
                <p>Priority work for the next 30 days.</p>
            </div>
            <a class="glass-link" href="<?= Auth::can('reminder.manage') ? '?page=reminders' : '?page=policies&renewal=soon' ?>">Open</a>
        </div>
        <div class="queue-grid">
            <span><strong>+<?= e((string) $stats['soon']) ?></strong> Renewal calls</span>
            <span><strong>+<?= e((string) $stats['missing_documents']) ?></strong> Document checks</span>
            <span><strong><?= e((string) $stats['expired']) ?></strong> Overdue policies</span>
            <span><strong><?= e((string) $stats['total']) ?></strong> Total policies</span>
        </div>
        <p class="queue-note">Prioritize clients nearing renewal and policies missing supporting documents.</p>
    </article>

    <?php if (!empty($showAdminInsights)): ?>
        <article class="panel activity-panel">
            <div class="panel-header">
                <div>
                    <h2>System Activity</h2>
                    <p>Latest changes across users, policies, documents, and reminders.</p>
                </div>
                <?php if (Auth::can('settings.manage')): ?>
                    <a class="button quiet small" href="?page=settings&tab=audit">View audit</a>
                <?php endif; ?>
            </div>
            <div class="activity-list">
                <?php foreach ($activity as $item): ?>
                    <div class="activity-item">
                        <span class="timeline-dot"></span>
                        <div>
                            <p><strong><?= e($item['name'] ?? 'System') ?></strong> <?= e($item['description']) ?></p>
                            <small><?= e($item['created_at']) ?></small>
                        </div>
                        <span class="pill"><?= e($item['action']) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$activity): ?>
                    <div class="empty-card">No activity recorded yet.</div>
                <?php endif; ?>
            </div>
        </article>
    <?php endif; ?>
</section>
