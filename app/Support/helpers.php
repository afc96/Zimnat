<?php

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|string $value): string
{
    return '$' . number_format((float) $value, 2);
}

function role_label(string $role): string
{
    try {
        return \App\Models\Role::label($role);
    } catch (Throwable) {
        return match ($role) {
            'admin' => 'Admin',
            'policy_officer' => 'Policy Officer',
            default => ucwords(str_replace('_', ' ', $role)),
        };
    }
}

function role_tone(string $role): string
{
    return match ($role) {
        'admin' => 'role-admin',
        'policy_officer' => 'role-policy-officer',
        'viewer' => 'role-viewer',
        default => 'role-custom',
    };
}

function sort_icon(bool $active, string $direction): string
{
    $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
    $symbol = $active ? ($direction === 'ASC' ? '&uarr;' : '&darr;') : '&#8597;';
    $label = $active ? 'Sorted ' . strtolower($direction) : 'Sortable column';
    return '<span class="sort-icon" aria-hidden="true">' . $symbol . '</span><span class="sr-only">' . e($label) . '</span>';
}

function renewal_badge(string $renewalDate, string $status): array
{
    $today = new DateTimeImmutable('today');
    $renewal = new DateTimeImmutable($renewalDate);
    $days = (int) $today->diff($renewal)->format('%r%a');

    if ($days < 0) {
        $overdue = abs($days);
        return ['label' => $overdue === 1 ? '1 day overdue' : "{$overdue} days overdue", 'tone' => 'danger'];
    }

    if ($days === 0) {
        return ['label' => 'Due today', 'tone' => 'warning'];
    }

    if ($days <= 30) {
        return ['label' => "in {$days} days", 'tone' => 'warning'];
    }

    return ['label' => 'Current', 'tone' => 'success'];
}

function reminder_tone(string $status): string
{
    return match ($status) {
        'Contacted', 'Resolved' => 'success',
        'Snoozed' => 'warning',
        'Failed' => 'danger',
        default => 'muted',
    };
}
