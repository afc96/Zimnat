<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$checks = [
    [
        'name' => 'No raw request SQL interpolation',
        'pattern' => '/->(?:query|exec)\([^;]*(\$_GET|\$_POST|\$_REQUEST)/i',
        'paths' => ['app'],
    ],
    [
        'name' => 'No public upload path exposure',
        'pattern' => '/public\/uploads|\/storage\/uploads/i',
        'paths' => ['app', 'public'],
    ],
    [
        'name' => 'No committed obvious secrets',
        'pattern' => '/(api[_-]?key|secret|password)\s*=\s*["\'][^"\']{12,}/i',
        'paths' => ['app', 'config', 'public', 'tools'],
    ],
];

$findings = [];
foreach ($checks as $check) {
    foreach ($check['paths'] as $relativePath) {
        $path = $root . '/' . $relativePath;
        if (!is_dir($path)) {
            continue;
        }
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($iterator as $file) {
            if (!$file->isFile() || !in_array($file->getExtension(), ['php', 'js', 'css'], true)) {
                continue;
            }
            $lines = file($file->getPathname()) ?: [];
            foreach ($lines as $number => $line) {
                if (preg_match($check['pattern'], $line)) {
                    $findings[] = $check['name'] . ': ' . str_replace($root . '/', '', $file->getPathname()) . ':' . ($number + 1);
                }
            }
        }
    }
}

if ($findings) {
    fwrite(STDERR, "SECURITY_GATE_FAILED\n" . implode("\n", $findings) . "\n");
    exit(1);
}

echo "SECURITY_GATE_PASSED\n";
