<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Models\Client;
use App\Models\Document;
use App\Models\Policy;
use App\Services\ClientService;
use App\Services\ReminderService;
use App\Support\Validator;

class OperationsController extends Controller
{
    public function reminders(): void
    {
        $this->requirePermission('reminder.manage');
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'reminder_status' => trim((string) ($_GET['reminder_status'] ?? '')),
            'renewal' => trim((string) ($_GET['renewal'] ?? 'soon')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'policy_id' => (int) ($_GET['policy_id'] ?? 0),
            'sort' => trim((string) ($_GET['sort'] ?? 'renewal_date')),
            'direction' => trim((string) ($_GET['direction'] ?? 'ASC')),
            'page' => (int) ($_GET['p'] ?? 1),
            'per_page' => 10,
        ];

        $this->view('reminders/index', [
            'result' => Policy::paginate($filters),
            'filters' => $filters,
            'documentTypeCount' => $this->requiredDocumentTypeCount(),
        ]);
    }

    public function myTasks(): void
    {
        $this->requirePermission('reminder.manage');
        $userId = (int) Auth::user()['id'];
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

        $this->view('my_tasks/index', [
            'result' => Policy::myQueue($userId, $filters),
            'summaryItems' => Policy::myQueue($userId, ['per_page' => 10000, 'export' => true])['items'],
            'filters' => $filters,
            'staff' => [],
            'documentTypeCount' => $this->requiredDocumentTypeCount(),
        ]);
    }

    public function updateReminder(): void
    {
        $this->requirePermission('reminder.manage');
        Csrf::verify();
        $id = (int) ($_GET['id'] ?? 0);
        $status = trim((string) ($_POST['reminder_status'] ?? 'Pending'));
        if (!in_array($status, ['Pending', 'Contacted', 'Snoozed', 'Failed', 'Resolved'], true)) {
            $this->flash('danger', 'Choose a valid reminder action.');
            $this->redirect('?page=reminders');
        }

        $snoozedUntil = trim((string) ($_POST['reminder_snoozed_until'] ?? ''));
        if ($status === 'Snoozed' && $snoozedUntil === '') {
            $snoozedUntil = date('Y-m-d', strtotime('+7 days'));
        }

        $updated = (new ReminderService())->update($id, $status, trim((string) ($_POST['reminder_note'] ?? '')), $snoozedUntil ?: null, (int) Auth::user()['id']);
        if (!$updated) {
            $this->flash('danger', 'Policy not found.');
            $this->redirect('?page=reminders');
        }
        $this->flash('success', 'Reminder marked as ' . strtolower($status) . '.');
        $this->redirect($this->safeReturn($_POST['return_to'] ?? '?page=reminders'));
    }

    public function bulkReminders(): void
    {
        $this->requirePermission('reminder.manage');
        Csrf::verify();
        $ids = array_map('intval', $_POST['ids'] ?? []);
        if (!$ids) {
            $this->flash('danger', 'Select at least one reminder first.');
            $this->redirect($this->safeReturn($_POST['return_to'] ?? '?page=reminders'));
        }

        if (($_POST['bulk_action'] ?? '') === 'export') {
            $_GET['ids'] = implode(',', $ids);
            $this->exportReminders();
            return;
        }

        $status = (string) ($_POST['reminder_status'] ?? '');
        $snoozedUntil = trim((string) ($_POST['reminder_snoozed_until'] ?? ''));
        if ($status === 'Snoozed' && $snoozedUntil === '') {
            $snoozedUntil = date('Y-m-d', strtotime('+7 days'));
        }

        $count = (new ReminderService())->bulk($ids, $status, trim((string) ($_POST['reminder_note'] ?? '')), $snoozedUntil ?: null, (int) Auth::user()['id']);
        $this->flash($count ? 'success' : 'danger', $count ? $count . ' reminders updated.' : 'Choose a valid reminder action.');
        $this->redirect($this->safeReturn($_POST['return_to'] ?? '?page=reminders'));
    }

    public function exportReminders(): void
    {
        $this->requirePermission('reminder.manage');
        $ids = $this->idsFromRequest($_GET['ids'] ?? '');
        $filters = [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'reminder_status' => trim((string) ($_GET['reminder_status'] ?? '')),
            'renewal' => $ids ? trim((string) ($_GET['renewal'] ?? '')) : trim((string) ($_GET['renewal'] ?? 'soon')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'docs' => trim((string) ($_GET['docs'] ?? '')),
            'client_id' => (int) ($_GET['client_id'] ?? 0),
            'policy_id' => (int) ($_GET['policy_id'] ?? 0),
            'assigned_to' => !empty($_GET['mine']) ? (int) Auth::user()['id'] : 0,
            'ids' => $ids,
            'sort' => trim((string) ($_GET['sort'] ?? 'renewal_date')),
            'direction' => trim((string) ($_GET['direction'] ?? 'ASC')),
        ];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reminders-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Policy Number', 'Client', 'Phone', 'Email', 'Renewal Date', 'Lead Time', 'Reminder Status', 'Last Contacted', 'Snoozed Until', 'Assigned'], ',', '"', '');
        foreach (Policy::exportRows($filters) as $policy) {
            fputcsv($out, [
                $policy['policy_number'],
                $policy['client_name'],
                $policy['client_phone'],
                $policy['client_email'],
                $policy['renewal_date'],
                $policy['reminder_days'] . ' days',
                $policy['reminder_status'],
                $policy['reminder_last_contacted_at'],
                $policy['reminder_snoozed_until'],
                $policy['assigned_name'] ?? 'Unassigned',
            ], ',', '"', '');
        }
        exit;
    }

    public function clients(): void
    {
        $this->requirePermission('client.view');
        $filters = $this->clientFiltersFromRequest() + ['page' => (int) ($_GET['p'] ?? 1)];
        $this->view('clients/index', [
            'result' => Client::paginate($filters),
            'filters' => $filters,
            'clientPolicies' => Client::policiesByClient(),
            'documentTypeCount' => $this->requiredDocumentTypeCount(),
        ]);
    }

    public function exportClients(): void
    {
        $this->requirePermission('client.view');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="clients-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Client Code', 'Client', 'Type', 'Segment', 'Email', 'Phone', 'City', 'Preferred Contact', 'Policies', 'Active', 'Renewal/Overdue Needs', 'Missing Docs', 'Next Renewal'], ',', '"', '');
        foreach (Client::exportRows($this->clientFiltersFromRequest()) as $client) {
            fputcsv($out, [
                $client['client_code'],
                $client['client_name'],
                $client['client_type'],
                $client['segment'],
                $client['client_email'],
                $client['client_phone'],
                $client['city'],
                $client['preferred_contact'],
                $client['policy_count'],
                $client['active_count'],
                (int) $client['renewal_soon_count'] + (int) $client['expired_count'],
                $client['missing_docs_count'],
                $client['next_renewal'],
            ], ',', '"', '');
        }
        exit;
    }

    public function storeClient(): void
    {
        $this->requirePermission('policy.update');
        Csrf::verify();
        $errors = Validator::client($_POST);
        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('?page=clients');
        }

        try {
            $id = (new ClientService())->create($_POST, (int) Auth::user()['id']);
            $this->flash('success', 'Client created. Add the first policy for this client.');
            $this->redirect('?page=policy_new&client_id=' . $id);
        } catch (\PDOException) {
            $this->flash('danger', 'That client email is already assigned to another client.');
        }
        $this->redirect($this->safeReturn($_POST['return_to'] ?? '?page=clients'));
    }

    public function updateClient(): void
    {
        $this->requirePermission('policy.update');
        Csrf::verify();
        $id = (int) ($_GET['id'] ?? 0);
        $errors = Validator::client($_POST);
        if ($errors) {
            $this->flash('danger', reset($errors));
            $this->redirect('?page=clients');
        }

        try {
            (new ClientService())->update($id, $_POST, (int) Auth::user()['id']);
            $this->flash('success', 'Client profile updated.');
        } catch (\PDOException) {
            $this->flash('danger', 'That client email is already assigned to another client.');
        }
        $this->redirect($this->safeReturn($_POST['return_to'] ?? '?page=clients'));
    }

    public function deleteClient(): void
    {
        $this->requirePermission('policy.update');
        Csrf::verify();
        $deleted = (new ClientService())->delete((int) ($_GET['id'] ?? 0), (int) Auth::user()['id']);
        $this->flash($deleted ? 'success' : 'danger', $deleted ? 'Client archived.' : 'Clients with active policies cannot be archived.');
        $this->redirect($this->safeReturn($_POST['return_to'] ?? '?page=clients'));
    }

    private function idsFromRequest(string $ids): array
    {
        if ($ids === '') {
            return [];
        }
        return array_values(array_filter(array_map('intval', explode(',', $ids))));
    }

    private function safeReturn(string $url): string
    {
        return str_starts_with($url, '?page=') ? $url : '?page=reminders';
    }

    private function requiredDocumentTypeCount(): int
    {
        return max(1, count(array_filter(Document::types(), fn (array $type) => (int) ($type['is_required'] ?? 0) === 1)));
    }

    private function clientFiltersFromRequest(): array
    {
        return [
            'search' => trim((string) ($_GET['search'] ?? '')),
            'status' => trim((string) ($_GET['status'] ?? '')),
            'renewal' => trim((string) ($_GET['renewal'] ?? '')),
            'type' => trim((string) ($_GET['type'] ?? '')),
            'docs' => trim((string) ($_GET['docs'] ?? '')),
            'contact' => trim((string) ($_GET['contact'] ?? '')),
            'segment' => trim((string) ($_GET['segment'] ?? '')),
            'city' => trim((string) ($_GET['city'] ?? '')),
            'client_status' => trim((string) ($_GET['client_status'] ?? '')),
            'sort' => trim((string) ($_GET['sort'] ?? 'client_name')),
            'direction' => trim((string) ($_GET['direction'] ?? 'ASC')),
        ];
    }
}
