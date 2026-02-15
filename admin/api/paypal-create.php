<?php
/**
 * PayPal Order Create Handler (public endpoint)
 * Called by PayPal JS SDK to create an order
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

$orderId = (int)($input['order_id'] ?? 0);

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order ID']);
    exit;
}

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

$paypalOrder = createPayPalOrder($orderId);

if ($paypalOrder && isset($paypalOrder['id'])) {
    echo json_encode(['id' => $paypalOrder['id']]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create PayPal order']);
}
