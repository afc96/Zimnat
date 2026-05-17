<?php

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Policy;

class PolicyService
{
    private const AUDITED_FIELDS = [
        'policy_number',
        'client_name',
        'client_email',
        'client_phone',
        'insurance_type',
        'premium_amount',
        'payment_frequency',
        'start_date',
        'renewal_date',
        'reminder_days',
        'status',
        'assigned_to',
        'notes',
    ];

    public function create(array $data, int $userId): int
    {
        return Database::transaction(function () use ($data, $userId): int {
            $id = Policy::create($data, $userId);
            ActivityLog::record($userId, 'policy_created', 'Created policy ' . $data['policy_number'], $id, [
                'policy_number' => strtoupper(trim((string) $data['policy_number'])),
                'client' => trim((string) ($data['client_name'] ?? '')),
            ]);
            return $id;
        });
    }

    public function update(int $id, array $data, int $userId): void
    {
        Database::transaction(function () use ($id, $data, $userId): void {
            $before = Policy::find($id) ?: [];
            Policy::update($id, $data, $userId);
            $after = Policy::find($id) ?: [];

            ActivityLog::record($userId, 'policy_updated', 'Updated policy ' . $data['policy_number'], $id, [
                'changes' => AuditService::diff($before, $after, self::AUDITED_FIELDS),
            ]);
        });
    }

    public function delete(int $id, int $userId): void
    {
        Database::transaction(function () use ($id, $userId): void {
            $policy = Policy::find($id);
            if (!$policy) {
                return;
            }

            Policy::delete($id, $userId);
            ActivityLog::record($userId, 'policy_deleted', 'Archived policy ' . $policy['policy_number'], $id, [
                'policy_number' => $policy['policy_number'],
                'client' => $policy['client_name'] ?? null,
            ]);
        });
    }
}
