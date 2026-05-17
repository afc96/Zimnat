<?php

namespace App\Services;

class AuditService
{
    public static function diff(array $before, array $after, array $fields): array
    {
        $changes = [];
        foreach ($fields as $field) {
            $old = self::normalize($before[$field] ?? null);
            $new = self::normalize($after[$field] ?? null);
            if ($old !== $new) {
                $changes[$field] = ['from' => $old, 'to' => $new];
            }
        }
        return $changes;
    }

    private static function normalize(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_float($value) || is_int($value)) {
            return (string) $value;
        }
        return trim((string) $value);
    }
}
