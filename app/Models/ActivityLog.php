<?php

namespace App\Models;

use App\Core\Database;

class ActivityLog
{
    private const SORT_COLUMNS = [
        'created_at' => 'activity_logs.created_at',
        'user' => 'users.name',
        'policy' => 'policies.policy_number',
        'action' => 'activity_logs.action',
    ];

    public static function record(?int $userId, string $action, string $description, ?int $policyId = null, ?array $details = null): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO activity_logs (user_id, policy_id, action, description, details_json)
             VALUES (:user_id, :policy_id, :action, :description, :details_json)'
        );
        $stmt->execute([
            'user_id' => $userId,
            'policy_id' => $policyId,
            'action' => $action,
            'description' => substr($description, 0, 255),
            'details_json' => $details ? json_encode($details, JSON_THROW_ON_ERROR) : null,
        ]);
    }

    public static function latest(int $limit = 8): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT activity_logs.*, users.name
             FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             ORDER BY activity_logs.created_at DESC
             LIMIT :limit'
        );
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function paginate(int $page = 1, int $perPage = 20, array $filters = []): array
    {
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;
        $db = Database::connection();
        $sort = self::SORT_COLUMNS[$filters['sort'] ?? 'created_at'] ?? 'activity_logs.created_at';
        $direction = strtoupper($filters['direction'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        [$where, $params] = self::whereClause($filters);

        $countStmt = $db->prepare(
            "SELECT COUNT(*)
             FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             LEFT JOIN policies ON policies.id = activity_logs.policy_id
             {$where}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $db->prepare(
            "SELECT activity_logs.*, users.name, policies.policy_number
             FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             LEFT JOIN policies ON policies.id = activity_logs.policy_id
             {$where}
             ORDER BY {$sort} {$direction}, activity_logs.id DESC
             LIMIT :limit OFFSET :offset"
        );
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'sort' => $filters['sort'] ?? 'created_at',
            'direction' => $direction,
        ];
    }

    public static function forPolicy(int $policyId): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT activity_logs.*, users.name
             FROM activity_logs
             LEFT JOIN users ON users.id = activity_logs.user_id
             WHERE activity_logs.policy_id = :policy_id
             ORDER BY activity_logs.created_at DESC'
        );
        $stmt->execute(['policy_id' => $policyId]);
        return $stmt->fetchAll();
    }

    private static function whereClause(array $filters): array
    {
        $where = [];
        $params = [];

        if (($filters['search'] ?? '') !== '') {
            $where[] = '(activity_logs.action LIKE :search_action OR activity_logs.description LIKE :search_description OR users.name LIKE :search_user OR policies.policy_number LIKE :search_policy)';
            $term = '%' . trim($filters['search']) . '%';
            $params['search_action'] = $term;
            $params['search_description'] = $term;
            $params['search_user'] = $term;
            $params['search_policy'] = $term;
        }

        if (($filters['action'] ?? '') !== '') {
            $where[] = 'activity_logs.action = :action';
            $params['action'] = $filters['action'];
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }
}
