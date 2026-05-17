<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Database;
use App\Core\RateLimiter;
use App\Models\Client;
use App\Models\Dashboard;
use App\Models\Policy;
use App\Services\ClientService;
use App\Services\PolicyService;
use App\Support\Validator;

function assertTrue(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS {$message}\n";
}

$stamp = date('YmdHis');
$policyNumber = 'UNIT-' . $stamp;
$clientEmail = 'unit.client.' . $stamp . '@example.com';
$db = Database::connection();

try {
    $rateLimitKey = 'unit|' . $stamp;
    RateLimiter::clear($rateLimitKey);
    RateLimiter::hit($rateLimitKey, 60);
    RateLimiter::hit($rateLimitKey, 60);
    assertTrue(RateLimiter::tooManyAttempts($rateLimitKey, 2, 60), 'rate limiter blocks repeated login attempts');
    RateLimiter::clear($rateLimitKey);

    $policyErrors = Validator::policy([
        'policy_number' => '',
        'client_name' => '',
        'client_email' => 'not-an-email',
        'insurance_type' => '',
        'premium_amount' => 'abc',
        'payment_frequency' => 'Weekly',
        'start_date' => '2026-05-20',
        'renewal_date' => '2026-05-01',
        'reminder_days' => '999',
        'status' => 'Unknown',
    ]);
    assertTrue(isset($policyErrors['policy_number'], $policyErrors['client_email'], $policyErrors['renewal_date']), 'policy validator catches invalid input');

    $clientId = (new ClientService())->create([
        'client_name' => 'Unit Client',
        'client_email' => $clientEmail,
        'client_phone' => '+263 77 999 0000',
        'client_type' => 'Individual',
        'segment' => 'Retail',
        'client_status' => 'Active',
        'preferred_contact' => 'Email',
        'city' => 'Harare',
        'province' => 'Harare',
        'country' => 'Zimbabwe',
    ], 1);
    $client = Client::find($clientId);
    assertTrue($client && $client['client_email'] === $clientEmail, 'client service creates standalone clients');

    $policyId = (new PolicyService())->create([
        'policy_number' => $policyNumber,
        'client_name' => 'Unit Client',
        'client_email' => $clientEmail,
        'client_phone' => '+263 77 999 0000',
        'client_type' => 'Individual',
        'segment' => 'Retail',
        'client_status' => 'Active',
        'preferred_contact' => 'Email',
        'insurance_type' => 'Life Assurance',
        'premium_amount' => '100.00',
        'payment_frequency' => 'Monthly',
        'start_date' => date('Y-m-d'),
        'renewal_date' => date('Y-m-d', strtotime('+45 days')),
        'reminder_days' => '30',
        'status' => 'Active',
        'assigned_to' => '',
        'notes' => 'Unit test policy',
    ], 1);
    $policy = Policy::find($policyId);
    assertTrue($policy && $policy['client_email'] === $clientEmail, 'policy service links policies to clients');

    $dashboardStats = Dashboard::stats();
    assertTrue(
        $dashboardStats['policies_with_documents'] + $dashboardStats['missing_documents'] === $dashboardStats['total'],
        'dashboard document compliance uses policy counts'
    );

    (new PolicyService())->delete($policyId, 1);
    assertTrue(Policy::find($policyId) === null, 'policy delete is soft and hidden from normal reads');
} finally {
    $db->prepare('DELETE FROM policies WHERE policy_number = :policy_number')->execute(['policy_number' => $policyNumber]);
    $db->prepare('DELETE FROM clients WHERE email = :email')->execute(['email' => $clientEmail]);
}

echo "TESTS_PASSED\n";
