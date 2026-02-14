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
 * Attempt login
 */
function attemptLogin(string $username, string $password): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        ensureSession();
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        $_SESSION['last_activity'] = time();
        return true;
    }
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
