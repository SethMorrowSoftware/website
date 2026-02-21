<?php
/**
 * REST API Core
 *
 * Authentication, rate limiting, response helpers, and API key management.
 * All API endpoints use this module.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

// ============================================================
// API Response Helpers
// ============================================================

/**
 * Send a JSON API response and exit.
 */
function apiResponse(mixed $data, int $status = 200, array $meta = []): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');

    $response = ['data' => $data];
    if (!empty($meta)) {
        $response['meta'] = $meta;
    }

    echo json_encode($response, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Send an error response and exit.
 */
function apiError(string $message, int $status = 400, ?string $code = null): never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');

    $error = ['message' => $message];
    if ($code) $error['code'] = $code;

    echo json_encode(['data' => null, 'errors' => [$error]], JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Get the request body as decoded JSON.
 */
function getRequestBody(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Get a query parameter with optional default.
 */
function apiParam(string $key, mixed $default = null): mixed {
    return $_GET[$key] ?? $default;
}

// ============================================================
// API Authentication
// ============================================================

/**
 * Authenticate the API request.
 *
 * Expects: Authorization: Bearer <api_key>:<api_secret>
 *
 * @return array The authenticated API key record
 */
function authenticateApiRequest(): array {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    // Also check Apache/CGI fallback
    if (!$authHeader && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    }

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        apiError('Missing or invalid Authorization header. Use: Bearer <key>:<secret>', 401, 'auth_required');
    }

    $token = substr($authHeader, 7);
    $parts = explode(':', $token, 2);

    if (count($parts) !== 2) {
        apiError('Invalid token format. Use: Bearer <key>:<secret>', 401, 'auth_invalid');
    }

    [$apiKey, $apiSecret] = $parts;

    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM api_keys WHERE api_key = ? AND is_active = 1');
        $stmt->execute([$apiKey]);
        $keyRecord = $stmt->fetch();
    } catch (\Throwable $e) {
        apiError('Authentication service unavailable', 503, 'auth_unavailable');
    }

    if (!$keyRecord) {
        apiError('Invalid API key', 401, 'auth_invalid_key');
    }

    if (!password_verify($apiSecret, $keyRecord['secret_hash'])) {
        apiError('Invalid API secret', 401, 'auth_invalid_secret');
    }

    // Update last used timestamp (non-blocking)
    try {
        $db->prepare('UPDATE api_keys SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?')
           ->execute([$keyRecord['id']]);
    } catch (\Throwable $e) {}

    return $keyRecord;
}

/**
 * Check if the API key has a specific permission.
 */
function apiHasPermission(array $keyRecord, string $permission): bool {
    $perms = json_decode($keyRecord['permissions'] ?? '[]', true);
    if (!is_array($perms)) return false;
    if (in_array('*', $perms, true)) return true;
    return in_array($permission, $perms, true);
}

/**
 * Require a specific API permission or return 403.
 */
function requireApiPermission(array $keyRecord, string $permission): void {
    if (!apiHasPermission($keyRecord, $permission)) {
        apiError("Insufficient permissions. Required: $permission", 403, 'forbidden');
    }
}

// ============================================================
// Rate Limiting
// ============================================================

/**
 * Check and enforce rate limiting for an API key.
 *
 * Uses a fixed-window approach: count requests per minute.
 */
function enforceRateLimit(array $keyRecord): void {
    $limit = (int)($keyRecord['rate_limit'] ?: 60);
    $windowStart = date('Y-m-d H:i:00'); // 1-minute window

    try {
        $db = getDB();

        // Upsert: increment or create counter
        $db->prepare(
            'INSERT INTO api_rate_limits (api_key_id, window_start, request_count) VALUES (?, ?, 1) '
            . 'ON DUPLICATE KEY UPDATE request_count = request_count + 1'
        )->execute([$keyRecord['id'], $windowStart]);

        // Check the count
        $stmt = $db->prepare('SELECT request_count FROM api_rate_limits WHERE api_key_id = ? AND window_start = ?');
        $stmt->execute([$keyRecord['id'], $windowStart]);
        $count = (int)$stmt->fetchColumn();

        // Set rate limit headers
        header("X-RateLimit-Limit: $limit");
        header('X-RateLimit-Remaining: ' . max(0, $limit - $count));
        header('X-RateLimit-Reset: ' . (strtotime($windowStart) + 60));

        if ($count > $limit) {
            header('Retry-After: ' . (60 - (time() % 60)));
            apiError('Rate limit exceeded', 429, 'rate_limited');
        }

        // Cleanup old rate limit entries (>1 hour old)
        $db->exec("DELETE FROM api_rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    } catch (\Throwable $e) {
        // If rate limiting fails, allow the request (fail-open for availability)
        error_log('[API] Rate limiting error: ' . $e->getMessage());
    }
}

// ============================================================
// API Key Management
// ============================================================

/**
 * Generate a new API key pair.
 *
 * @return array ['key' => string, 'secret' => string] (secret is shown once)
 */
function generateApiKeyPair(): array {
    return [
        'key' => 'cms_' . bin2hex(random_bytes(16)),
        'secret' => bin2hex(random_bytes(32)),
    ];
}

/**
 * Create a new API key.
 *
 * @return array ['id' => int, 'key' => string, 'secret' => string]
 */
function createApiKey(string $label, array $permissions = ['*'], int $rateLimit = 60, ?int $createdBy = null): array {
    $pair = generateApiKeyPair();
    $secretHash = password_hash($pair['secret'], PASSWORD_DEFAULT);

    $db = getDB();
    $stmt = $db->prepare(
        'INSERT INTO api_keys (label, api_key, secret_hash, permissions, rate_limit, created_by) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$label, $pair['key'], $secretHash, json_encode($permissions), $rateLimit, $createdBy]);

    return [
        'id' => (int)$db->lastInsertId(),
        'key' => $pair['key'],
        'secret' => $pair['secret'],
    ];
}

/**
 * Revoke (deactivate) an API key.
 */
function revokeApiKey(int $id): bool {
    $db = getDB();
    return $db->prepare('UPDATE api_keys SET is_active = 0 WHERE id = ?')->execute([$id]);
}

/**
 * Delete an API key permanently.
 */
function deleteApiKey(int $id): bool {
    $db = getDB();
    return $db->prepare('DELETE FROM api_keys WHERE id = ?')->execute([$id]);
}

/**
 * Get all API keys (for admin listing).
 */
function getAllApiKeys(): array {
    $db = getDB();
    return $db->query(
        'SELECT ak.*, u.username as created_by_username FROM api_keys ak '
        . 'LEFT JOIN users u ON ak.created_by = u.id ORDER BY ak.created_at DESC'
    )->fetchAll();
}

// ============================================================
// API Router Helper
// ============================================================

/**
 * Simple API router. Matches method + path pattern.
 *
 * @param string $method HTTP method (GET, POST, PUT, DELETE)
 * @param string $pattern URL pattern with :param placeholders (e.g. '/products/:id')
 * @param string $requestMethod Actual request method
 * @param string $requestPath Actual request path
 * @return array|false Matched params or false
 */
function matchRoute(string $method, string $pattern, string $requestMethod, string $requestPath): array|false {
    if (strtoupper($method) !== strtoupper($requestMethod)) return false;

    $patternParts = explode('/', trim($pattern, '/'));
    $pathParts = explode('/', trim($requestPath, '/'));

    if (count($patternParts) !== count($pathParts)) return false;

    $params = [];
    foreach ($patternParts as $i => $part) {
        if (str_starts_with($part, ':')) {
            $params[substr($part, 1)] = $pathParts[$i];
        } elseif ($part !== $pathParts[$i]) {
            return false;
        }
    }

    return $params;
}
