<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Database;

$db = Database::connection();
$db->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        migration VARCHAR(190) PRIMARY KEY,
        applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB'
);

$applied = array_fill_keys(
    $db->query('SELECT migration FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN),
    true
);

$files = glob(dirname(__DIR__) . '/database/migrations/*.sql') ?: [];
sort($files);

$baselineThrough = null;
foreach ($argv as $argument) {
    if (str_starts_with($argument, '--baseline-through=')) {
        $baselineThrough = substr($argument, strlen('--baseline-through='));
    }
}

if ($baselineThrough !== null) {
    $insert = $db->prepare('INSERT IGNORE INTO schema_migrations (migration) VALUES (:migration)');
    foreach ($files as $file) {
        $name = basename($file);
        $insert->execute(['migration' => $name]);
        echo "BASELINE {$name}\n";
        if ($name === $baselineThrough) {
            break;
        }
    }
    echo "BASELINE_COMPLETE\n";
    exit(0);
}

$appliedCount = 0;
foreach ($files as $file) {
    $name = basename($file);
    if (isset($applied[$name])) {
        echo "SKIP {$name}\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException("Migration {$name} is empty or unreadable.");
    }

    try {
        $db->exec($sql);
        $stmt = $db->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $stmt->execute(['migration' => $name]);
        $appliedCount++;
        echo "APPLY {$name}\n";
    } catch (Throwable $exception) {
        throw new RuntimeException("Migration {$name} failed: " . $exception->getMessage(), 0, $exception);
    }
}

echo $appliedCount === 0 ? "MIGRATIONS_CURRENT\n" : "MIGRATIONS_APPLIED {$appliedCount}\n";
