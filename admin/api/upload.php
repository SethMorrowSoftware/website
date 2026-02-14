<?php
/**
 * AJAX File Upload Handler
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

if (empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file provided']);
    exit;
}

$result = handleUpload($_FILES['file']);

if ($result) {
    echo json_encode([
        'success' => true,
        'url' => $result,
        'message' => 'File uploaded successfully'
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'error' => 'Upload failed. Check file type and size (max 50MB).'
    ]);
}
