<?php
/**
 * Authentication helpers for admin panel
 */

require_once __DIR__ . '/../config.php';

/**
 * Start session if not started
 */
function ensureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    ensureSession();
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        return false;
    }
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > ADMIN_SESSION_TIMEOUT) {
        logout();
        return false;
    }
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Require login — redirect to login page if not authenticated
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        redirect('admin/login.php');
    }
}

/**
 * Check if login is rate-limited for this IP.
 * Returns remaining seconds if locked out, or 0 if OK.
 */
function checkLoginThrottle(string $ip): int {
    $db = getDB();
    // Ensure table exists (for existing DBs before migration)
    $db->exec('CREATE TABLE IF NOT EXISTS login_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, ip_address TEXT NOT NULL, username TEXT NOT NULL, attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP)');

    $window = 15 * 60; // 15 minute window
    $maxAttempts = 5;
    $cutoff = date('Y-m-d H:i:s', time() - $window);

    $stmt = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?');
    $stmt->execute([$ip, $cutoff]);
    $count = (int)$stmt->fetchColumn();

    if ($count >= $maxAttempts) {
        // Find when the oldest relevant attempt expires
        $stmt = $db->prepare('SELECT attempted_at FROM login_attempts WHERE ip_address = ? AND attempted_at > ? ORDER BY attempted_at ASC LIMIT 1');
        $stmt->execute([$ip, $cutoff]);
        $oldest = $stmt->fetchColumn();
        return max(1, $window - (time() - strtotime($oldest)));
    }
    return 0;
}

/**
 * Record a failed login attempt
 */
function recordLoginAttempt(string $ip, string $username): void {
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)');
    $stmt->execute([$ip, $username]);
    // Cleanup old entries (older than 1 hour)
    $db->exec("DELETE FROM login_attempts WHERE attempted_at < datetime('now', '-1 hour')");
}

/**
 * Clear login attempts for an IP after successful login
 */
function clearLoginAttempts(string $ip): void {
    $db = getDB();
    $db->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
}

/**
 * Attempt login
 */
function attemptLogin(string $username, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        clearLoginAttempts($_SERVER['REMOTE_ADDR'] ?? '');
        ensureSession();
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['last_activity'] = time();

        // Auto-delete bootstrap credential file after first successful login
        $credFile = BASE_PATH . '/ADMIN_CREDENTIALS.txt';
        if (file_exists($credFile)) {
            @unlink($credFile);
        }

        return true;
    }
    recordLoginAttempt($_SERVER['REMOTE_ADDR'] ?? '', $username);
    return false;
}

/**
 * Logout
 */
function logout(): void {
    ensureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Change password
 */
function changePassword(int $userId, string $currentPassword, string $newPassword): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        return false;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    return $stmt->execute([$newHash, $userId]);
}

/**
 * Get current admin user ID
 */
function getCurrentUserId(): ?int {
    ensureSession();
    return $_SESSION['admin_user_id'] ?? null;
}

/**
 * Get current admin username
 */
function getCurrentUsername(): ?string {
    ensureSession();
    return $_SESSION['admin_username'] ?? null;
}
