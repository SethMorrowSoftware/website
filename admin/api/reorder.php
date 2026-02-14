<?php
/**
 * AJAX Reorder Handler
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$table = $input['table'] ?? '';
$items = $input['items'] ?? [];

$allowedTables = ['pages', 'products', 'product_categories', 'containers', 'testimonials', 'navigation'];

if (!in_array($table, $allowedTables)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid table']);
    exit;
}

$db = getDB();

try {
    $db->beginTransaction();
    $stmt = $db->prepare("UPDATE $table SET sort_order = ? WHERE id = ?");
    foreach ($items as $index => $id) {
        $stmt->execute([$index, (int)$id]);
    }
    $db->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $db->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Reorder failed']);
}
