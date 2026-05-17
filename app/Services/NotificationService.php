<?php

namespace App\Services;

class NotificationService
{
    public function sendRenewalContact(array $policy, ?string $note = null): array
    {
        $subject = 'Policy renewal reminder for ' . $policy['policy_number'];
        $message = $this->message($policy, $note);
        $results = [];

        if (!empty($policy['client_email'])) {
            $results[] = $this->sendEmail((string) $policy['client_email'], $subject, $message);
        }

        if (!empty($policy['client_phone'])) {
            $results[] = $this->sendSms((string) $policy['client_phone'], $message);
        }

        if (!$results) {
            $results[] = ['channel' => 'notification', 'status' => 'skipped', 'detail' => 'No client email or phone captured.'];
        }

        foreach ($results as $result) {
            $this->log($result + ['policy' => $policy['policy_number']]);
        }

        return $results;
    }

    private function sendEmail(string $to, string $subject, string $message): array
    {
        $config = $this->config();
        if (($config['email_transport'] ?? 'log') !== 'mail') {
            return ['channel' => 'email', 'status' => 'logged', 'to' => $to, 'subject' => $subject];
        }

        $headers = 'From: ' . ($config['email_from'] ?? 'noreply@zimnat.test');
        $sent = @mail($to, $subject, $message, $headers);
        return ['channel' => 'email', 'status' => $sent ? 'sent' : 'failed', 'to' => $to, 'subject' => $subject];
    }

    private function sendSms(string $to, string $message): array
    {
        $config = $this->config();
        $webhook = trim((string) ($config['sms_webhook_url'] ?? ''));
        if ($webhook === '') {
            return ['channel' => 'sms', 'status' => 'logged', 'to' => $to];
        }

        $payload = json_encode(['to' => $to, 'message' => $message], JSON_THROW_ON_ERROR);
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => $payload,
                'timeout' => 3,
            ],
        ]);
        $response = @file_get_contents($webhook, false, $context);

        return ['channel' => 'sms', 'status' => $response === false ? 'failed' : 'sent', 'to' => $to];
    }

    private function message(array $policy, ?string $note): string
    {
        $lines = [
            'Hello ' . $policy['client_name'] . ',',
            '',
            'This is a reminder that policy ' . $policy['policy_number'] . ' is due for renewal on ' . $policy['renewal_date'] . '.',
            'Please contact your policy officer if any documents or renewal details need to be updated.',
        ];

        if ($note !== null && trim($note) !== '') {
            $lines[] = '';
            $lines[] = 'Staff note: ' . trim($note);
        }

        return implode("\n", $lines);
    }

    private function config(): array
    {
        $config = require dirname(__DIR__, 2) . '/config/config.php';
        return $config['notifications'];
    }

    private function log(array $entry): void
    {
        $config = $this->config();
        $path = $config['log_path'];
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, json_encode(['time' => date('c')] + $entry) . PHP_EOL, FILE_APPEND);
    }
}
