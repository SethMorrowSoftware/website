<?php
/**
 * Preflight Healthcheck Endpoint
 *
 * Reports on system readiness: database connectivity, migration status,
 * and payment webhook configuration. Intended for deployment pipelines,
 * load-balancer probes, and operational dashboards.
 *
 * Access: unauthenticated (safe — exposes no secrets, only boolean status).
 * Returns HTTP 200 if healthy, HTTP 503 if any critical check fails.
 */

require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

$checks = [];
$healthy = true;

// 1. Database connectivity
try {
    $db = getDB();
    $db->query('SELECT 1');
    $checks['database'] = ['status' => 'ok'];
} catch (Exception $e) {
    $checks['database'] = ['status' => 'fail', 'error' => 'Connection failed'];
    $healthy = false;
}

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

// 4. Uploads directory writable
$checks['uploads_writable'] = is_writable(UPLOADS_PATH) ? 'ok' : 'fail';
if ($checks['uploads_writable'] !== 'ok') {
    $healthy = false;
}

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
