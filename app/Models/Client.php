<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Client
{
    private const SORT_COLUMNS = [
        'client_name' => 'clients.display_name',
        'client_code' => 'clients.client_code',
        'client_type' => 'clients.client_type',
        'client_email' => 'clients.email',
        'client_phone' => 'clients.phone',
        'city' => 'clients.city',
        'segment' => 'clients.segment',
        'status' => 'clients.status',
        'client_status' => 'clients.status',
        'policy_count' => 'policy_count',
        'active_count' => 'active_count',
        'renewal_soon_count' => 'renewal_soon_count',
        'missing_docs_count' => 'missing_docs_count',
        'next_renewal' => 'next_renewal',
    ];

    public static function upsertFromPolicy(array $data): int
    {
        $email = trim((string) ($data['client_email'] ?? '')) ?: null;
        $phone = trim((string) ($data['client_phone'] ?? '')) ?: null;
        $name = trim((string) ($data['client_name'] ?? ''));

        $existing = self::findMatch($name, $email, $phone);
        if ($existing) {
            self::updateFromPolicy((int) $existing['id'], $data);
            return (int) $existing['id'];
        }

        $stmt = Database::connection()->prepare(
            'INSERT INTO clients
                (client_code, client_type, display_name, email, phone, alternate_phone, national_id, tax_number, address_line1, suburb, city, province, country, preferred_contact, segment, status, notes)
             VALUES
                (:client_code, :client_type, :display_name, :email, :phone, :alternate_phone, :national_id, :tax_number, :address_line1, :suburb, :city, :province, :country, :preferred_contact, :segment, :status, :notes)'
        );
        $stmt->execute(self::payload($data) + ['client_code' => self::nextCode()]);

        return (int) Database::connection()->lastInsertId();
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO clients
                (client_code, client_type, display_name, email, phone, alternate_phone, national_id, tax_number, address_line1, suburb, city, province, country, preferred_contact, segment, status, notes)
             VALUES
                (:client_code, :client_type, :display_name, :email, :phone, :alternate_phone, :national_id, :tax_number, :address_line1, :suburb, :city, :province, :country, :preferred_contact, :segment, :status, :notes)'
        );
        $stmt->execute(self::payload($data) + ['client_code' => self::nextCode()]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            self::selectSql() . ' FROM clients
             LEFT JOIN policies ON policies.client_id = clients.id AND policies.deleted_at IS NULL
             WHERE clients.id = :id AND clients.deleted_at IS NULL
             GROUP BY clients.id
             LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function options(): array
    {
        return Database::connection()->query(
            'SELECT id, client_code, client_type, display_name AS client_name, email AS client_email, phone AS client_phone,
                    alternate_phone, national_id, tax_number, address_line1, suburb, city, province, country,
                    preferred_contact, segment, status AS client_status, notes AS client_notes
             FROM clients
             WHERE deleted_at IS NULL
             ORDER BY display_name ASC'
        )->fetchAll();
    }

    public static function update(int $id, array $data): void
    {
        self::updateFromPolicy($id, [
            'client_name' => $data['client_name'] ?? $data['display_name'] ?? '',
            'client_email' => $data['client_email'] ?? $data['email'] ?? '',
            'client_phone' => $data['client_phone'] ?? $data['phone'] ?? '',
            'client_status' => $data['client_status'] ?? $data['status'] ?? 'Active',
        ] + $data);
    }

    public static function delete(int $id): bool
    {
        $hasPolicies = Database::connection()->prepare(
            'SELECT COUNT(*) FROM policies WHERE client_id = :id AND deleted_at IS NULL'
        );
        $hasPolicies->execute(['id' => $id]);
        if ((int) $hasPolicies->fetchColumn() > 0) {
            return false;
        }

        $stmt = Database::connection()->prepare(
            'UPDATE clients SET deleted_at = NOW(), status = "Inactive" WHERE id = :id AND deleted_at IS NULL'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount() > 0;
    }

    public static function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPageLimit = !empty($filters['export']) ? 10000 : 10;
        $perPage = min($perPageLimit, max(5, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $sort = self::SORT_COLUMNS[$filters['sort'] ?? 'client_name'] ?? 'clients.display_name';
        $direction = strtoupper($filters['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        [$where, $having, $params] = self::filters($filters);

        $base = self::baseSql($where, $having);
        $countStmt = Database::connection()->prepare('SELECT COUNT(*) FROM (' . self::selectSql() . ' ' . $base . ') AS counted_clients');
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = Database::connection()->prepare(self::selectSql() . ' ' . $base . " ORDER BY {$sort} {$direction}, clients.id DESC LIMIT :limit OFFSET :offset");
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
            'sort' => $filters['sort'] ?? 'client_name',
            'direction' => $direction,
        ];
    }

    public static function exportRows(array $filters): array
    {
        $filters['page'] = 1;
        $filters['per_page'] = 10000;
        $filters['export'] = true;
        return self::paginate($filters)['items'];
    }

    public static function policiesByClient(): array
    {
        $rows = Database::connection()->query(
            'SELECT policies.*, clients.id AS client_entity_id, assigned_user.name AS assigned_name,
                    (SELECT COUNT(*) FROM documents WHERE documents.policy_id = policies.id) AS document_count
             FROM policies
             LEFT JOIN clients ON clients.id = policies.client_id
             LEFT JOIN users assigned_user ON assigned_user.id = policies.assigned_to
             WHERE policies.deleted_at IS NULL AND clients.deleted_at IS NULL
             ORDER BY policies.renewal_date ASC'
        )->fetchAll();

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[self::key($row)][] = $row;
        }
        return $grouped;
    }

    public static function key(array $client): string
    {
        if (!empty($client['client_entity_id'])) {
            return 'client:' . $client['client_entity_id'];
        }
        if (!empty($client['id'])) {
            return 'client:' . $client['id'];
        }

        return implode('|', [
            strtolower(trim((string) ($client['client_name'] ?? $client['display_name'] ?? ''))),
            strtolower(trim((string) ($client['client_email'] ?? $client['email'] ?? ''))),
            preg_replace('/\s+/', '', strtolower(trim((string) ($client['client_phone'] ?? $client['phone'] ?? '')))),
        ]);
    }

    private static function updateFromPolicy(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE clients SET
                client_type = :client_type,
                display_name = :display_name,
                email = :email,
                phone = :phone,
                alternate_phone = :alternate_phone,
                national_id = :national_id,
                tax_number = :tax_number,
                address_line1 = :address_line1,
                suburb = :suburb,
                city = :city,
                province = :province,
                country = :country,
                preferred_contact = :preferred_contact,
                segment = :segment,
                status = :status,
                notes = :notes
             WHERE id = :id'
        );
        $stmt->execute(self::payload($data) + ['id' => $id]);
    }

    private static function findMatch(string $name, ?string $email, ?string $phone): ?array
    {
        if ($email !== null) {
            $stmt = Database::connection()->prepare('SELECT * FROM clients WHERE email = :email AND deleted_at IS NULL LIMIT 1');
            $stmt->execute(['email' => strtolower($email)]);
            $client = $stmt->fetch();
            if ($client) {
                return $client;
            }
        }

        if ($phone !== null) {
            $stmt = Database::connection()->prepare('SELECT * FROM clients WHERE phone = :phone AND deleted_at IS NULL LIMIT 1');
            $stmt->execute(['phone' => $phone]);
            $client = $stmt->fetch();
            if ($client) {
                return $client;
            }
        }

        $stmt = Database::connection()->prepare('SELECT * FROM clients WHERE display_name = :name AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['name' => $name]);
        return $stmt->fetch() ?: null;
    }

    private static function payload(array $data): array
    {
        return [
            'client_type' => $data['client_type'] ?? 'Individual',
            'display_name' => trim((string) ($data['client_name'] ?? '')),
            'email' => strtolower(trim((string) ($data['client_email'] ?? ''))) ?: null,
            'phone' => trim((string) ($data['client_phone'] ?? '')) ?: null,
            'alternate_phone' => trim((string) ($data['alternate_phone'] ?? '')) ?: null,
            'national_id' => trim((string) ($data['national_id'] ?? '')) ?: null,
            'tax_number' => trim((string) ($data['tax_number'] ?? '')) ?: null,
            'address_line1' => trim((string) ($data['address_line1'] ?? '')) ?: null,
            'suburb' => trim((string) ($data['suburb'] ?? '')) ?: null,
            'city' => trim((string) ($data['city'] ?? 'Harare')) ?: null,
            'province' => trim((string) ($data['province'] ?? 'Harare')) ?: null,
            'country' => trim((string) ($data['country'] ?? 'Zimbabwe')) ?: 'Zimbabwe',
            'preferred_contact' => $data['preferred_contact'] ?? 'Phone',
            'segment' => $data['segment'] ?? 'Retail',
            'status' => $data['client_status'] ?? 'Active',
            'notes' => trim((string) ($data['client_notes'] ?? '')) ?: null,
        ];
    }

    private static function nextCode(): string
    {
        return 'CL-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    private static function selectSql(): string
    {
        return 'SELECT clients.id, clients.client_code, clients.client_type, clients.display_name AS client_name,
                       clients.email AS client_email, clients.phone AS client_phone, clients.alternate_phone,
                       clients.national_id, clients.tax_number, clients.address_line1, clients.suburb,
                       clients.city, clients.province, clients.country, clients.preferred_contact,
                       clients.segment, clients.status AS client_status, clients.notes AS client_notes,
                       COUNT(policies.id) AS policy_count,
                       SUM(CASE WHEN policies.status = "Active" THEN 1 ELSE 0 END) AS active_count,
                       SUM(CASE WHEN policies.renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS renewal_soon_count,
                       SUM(CASE WHEN policies.renewal_date < CURDATE() THEN 1 ELSE 0 END) AS expired_count,
                       SUM(CASE WHEN policies.status = "Pending Renewal" THEN 1 ELSE 0 END) AS pending_count,
                       SUM(CASE WHEN policies.id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM documents WHERE documents.policy_id = policies.id) THEN 1 ELSE 0 END) AS missing_docs_count,
                       COALESCE(MIN(CASE WHEN policies.renewal_date >= CURDATE() THEN policies.renewal_date END), MAX(policies.renewal_date)) AS next_renewal';
    }

    private static function baseSql(string $where, string $having): string
    {
        return 'FROM clients
                LEFT JOIN policies ON policies.client_id = clients.id AND policies.deleted_at IS NULL
                ' . $where . '
                GROUP BY clients.id
                ' . $having;
    }

    private static function filters(array $filters): array
    {
        $where = ['clients.deleted_at IS NULL'];
        $having = [];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(clients.display_name LIKE :search_name OR clients.email LIKE :search_email OR clients.phone LIKE :search_phone OR clients.client_code LIKE :search_code OR clients.national_id LIKE :search_id OR clients.city LIKE :search_city OR policies.policy_number LIKE :search_policy)';
            $term = '%' . trim($filters['search']) . '%';
            $params['search_name'] = $term;
            $params['search_email'] = $term;
            $params['search_phone'] = $term;
            $params['search_code'] = $term;
            $params['search_id'] = $term;
            $params['search_city'] = $term;
            $params['search_policy'] = $term;
        }

        foreach (['segment', 'city'] as $field) {
            if (($filters[$field] ?? '') !== '') {
                $where[] = "clients.{$field} = :{$field}";
                $params[$field] = $filters[$field];
            }
        }

        if (($filters['client_status'] ?? '') !== '') {
            $where[] = 'clients.status = :client_status';
            $params['client_status'] = $filters['client_status'];
        }

        if (($filters['contact'] ?? '') === 'missing') {
            $where[] = '(COALESCE(clients.email, "") = "" OR COALESCE(clients.phone, "") = "")';
        } elseif (($filters['contact'] ?? '') === 'complete') {
            $where[] = '(COALESCE(clients.email, "") <> "" AND COALESCE(clients.phone, "") <> "")';
        }

        if (($filters['status'] ?? '') !== '') {
            $having[] = 'SUM(CASE WHEN policies.status = :status THEN 1 ELSE 0 END) > 0';
            $params['status'] = $filters['status'];
        }

        if (($filters['type'] ?? '') !== '') {
            $having[] = 'SUM(CASE WHEN policies.insurance_type = :type THEN 1 ELSE 0 END) > 0';
            $params['type'] = $filters['type'];
        }

        if (($filters['renewal'] ?? '') === 'soon') {
            $having[] = 'SUM(CASE WHEN policies.renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) > 0';
        } elseif (($filters['renewal'] ?? '') === 'expired') {
            $having[] = 'SUM(CASE WHEN policies.renewal_date < CURDATE() THEN 1 ELSE 0 END) > 0';
        }

        if (($filters['docs'] ?? '') === 'missing') {
            $having[] = 'SUM(CASE WHEN policies.id IS NOT NULL AND NOT EXISTS (SELECT 1 FROM documents WHERE documents.policy_id = policies.id) THEN 1 ELSE 0 END) > 0';
        }

        return [
            $where ? 'WHERE ' . implode(' AND ', $where) : '',
            $having ? 'HAVING ' . implode(' AND ', $having) : '',
            $params,
        ];
    }
}
