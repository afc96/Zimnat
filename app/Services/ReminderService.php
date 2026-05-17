<?php

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Policy;

class ReminderService
{
    private const STATUSES = ['Pending', 'Contacted', 'Snoozed', 'Failed', 'Resolved'];

    public function update(int $policyId, string $status, ?string $note, ?string $snoozedUntil, int $userId): int
    {
        if (!in_array($status, self::STATUSES, true)) {
            return 0;
        }

        return Database::transaction(function () use ($policyId, $status, $note, $snoozedUntil, $userId): int {
            $policy = Policy::find($policyId);
            if (!$policy) {
                return 0;
            }

            $before = $policy;
            Policy::updateReminder($policyId, $status, $note, $snoozedUntil, $userId);
            $after = Policy::find($policyId) ?: [];
            ActivityLog::record($userId, 'reminder_' . strtolower(str_replace(' ', '_', $status)), $status . ' reminder for ' . $policy['policy_number'], $policyId, [
                'changes' => AuditService::diff($before, $after, ['reminder_status', 'reminder_note', 'reminder_last_contacted_at', 'reminder_snoozed_until']),
            ]);

            if ($status === 'Contacted') {
                $notifications = (new NotificationService())->sendRenewalContact($before, $note ?? '');
                ActivityLog::record($userId, 'notification_logged', 'Logged ' . count($notifications) . ' renewal notification channel(s) for ' . $policy['policy_number'], $policyId, [
                    'channels' => array_column($notifications, 'channel'),
                ]);
            }

            return 1;
        });
    }

    public function bulk(array $ids, string $status, ?string $note, ?string $snoozedUntil, int $userId): int
    {
        if (!in_array($status, self::STATUSES, true)) {
            return 0;
        }

        return Database::transaction(function () use ($ids, $status, $note, $snoozedUntil, $userId): int {
            $policies = Policy::findMany($ids);
            $count = Policy::bulkReminder($ids, $status, $note, $snoozedUntil, $userId);
            if ($status === 'Contacted' && $count > 0) {
                $notifier = new NotificationService();
                foreach ($policies as $policy) {
                    $notifier->sendRenewalContact($policy, $note ?? '');
                }
            }
            ActivityLog::record($userId, 'reminder_bulk_update', 'Updated ' . $count . ' selected reminders', null, [
                'ids' => array_values(array_map('intval', $ids)),
                'status' => $status,
                'count' => $count,
            ]);
            return $count;
        });
    }
}
