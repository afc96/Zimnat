<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Policy
{
    private const SORT_COLUMNS = [
        'client_name' => 'clients.display_name',
        'client_email' => 'clients.email',
        'client_phone' => 'clients.phone',
        'segment' => 'clients.segment',
        'city' => 'clients.city',
        'policy_number' => 'policies.policy_number',
        'insurance_type' => 'policies.insurance_type',
        'premium_amount' => 'policies.premium_amount',
        'start_date' => 'policies.start_date',
        'renewal_date' => 'policies.renewal_date',
        'status' => 'policies.status',
        'assigned_name' => 'assigned_name',
        'document_count' => 'document_count',
        'reminder_days' => 'policies.reminder_days',
        'reminder_status' => 'policies.reminder_status',
        'reminder_last_contacted_at' => 'policies.reminder_last_contacted_at',
        'payment_frequency' => 'policies.payment_frequency',
        'created_at' => 'policies.created_at',
    ];

    private const REMINDER_STATUSES = ['Pending', 'Contacted', 'Snoozed', 'Failed', 'Resolved'];

    public static function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPageLimit = !empty($filters['export']) ? 10000 : 50;
        $perPage = min($perPageLimit, max(5, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        // Sort columns are whitelisted because SQL identifiers cannot be bound.
        $sort = self::SORT_COLUMNS[$filters['sort'] ?? 'renewal_date'] ?? 'policies.renewal_date';
        $direction = strtoupper($filters['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        [$where, $params] = self::whereClause($filters);

        $countStmt = Database::connection()->prepare("SELECT COUNT(*) FROM policies LEFT JOIN clients ON clients.id = policies.client_id {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $sql = "SELECT policies.*, clients.display_name AS client_name, clients.email AS client_email, clients.phone AS client_phone,
                   created_user.name AS created_name, updated_user.name AS updated_name, assigned_user.name AS assigned_name,
                   clients.client_type, clients.alternate_phone, clients.national_id, clients.tax_number, clients.address_line1,
                   clients.suburb, clients.city, clients.province, clients.country, clients.preferred_contact,
                   clients.segment, clients.status AS client_status, clients.notes AS client_notes,
                   (SELECT COUNT(*) FROM documents WHERE documents.policy_id = policies.id) AS document_count
                FROM policies
                LEFT JOIN clients ON clients.id = policies.client_id
                LEFT JOIN users created_user ON created_user.id = policies.created_by
                LEFT JOIN users updated_user ON updated_user.id = policies.updated_by
                LEFT JOIN users assigned_user ON assigned_user.id = policies.assigned_to
                {$where}
                ORDER BY {$sort} {$direction}, policies.id DESC
                LIMIT :limit OFFSET :offset";

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'sort' => $filters['sort'] ?? 'renewal_date',
            'direction' => $direction,
        ];
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT policies.*, clients.display_name AS client_name, clients.email AS client_email, clients.phone AS client_phone,
                    assigned_user.name AS assigned_name, created_user.name AS created_name, updated_user.name AS updated_name,
                    clients.client_type, clients.alternate_phone, clients.national_id, clients.tax_number, clients.address_line1,
                    clients.suburb, clients.city, clients.province, clients.country, clients.preferred_contact,
                    clients.segment, clients.status AS client_status, clients.notes AS client_notes
             FROM policies
             LEFT JOIN clients ON clients.id = policies.client_id
             LEFT JOIN users assigned_user ON assigned_user.id = policies.assigned_to
             LEFT JOIN users created_user ON created_user.id = policies.created_by
             LEFT JOIN users updated_user ON updated_user.id = policies.updated_by
             WHERE policies.id = :id AND policies.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findMany(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $index => $id) {
            $key = 'id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $stmt = Database::connection()->prepare(
            'SELECT policies.*, clients.display_name AS client_name, clients.email AS client_email, clients.phone AS client_phone,
                    assigned_user.name AS assigned_name,
                    clients.client_type, clients.alternate_phone, clients.national_id, clients.tax_number, clients.address_line1,
                    clients.suburb, clients.city, clients.province, clients.country, clients.preferred_contact,
                    clients.segment, clients.status AS client_status, clients.notes AS client_notes
             FROM policies
             LEFT JOIN clients ON clients.id = policies.client_id
             LEFT JOIN users assigned_user ON assigned_user.id = policies.assigned_to
             WHERE policies.deleted_at IS NULL AND policies.id IN (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function exportRows(array $filters): array
    {
        $filters['page'] = 1;
        $filters['per_page'] = 10000;
        $filters['export'] = true;
        return self::paginate($filters)['items'];
    }

    public static function create(array $data, int $userId): int
    {
        $clientId = !empty($data['client_id']) ? (int) $data['client_id'] : Client::upsertFromPolicy($data);
        if (!empty($data['client_id'])) {
            Client::update($clientId, $data);
        }
        $stmt = Database::connection()->prepare(
            'INSERT INTO policies
              (client_id, policy_number, insurance_type, premium_amount, payment_frequency, start_date, renewal_date, reminder_days, status, assigned_to, notes, created_by, updated_by)
             VALUES
              (:client_id, :policy_number, :insurance_type, :premium_amount, :payment_frequency, :start_date, :renewal_date, :reminder_days, :status, :assigned_to, :notes, :created_by, :updated_by)'
        );
        $stmt->execute(self::payload($data, $userId) + ['client_id' => $clientId, 'created_by' => $userId]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data, int $userId): void
    {
        $clientId = !empty($data['client_id']) ? (int) $data['client_id'] : Client::upsertFromPolicy($data);
        if (!empty($data['client_id'])) {
            Client::update($clientId, $data);
        }
        $stmt = Database::connection()->prepare(
            'UPDATE policies SET
              client_id = :client_id,
              policy_number = :policy_number,
              insurance_type = :insurance_type,
              premium_amount = :premium_amount,
              payment_frequency = :payment_frequency,
              start_date = :start_date,
              renewal_date = :renewal_date,
              reminder_days = :reminder_days,
              status = :status,
              assigned_to = :assigned_to,
              notes = :notes,
              updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute(self::payload($data, $userId) + ['client_id' => $clientId, 'id' => $id]);
    }

    public static function delete(int $id, ?int $userId = null): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE policies SET deleted_at = NOW(), updated_by = :updated_by WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id, 'updated_by' => $userId]);
    }

    public static function updateReminder(int $id, string $status, ?string $note, ?string $snoozedUntil, int $userId): void
    {
        $lastContactedAt = in_array($status, ['Contacted', 'Failed', 'Resolved'], true) ? date('Y-m-d H:i:s') : null;
        $stmt = Database::connection()->prepare(
            'UPDATE policies SET
                reminder_status = :reminder_status,
                reminder_note = :reminder_note,
                reminder_last_contacted_at = :reminder_last_contacted_at,
                reminder_snoozed_until = :reminder_snoozed_until,
                updated_by = :updated_by
             WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'reminder_status' => $status,
            'reminder_note' => $note ? substr(trim($note), 0, 255) : null,
            'reminder_last_contacted_at' => $lastContactedAt,
            'reminder_snoozed_until' => $status === 'Snoozed' ? $snoozedUntil : null,
            'updated_by' => $userId,
        ]);
    }

    public static function latest(int $limit = 5): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT policies.*, clients.display_name AS client_name, clients.email AS client_email, clients.phone AS client_phone,
                    assigned_user.name AS assigned_name,
                    clients.segment, clients.city, clients.preferred_contact,
                    (SELECT COUNT(*) FROM documents WHERE documents.policy_id = policies.id) AS document_count
             FROM policies
             LEFT JOIN clients ON clients.id = policies.client_id
             LEFT JOIN users assigned_user ON assigned_user.id = policies.assigned_to
             WHERE policies.deleted_at IS NULL
             ORDER BY policies.updated_at DESC LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private static function whereClause(array $filters): array
    {
        $where = ['policies.deleted_at IS NULL'];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(policies.policy_number LIKE :search_policy OR clients.display_name LIKE :search_client OR clients.email LIKE :search_email OR clients.phone LIKE :search_phone OR policies.insurance_type LIKE :search_type OR clients.client_code LIKE :search_code OR clients.national_id LIKE :search_id)';
            $term = '%' . trim($filters['search']) . '%';
            $params['search_policy'] = $term;
            $params['search_client'] = $term;
            $params['search_email'] = $term;
            $params['search_phone'] = $term;
            $params['search_type'] = $term;
            $params['search_code'] = $term;
            $params['search_id'] = $term;
        }

        if (!empty($filters['ids']) && is_array($filters['ids'])) {
            $ids = array_values(array_filter(array_map('intval', $filters['ids'])));
            if ($ids) {
                $placeholders = [];
                foreach ($ids as $index => $id) {
                    $key = 'id_' . $index;
                    $placeholders[] = ':' . $key;
                    $params[$key] = $id;
                }
                $where[] = 'policies.id IN (' . implode(',', $placeholders) . ')';
            }
        }

        if (($filters['status'] ?? '') !== '') {
            $where[] = 'policies.status = :status';
            $params['status'] = $filters['status'];
        }

        if (($filters['reminder_status'] ?? '') !== '') {
            $where[] = 'policies.reminder_status = :reminder_status';
            $params['reminder_status'] = $filters['reminder_status'];
        }

        if (($filters['type'] ?? '') !== '') {
            $where[] = 'policies.insurance_type = :type';
            $params['type'] = $filters['type'];
        }

        if (!empty($filters['client_id'])) {
            $where[] = 'policies.client_id = :client_id';
            $params['client_id'] = (int) $filters['client_id'];
        }

        if (!empty($filters['policy_id'])) {
            $where[] = 'policies.id = :policy_id';
            $params['policy_id'] = (int) $filters['policy_id'];
        }

        if (($filters['renewal'] ?? '') === 'soon') {
            $where[] = 'policies.renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)';
        } elseif (($filters['renewal'] ?? '') === 'expired') {
            $where[] = 'policies.renewal_date < CURDATE()';
        }

        if (($filters['docs'] ?? '') === 'missing') {
            $where[] = 'NOT EXISTS (SELECT 1 FROM documents WHERE documents.policy_id = policies.id)';
        }

        if (!empty($filters['assigned_to'])) {
            $where[] = 'policies.assigned_to = :assigned_to';
            $params['assigned_to'] = (int) $filters['assigned_to'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private static function payload(array $data, int $userId): array
    {
        return [
            'policy_number' => strtoupper(trim($data['policy_number'])),
            'insurance_type' => trim($data['insurance_type']),
            'premium_amount' => (float) $data['premium_amount'],
            'payment_frequency' => $data['payment_frequency'] ?? 'Monthly',
            'start_date' => $data['start_date'],
            'renewal_date' => $data['renewal_date'],
            'reminder_days' => (int) ($data['reminder_days'] ?? 30),
            'status' => $data['status'],
            'assigned_to' => !empty($data['assigned_to']) ? (int) $data['assigned_to'] : null,
            'notes' => trim((string) ($data['notes'] ?? '')) ?: null,
            'updated_by' => $userId,
        ];
    }

    public static function myQueue(int $userId, array $filters = []): array
    {
        return self::paginate($filters + [
            'assigned_to' => $userId,
            'renewal' => '',
            'sort' => 'renewal_date',
            'direction' => 'ASC',
            'page' => 1,
            'per_page' => 10,
        ]);
    }

    public static function bulkUpdateStatus(array $ids, string $status, int $userId): int
    {
        if (!in_array($status, ['Active', 'Expired', 'Pending Renewal'], true)) {
            return 0;
        }
        return self::bulkUpdate($ids, 'status = :status, updated_by = :updated_by', [
            'status' => $status,
            'updated_by' => $userId,
        ]);
    }

    public static function bulkAssign(array $ids, ?int $assignedTo, int $userId): int
    {
        return self::bulkUpdate($ids, 'assigned_to = :assigned_to, updated_by = :updated_by', [
            'assigned_to' => $assignedTo,
            'updated_by' => $userId,
        ]);
    }

    public static function bulkReminder(array $ids, string $status, ?string $note, ?string $snoozedUntil, int $userId): int
    {
        if (!in_array($status, self::REMINDER_STATUSES, true)) {
            return 0;
        }
        $lastContactedAt = in_array($status, ['Contacted', 'Failed', 'Resolved'], true) ? date('Y-m-d H:i:s') : null;
        return self::bulkUpdate($ids, 'reminder_status = :reminder_status, reminder_note = :reminder_note, reminder_last_contacted_at = :reminder_last_contacted_at, reminder_snoozed_until = :reminder_snoozed_until, updated_by = :updated_by', [
            'reminder_status' => $status,
            'reminder_note' => $note ? substr(trim($note), 0, 255) : null,
            'reminder_last_contacted_at' => $lastContactedAt,
            'reminder_snoozed_until' => $status === 'Snoozed' ? $snoozedUntil : null,
            'updated_by' => $userId,
        ]);
    }

    private static function bulkUpdate(array $ids, string $setSql, array $params): int
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (!$ids) {
            return 0;
        }

        $placeholders = [];
        foreach ($ids as $index => $id) {
            $key = 'id_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $id;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE policies SET ' . $setSql . ' WHERE deleted_at IS NULL AND id IN (' . implode(',', $placeholders) . ')'
        );
        $stmt->execute($params);
        return $stmt->rowCount();
    }
}
