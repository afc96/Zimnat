<?php

namespace App\Services;

use App\Core\Database;
use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Policy;
use RuntimeException;

class DocumentService
{
    public function upload(int $policyId, int $userId, ?array $file, string $documentType): void
    {
        $policy = Policy::find($policyId);
        if (!$policy) {
            throw new RuntimeException('Policy not found.');
        }

        $this->validateUpload($file);
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $mime = mime_content_type($file['tmp_name']);
        $extension = $config['uploads']['allowed_mime'][$mime];
        $storedName = bin2hex(random_bytes(18)) . '.' . $extension;
        $destination = $config['uploads']['path'] . '/' . $storedName;

        if (!is_dir($config['uploads']['path'])) {
            mkdir($config['uploads']['path'], 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new RuntimeException('Upload failed. Try again.');
        }

        $file['type'] = $mime;
        Database::transaction(function () use ($policyId, $userId, $file, $storedName, $documentType, $policy): void {
            Document::create($policyId, $userId, $file, $storedName, $documentType);
            ActivityLog::record($userId, 'document_uploaded', 'Uploaded ' . ($documentType ?: 'document') . ' for ' . $policy['policy_number'], $policyId, [
                'document_type' => $documentType ?: 'Other',
                'original_name' => $file['name'],
                'size_bytes' => $file['size'],
            ]);
        });
    }

    public function delete(int $documentId, int $userId): ?int
    {
        $document = Document::find($documentId);
        if (!$document) {
            return null;
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        $path = $config['uploads']['path'] . '/' . $document['stored_name'];
        if (is_file($path)) {
            unlink($path);
        }

        Database::transaction(function () use ($document, $userId): void {
            Document::delete((int) $document['id']);
            ActivityLog::record($userId, 'document_deleted', 'Deleted document ' . $document['original_name'], (int) $document['policy_id'], [
                'document_id' => (int) $document['id'],
                'document_type' => $document['document_type'],
                'original_name' => $document['original_name'],
            ]);
        });

        return (int) $document['policy_id'];
    }

    private function validateUpload(?array $file): void
    {
        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Choose a JPG, PNG, or PDF file.');
        }

        $config = require dirname(__DIR__, 2) . '/config/config.php';
        if ($file['size'] > $config['uploads']['max_bytes']) {
            throw new RuntimeException('File is too large. Maximum size is 5 MB.');
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!array_key_exists($mime, $config['uploads']['allowed_mime'])) {
            throw new RuntimeException('Only JPG, PNG, and PDF files are allowed.');
        }
    }
}
