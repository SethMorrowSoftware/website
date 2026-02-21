<?php
/**
 * Stripe Webhook Handler
 *
 * Receives payment event notifications from Stripe.
 * Configure in Stripe Dashboard > Developers > Webhooks:
 *   URL: https://yourdomain.com/admin/api/stripe-webhook.php
 *   Events: checkout.session.completed, checkout.session.expired
 *
 * Store the webhook signing secret in the `stripe_webhook_secret` setting.
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Read raw body
$payload = file_get_contents('php://input');
if (!$payload) {
    http_response_code(400);
    echo json_encode(['error' => 'Empty payload']);
    exit;
}

// Verify webhook signature (Stripe-Signature header)
$sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$webhookSecret = getSetting('stripe_webhook_secret');

if (!$webhookSecret) {
    // Fail closed: reject webhooks when no secret is configured
    error_log('[STRIPE WEBHOOK] Rejected: no webhook secret configured');
    http_response_code(403);
    echo json_encode(['error' => 'Webhook secret not configured']);
    exit;
}

if (!$sigHeader) {
    http_response_code(403);
    echo json_encode(['error' => 'Missing Stripe-Signature header']);
    exit;
}

// Parse Stripe-Signature header: "t=<timestamp>,v1=<signature>,..."
$sigParts = [];
foreach (explode(',', $sigHeader) as $part) {
    $kv = explode('=', trim($part), 2);
    if (count($kv) === 2) {
        $sigParts[$kv[0]] = $kv[1];
    }
}

$timestamp = $sigParts['t'] ?? '';
$v1Signature = $sigParts['v1'] ?? '';

if (!$timestamp || !$v1Signature) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid Stripe-Signature format']);
    exit;
}

// Reject events older than 5 minutes to prevent replay attacks
if (abs(time() - (int)$timestamp) > 300) {
    error_log('[STRIPE WEBHOOK] Rejected: timestamp too old (' . $timestamp . ')');
    http_response_code(403);
    echo json_encode(['error' => 'Timestamp outside tolerance']);
    exit;
}

// Compute expected signature: HMAC-SHA256 of "timestamp.payload"
$signedPayload = $timestamp . '.' . $payload;
$expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

if (!hash_equals($expectedSignature, $v1Signature)) {
    error_log('[STRIPE WEBHOOK] Invalid signature');
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$event = json_decode($payload, true);
if (!$event) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$eventType = $event['type'] ?? '';
$eventId = $event['id'] ?? '';

// Replay protection: record event_id in webhook_events table
if ($eventId) {
    $db = getDB();
    try {
        $insertStmt = $db->prepare('INSERT INTO webhook_events (provider, event_id) VALUES (?, ?)');
        $insertStmt->execute(['stripe', $eventId]);
    } catch (PDOException $e) {
        // Check MySQL error code 1062 (locale-independent) before string fallback
        $errCode = (int)($e->errorInfo[1] ?? 0);
        if ($errCode === 1062
            || str_contains($e->getMessage(), 'Duplicate entry')
            || str_contains($e->getMessage(), 'UNIQUE constraint')) {
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'Duplicate event ignored']);
            exit;
        }
        // Other DB errors — log but continue processing
        error_log('[STRIPE WEBHOOK] webhook_events insert error: ' . $e->getMessage());
    }
}

// Handle events
switch ($eventType) {
    case 'checkout.session.completed':
        $session = $event['data']['object'] ?? null;
        if (!$session) {
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'No session data']);
            exit;
        }

        $paymentStatus = $session['payment_status'] ?? '';
        $orderId = (int)($session['metadata']['order_id'] ?? 0);
        $orderNumber = $session['metadata']['order_number'] ?? '';
        $paymentIntent = $session['payment_intent'] ?? $session['id'] ?? '';

        if (!$orderId || !$orderNumber) {
            error_log('[STRIPE WEBHOOK] Missing order metadata in session: ' . ($session['id'] ?? 'unknown'));
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'No order metadata']);
            exit;
        }

        // Verify order exists and metadata matches
        $db = getDB();
        $order = getOrder($orderId);
        if (!$order || $order['order_number'] !== $orderNumber) {
            error_log('[STRIPE WEBHOOK] Order metadata mismatch: id=' . $orderId . ' number=' . $orderNumber);
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'No matching order']);
            exit;
        }

        // Validate amount/currency binding
        $sessionAmountTotal = $session['amount_total'] ?? null;
        $sessionCurrency = strtolower($session['currency'] ?? '');
        $expectedAmountCents = (int)round((float)$order['total'] * 100);
        $expectedCurrency = strtolower(getSetting('currency_code', 'usd'));

        if ((int)$sessionAmountTotal !== $expectedAmountCents || $sessionCurrency !== $expectedCurrency) {
            error_log('[STRIPE WEBHOOK] Amount/currency mismatch for order #' . $orderId . ': '
                . 'session=' . $sessionAmountTotal . ' ' . $sessionCurrency
                . ' expected=' . $expectedAmountCents . ' ' . $expectedCurrency);
            http_response_code(200);
            echo json_encode(['ok' => true, 'message' => 'Amount mismatch — skipped']);
            exit;
        }

        if ($paymentStatus === 'paid') {
            $finalized = finalizePaidOrder($order['id'], $paymentIntent, 'stripe');
            if ($finalized) {
                error_log('[STRIPE WEBHOOK] Order #' . $order['order_number'] . ' finalized');
            } else {
                error_log('[STRIPE WEBHOOK] Finalization failed for order #' . $order['order_number']);
            }
        }
        break;

    case 'checkout.session.expired':
        $session = $event['data']['object'] ?? null;
        $orderId = (int)($session['metadata']['order_id'] ?? 0);
        if ($orderId) {
            $order = getOrder($orderId);
            if ($order && $order['payment_status'] === 'pending') {
                $db = getDB();
                $db->prepare('UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
                   ->execute(['failed', $orderId]);
                error_log('[STRIPE WEBHOOK] Order #' . ($order['order_number'] ?? $orderId) . ' session expired');
            }
        }
        break;

    default:
        error_log('[STRIPE WEBHOOK] Unhandled event type: ' . $eventType);
        break;
}

http_response_code(200);
echo json_encode(['ok' => true]);
