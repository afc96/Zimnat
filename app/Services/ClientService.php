<?php

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Client;

class ClientService
{
    private const AUDITED_FIELDS = [
        'client_name',
        'client_email',
        'client_phone',
        'alternate_phone',
        'client_type',
        'segment',
        'client_status',
        'preferred_contact',
        'national_id',
        'tax_number',
        'city',
        'province',
        'country',
        'address_line1',
        'suburb',
        'client_notes',
    ];

    public function create(array $data, int $userId): int
    {
        return Database::transaction(function () use ($data, $userId): int {
            $id = Client::create($data);
            $client = Client::find($id) ?: [];
            ActivityLog::record($userId, 'client_created', 'Created client ' . ($client['client_name'] ?? $id), null, [
                'client_id' => $id,
                'client_code' => $client['client_code'] ?? null,
            ]);
            return $id;
        });
    }

    public function update(int $id, array $data, int $userId): void
    {
        Database::transaction(function () use ($id, $data, $userId): void {
            $before = Client::find($id) ?: [];
            Client::update($id, $data);
            $after = Client::find($id) ?: [];
            ActivityLog::record($userId, 'client_updated', 'Updated client ' . ($after['client_name'] ?? $id), null, [
                'client_id' => $id,
                'changes' => AuditService::diff($before, $after, self::AUDITED_FIELDS),
            ]);
        });
    }

    public function delete(int $id, int $userId): bool
    {
        return Database::transaction(function () use ($id, $userId): bool {
            $client = Client::find($id);
            if (!$client) {
                return false;
            }

            $deleted = Client::delete($id);
            if ($deleted) {
                ActivityLog::record($userId, 'client_deleted', 'Archived client ' . $client['client_name'], null, [
                    'client_id' => $id,
                    'client_code' => $client['client_code'] ?? null,
                ]);
            }
            return $deleted;
        });
    }
}
