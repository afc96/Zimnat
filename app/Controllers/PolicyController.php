<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\Document;
use App\Models\Policy;
use App\Models\User;
use App\Services\PolicyService;
use App\Support\Validator;
use PDOException;

class PolicyController extends Controller
{
    public function index(): void
    {
        $this->requirePermission('policy.view');
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'reminder_status' => trim((string) ($_GET['reminder_status'] ?? '')),
            'renewal' => trim((string) ($_GET['renewal'] ?? '')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'docs' => trim((string) ($_GET['docs'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'policy_id' => (int) ($_GET['policy_id'] ?? 0),
            'sort' => trim((string) ($_GET['sort'] ?? 'renewal_date')),
            'direction' => trim((string) ($_GET['direction'] ?? 'ASC')),
            'page' => (int) ($_GET['p'] ?? 1),
            'per_page' => 10,
        ];

        $this->view('policies/index', [
            'result' => Policy::paginate($filters),
            'filters' => $filters,
            'staff' => User::assignable(),
        ]);
    }

    public function export(): void
    {
        $this->requirePermission('policy.view');
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'reminder_status' => trim((string) ($_GET['reminder_status'] ?? '')),
            'renewal' => trim((string) ($_GET['renewal'] ?? '')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'docs' => trim((string) ($_GET['docs'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'policy_id' => (int) ($_GET['policy_id'] ?? 0),
            'ids' => $this->idsFromRequest($_GET['ids'] ?? ''),
            'sort' => trim((string) ($_GET['sort'] ?? 'renewal_date')),
            'direction' => trim((string) ($_GET['direction'] ?? 'ASC')),
        ];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="policies-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Policy Number', 'Client', 'Email', 'Phone', 'Type', 'Premium', 'Frequency', 'Renewal Date', 'Policy Status', 'Reminder Status', 'Assigned', 'Created By', 'Last Edited'], ',', '"', '');
        foreach (Policy::exportRows($filters) as $policy) {
            fputcsv($out, [
                $policy['policy_number'],
                $policy['client_name'],
                $policy['client_email'],
                $policy['client_phone'],
                $policy['insurance_type'],
                $policy['premium_amount'],
                $policy['payment_frequency'],
                $policy['renewal_date'],
                $policy['status'],
                $policy['reminder_status'],
                $policy['assigned_name'] ?? 'Unassigned',
                $policy['created_name'] ?? 'Unknown',
                $policy['updated_at'],
            ], ',', '"', '');
        }
        exit;
    }

    public function create(): void
    {
        $this->requirePermission('policy.create');
        $client = !empty($_GET['client_id']) ? Client::find((int) $_GET['client_id']) : null;
        $policy = $client ? [
            'client_id' => $client['id'],
            'client_name' => $client['client_name'],
            'client_email' => $client['client_email'],
            'client_phone' => $client['client_phone'],
            'client_type' => $client['client_type'],
            'alternate_phone' => $client['alternate_phone'],
            'national_id' => $client['national_id'],
            'tax_number' => $client['tax_number'],
            'address_line1' => $client['address_line1'],
            'suburb' => $client['suburb'],
            'city' => $client['city'],
            'province' => $client['province'],
            'country' => $client['country'],
            'preferred_contact' => $client['preferred_contact'],
            'segment' => $client['segment'],
            'client_status' => $client['client_status'],
            'client_notes' => $client['client_notes'],
        ] : null;
        $this->view('policies/form', [
            'policy' => $policy,
            'errors' => [],
            'documents' => [],
            'checklist' => [],
            'timeline' => [],
            'staff' => User::assignable(),
            'clients' => Client::options(),
        ]);
    }

    public function store(): void
    {
        $this->requirePermission('policy.create');
        Csrf::verify();
        $errors = Validator::policy($_POST);
        if ($errors) {
            $this->view('policies/form', ['policy' => $_POST, 'errors' => $errors, 'documents' => [], 'checklist' => [], 'timeline' => [], 'staff' => User::assignable(), 'clients' => Client::options()]);
            return;
        }

        try {
            $id = (new PolicyService())->create($_POST, (int) Auth::user()['id']);
            $this->flash('success', 'Policy created successfully.');
            $this->redirect('?page=policy_edit&id=' . $id);
        } catch (PDOException $exception) {
            $errors = ['policy_number' => 'This policy number already exists.'];
            $this->view('policies/form', ['policy' => $_POST, 'errors' => $errors, 'documents' => [], 'checklist' => [], 'timeline' => [], 'staff' => User::assignable(), 'clients' => Client::options()]);
        }
    }

    public function edit(): void
    {
        $this->requirePermission('policy.view');
        $policy = Policy::find((int) ($_GET['id'] ?? 0));
        if (!$policy) {
            http_response_code(404);
            $this->view('errors/404');
            return;
        }

        $this->view('policies/form', [
            'policy' => $policy,
            'errors' => [],
            'documents' => Document::forPolicy((int) $policy['id']),
            'checklist' => Document::checklist((int) $policy['id']),
            'timeline' => ActivityLog::forPolicy((int) $policy['id']),
            'staff' => User::assignable(),
            'clients' => Client::options(),
        ]);
    }

    public function update(): void
    {
        $this->requirePermission('policy.update');
        Csrf::verify();
        $id = (int) ($_GET['id'] ?? 0);
        $errors = Validator::policy($_POST);
        if ($errors) {
            $this->view('policies/form', [
                'policy' => $_POST + ['id' => $id],
                'errors' => $errors,
                'documents' => Document::forPolicy($id),
                'checklist' => Document::checklist($id),
                'timeline' => ActivityLog::forPolicy($id),
                'staff' => User::assignable(),
                'clients' => Client::options(),
            ]);
            return;
        }

        try {
            (new PolicyService())->update($id, $_POST, (int) Auth::user()['id']);
            $this->flash('success', 'Policy updated.');
            $this->redirect('?page=policy_edit&id=' . $id);
        } catch (PDOException $exception) {
            $errors = ['policy_number' => 'This policy number already exists.'];
            $this->view('policies/form', [
                'policy' => $_POST + ['id' => $id],
                'errors' => $errors,
                'documents' => Document::forPolicy($id),
                'checklist' => Document::checklist($id),
                'timeline' => ActivityLog::forPolicy($id),
                'staff' => User::assignable(),
                'clients' => Client::options(),
            ]);
        }
    }

    public function destroy(): void
    {
        $this->requirePermission('policy.delete');
        Csrf::verify();
        (new PolicyService())->delete((int) ($_GET['id'] ?? 0), (int) Auth::user()['id']);
        $this->flash('success', 'Policy removed.');
        $this->redirect('?page=policies');
    }

    public function bulk(): void
    {
        $this->requirePermission('policy.update');
        Csrf::verify();

        $ids = array_map('intval', $_POST['ids'] ?? []);
        $bulkAction = (string) ($_POST['bulk_action'] ?? '');
        if (!$ids) {
            $this->flash('danger', 'Select at least one policy first.');
            $this->redirect('?page=policies');
        }

        if ($bulkAction === 'export') {
            $_GET['ids'] = implode(',', $ids);
            $this->export();
            return;
        }

        $count = 0;
        if ($bulkAction === 'status') {
            $count = Policy::bulkUpdateStatus($ids, (string) ($_POST['bulk_status'] ?? ''), (int) Auth::user()['id']);
        } elseif ($bulkAction === 'assign') {
            $assignedTo = $_POST['bulk_assigned_to'] !== '' ? (int) $_POST['bulk_assigned_to'] : null;
            $count = Policy::bulkAssign($ids, $assignedTo, (int) Auth::user()['id']);
        }

        ActivityLog::record((int) Auth::user()['id'], 'policy_bulk_update', 'Updated ' . $count . ' selected policies', null, [
            'ids' => $ids,
            'bulk_action' => $bulkAction,
            'count' => $count,
        ]);
        $this->flash($count ? 'success' : 'danger', $count ? $count . ' policies updated.' : 'Choose a valid bulk action.');
        $this->redirect('?page=policies');
    }

    private function idsFromRequest(string $ids): array
    {
        if ($ids === '') {
            return [];
        }
        return array_values(array_filter(array_map('intval', explode(',', $ids))));
    }
}
