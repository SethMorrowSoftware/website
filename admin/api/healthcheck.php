<?php
/**
 * Healthcheck Endpoint
 *
 * Two modes:
 *   1. Public liveness probe  — unauthenticated requests receive only a
 *      minimal 200/503 response with no internal details.
 *   2. Authenticated diagnostics — admin-session or bearer-token requests
 *      receive full migration drift, webhook readiness, and subsystem status.
 *
 * Authentication for diagnostics mode:
 *   - Active admin session (cookie), OR
 *   - `Authorization: Bearer <token>` header matching the configured
 *     `healthcheck_token` setting (for CI/monitoring systems).
 *
 * Returns HTTP 200 if healthy, HTTP 503 if any critical check fails.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

// Determine whether the caller is authorized for detailed diagnostics.
$authorized = false;
if (isLoggedIn()) {
    $authorized = true;
} else {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
        $configuredToken = getSetting('healthcheck_token');
        if ($configuredToken && hash_equals($configuredToken, $m[1])) {
            $authorized = true;
        }
    }
}

$checks = [];
$healthy = true;

// 1. Database connectivity
try {
    $db = getDB();
    $db->query('SELECT 1');
    $checks['database'] = ['status' => 'ok'];
} catch (Exception $e) {
    $checks['database'] = ['status' => 'fail'];
    $healthy = false;
}

// 4. Uploads directory writable (critical — always checked)
$checks['uploads_writable'] = is_writable(UPLOADS_PATH) ? 'ok' : 'fail';
if ($checks['uploads_writable'] !== 'ok') {
    $healthy = false;
}

// --- Unauthenticated callers get liveness-only response ---
if (!$authorized) {
    http_response_code($healthy ? 200 : 503);
    echo json_encode([
        'healthy' => $healthy,
        'timestamp' => date('c'),
    ], JSON_PRETTY_PRINT);
    exit;
}

// --- Authenticated callers get full diagnostics below ---

// 2. Migration drift — compare pending file-based migrations
if (isset($db)) {
    try {
        $executed = [];
        $stmt = $db->query('SELECT filename FROM migrations');
        while ($row = $stmt->fetch()) {
            $executed[] = $row['filename'];
        }

        $migrationsDir = BASE_PATH . '/database/migrations';
        $pending = [];
        if (is_dir($migrationsDir)) {
            $phpFiles = glob($migrationsDir . '/*.php') ?: [];
            $sqlFiles = glob($migrationsDir . '/*.sql') ?: [];
            $files = array_merge($phpFiles, $sqlFiles);
            sort($files);
            foreach ($files as $file) {
                $filename = basename($file);
                if (!in_array($filename, $executed)) {
                    $pending[] = $filename;
                }
            }
        }

        $checks['migrations'] = [
            'status' => empty($pending) ? 'ok' : 'drift',
            'pending_count' => count($pending),
        ];
        if (!empty($pending)) {
            $checks['migrations']['pending'] = $pending;
        }
    } catch (Exception $e) {
        $checks['migrations'] = ['status' => 'unknown', 'error' => 'Could not check'];
    }
} else {
    $checks['migrations'] = ['status' => 'skip', 'error' => 'No database connection'];
}

// 3. Webhook / payment readiness
$webhookChecks = [];

// Stripe
if (getSetting('stripe_enabled') === '1') {
    $webhookChecks['stripe'] = [
        'enabled' => true,
        'secret_key_set' => (bool)getSetting('stripe_secret_key'),
        'webhook_secret_set' => (bool)getSetting('stripe_webhook_secret'),
    ];
}

// PayPal
if (getSetting('paypal_enabled') === '1') {
    $webhookChecks['paypal'] = [
        'enabled' => true,
        'client_id_set' => (bool)getSetting('paypal_client_id'),
        'secret_set' => (bool)getSetting('paypal_secret'),
    ];
}

// Square
if (getSetting('square_enabled') === '1') {
    $webhookChecks['square'] = [
        'enabled' => true,
        'access_token_set' => (bool)getSetting('square_access_token'),
        'webhook_sig_key_set' => (bool)getSetting('square_webhook_signature_key'),
        'canonical_url_set' => (bool)getSetting('square_webhook_url'),
    ];
}

// BTCPay
if (getSetting('btcpay_enabled') === '1') {
    $webhookChecks['btcpay'] = [
        'enabled' => true,
        'api_key_set' => (bool)getSetting('btcpay_api_key'),
        'webhook_secret_set' => (bool)getSetting('btcpay_webhook_secret'),
    ];
}

$checks['webhooks'] = $webhookChecks ?: ['status' => 'none_enabled'];

// 5. Mail configuration
$checks['mail'] = [
    'from_address' => (bool)getSetting('mail_from_address') || (bool)getSetting('company_domain'),
    'contact_email_set' => (bool)getSetting('contact_email'),
];

http_response_code($healthy ? 200 : 503);
echo json_encode([
    'healthy' => $healthy,
    'checks' => $checks,
    'timestamp' => date('c'),
], JSON_PRETTY_PRINT);
