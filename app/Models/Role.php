<?php

namespace App\Models;

use App\Core\Database;

class Role
{
    public static function all(): array
    {
        return Database::connection()
            ->query('SELECT * FROM roles ORDER BY is_system DESC, name ASC')
            ->fetchAll();
    }

    public static function options(): array
    {
        return Database::connection()
            ->query('SELECT slug, name FROM roles ORDER BY name ASC')
            ->fetchAll();
    }

    public static function exists(string $slug): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM roles WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);
        return (bool) $stmt->fetchColumn();
    }

    public static function label(string $slug): string
    {
        $stmt = Database::connection()->prepare('SELECT name FROM roles WHERE slug = :slug LIMIT 1');
        $stmt->execute(['slug' => $slug]);
        return (string) ($stmt->fetchColumn() ?: ucwords(str_replace('_', ' ', $slug)));
    }

    public static function create(array $data): void
    {
        $name = trim($data['name']);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '_', $name));
        $slug = trim($slug, '_') ?: 'custom_role';
        $stmt = Database::connection()->prepare(
            'INSERT INTO roles (slug, name, description) VALUES (:slug, :name, :description)'
        );
        $stmt->execute([
            'slug' => $slug,
            'name' => $name,
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
        ]);
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE roles SET name = :name, description = :description WHERE id = :id'
        );
        $stmt->execute([
            'id' => $id,
            'name' => trim($data['name']),
            'description' => trim((string) ($data['description'] ?? '')) ?: null,
        ]);
    }

    public static function delete(int $id): bool
    {
        $stmt = Database::connection()->prepare('SELECT slug, is_system FROM roles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $role = $stmt->fetch();
        if (!$role || (int) $role['is_system']) {
            return false;
        }

        $assigned = Database::connection()->prepare('SELECT COUNT(*) FROM users WHERE role = :role AND deleted_at IS NULL');
        $assigned->execute(['role' => $role['slug']]);
        if ((int) $assigned->fetchColumn() > 0) {
            return false;
        }

        $delete = Database::connection()->prepare('DELETE FROM roles WHERE id = :id');
        $delete->execute(['id' => $id]);
        return true;
    }

    public static function permissions(): array
    {
        return Database::connection()
            ->query('SELECT * FROM permissions ORDER BY category ASC, name ASC')
            ->fetchAll();
    }

    public static function permissionMap(): array
    {
        $rows = Database::connection()->query('SELECT role_id, permission_slug FROM role_permissions')->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[(int) $row['role_id']][] = $row['permission_slug'];
        }
        return $map;
    }

    public static function syncPermissions(int $roleId, array $permissions): void
    {
        $db = Database::connection();
        $db->prepare('DELETE FROM role_permissions WHERE role_id = :role_id')->execute(['role_id' => $roleId]);
        $stmt = $db->prepare('INSERT INTO role_permissions (role_id, permission_slug) VALUES (:role_id, :permission_slug)');
        foreach (array_unique($permissions) as $permission) {
            $stmt->execute(['role_id' => $roleId, 'permission_slug' => $permission]);
        }
    }

    public static function userCan(int $userId, string $permission): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT COUNT(*)
             FROM users
             INNER JOIN roles ON roles.slug = users.role
             INNER JOIN role_permissions ON role_permissions.role_id = roles.id
             WHERE users.id = :user_id AND users.deleted_at IS NULL AND role_permissions.permission_slug = :permission'
        );
        $stmt->execute(['user_id' => $userId, 'permission' => $permission]);
        return (bool) $stmt->fetchColumn();
    }
}
