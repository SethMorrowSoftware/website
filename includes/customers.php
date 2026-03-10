<?php
/**
 * Customer account functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Register a new customer account
 */
function registerCustomer(string $email, string $password, string $firstName, string $lastName, string $phone = ''): ?int {
    $db = getDB();
    $email = strtolower(trim($email));

    // Check if email already exists
    $stmt = $db->prepare('SELECT id FROM customers WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) return null;

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $db->prepare('INSERT INTO customers (email, password_hash, first_name, last_name, phone) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$email, $hash, $firstName, $lastName, $phone]);
    return (int)$db->lastInsertId();
}

/**
 * Authenticate a customer
 */
function authenticateCustomer(string $email, string $password): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM customers WHERE email = ? AND is_active = 1');
    $stmt->execute([strtolower(trim($email))]);
    $customer = $stmt->fetch();

    if ($customer && password_verify($password, $customer['password_hash'])) {
        // Update last login
        $db->prepare('UPDATE customers SET last_login = CURRENT_TIMESTAMP WHERE id = ?')->execute([$customer['id']]);
        return $customer;
    }
    return null;
}

/**
 * Login a customer (set session)
 */
function loginCustomer(array $customer): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    session_regenerate_id(true);
    $_SESSION['customer_id'] = $customer['id'];
    $_SESSION['customer_email'] = $customer['email'];
    $_SESSION['customer_name'] = $customer['first_name'] . ' ' . $customer['last_name'];
}

/**
 * Check if a customer is logged in
 */
function isCustomerLoggedIn(): bool {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return !empty($_SESSION['customer_id']);
}

/**
 * Get logged-in customer data
 */
function getLoggedInCustomer(): ?array {
    if (!isCustomerLoggedIn()) return null;
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM customers WHERE id = ? AND is_active = 1');
    $stmt->execute([$_SESSION['customer_id']]);
    $customer = $stmt->fetch();
    return $customer ?: null;
}

/**
 * Get customer ID from session
 */
function getCustomerId(): ?int {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['customer_id'] ?? null;
}

/**
 * Logout customer
 */
function logoutCustomer(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    unset($_SESSION['customer_id'], $_SESSION['customer_email'], $_SESSION['customer_name']);
    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);
}

/**
 * Update customer profile
 */
function updateCustomerProfile(int $customerId, array $data): bool {
    $db = getDB();
    $fields = [];
    $params = [];

    foreach (['first_name', 'last_name', 'phone', 'default_shipping_address'] as $field) {
        if (isset($data[$field])) {
            $fields[] = "$field = ?";
            $params[] = $data[$field];
        }
    }

    if (empty($fields)) return false;
    $params[] = $customerId;
    $stmt = $db->prepare('UPDATE customers SET ' . implode(', ', $fields) . ' WHERE id = ?');
    return $stmt->execute($params);
}

/**
 * Change customer password
 */
function changeCustomerPassword(int $customerId, string $currentPassword, string $newPassword): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT password_hash FROM customers WHERE id = ?');
    $stmt->execute([$customerId]);
    $customer = $stmt->fetch();

    if (!$customer || !password_verify($currentPassword, $customer['password_hash'])) {
        return false;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE customers SET password_hash = ? WHERE id = ?');
    return $stmt->execute([$newHash, $customerId]);
}

/**
 * Get customer orders
 */
function getCustomerOrders(int $customerId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC');
    $stmt->execute([$customerId]);
    return $stmt->fetchAll();
}

/**
 * Get customer by email
 */
function getCustomerByEmail(string $email): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM customers WHERE email = ?');
    $stmt->execute([strtolower(trim($email))]);
    $customer = $stmt->fetch();
    return $customer ?: null;
}

/**
 * Create a password reset token
 */
function createPasswordResetToken(int $customerId): string {
    $db = getDB();
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

    $stmt = $db->prepare('INSERT INTO password_resets (customer_id, token, expires_at) VALUES (?, ?, ?)');
    $stmt->execute([$customerId, $tokenHash, $expiresAt]);
    return $token;
}

/**
 * Validate and use a password reset token
 *
 * Uses an atomic consume pattern to prevent race conditions:
 * a single UPDATE with WHERE conditions atomically marks the token
 * as used only if it is still valid. Two concurrent requests cannot
 * both succeed because only one UPDATE will affect a row.
 */
function validatePasswordResetToken(string $token): ?int {
    $db = getDB();
    $tokenHash = hash('sha256', $token);

    // Atomic consume: mark used only if still valid (unused + not expired)
    $stmt = $db->prepare('UPDATE password_resets SET used = 1 WHERE token = ? AND used = 0 AND expires_at > NOW()');
    $stmt->execute([$tokenHash]);

    if ($stmt->rowCount() === 0) {
        return null; // Token invalid, already used, or expired
    }

    // Fetch the customer_id for the consumed token
    $stmt = $db->prepare('SELECT customer_id FROM password_resets WHERE token = ?');
    $stmt->execute([$tokenHash]);
    $reset = $stmt->fetch();

    return $reset ? (int)$reset['customer_id'] : null;
}

/**
 * Reset customer password (no current password required — token-based)
 */
function resetCustomerPassword(int $customerId, string $newPassword): bool {
    $db = getDB();
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare('UPDATE customers SET password_hash = ? WHERE id = ?');
    return $stmt->execute([$hash, $customerId]);
}
