<?php

namespace App\Models;

use App\Core\Database;
use PDO;

class Document
{
    private const SORT_COLUMNS = [
        'original_name' => 'documents.original_name',
        'document_type' => 'documents.document_type',
        'policy_number' => 'policies.policy_number',
        'client_name' => 'clients.display_name',
        'policy_status' => 'policies.status',
        'uploaded_by' => 'users.name',
        'size_bytes' => 'documents.size_bytes',
        'created_at' => 'documents.created_at',
    ];

    public const EXPECTED_TYPES = [
        'Identity Document',
        'Policy Form',
        'Proof of Payment',
        'Signed Renewal Form',
        'Beneficiary Form',
    ];

    public static function types(): array
    {
        try {
            $rows = Database::connection()
                ->query('SELECT * FROM document_types ORDER BY sort_order ASC, name ASC')
                ->fetchAll();
            return $rows ?: array_map(fn ($name) => ['name' => $name, 'is_required' => 1], self::EXPECTED_TYPES);
        } catch (\Throwable) {
            return array_map(fn ($name) => ['name' => $name, 'is_required' => 1], self::EXPECTED_TYPES);
        }
    }

    public static function createType(array $data): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO document_types (name, is_required, sort_order) VALUES (:name, :is_required, :sort_order)'
        );
        $stmt->execute([
            'name' => trim($data['name']),
            'is_required' => !empty($data['is_required']) ? 1 : 0,
            'sort_order' => (int) ($data['sort_order'] ?? 100),
        ]);
    }

    public static function deleteType(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM document_types WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::connection()->prepare(
            'SELECT documents.*, policies.policy_number, clients.display_name AS client_name
             FROM documents
             INNER JOIN policies ON policies.id = documents.policy_id
             INNER JOIN clients ON clients.id = policies.client_id
             WHERE documents.id = :id AND policies.deleted_at IS NULL AND clients.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function forPolicy(int $policyId): array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM documents WHERE policy_id = :policy_id ORDER BY created_at DESC');
        $stmt->execute(['policy_id' => $policyId]);
        return $stmt->fetchAll();
    }

    public static function checklist(int $policyId): array
    {
        $documents = self::forPolicy($policyId);
        $byType = [];
        foreach ($documents as $document) {
            $byType[$document['document_type']][] = $document;
        }

        $items = [];
        foreach (self::types() as $documentType) {
            if (!(int) $documentType['is_required']) {
                continue;
            }
            $type = $documentType['name'];
            $items[] = [
                'type' => $type,
                'uploaded' => !empty($byType[$type]),
                'documents' => $byType[$type] ?? [],
            ];
        }

        return $items;
    }

    public static function paginate(array $filters): array
    {
        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPageLimit = !empty($filters['export']) ? 10000 : 10;
        $perPage = min($perPageLimit, max(5, (int) ($filters['per_page'] ?? 10)));
        $offset = ($page - 1) * $perPage;
        $sort = self::SORT_COLUMNS[$filters['sort'] ?? 'created_at'] ?? 'documents.created_at';
        $direction = strtoupper($filters['direction'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';
        $where = ['policies.deleted_at IS NULL', 'clients.deleted_at IS NULL'];
        $params = [];
        if (($filters['search'] ?? '') !== '') {
            $where[] = '(documents.original_name LIKE :search_file OR documents.document_type LIKE :search_type OR policies.policy_number LIKE :search_policy OR clients.display_name LIKE :search_client)';
            $term = '%' . trim($filters['search']) . '%';
            $params['search_file'] = $term;
            $params['search_type'] = $term;
            $params['search_policy'] = $term;
            $params['search_client'] = $term;
        }

        if (($filters['document_type'] ?? '') !== '') {
            $where[] = 'documents.document_type = :document_type';
            $params['document_type'] = $filters['document_type'];
        }

        if (($filters['file_type'] ?? '') === 'pdf') {
            $where[] = 'documents.mime_type = :file_type_pdf';
            $params['file_type_pdf'] = 'application/pdf';
        } elseif (($filters['file_type'] ?? '') === 'image') {
            $where[] = 'documents.mime_type IN (:file_type_jpg, :file_type_png)';
            $params['file_type_jpg'] = 'image/jpeg';
            $params['file_type_png'] = 'image/png';
        }

        if (($filters['policy_status'] ?? '') !== '') {
            $where[] = 'policies.status = :policy_status';
            $params['policy_status'] = $filters['policy_status'];
        }

        if (!empty($filters['client_id'])) {
            $where[] = 'clients.id = :client_id';
            $params['client_id'] = (int) $filters['client_id'];
        }

        if (!empty($filters['policy_id'])) {
            $where[] = 'policies.id = :policy_id';
            $params['policy_id'] = (int) $filters['policy_id'];
        }

        if (($filters['uploaded_from'] ?? '') !== '') {
            $where[] = 'DATE(documents.created_at) >= :uploaded_from';
            $params['uploaded_from'] = $filters['uploaded_from'];
        }

        if (($filters['uploaded_to'] ?? '') !== '') {
            $where[] = 'DATE(documents.created_at) <= :uploaded_to';
            $params['uploaded_to'] = $filters['uploaded_to'];
        }

        $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $countStmt = Database::connection()->prepare(
            "SELECT COUNT(*) FROM documents INNER JOIN policies ON policies.id = documents.policy_id INNER JOIN clients ON clients.id = policies.client_id {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = Database::connection()->prepare(
            "SELECT documents.*, policies.policy_number, policies.client_id, clients.display_name AS client_name, policies.status AS policy_status, users.name AS uploaded_by_name
             FROM documents
             INNER JOIN policies ON policies.id = documents.policy_id
             INNER JOIN clients ON clients.id = policies.client_id
             LEFT JOIN users ON users.id = documents.uploaded_by
             {$whereSql}
             ORDER BY {$sort} {$direction}, documents.id DESC
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
            'sort' => $filters['sort'] ?? 'created_at',
            'direction' => $direction,
        ];
    }

    public static function create(int $policyId, int $userId, array $file, string $storedName, string $documentType): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO documents (policy_id, uploaded_by, document_type, original_name, stored_name, mime_type, size_bytes)
             VALUES (:policy_id, :uploaded_by, :document_type, :original_name, :stored_name, :mime_type, :size_bytes)'
        );
        $stmt->execute([
            'policy_id' => $policyId,
            'uploaded_by' => $userId,
            'document_type' => trim($documentType) ?: 'Other',
            'original_name' => $file['name'],
            'stored_name' => $storedName,
            'mime_type' => $file['type'],
            'size_bytes' => $file['size'],
        ]);
    }

    public static function exportRows(array $filters): array
    {
        $filters['page'] = 1;
        $filters['per_page'] = 10000;
        $filters['export'] = true;
        return self::paginate($filters)['items'];
    }

    public static function delete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM documents WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
