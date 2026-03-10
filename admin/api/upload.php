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

if (!hasPermission('manage_products') && !hasPermission('manage_pages') && !hasPermission('manage_blog')) {
    http_response_code(403);
    echo json_encode(['error' => 'Insufficient permissions']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
if (!verifyCSRFToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid CSRF token']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $errorMsg = 'Upload failed. ';
    if (!empty($_FILES['file']['error'])) {
        if ($_FILES['file']['error'] === UPLOAD_ERR_INI_SIZE || $_FILES['file']['error'] === UPLOAD_ERR_FORM_SIZE) {
            $errorMsg .= 'File exceeds the maximum allowed size.';
        } elseif ($_FILES['file']['error'] === UPLOAD_ERR_PARTIAL) {
            $errorMsg .= 'File was only partially uploaded.';
        } elseif ($_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
            $errorMsg .= 'No file was uploaded.';
        } else {
            $errorMsg .= 'An unexpected error occurred (code ' . $_FILES['file']['error'] . ').';
        }
    } else {
        $errorMsg = 'No file provided.';
    }
    echo json_encode(['error' => $errorMsg]);
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
