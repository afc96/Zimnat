<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Document;
use App\Services\DocumentService;
use RuntimeException;

class DocumentController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('document.view');
        $filters = $this->filtersFromRequest();
        $this->view('documents/index', [
            'result' => Document::paginate($filters + ['page' => (int) ($_GET['p'] ?? 1)]),
            'filters' => $filters,
            'documentTypes' => Document::types(),
            'search' => trim((string) ($_GET['search'] ?? '')),
        ]);
    }

    public function export(): void
    {
        $this->requirePermission('document.view');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="documents-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['File', 'Document Type', 'Policy Number', 'Client', 'Policy Status', 'Uploaded By', 'MIME Type', 'Size KB', 'Uploaded At'], ',', '"', '');
        foreach (Document::exportRows($this->filtersFromRequest()) as $document) {
            fputcsv($out, [
                $document['original_name'],
                $document['document_type'],
                $document['policy_number'],
                $document['client_name'],
                $document['policy_status'],
                $document['uploaded_by_name'] ?? 'Unknown',
                $document['mime_type'],
                number_format($document['size_bytes'] / 1024, 1),
                $document['created_at'],
            ], ',', '"', '');
        }
        exit;
    }

    public function upload(): void
    {
        $this->requirePermission('document.upload');
        Csrf::verify();

        $policyId = (int) ($_GET['policy_id'] ?? $_POST['policy_id'] ?? 0);
        try {
            (new DocumentService())->upload($policyId, (int) Auth::user()['id'], $_FILES['document'] ?? null, (string) ($_POST['document_type'] ?? 'Other'));
            $this->flash('success', 'Document uploaded.');
        } catch (RuntimeException $exception) {
            $this->flash('danger', $exception->getMessage());
        }
        $this->redirect('?page=policy_edit&id=' . $policyId);
    }

    public function download(): void
    {
        $this->streamDocument(false);
    }

    public function preview(): void
    {
        $this->streamDocument(true);
    }

    public function destroy(): void
    {
        $this->requirePermission('document.delete');
        Csrf::verify();
        $document = Document::find((int) ($_GET['id'] ?? 0));
        if ($document) {
            $policyId = (new DocumentService())->delete((int) $document['id'], (int) Auth::user()['id']);
            $this->flash('success', 'Document deleted.');
            $this->redirect('?page=policy_edit&id=' . $policyId);
        }
        $this->redirect('?page=documents');
    }

    private function filtersFromRequest(): array
    {
        return [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'document_type' => trim((string) ($_GET['document_type'] ?? '')),
            'file_type' => trim((string) ($_GET['file_type'] ?? '')),
            'policy_status' => trim((string) ($_GET['policy_status'] ?? '')),
            'uploaded_from' => trim((string) ($_GET['uploaded_from'] ?? '')),
            'uploaded_to' => trim((string) ($_GET['uploaded_to'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'policy_id' => (int) ($_GET['policy_id'] ?? 0),
            'sort' => trim((string) ($_GET['sort'] ?? 'created_at')),
            'direction' => trim((string) ($_GET['direction'] ?? 'DESC')),
        ];
    }

    private function streamDocument(bool $inline): void
    {
        $this->requirePermission('document.view');
        $document = Document::find((int) ($_GET['id'] ?? 0));
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        if (!$document || !is_file($config['uploads']['path'] . '/' . $document['stored_name'])) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $name = preg_replace('/[^A-Za-z0-9._ -]/', '_', basename($document['original_name']));
        $disposition = $inline ? 'inline' : 'attachment';
        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $document['mime_type']);
        header('Content-Disposition: ' . $disposition . '; filename="' . $name . '"');
        header('Content-Length: ' . filesize($config['uploads']['path'] . '/' . $document['stored_name']));
        readfile($config['uploads']['path'] . '/' . $document['stored_name']);
        exit;
    }
}
