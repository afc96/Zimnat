<?php

namespace App\Models;

use App\Core\Database;

class Dashboard
{
    public static function stats(): array
    {
        $db = Database::connection();
        return [
            'total' => (int) $db->query('SELECT COUNT(*) FROM policies WHERE deleted_at IS NULL')->fetchColumn(),
            'active' => (int) $db->query("SELECT COUNT(*) FROM policies WHERE deleted_at IS NULL AND status = 'Active'")->fetchColumn(),
            'expired' => (int) $db->query("SELECT COUNT(*) FROM policies WHERE deleted_at IS NULL AND (status = 'Expired' OR renewal_date < CURDATE())")->fetchColumn(),
            'pending' => (int) $db->query("SELECT COUNT(*) FROM policies WHERE deleted_at IS NULL AND status = 'Pending Renewal'")->fetchColumn(),
            'soon' => (int) $db->query("SELECT COUNT(*) FROM policies WHERE deleted_at IS NULL AND renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn(),
            'missing_documents' => (int) $db->query('SELECT COUNT(*) FROM policies WHERE deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM documents WHERE documents.policy_id = policies.id)')->fetchColumn(),
            'policies_with_documents' => (int) $db->query('SELECT COUNT(*) FROM policies WHERE deleted_at IS NULL AND EXISTS (SELECT 1 FROM documents WHERE documents.policy_id = policies.id)')->fetchColumn(),
            'documents' => (int) $db->query('SELECT COUNT(*) FROM documents')->fetchColumn(),
        ];
    }

    public static function officerLoad(): array
    {
        return Database::connection()->query(
            "SELECT users.name, users.role,
                    COUNT(policies.id) AS policy_count,
                    SUM(CASE WHEN policies.renewal_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) AS renewal_count
             FROM users
             LEFT JOIN policies ON policies.assigned_to = users.id AND policies.deleted_at IS NULL
             WHERE users.role IN ('admin', 'policy_officer') AND users.is_active = 1 AND users.deleted_at IS NULL
             GROUP BY users.id, users.name, users.role
             ORDER BY renewal_count DESC, policy_count DESC
             LIMIT 5"
        )->fetchAll();
    }
}
