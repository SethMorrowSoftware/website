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

// Verify local order has a stored PayPal payment_id that matches the
// client-supplied paypal_order_id.  createPayPalOrder() persists the PayPal
// order ID in orders.payment_id — if the two don't match, the client is
// submitting a forged or replayed PayPal order ID.
if ($order['payment_id'] !== $paypalOrderId) {
    error_log('[PAYPAL CAPTURE REJECT] Binding mismatch: local payment_id=' . $order['payment_id'] . ' vs supplied=' . $paypalOrderId);
    http_response_code(400);
    echo json_encode(['error' => 'PayPal order ID does not match the order on file.']);
    exit;
}

// Capture the PayPal order
$captureResult = capturePayPalOrder($paypalOrderId);

if ($captureResult && isset($captureResult['status']) && $captureResult['status'] === 'COMPLETED') {

    // Validate the captured amount/currency and reference_id match the local order
    $capturedUnit = $captureResult['purchase_units'][0] ?? null;
    $capturedAmount = $capturedUnit['payments']['captures'][0]['amount'] ?? null;
    $expectedCurrency = strtoupper(getSetting('currency_code', 'USD'));
    $expectedTotal = number_format((float)$order['total'], 2, '.', '');

    if (!$capturedUnit
        || ($capturedUnit['reference_id'] ?? '') !== $order['order_number']
        || !$capturedAmount
        || $capturedAmount['value'] !== $expectedTotal
        || strtoupper($capturedAmount['currency_code'] ?? '') !== $expectedCurrency
    ) {
        error_log('[PAYPAL CAPTURE REJECT] Binding validation failed for order #' . $orderId . ': ' . json_encode([
            'expected_ref' => $order['order_number'],
            'got_ref' => $capturedUnit['reference_id'] ?? null,
            'expected_amount' => $expectedTotal,
            'got_amount' => $capturedAmount['value'] ?? null,
            'expected_currency' => $expectedCurrency,
            'got_currency' => $capturedAmount['currency_code'] ?? null,
        ]));
        http_response_code(400);
        echo json_encode(['error' => 'Payment details do not match order. Please contact support.']);
        exit;
    }

    // Idempotent finalization — safe against duplicate callbacks, retries,
    // or user double-submit.  Handles payment status update, inventory
    // decrement, download tokens, and confirmation email exactly once.
    $finalized = finalizePaidOrder($orderId, $paypalOrderId, 'paypal');

    if (!$finalized) {
        // Payment was captured but finalization failed (e.g. inventory issue).
        // Do NOT report success — route customer to a review-pending state.
        http_response_code(202);
        echo json_encode([
            'success' => false,
            'message' => 'Payment received but your order requires review. Our team will contact you shortly.',
            'redirect' => url('index.php?page=order-complete&order=' . $order['order_number'] . '&payment=paypal&review=1'),
        ]);
        exit;
    }

    // Clear the cart only after confirmed finalization
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
