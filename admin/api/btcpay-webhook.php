<?php
/**
 * BTCPay Server Webhook Handler
 *
 * Receives invoice status notifications from BTCPay Server.
 * Configure in BTCPay: Store > Settings > Webhooks
 *   URL: https://yourdomain.com/admin/api/btcpay-webhook.php
 *   Events: Invoice settled, Invoice payment settled, Invoice expired, Invoice invalid
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

// Verify webhook signature if secret is configured
$signature = $_SERVER['HTTP_BTCPAY_SIG'] ?? '';
if (!verifyBTCPayWebhookSignature($payload, $signature)) {
    error_log('[BTCPAY WEBHOOK] Invalid signature');
    http_response_code(403);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$data = json_decode($payload, true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$eventType = $data['type'] ?? '';
$invoiceId = $data['invoiceId'] ?? '';

if (!$invoiceId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing invoiceId']);
    exit;
}

// Find the order by payment_id (which stores the BTCPay invoice ID)
$db = getDB();
$stmt = $db->prepare('SELECT * FROM orders WHERE payment_id = ? AND payment_method = ?');
$stmt->execute([$invoiceId, 'btcpay']);
$order = $stmt->fetch();

if (!$order) {
    // Order not found — could be a test webhook or unrelated invoice
    error_log('[BTCPAY WEBHOOK] No order found for invoice: ' . $invoiceId);
    http_response_code(200);
    echo json_encode(['ok' => true, 'message' => 'No matching order']);
    exit;
}

// Process based on event type
// BTCPay event types: https://docs.btcpayserver.org/Development/GreenFieldAPI/#tag/Webhooks
switch ($eventType) {
    case 'InvoiceSettled':
    case 'InvoicePaymentSettled':
        // Payment received and confirmed
        if ($order['payment_status'] !== 'completed') {
            updateOrderPayment($order['id'], 'completed', $invoiceId, 'btcpay');

            // Decrement inventory now that payment is confirmed
            processOrderInventory($order['id']);

            // Generate download tokens for digital products
            $downloads = getDownloadTokens($order['id']);
            if (empty($downloads)) {
                generateDownloadTokens($order['id']);
            }

            // Send confirmation email
            sendOrderConfirmation($order['id']);

            error_log('[BTCPAY WEBHOOK] Order #' . $order['order_number'] . ' marked as completed (event: ' . $eventType . ')');
        }
        break;

    case 'InvoiceProcessing':
        // Payment detected but not yet confirmed (0-conf or waiting for confirmations)
        if ($order['payment_status'] === 'pending') {
            $updateStmt = $db->prepare('UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $updateStmt->execute(['processing', $order['id']]);
            error_log('[BTCPAY WEBHOOK] Order #' . $order['order_number'] . ' payment processing');
        }
        break;

    case 'InvoiceExpired':
        // Invoice expired without payment
        if ($order['payment_status'] === 'pending' || $order['payment_status'] === 'processing') {
            $updateStmt = $db->prepare('UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $updateStmt->execute(['failed', $order['id']]);
            error_log('[BTCPAY WEBHOOK] Order #' . $order['order_number'] . ' invoice expired');
        }
        break;

    case 'InvoiceInvalid':
        // Invoice became invalid (e.g., double-spend detected)
        if ($order['payment_status'] !== 'completed') {
            $updateStmt = $db->prepare('UPDATE orders SET payment_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
            $updateStmt->execute(['failed', $order['id']]);
            error_log('[BTCPAY WEBHOOK] Order #' . $order['order_number'] . ' invoice invalid');
        }
        break;

    default:
        // Other events (InvoiceCreated, InvoiceReceivedPayment, etc.) — acknowledge but no action
        error_log('[BTCPAY WEBHOOK] Unhandled event type: ' . $eventType . ' for invoice: ' . $invoiceId);
        break;
}

http_response_code(200);
echo json_encode(['ok' => true]);
