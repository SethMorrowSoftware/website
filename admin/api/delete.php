<?php
/**
 * AJAX Delete Handler
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

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

$table = $input['table'] ?? '';
$id = (int)($input['id'] ?? 0);

$allowedTables = ['products', 'containers', 'testimonials', 'media', 'navigation', 'orders'];

if (!in_array($table, $allowedTables) || !$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
    exit;
}

$db = getDB();

try {
    // If media, delete file too
    if ($table === 'media') {
        $stmt = $db->prepare('SELECT filename FROM media WHERE id = ?');
        $stmt->execute([$id]);
        $media = $stmt->fetch();
        if ($media) {
            $filepath = UPLOADS_PATH . '/' . $media['filename'];
            if (file_exists($filepath)) unlink($filepath);
        }
    }

    $stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed']);
}
