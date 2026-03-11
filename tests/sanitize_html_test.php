#!/usr/bin/env php
<?php
/**
 * XSS Sanitizer Regression Tests
 *
 * Run: php tests/sanitize_html_test.php
 *
 * Tests sanitizeHtml() against known XSS vectors and edge cases.
 * Exit code 0 = all pass, 1 = failures.
 */

// Minimal bootstrap — only need config.php for sanitizeHtml()
// Prevent DB connection attempts during testing
define('BASE_PATH', dirname(__DIR__));

// We need to load the function definitions from config.php without
// triggering DB connections. Source only the functions we need.
// Load the full config — DB calls are lazy (only on getDB()).
require_once BASE_PATH . '/config.php';

$passed = 0;
$failed = 0;
$failures = [];

function assertSanitized(string $description, string $input, callable $check): void {
    global $passed, $failed, $failures;
    $output = sanitizeHtml($input);
    if ($check($output)) {
        $passed++;
        echo "  PASS: $description\n";
    } else {
        $failed++;
        $failures[] = $description;
        echo "  FAIL: $description\n";
        echo "    Input:  " . substr($input, 0, 200) . "\n";
        echo "    Output: " . substr($output, 0, 200) . "\n";
    }
}

function assertNoPattern(string $output, string $pattern): bool {
    return !preg_match($pattern, $output);
}

echo "=== XSS Sanitizer Regression Tests ===\n\n";

// --- Event handler injection ---
echo "Event Handlers:\n";

assertSanitized('Basic onclick', '<div onclick="alert(1)">test</div>', function($o) {
    return assertNoPattern($o, '/onclick/i');
});

assertSanitized('onmouseover', '<a href="#" onmouseover="alert(1)">link</a>', function($o) {
    return assertNoPattern($o, '/onmouseover/i');
});

assertSanitized('onerror on img', '<img src=x onerror="alert(1)">', function($o) {
    return assertNoPattern($o, '/onerror/i');
});

assertSanitized('onfocus with tab', "<input onfocus=\"alert(1)\">", function($o) {
    return assertNoPattern($o, '/onfocus/i');
});

assertSanitized('onload on body tag (stripped)', '<body onload="alert(1)">test</body>', function($o) {
    return assertNoPattern($o, '/onload/i');
});

// --- Dangerous URI schemes ---
echo "\nDangerous URIs:\n";

assertSanitized('javascript: href', '<a href="javascript:alert(1)">click</a>', function($o) {
    return assertNoPattern($o, '/javascript:/i');
});

assertSanitized('javascript: with entities', '<a href="&#106;&#97;vascript:alert(1)">click</a>', function($o) {
    return assertNoPattern($o, '/javascript:/i');
});

assertSanitized('javascript: with whitespace padding', '<a href=" j a v a s c r i p t:alert(1)">click</a>', function($o) {
    return assertNoPattern($o, '/javascript:/i');
});

assertSanitized('javascript: with newlines', "<a href=\"java\nscript:alert(1)\">click</a>", function($o) {
    return assertNoPattern($o, '/javascript:/i');
});

assertSanitized('data: URI in href', '<a href="data:text/html,<script>alert(1)</script>">click</a>', function($o) {
    return assertNoPattern($o, '/data:/i');
});

assertSanitized('vbscript: URI', '<a href="vbscript:MsgBox(1)">click</a>', function($o) {
    return assertNoPattern($o, '/vbscript:/i');
});

assertSanitized('data: URI in img src', '<img src="data:image/svg+xml,<svg onload=alert(1)>">', function($o) {
    return assertNoPattern($o, '/data:/i');
});

// --- Disallowed tags ---
echo "\nDisallowed Tags:\n";

assertSanitized('script tag removed', '<script>alert(1)</script>', function($o) {
    return assertNoPattern($o, '/<script/i');
});

assertSanitized('iframe tag removed', '<iframe src="evil.com"></iframe>', function($o) {
    return assertNoPattern($o, '/<iframe/i');
});

assertSanitized('object tag removed', '<object data="evil.swf"></object>', function($o) {
    return assertNoPattern($o, '/<object/i');
});

assertSanitized('embed tag removed', '<embed src="evil.swf">', function($o) {
    return assertNoPattern($o, '/<embed/i');
});

assertSanitized('form tag removed', '<form action="evil.com"><input></form>', function($o) {
    return assertNoPattern($o, '/<form/i');
});

assertSanitized('style tag removed', '<style>body{background:url(evil)}</style>', function($o) {
    return assertNoPattern($o, '/<style/i');
});

assertSanitized('svg tag removed', '<svg onload="alert(1)"><circle></circle></svg>', function($o) {
    return assertNoPattern($o, '/<svg/i');
});

assertSanitized('math tag removed', '<math><maction actiontype="statusline">click</maction></math>', function($o) {
    return assertNoPattern($o, '/<math/i');
});

// --- CSS injection ---
echo "\nCSS Injection:\n";

assertSanitized('expression() in style', '<div style="width: expression(alert(1))">test</div>', function($o) {
    return assertNoPattern($o, '/expression/i');
});

assertSanitized('url() in style', '<div style="background: url(javascript:alert(1))">test</div>', function($o) {
    return assertNoPattern($o, '/url\s*\(/i');
});

// --- Valid content preserved ---
echo "\nValid Content:\n";

assertSanitized('Simple paragraph preserved', '<p>Hello world</p>', function($o) {
    return str_contains($o, '<p>Hello world</p>');
});

assertSanitized('Bold and italic preserved', '<strong>bold</strong> <em>italic</em>', function($o) {
    return str_contains($o, '<strong>bold</strong>') && str_contains($o, '<em>italic</em>');
});

assertSanitized('Safe link preserved', '<a href="https://example.com">link</a>', function($o) {
    return str_contains($o, 'href="https://example.com"') && str_contains($o, '>link</a>');
});

assertSanitized('Safe image preserved', '<img src="/uploads/photo.jpg" alt="Photo">', function($o) {
    return str_contains($o, 'src="/uploads/photo.jpg"') && str_contains($o, 'alt="Photo"');
});

assertSanitized('Table structure preserved', '<table><tr><td>cell</td></tr></table>', function($o) {
    return str_contains($o, '<table>') && str_contains($o, '<td>cell</td>');
});

assertSanitized('Null input returns empty', '', function($o) {
    return $o === '';
});

assertSanitized('Safe mailto link preserved', '<a href="mailto:info@example.com">email</a>', function($o) {
    return str_contains($o, 'mailto:info@example.com');
});

// --- Edge cases ---
echo "\nEdge Cases:\n";

assertSanitized('Mixed case tag stripping', '<ScRiPt>alert(1)</ScRiPt>', function($o) {
    return assertNoPattern($o, '/<script/i');
});

assertSanitized('Nested dangerous content', '<div><p><a href="javascript:alert(1)">x</a></p></div>', function($o) {
    return assertNoPattern($o, '/javascript:/i');
});

assertSanitized('Multiple event handlers', '<div onclick="a()" onmouseover="b()" onload="c()">x</div>', function($o) {
    return assertNoPattern($o, '/onclick|onmouseover|onload/i');
});

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
