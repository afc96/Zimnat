<?php

declare(strict_types=1);

$paths = [
    dirname(__DIR__) . '/app',
    dirname(__DIR__) . '/public',
    dirname(__DIR__) . '/tools',
];

$files = [];
foreach ($paths as $path) {
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
}

sort($files);
$failures = [];
foreach ($files as $file) {
    $command = 'php -l ' . escapeshellarg($file);
    exec($command, $output, $status);
    if ($status !== 0) {
        $failures[] = implode("\n", $output);
    }
    $output = [];
}

if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo 'LINT_PASSED ' . count($files) . " files\n";
