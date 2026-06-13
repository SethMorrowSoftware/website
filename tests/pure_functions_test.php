#!/usr/bin/env php
<?php
/**
 * Pure-Function Unit Tests
 *
 * Run: php tests/pure_functions_test.php
 *
 * Covers side-effect-free helpers that can be exercised without a database
 * connection: createSlug(), parsePrice(), calculateDiscount(),
 * ipInCidr()/ipMatchesAllowlist(), and _sanitizeCssStyle().
 *
 * Exit code 0 = all pass, 1 = failures.
 */

// functions.php pulls in config.php (which defines BASE_PATH and the IP/CSS
// helpers) plus coupons.php (calculateDiscount). DB access is lazy, so loading
// the definitions never opens a connection.
require_once __DIR__ . '/../includes/functions.php';

$passed = 0;
$failed = 0;
$failures = [];

function check(string $description, $expected, $actual): void {
    global $passed, $failed, $failures;
    if ($expected === $actual) {
        $passed++;
        echo "  PASS: $description\n";
    } else {
        $failed++;
        $failures[] = $description;
        echo "  FAIL: $description\n";
        echo "    Expected: " . var_export($expected, true) . "\n";
        echo "    Actual:   " . var_export($actual, true) . "\n";
    }
}

echo "=== Pure-Function Unit Tests ===\n\n";

// --- createSlug() ---
echo "createSlug():\n";
check('lowercases and hyphenates spaces', 'hello-world', createSlug('Hello World'));
check('collapses repeated separators', 'a-b', createSlug('a   !!!   b'));
check('trims leading/trailing separators', 'abc', createSlug('   ---abc---   '));
check('all-symbol input collapses to empty', '', createSlug('!!!@@@###'));
check('keeps existing hyphens', 'big-bus', createSlug('big-bus'));
check('strips non-ascii letters', 'caf', createSlug('Café'));

// --- parsePrice() ---
echo "\nparsePrice():\n";
check('strips currency symbol', 19.99, parsePrice('$19.99'));
check('strips thousands separators', 1299.99, parsePrice('$1,299.99'));
check('handles plain integer string', 50.0, parsePrice('50'));
check('handles text around number', 12.5, parsePrice('USD 12.50 each'));
check('empty string yields zero', 0.0, parsePrice(''));

// --- calculateDiscount() ---
echo "\ncalculateDiscount():\n";
check('10% of 200 = 20', 20.0, calculateDiscount(['type' => 'percentage', 'value' => 10, 'maximum_discount' => 0], 200));
check('percentage rounds to 2dp', 8.25, calculateDiscount(['type' => 'percentage', 'value' => 8.25, 'maximum_discount' => 0], 100));
check('fixed discount passthrough', 15.0, calculateDiscount(['type' => 'fixed', 'value' => 15, 'maximum_discount' => 0], 200));
check('maximum_discount cap applied', 25.0, calculateDiscount(['type' => 'percentage', 'value' => 50, 'maximum_discount' => 25], 200));
check('discount never exceeds subtotal', 30.0, calculateDiscount(['type' => 'fixed', 'value' => 100, 'maximum_discount' => 0], 30));
check('free_shipping yields zero line discount', 0.0, calculateDiscount(['type' => 'free_shipping', 'value' => 0, 'maximum_discount' => 0], 200));

// --- ipInCidr() ---
echo "\nipInCidr():\n";
check('IPv4 inside /8', true, ipInCidr('10.1.2.3', '10.0.0.0/8'));
check('IPv4 outside /8', false, ipInCidr('11.1.2.3', '10.0.0.0/8'));
check('IPv4 /32 exact match', true, ipInCidr('192.168.1.5', '192.168.1.5/32'));
check('IPv4 /32 non-match', false, ipInCidr('192.168.1.6', '192.168.1.5/32'));
check('IPv6 inside /32', true, ipInCidr('2001:db8::1', '2001:db8::/32'));
check('IPv4 vs IPv6 family mismatch is false', false, ipInCidr('10.0.0.1', '2001:db8::/32'));
check('invalid CIDR returns false', false, ipInCidr('10.0.0.1', 'not-a-cidr'));
check('out-of-range prefix returns false', false, ipInCidr('10.0.0.1', '10.0.0.0/40'));

// --- ipMatchesAllowlist() ---
echo "\nipMatchesAllowlist():\n";
check('exact match in list', true, ipMatchesAllowlist('203.0.113.7', ['198.51.100.1', '203.0.113.7']));
check('CIDR match in list', true, ipMatchesAllowlist('10.9.8.7', ['192.168.0.0/16', '10.0.0.0/8']));
check('no match', false, ipMatchesAllowlist('8.8.8.8', ['10.0.0.0/8', '172.16.0.0/12']));
check('empty entries never match', false, ipMatchesAllowlist('8.8.8.8', ['', '  ']));

// --- _sanitizeCssStyle() ---
echo "\n_sanitizeCssStyle():\n";
check('safe declarations preserved', 'color: red; text-align: center', _sanitizeCssStyle('color: red; text-align: center'));
check('expression() stripped', '', _sanitizeCssStyle('width: expression(alert(1))'));
check('url() stripped', '', _sanitizeCssStyle('background: url(javascript:alert(1))'));
check('unknown property dropped, safe kept', 'color: blue', _sanitizeCssStyle('position: fixed; color: blue'));

// --- Summary ---
echo "\n=== Results ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    echo "\nFailed tests:\n";
    foreach ($failures as $f) {
        echo "  - $f\n";
    }
    exit(1);
}

echo "\nAll tests passed!\n";
exit(0);
