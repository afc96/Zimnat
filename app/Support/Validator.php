<?php

namespace App\Support;

use App\Models\Role;

class Validator
{
    public static function policy(array $data): array
    {
        $errors = [];

        // Keep validation close to the domain rules shared by create and update.
        foreach (['policy_number', 'insurance_type', 'premium_amount', 'payment_frequency', 'start_date', 'renewal_date', 'reminder_days', 'status'] as $field) {
            if (trim((string) ($data[$field] ?? '')) === '') {
                $errors[$field] = 'This field is required.';
            }
        }

        if (empty($data['client_id']) && trim((string) ($data['client_name'] ?? '')) === '') {
            $errors['client_name'] = 'Select an existing client or enter a new client name.';
        }

        if (!empty($data['client_email']) && !filter_var($data['client_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['client_email'] = 'Enter a valid email address.';
        }

        if (!in_array($data['client_type'] ?? 'Individual', ['Individual', 'Corporate', 'SME', 'Group'], true)) {
            $errors['client_type'] = 'Choose a valid client type.';
        }

        if (!in_array($data['segment'] ?? 'Retail', ['Retail', 'VIP', 'SME', 'Corporate'], true)) {
            $errors['segment'] = 'Choose a valid client segment.';
        }

        if (!in_array($data['preferred_contact'] ?? 'Phone', ['Phone', 'Email', 'SMS', 'WhatsApp'], true)) {
            $errors['preferred_contact'] = 'Choose a valid contact method.';
        }

        if (!in_array($data['client_status'] ?? 'Active', ['Active', 'Inactive', 'Watchlist'], true)) {
            $errors['client_status'] = 'Choose a valid client status.';
        }

        if (!empty($data['premium_amount']) && !is_numeric($data['premium_amount'])) {
            $errors['premium_amount'] = 'Enter a valid premium amount.';
        }

        if (!empty($data['start_date']) && !self::isDate($data['start_date'])) {
            $errors['start_date'] = 'Use a valid date.';
        }

        if (!empty($data['renewal_date']) && !self::isDate($data['renewal_date'])) {
            $errors['renewal_date'] = 'Use a valid date.';
        }

        if (empty($errors['start_date']) && empty($errors['renewal_date']) && !empty($data['start_date']) && !empty($data['renewal_date'])) {
            if (strtotime($data['renewal_date']) < strtotime($data['start_date'])) {
                $errors['renewal_date'] = 'Renewal date must be after the start date.';
            }
        }

        if (!in_array($data['status'] ?? '', ['Active', 'Expired', 'Pending Renewal'], true)) {
            $errors['status'] = 'Choose a valid status.';
        }

        if (!in_array($data['payment_frequency'] ?? '', ['Monthly', 'Quarterly', 'Annually'], true)) {
            $errors['payment_frequency'] = 'Choose a valid payment frequency.';
        }

        if (!empty($data['reminder_days']) && ((int) $data['reminder_days'] < 1 || (int) $data['reminder_days'] > 365)) {
            $errors['reminder_days'] = 'Reminder days must be between 1 and 365.';
        }

        return $errors;
    }

    public static function user(array $data, bool $passwordRequired = true): array
    {
        $errors = [];
        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = 'Name is required.';
        }
        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }
        if (!Role::exists((string) ($data['role'] ?? ''))) {
            $errors['role'] = 'Choose a valid role.';
        }
        if ($passwordRequired && strlen((string) ($data['password'] ?? '')) < 8) {
            $errors['password'] = 'Use at least 8 characters.';
        }

        return $errors;
    }

    public static function profile(array $data): array
    {
        $errors = [];
        if (trim((string) ($data['name'] ?? '')) === '') {
            $errors['name'] = 'Name is required.';
        }
        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Enter a valid email address.';
        }

        $password = (string) ($data['password'] ?? '');
        if ($password !== '') {
            if (strlen($password) < 8) {
                $errors['password'] = 'Use at least 8 characters.';
            }
            if ($password !== (string) ($data['password_confirmation'] ?? '')) {
                $errors['password_confirmation'] = 'Passwords must match.';
            }
            if (trim((string) ($data['current_password'] ?? '')) === '') {
                $errors['current_password'] = 'Enter your current password to change it.';
            }
        }

        return $errors;
    }

    public static function client(array $data): array
    {
        $errors = [];
        if (trim((string) ($data['client_name'] ?? '')) === '') {
            $errors['client_name'] = 'Client name is required.';
        }
        if (!empty($data['client_email']) && !filter_var($data['client_email'], FILTER_VALIDATE_EMAIL)) {
            $errors['client_email'] = 'Enter a valid email address.';
        }
        if (!in_array($data['client_type'] ?? 'Individual', ['Individual', 'Corporate', 'SME', 'Group'], true)) {
            $errors['client_type'] = 'Choose a valid client type.';
        }
        if (!in_array($data['segment'] ?? 'Retail', ['Retail', 'VIP', 'SME', 'Corporate'], true)) {
            $errors['segment'] = 'Choose a valid segment.';
        }
        if (!in_array($data['preferred_contact'] ?? 'Phone', ['Phone', 'Email', 'SMS', 'WhatsApp'], true)) {
            $errors['preferred_contact'] = 'Choose a valid contact method.';
        }
        if (!in_array($data['client_status'] ?? 'Active', ['Active', 'Inactive', 'Watchlist'], true)) {
            $errors['client_status'] = 'Choose a valid client status.';
        }
        return $errors;
    }

    public static function role(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = 'Role name is required.';
        } elseif (strlen($name) > 120) {
            $errors['name'] = 'Role name must be 120 characters or fewer.';
        }

        $validPermissions = array_column(Role::permissions(), 'slug');
        $submittedPermissions = (array) ($data['permissions'] ?? []);
        foreach ($submittedPermissions as $permission) {
            if (!in_array($permission, $validPermissions, true)) {
                $errors['permissions'] = 'Choose valid permissions only.';
                break;
            }
        }

        return $errors;
    }

    public static function documentType(array $data): array
    {
        $errors = [];
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = 'Document type is required.';
        } elseif (strlen($name) > 120) {
            $errors['name'] = 'Document type must be 120 characters or fewer.';
        }

        $sortOrder = $data['sort_order'] ?? 100;
        if (!is_numeric($sortOrder) || (int) $sortOrder < 0 || (int) $sortOrder > 9999) {
            $errors['sort_order'] = 'Order must be between 0 and 9999.';
        }

        return $errors;
    }

    private static function isDate(string $date): bool
    {
        $parsed = date_create_from_format('Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
