<?php

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\User;

class UserService
{
    private const AUDITED_FIELDS = ['name', 'email', 'role', 'is_active'];

    public function create(array $data, int $actorId): int
    {
        return Database::transaction(function () use ($data, $actorId): int {
            $id = User::create($data);
            ActivityLog::record($actorId, 'user_created', 'Created user ' . $data['email'], null, [
                'user_id' => $id,
                'email' => strtolower(trim((string) $data['email'])),
                'role' => $data['role'],
            ]);
            return $id;
        });
    }

    public function update(int $id, array $data, int $actorId): void
    {
        Database::transaction(function () use ($id, $data, $actorId): void {
            $before = User::find($id) ?: [];
            User::update($id, $data);
            $after = User::find($id) ?: [];
            ActivityLog::record($actorId, 'user_updated', 'Updated user ' . $data['email'], null, [
                'user_id' => $id,
                'changes' => AuditService::diff($before, $after, self::AUDITED_FIELDS),
                'password_changed' => !empty($data['password']),
            ]);
        });
    }

    public function setActive(int $id, bool $active, int $actorId): void
    {
        Database::transaction(function () use ($id, $active, $actorId): void {
            $before = User::find($id) ?: [];
            User::setActive($id, $active);
            $after = User::find($id) ?: [];
            ActivityLog::record($actorId, 'user_status', 'Changed user active status', null, [
                'user_id' => $id,
                'changes' => AuditService::diff($before, $after, ['is_active']),
            ]);
        });
    }

    public function delete(int $id, int $actorId): void
    {
        Database::transaction(function () use ($id, $actorId): void {
            $user = User::find($id);
            if (!$user) {
                return;
            }
            User::delete($id);
            ActivityLog::record($actorId, 'user_deleted', 'Archived user ' . $user['email'], null, [
                'user_id' => $id,
                'email' => $user['email'],
            ]);
        });
    }
}
