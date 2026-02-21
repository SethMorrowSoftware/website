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

// getClientIp() is defined in config.php (shared between front-end and admin)

/**
 * Check if login is rate-limited.
 *
 * Uses composite key: both IP-based and username-based windows are
 * checked independently. This prevents shared-NAT false positives
 * (different usernames aren't penalised together) while also catching
 * credential-stuffing against a single account from rotating IPs.
 *
 * Returns remaining seconds if locked out, or 0 if OK.
 */
function checkLoginThrottle(string $ip, string $username = ''): int {
    $db = getDB();
    // Ensure table exists (for existing DBs before migration)
    $db->exec('CREATE TABLE IF NOT EXISTS login_attempts (id INT AUTO_INCREMENT PRIMARY KEY, ip_address VARCHAR(45) NOT NULL, username VARCHAR(255) NOT NULL, attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

    $window = 15 * 60; // 15 minute window
    $maxAttemptsPerIp = 10;      // Higher threshold to reduce shared-NAT false positives
    $maxAttemptsPerUser = 5;     // Tighter per-account to block credential stuffing
    $cutoff = date('Y-m-d H:i:s', time() - $window);

    // Check per-IP rate limit
    $stmt = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempted_at > ?');
    $stmt->execute([$ip, $cutoff]);
    $ipCount = (int)$stmt->fetchColumn();

    if ($ipCount >= $maxAttemptsPerIp) {
        $stmt = $db->prepare('SELECT attempted_at FROM login_attempts WHERE ip_address = ? AND attempted_at > ? ORDER BY attempted_at ASC LIMIT 1');
        $stmt->execute([$ip, $cutoff]);
        $oldest = $stmt->fetchColumn();
        return max(1, $window - (time() - strtotime($oldest)));
    }

    // Check per-username rate limit (catch rotating-IP attacks on one account)
    if ($username) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM login_attempts WHERE username = ? AND attempted_at > ?');
        $stmt->execute([$username, $cutoff]);
        $userCount = (int)$stmt->fetchColumn();

        if ($userCount >= $maxAttemptsPerUser) {
            $stmt = $db->prepare('SELECT attempted_at FROM login_attempts WHERE username = ? AND attempted_at > ? ORDER BY attempted_at ASC LIMIT 1');
            $stmt->execute([$username, $cutoff]);
            $oldest = $stmt->fetchColumn();
            return max(1, $window - (time() - strtotime($oldest)));
        }
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
    $db->exec("DELETE FROM login_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
}

/**
 * Clear login attempts for an IP and username after successful login
 */
function clearLoginAttempts(string $ip, string $username = ''): void {
    $db = getDB();
    $db->prepare('DELETE FROM login_attempts WHERE ip_address = ?')->execute([$ip]);
    if ($username) {
        $db->prepare('DELETE FROM login_attempts WHERE username = ?')->execute([$username]);
    }
}

/**
 * Attempt login
 */
function attemptLogin(string $username, string $password): bool {
    $db = getDB();
    $ip = getClientIp();
    $stmt = $db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        clearLoginAttempts($ip, $username);
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
    recordLoginAttempt($ip, $username);
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
