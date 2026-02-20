<?php
/**
 * PayPal Order Capture Handler (public endpoint)
 * Called by PayPal JS SDK after buyer approves payment
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true);
$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';

if (!verifyCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$paypalOrderId = $input['paypal_order_id'] ?? '';
$orderId = (int)($input['order_id'] ?? 0);

if (!$paypalOrderId || !$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

// Verify order exists
$order = getOrder($orderId);
if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    exit;
}

// Verify order belongs to the current session's pending PayPal checkout
if (!isset($_SESSION['pending_paypal_order']) || (int)$_SESSION['pending_paypal_order'] !== $orderId) {
    http_response_code(403);
    echo json_encode(['error' => 'Order does not match current checkout session']);
    exit;
}

// Capture the PayPal order
$captureResult = capturePayPalOrder($paypalOrderId);

if ($captureResult && isset($captureResult['status']) && $captureResult['status'] === 'COMPLETED') {
    // Update our order
    updateOrderPayment($orderId, 'completed', $paypalOrderId, 'paypal');

    // Decrement inventory now that payment is confirmed
    processOrderInventory($orderId);

    // Generate download tokens for digital products
    $downloads = generateDownloadTokens($orderId);

    // Send confirmation email
    sendOrderConfirmation($orderId);

    // Clear the cart
    clearCart();

    echo json_encode([
        'success' => true,
        'redirect' => url('index.php?page=order-complete&order=' . $order['order_number'] . '&payment=paypal'),
    ]);
} else {
    error_log('[PAYPAL CAPTURE ERROR] ' . json_encode($captureResult));
    http_response_code(500);
    echo json_encode(['error' => 'Payment capture failed. Please try again.']);
}
