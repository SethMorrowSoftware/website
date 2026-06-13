#!/usr/bin/env php
<?php
/**
 * Test Runner
 *
 * Runs every tests/*_test.php file in a separate PHP process and reports an
 * aggregate pass/fail. Each test file exits 0 on success, non-zero on failure.
 *
 * Run: php tests/run.php
 * Exit code 0 = all suites passed, 1 = one or more suites failed.
 */

$dir = __DIR__;
$files = glob($dir . '/*_test.php');
sort($files);

if (!$files) {
    echo "No test files found in $dir\n";
    exit(0);
}

$phpBinary = PHP_BINARY ?: 'php';
$failedSuites = [];

foreach ($files as $file) {
    $name = basename($file);
    echo "\n>>> Running $name\n";
    $cmd = escapeshellarg($phpBinary) . ' ' . escapeshellarg($file);
    passthru($cmd, $exitCode);
    if ($exitCode !== 0) {
        $failedSuites[] = $name;
    }
}

echo "\n========================================\n";
echo 'Suites run: ' . count($files) . "\n";
echo 'Failed:     ' . count($failedSuites) . "\n";

if ($failedSuites) {
    echo "\nFailing suites:\n";
    foreach ($failedSuites as $s) {
        echo "  - $s\n";
    }
    exit(1);
}

echo "\nAll test suites passed!\n";
exit(0);
