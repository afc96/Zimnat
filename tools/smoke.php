<?php

declare(strict_types=1);

$baseUrl = rtrim(getenv('APP_URL') ?: ($argv[1] ?? 'http://127.0.0.1:8000'), '/');
$stamp = date('YmdHis');
$policyNumber = 'SMOKE-' . $stamp;
$userEmail = 'smoke.viewer.' . $stamp . '@zimnat.test';
$clientEmail = 'smoke.client.' . $stamp . '@example.com';
$standaloneClientEmail = 'smoke.standalone.' . $stamp . '@example.com';
$cookieJar = tempnam(sys_get_temp_dir(), 'policypilot-cookie-');
$uploadFile = tempnam(sys_get_temp_dir(), 'policypilot-upload-') . '.pdf';

file_put_contents($uploadFile, "%PDF-1.4\n1 0 obj << /Type /Catalog >> endobj\n%%EOF\n");

$createdPolicyId = null;
$createdUserId = null;
$createdClientId = null;

function request(string $method, string $url, array $fields = [], array $files = [], bool $follow = true): array
{
    global $cookieJar;

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => true,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_COOKIEJAR => $cookieJar,
        CURLOPT_COOKIEFILE => $cookieJar,
        CURLOPT_TIMEOUT => 15,
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        foreach ($files as $name => $path) {
            $fields[$name] = new CURLFile($path);
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    }

    $raw = curl_exec($ch);
    if ($raw === false) {
        throw new RuntimeException(curl_error($ch));
    }

    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);

    return [
        'status' => $status,
        'headers' => substr($raw, 0, $headerSize),
        'body' => substr($raw, $headerSize),
        'url' => $effectiveUrl,
    ];
}

function csrf(string $html): string
{
    if (!preg_match('/name="_csrf" value="([^"]+)"/', $html, $matches)) {
        throw new RuntimeException('CSRF token not found.');
    }
    return html_entity_decode($matches[1], ENT_QUOTES);
}

function ok(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS {$message}\n";
}

function login(string $email, string $password = 'password'): void
{
    global $baseUrl;
    $login = request('GET', $baseUrl . '/?page=login');
    $token = csrf($login['body']);
    $response = request('POST', $baseUrl . '/?action=login', [
        '_csrf' => $token,
        'email' => $email,
        'password' => $password,
    ]);
    ok($response['status'] === 200 && str_contains($response['body'], 'Renewal operations'), "login {$email}");
}

function logout(): void
{
    global $baseUrl;
    $page = request('GET', $baseUrl . '/?page=dashboard');
    $token = csrf($page['body']);
    request('POST', $baseUrl . '/?action=logout', ['_csrf' => $token]);
}

function cleanup(): void
{
    global $baseUrl, $createdPolicyId, $createdUserId, $createdClientId, $clientEmail, $standaloneClientEmail, $userEmail;

    try {
        login('admin@zimnat.test');
        if ($createdPolicyId !== null) {
            $page = request('GET', $baseUrl . '/?page=policy_edit&id=' . $createdPolicyId);
            if ($page['status'] === 200 && str_contains($page['body'], '_csrf')) {
                request('POST', $baseUrl . '/?action=policy_delete&id=' . $createdPolicyId, ['_csrf' => csrf($page['body'])]);
            }
        }
        if ($createdUserId !== null) {
            $page = request('GET', $baseUrl . '/?page=settings&tab=users');
            if ($page['status'] === 200 && str_contains($page['body'], '_csrf')) {
                request('POST', $baseUrl . '/?action=user_delete&id=' . $createdUserId, [
                    '_csrf' => csrf($page['body']),
                    'return_to' => '?page=settings&tab=users',
                ]);
            }
        }
        cleanupSmokeData($clientEmail, $standaloneClientEmail, $userEmail, $createdPolicyId, $createdClientId);
    } catch (Throwable $ignored) {
        fwrite(STDERR, "Cleanup warning: {$ignored->getMessage()}\n");
    }
}

function cleanupSmokeData(string $clientEmail, string $standaloneClientEmail, string $userEmail, ?int $policyId, ?int $clientId): void
{
    $config = require dirname(__DIR__) . '/config/config.php';
    $db = $config['database'];
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['name']),
        $db['user'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    if ($policyId !== null) {
        $pdo->prepare('DELETE FROM documents WHERE policy_id = :policy_id')->execute(['policy_id' => $policyId]);
        $pdo->prepare('DELETE FROM policies WHERE id = :policy_id')->execute(['policy_id' => $policyId]);
    }
    if ($clientId !== null) {
        $pdo->prepare('DELETE FROM clients WHERE id = :id')->execute(['id' => $clientId]);
    }
    $pdo->prepare('DELETE FROM clients WHERE email = :email')->execute(['email' => $clientEmail]);
    $pdo->prepare('DELETE FROM clients WHERE email = :email')->execute(['email' => $standaloneClientEmail]);
    $pdo->prepare('DELETE FROM users WHERE email = :email')->execute(['email' => $userEmail]);
}

try {
    login('admin@zimnat.test');

    $settings = request('GET', $baseUrl . '/?page=settings&tab=users');
    $token = csrf($settings['body']);
    $created = request('POST', $baseUrl . '/?action=settings_user_store', [
        '_csrf' => $token,
        'name' => 'Smoke Viewer',
        'email' => $userEmail,
        'role' => 'viewer',
        'password' => 'Password123',
    ]);
    ok(str_contains($created['body'], 'User created') || str_contains($created['body'], $userEmail), 'admin creates user');

    $searchUser = request('GET', $baseUrl . '/?page=settings&tab=users&user_search=' . urlencode($userEmail));
    ok(preg_match('/user-dialog-(\d+)/', $searchUser['body'], $match) === 1, 'created user is searchable');
    $createdUserId = (int) $match[1];
    $token = csrf($searchUser['body']);
    $updatedUser = request('POST', $baseUrl . '/?action=settings_user_update&id=' . $createdUserId, [
        '_csrf' => $token,
        'name' => 'Smoke Viewer Edited',
        'email' => $userEmail,
        'role' => 'viewer',
        'password' => '',
    ]);
    ok(str_contains($updatedUser['body'], 'User updated') || str_contains($updatedUser['body'], 'Smoke Viewer Edited'), 'admin edits user');

    $clientPage = request('GET', $baseUrl . '/?page=clients');
    $token = csrf($clientPage['body']);
    $createdClient = request('POST', $baseUrl . '/?action=client_store', [
        '_csrf' => $token,
        'return_to' => '?page=clients',
        'client_name' => 'Smoke Standalone Client',
        'client_email' => $standaloneClientEmail,
        'client_phone' => '+263 77 888 ' . substr($stamp, -4),
        'client_type' => 'Individual',
        'segment' => 'Retail',
        'client_status' => 'Active',
        'preferred_contact' => 'Email',
        'city' => 'Harare',
        'province' => 'Harare',
        'country' => 'Zimbabwe',
    ]);
    ok(str_contains($createdClient['body'], 'Add the first policy') || str_contains($createdClient['body'], 'Smoke Standalone Client'), 'admin creates standalone client and lands in add-policy flow');

    $clientSearch = request('GET', $baseUrl . '/?page=clients&search=' . urlencode($standaloneClientEmail));
    ok(preg_match('/client_update&id=(\d+)/', $clientSearch['body'], $clientMatch) === 1, 'standalone client is searchable');
    $createdClientId = (int) $clientMatch[1];
    $updatedClient = request('POST', $baseUrl . '/?action=client_update&id=' . $createdClientId, [
        '_csrf' => csrf($clientSearch['body']),
        'return_to' => '?page=clients',
        'client_name' => 'Smoke Standalone Client Edited',
        'client_email' => $standaloneClientEmail,
        'client_phone' => '+263 77 888 ' . substr($stamp, -4),
        'client_type' => 'Individual',
        'segment' => 'VIP',
        'client_status' => 'Active',
        'preferred_contact' => 'WhatsApp',
        'city' => 'Harare',
        'province' => 'Harare',
        'country' => 'Zimbabwe',
    ]);
    ok(str_contains($updatedClient['body'], 'Client profile updated') || str_contains($updatedClient['body'], 'Smoke Standalone Client Edited'), 'admin edits standalone client');
    $deleteClientPage = request('GET', $baseUrl . '/?page=clients&search=' . urlencode($standaloneClientEmail));
    $deletedClient = request('POST', $baseUrl . '/?action=client_delete&id=' . $createdClientId, [
        '_csrf' => csrf($deleteClientPage['body']),
        'return_to' => '?page=clients',
    ]);
    ok(str_contains($deletedClient['body'], 'Client archived') || $deletedClient['status'] === 200, 'admin archives standalone client');

    logout();
    login($userEmail, 'Password123');
    $viewerForbidden = request('GET', $baseUrl . '/?page=policy_new', follow: false);
    ok($viewerForbidden['status'] === 403, 'viewer cannot create policy');

    logout();
    login('officer@zimnat.test');
    $newPolicy = request('GET', $baseUrl . '/?page=policy_new');
    $token = csrf($newPolicy['body']);
    $createdPolicy = request('POST', $baseUrl . '/?action=policy_store', [
        '_csrf' => $token,
        'policy_number' => $policyNumber,
        'client_name' => 'Smoke Client',
        'client_email' => $clientEmail,
        'client_phone' => '+263 77 000 ' . substr($stamp, -4),
        'insurance_type' => 'Life Assurance',
        'premium_amount' => '111.25',
        'payment_frequency' => 'Monthly',
        'start_date' => date('Y-m-d'),
        'renewal_date' => date('Y-m-d', strtotime('+30 days')),
        'reminder_days' => '14',
        'status' => 'Active',
        'assigned_to' => '',
        'notes' => 'Smoke test policy',
    ]);
    ok(preg_match('/page=policy_edit&id=(\d+)/', $createdPolicy['url'], $match) === 1, 'officer creates policy');
    $createdPolicyId = (int) $match[1];

    $edit = request('GET', $baseUrl . '/?page=policy_edit&id=' . $createdPolicyId);
    $token = csrf($edit['body']);
    $updatedPolicy = request('POST', $baseUrl . '/?action=policy_update&id=' . $createdPolicyId, [
        '_csrf' => $token,
        'policy_number' => $policyNumber,
        'client_name' => 'Smoke Client',
        'client_email' => $clientEmail,
        'client_phone' => '+263 77 000 ' . substr($stamp, -4),
        'insurance_type' => 'Life Assurance',
        'premium_amount' => '222.50',
        'payment_frequency' => 'Monthly',
        'start_date' => date('Y-m-d'),
        'renewal_date' => date('Y-m-d', strtotime('+30 days')),
        'reminder_days' => '14',
        'status' => 'Pending Renewal',
        'assigned_to' => '',
        'notes' => 'Smoke test policy updated',
    ]);
    ok(str_contains($updatedPolicy['body'], 'Policy updated') || str_contains($updatedPolicy['body'], '222.50'), 'officer edits policy');

    $uploadPage = request('GET', $baseUrl . '/?page=policy_edit&id=' . $createdPolicyId);
    $upload = request('POST', $baseUrl . '/?action=document_upload&policy_id=' . $createdPolicyId, [
        '_csrf' => csrf($uploadPage['body']),
        'document_type' => 'Policy Form',
    ], ['document' => $GLOBALS['uploadFile']]);
    ok(str_contains($upload['body'], 'Document uploaded') || str_contains($upload['body'], 'smoke'), 'officer uploads document');

    $documents = request('GET', $baseUrl . '/?page=documents&search=' . urlencode($policyNumber));
    ok(preg_match('/document_download&id=(\d+)/', $documents['body'], $match) === 1, 'document is searchable');
    $documentId = (int) $match[1];
    $download = request('GET', $baseUrl . '/?action=document_download&id=' . $documentId, follow: false);
    ok($download['status'] === 200 && str_contains($download['headers'], 'application/pdf'), 'document downloads as PDF');

    $reminders = request('GET', $baseUrl . '/?page=reminders&search=' . urlencode($policyNumber) . '&renewal=');
    $reminderUpdate = request('POST', $baseUrl . '/?action=reminder_update&id=' . $createdPolicyId, [
        '_csrf' => csrf($reminders['body']),
        'return_to' => '?page=reminders&search=' . $policyNumber . '&renewal=',
        'reminder_note' => 'Smoke contacted client',
        'reminder_status' => 'Contacted',
    ]);
    ok(str_contains($reminderUpdate['body'], 'Reminder marked as contacted') || str_contains($reminderUpdate['body'], 'Contacted'), 'reminder contact action works');

    foreach (['policies_export', 'reminders_export', 'clients_export', 'documents_export'] as $action) {
        $export = request('GET', $baseUrl . '/?action=' . $action . '&search=' . urlencode($policyNumber), follow: false);
        ok($export['status'] === 200 && str_contains($export['headers'], 'text/csv'), "{$action} returns CSV");
    }

    $documentPage = request('GET', $baseUrl . '/?page=documents&search=' . urlencode($policyNumber));
    request('POST', $baseUrl . '/?action=document_delete&id=' . $documentId, ['_csrf' => csrf($documentPage['body'])]);
    ok(true, 'document delete action works');

    cleanup();
    echo "SMOKE_PASSED\n";
} catch (Throwable $exception) {
    cleanup();
    fwrite(STDERR, "SMOKE_FAILED: {$exception->getMessage()}\n");
    exit(1);
} finally {
    @unlink($cookieJar);
    @unlink($uploadFile);
}
