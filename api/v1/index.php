<?php
/**
 * REST API v1 Router
 *
 * Entry point for all /api/v1/* requests.
 * Routes requests to the appropriate handler based on method + path.
 *
 * Authentication: Bearer <api_key>:<api_secret>
 * Response format: { "data": ..., "meta": {}, "errors": [] }
 */

require_once __DIR__ . '/../../includes/api.php';

// CORS headers for API consumers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
header('Access-Control-Max-Age: 86400');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Parse the request path relative to /api/v1/
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
// Strip query string
$requestPath = strtok($requestUri, '?');
// Strip BASE_URL and /api/v1 prefix
$basePath = rtrim(BASE_URL, '/') . '/api/v1';
$path = substr($requestPath, strlen($basePath));
$path = '/' . trim($path, '/');

$method = $_SERVER['REQUEST_METHOD'];

// Authenticate (all API endpoints require authentication)
$apiKey = authenticateApiRequest();

// Enforce rate limiting
enforceRateLimit($apiKey);

// ============================================================
// Route definitions
// ============================================================

// --- Products ---
if ($params = matchRoute('GET', '/products', $method, $path)) {
    requireApiPermission($apiKey, 'read_products');
    require __DIR__ . '/products.php';
    handleListProducts();
    exit;
}

if ($params = matchRoute('GET', '/products/:id', $method, $path)) {
    requireApiPermission($apiKey, 'read_products');
    require __DIR__ . '/products.php';
    handleGetProduct((int)$params['id']);
    exit;
}

if ($params = matchRoute('POST', '/products', $method, $path)) {
    requireApiPermission($apiKey, 'write_products');
    require __DIR__ . '/products.php';
    handleCreateProduct();
    exit;
}

if ($params = matchRoute('PUT', '/products/:id', $method, $path)) {
    requireApiPermission($apiKey, 'write_products');
    require __DIR__ . '/products.php';
    handleUpdateProduct((int)$params['id']);
    exit;
}

if ($params = matchRoute('DELETE', '/products/:id', $method, $path)) {
    requireApiPermission($apiKey, 'write_products');
    require __DIR__ . '/products.php';
    handleDeleteProduct((int)$params['id']);
    exit;
}

// --- Categories ---
if ($params = matchRoute('GET', '/categories', $method, $path)) {
    requireApiPermission($apiKey, 'read_products');
    require __DIR__ . '/categories.php';
    handleListCategories();
    exit;
}

// --- Orders ---
if ($params = matchRoute('GET', '/orders', $method, $path)) {
    requireApiPermission($apiKey, 'read_orders');
    require __DIR__ . '/orders.php';
    handleListOrders();
    exit;
}

if ($params = matchRoute('GET', '/orders/:id', $method, $path)) {
    requireApiPermission($apiKey, 'read_orders');
    require __DIR__ . '/orders.php';
    handleGetOrder((int)$params['id']);
    exit;
}

if ($params = matchRoute('PUT', '/orders/:id', $method, $path)) {
    requireApiPermission($apiKey, 'write_orders');
    require __DIR__ . '/orders.php';
    handleUpdateOrder((int)$params['id']);
    exit;
}

// --- Customers ---
if ($params = matchRoute('GET', '/customers', $method, $path)) {
    requireApiPermission($apiKey, 'read_customers');
    require __DIR__ . '/customers.php';
    handleListCustomers();
    exit;
}

if ($params = matchRoute('GET', '/customers/:id', $method, $path)) {
    requireApiPermission($apiKey, 'read_customers');
    require __DIR__ . '/customers.php';
    handleGetCustomer((int)$params['id']);
    exit;
}

// --- Inventory ---
if ($params = matchRoute('GET', '/inventory', $method, $path)) {
    requireApiPermission($apiKey, 'read_products');
    require __DIR__ . '/inventory.php';
    handleListInventory();
    exit;
}

if ($params = matchRoute('PUT', '/inventory/:id', $method, $path)) {
    requireApiPermission($apiKey, 'write_products');
    require __DIR__ . '/inventory.php';
    handleUpdateInventory((int)$params['id']);
    exit;
}

// --- Coupons ---
if ($params = matchRoute('GET', '/coupons', $method, $path)) {
    requireApiPermission($apiKey, 'read_orders');
    require __DIR__ . '/coupons.php';
    handleListCoupons();
    exit;
}

if ($params = matchRoute('POST', '/coupons', $method, $path)) {
    requireApiPermission($apiKey, 'write_orders');
    require __DIR__ . '/coupons.php';
    handleCreateCoupon();
    exit;
}

// --- Analytics ---
if ($params = matchRoute('GET', '/analytics/sales', $method, $path)) {
    requireApiPermission($apiKey, 'read_analytics');
    require __DIR__ . '/analytics.php';
    handleSalesAnalytics();
    exit;
}

// --- No match ---
apiError("Endpoint not found: $method $path", 404, 'not_found');
