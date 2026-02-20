<?php
/**
 * Square Webhook Handler
 *
 * Receives payment event notifications from Square.
 * Configure in Square Developer Dashboard > Webhooks:
 *   URL: https://yourdomain.com/admin/api/square-webhook.php
 *   Events: payment.completed, payment.updated
 *
 * Square signs webhooks using the webhook signature key found in
 * the Square Developer Dashboard.  Store it in the setting
 * `square_webhook_signature_key`.
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

// Verify webhook signature (HMAC-SHA256)
$signatureKey = getSetting('square_webhook_signature_key');
$signature = $_SERVER['HTTP_SQUARE_SIGNATURE'] ?? '';
$notificationUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')
    . ($_SERVER['REQUEST_URI'] ?? '/admin/api/square-webhook.php');

if ($signatureKey) {
    // Square signature = Base64(HMAC-SHA256(notificationUrl + body, signatureKey))
    $expectedSignature = base64_encode(hash_hmac('sha256', $notificationUrl . $payload, $signatureKey, true));
    if (!hash_equals($expectedSignature, $signature)) {
        error_log('[SQUARE WEBHOOK] Invalid signature');
        http_response_code(403);
        echo json_encode(['error' => 'Invalid signature']);
        exit;
    }
} else {
    // Fail closed: reject webhooks when no signature key is configured
    error_log('[SQUARE WEBHOOK] Rejected: no webhook signature key configured');
    http_response_code(403);
    echo json_encode(['error' => 'Webhook signature key not configured']);
    exit;
}

$data = json_decode($payload, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$eventType = $data['type'] ?? '';

// Replay protection: Square includes an event_id that is unique per delivery
$eventId = $data['event_id'] ?? '';

// Handle payment events
if ($eventType === 'payment.completed' || $eventType === 'payment.updated') {
    $payment = $data['data']['object']['payment'] ?? null;
    if (!$payment) {
        http_response_code(200);
        echo json_encode(['ok' => true, 'message' => 'No payment data']);
        exit;
    }

    $paymentStatus = $payment['status'] ?? '';
    $paymentId = $payment['id'] ?? '';
    $orderId = $payment['reference_id'] ?? '';

    // Square payment links store the link ID in our payment_id column.
    // Try to find the order by payment_id first, then by reference_id.
    $db = getDB();
    $order = null;

    // Try matching by order_id from the payment link's order reference
    if ($payment['order_id'] ?? '') {
        // Look up via Square order → our order mapping
        // Square payment links create a Square order; the link ID is in our payment_id
        $linkId = $payment['payment_link_id'] ?? '';
        if ($linkId) {
            $stmt = $db->prepare('SELECT * FROM orders WHERE payment_id = ? AND payment_method = ?');
            $stmt->execute([$linkId, 'square']);
            $order = $stmt->fetch();
        }
    }

    // Fallback: search by Square payment ID in payment_id column
    if (!$order && $paymentId) {
        $stmt = $db->prepare('SELECT * FROM orders WHERE payment_id = ? AND payment_method = ?');
        $stmt->execute([$paymentId, 'square']);
        $order = $stmt->fetch();
    }

    // Fallback: search any square order that is still pending/processing
    // and matches the amount (last resort)
    if (!$order) {
        error_log('[SQUARE WEBHOOK] No order found for payment: ' . $paymentId . ' (event: ' . $eventType . ')');
        http_response_code(200);
        echo json_encode(['ok' => true, 'message' => 'No matching order']);
        exit;
    }

    if ($paymentStatus === 'COMPLETED') {
        // Use shared idempotent finalizer
        $finalized = finalizePaidOrder($order['id'], $paymentId, 'square');
        if ($finalized) {
            error_log('[SQUARE WEBHOOK] Order #' . $order['order_number'] . ' finalized (event: ' . $eventType . ')');
        } else {
            error_log('[SQUARE WEBHOOK] Finalization failed for order #' . $order['order_number']);
        }
    } elseif ($paymentStatus === 'FAILED' || $paymentStatus === 'CANCELED') {
        if ($order['payment_status'] !== 'completed') {
            $updateStmt = $db->prepare('UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $updateStmt->execute(['failed', $order['id']]);
            error_log('[SQUARE WEBHOOK] Order #' . $order['order_number'] . ' payment ' . strtolower($paymentStatus));
        }
    }
}

http_response_code(200);
echo json_encode(['ok' => true]);
