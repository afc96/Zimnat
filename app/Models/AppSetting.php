<?php

namespace App\Models;

use App\Core\Database;

class AppSetting
{
    public static function all(): array
    {
        $rows = Database::connection()->query('SELECT setting_key, setting_value FROM app_settings')->fetchAll();
        return array_column($rows, 'setting_value', 'setting_key');
    }

    public static function updateMany(array $settings): void
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO app_settings (setting_key, setting_value) VALUES (:key_name, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );
        foreach ($settings as $key => $value) {
            $stmt->execute(['key_name' => $key, 'value' => (string) $value]);
        }
    }
}
