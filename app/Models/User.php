<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class User
{
    private const SORT_COLUMNS = [
        'name' => 'users.name',
        'email' => 'users.email',
        'role' => 'roles.name',
        'created_at' => 'users.created_at',
        'updated_at' => 'users.updated_at',
        'is_active' => 'users.is_active',
    ];

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE id = :id AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM users WHERE email = :email AND deleted_at IS NULL LIMIT 1');
        $stmt->execute(['email' => strtolower(trim($email))]);
        return $stmt->fetch() ?: null;
    }

    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT users.id, users.name, users.email, users.role, roles.name AS role_name, users.is_active, users.created_at, users.updated_at
                     FROM users
                     LEFT JOIN roles ON roles.slug = users.role
                     WHERE users.deleted_at IS NULL
                     ORDER BY users.is_active DESC, users.name ASC')
            ->fetchAll();
    }

    public static function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = min(50, max(5, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $sort = self::SORT_COLUMNS[$filters['sort'] ?? 'name'] ?? 'users.name';
        $direction = strtoupper($filters['direction'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';
        [$where, $params] = self::whereClause($filters);
        $db = Database::connection();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM users LEFT JOIN roles ON roles.slug = users.role {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT users.id, users.name, users.email, users.role, roles.name AS role_name, users.is_active, users.created_at, users.updated_at
             FROM users
             LEFT JOIN roles ON roles.slug = users.role
             {$where}
             ORDER BY {$sort} {$direction}, users.name ASC
             LIMIT :limit OFFSET :offset"
        );
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
            'sort' => $filters['sort'] ?? 'name',
            'direction' => $direction,
        ];
    }

    public static function assignable(): array
    {
        return Database::connection()
            ->query("SELECT id, name, role FROM users WHERE is_active = 1 AND deleted_at IS NULL AND role IN ('admin', 'policy_officer') ORDER BY name ASC")
            ->fetchAll();
    }

    public static function create(array $data): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, role, password_hash) VALUES (:name, :email, :role, :password_hash)'
        );
        $stmt->execute([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'],
            'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $params = [
            'id' => $id,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'],
        ];

        $passwordSql = '';
        if (!empty($data['password'])) {
            $passwordSql = ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $stmt = Database::connection()->prepare(
            "UPDATE users SET name = :name, email = :email, role = :role {$passwordSql} WHERE id = :id"
        );
        $stmt->execute($params);
    }

    public static function updateProfile(int $id, array $data): void
    {
        $params = [
            'id' => $id,
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
        ];

        $passwordSql = '';
        if (!empty($data['password'])) {
            $passwordSql = ', password_hash = :password_hash';
            $params['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $stmt = Database::connection()->prepare(
            "UPDATE users SET name = :name, email = :email {$passwordSql} WHERE id = :id"
        );
        $stmt->execute($params);
    }

    public static function setActive(int $id, bool $active): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET is_active = :active WHERE id = :id AND deleted_at IS NULL');
        $stmt->bindValue('active', $active ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue('id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET deleted_at = NOW(), is_active = 0 WHERE id = :id AND deleted_at IS NULL');
        $stmt->execute(['id' => $id]);
    }

    private static function whereClause(array $filters): array
    {
        $where = ['users.deleted_at IS NULL'];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(users.name LIKE :search_name OR users.email LIKE :search_email OR users.role LIKE :search_role OR roles.name LIKE :search_role_name)';
            $term = '%' . trim($filters['search']) . '%';
            $params['search_name'] = $term;
            $params['search_email'] = $term;
            $params['search_role'] = $term;
            $params['search_role_name'] = $term;
        }

        if (($filters['status'] ?? '') === 'active') {
            $where[] = 'users.is_active = 1';
        } elseif (($filters['status'] ?? '') === 'inactive') {
            $where[] = 'users.is_active = 0';
        }

        if (($filters['role'] ?? '') !== '') {
            $where[] = 'users.role = :role';
            $params['role'] = $filters['role'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }
}
