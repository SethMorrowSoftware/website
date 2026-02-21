<?php
/**
 * Two-Factor Authentication (TOTP)
 *
 * Provides TOTP-based 2FA for admin accounts using RFC 6238.
 * Compatible with Google Authenticator, Authy, 1Password, etc.
 *
 * No external libraries required — uses hash_hmac() directly.
 */

// ============================================================
// TOTP Core
// ============================================================

/**
 * Generate a random TOTP secret (Base32 encoded, 160 bits).
 */
function generateTotpSecret(): string {
    $bytes = random_bytes(20);
    return base32Encode($bytes);
}

/**
 * Generate a TOTP code for the given secret and time.
 */
function generateTotpCode(string $secret, ?int $timestamp = null, int $period = 30, int $digits = 6): string {
    $timestamp = $timestamp ?? time();
    $counter = intdiv($timestamp, $period);

    $key = base32Decode($secret);
    $counterBytes = pack('J', $counter); // 64-bit big-endian

    $hash = hash_hmac('sha1', $counterBytes, $key, true);
    $offset = ord($hash[strlen($hash) - 1]) & 0x0F;

    $code = (
        ((ord($hash[$offset]) & 0x7F) << 24) |
        ((ord($hash[$offset + 1]) & 0xFF) << 16) |
        ((ord($hash[$offset + 2]) & 0xFF) << 8) |
        (ord($hash[$offset + 3]) & 0xFF)
    ) % (10 ** $digits);

    return str_pad((string)$code, $digits, '0', STR_PAD_LEFT);
}

/**
 * Verify a TOTP code (checks current window +/- 1 period for clock skew).
 */
function verifyTotpCode(string $secret, string $code, int $period = 30): bool {
    $now = time();
    for ($offset = -1; $offset <= 1; $offset++) {
        $expected = generateTotpCode($secret, $now + ($offset * $period), $period);
        if (hash_equals($expected, $code)) {
            return true;
        }
    }
    return false;
}

/**
 * Generate an otpauth:// URI for QR code setup.
 */
function getTotpUri(string $secret, string $username, string $issuer = ''): string {
    if (!$issuer) {
        $issuer = getSetting('company_name', 'BusinessCMS');
    }
    $label = urlencode($issuer) . ':' . urlencode($username);
    return 'otpauth://totp/' . $label . '?secret=' . $secret . '&issuer=' . urlencode($issuer) . '&algorithm=SHA1&digits=6&period=30';
}

// ============================================================
// Base32 Encoding/Decoding
// ============================================================

function base32Encode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $binary = '';
    foreach (str_split($data) as $byte) {
        $binary .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
    }
    $result = '';
    foreach (str_split($binary, 5) as $chunk) {
        $chunk = str_pad($chunk, 5, '0', STR_PAD_RIGHT);
        $result .= $alphabet[bindec($chunk)];
    }
    return $result;
}

function base32Decode(string $data): string {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $data = strtoupper(rtrim($data, '='));
    $binary = '';
    foreach (str_split($data) as $char) {
        $pos = strpos($alphabet, $char);
        if ($pos === false) continue;
        $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
    }
    $result = '';
    foreach (str_split($binary, 8) as $byte) {
        if (strlen($byte) < 8) break;
        $result .= chr(bindec($byte));
    }
    return $result;
}

// ============================================================
// 2FA Database Operations
// ============================================================

/**
 * Check if 2FA is enabled for a user.
 */
function isTwoFactorEnabled(int $userId): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT totp_secret FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $secret = $stmt->fetchColumn();
        return !empty($secret);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Enable 2FA for a user — stores the secret and recovery codes.
 */
function enableTwoFactor(int $userId, string $secret): array {
    $db = getDB();

    // Generate 8 recovery codes
    $recoveryCodes = [];
    for ($i = 0; $i < 8; $i++) {
        $recoveryCodes[] = strtoupper(bin2hex(random_bytes(4)));
    }
    $hashedCodes = array_map(function($code) {
        return password_hash($code, PASSWORD_DEFAULT);
    }, $recoveryCodes);

    $db->prepare('UPDATE users SET totp_secret = ?, recovery_codes = ? WHERE id = ?')
        ->execute([$secret, json_encode($hashedCodes), $userId]);

    return $recoveryCodes;
}

/**
 * Disable 2FA for a user.
 */
function disableTwoFactor(int $userId): bool {
    $db = getDB();
    return $db->prepare('UPDATE users SET totp_secret = NULL, recovery_codes = NULL WHERE id = ?')
        ->execute([$userId]);
}

/**
 * Verify a 2FA code or recovery code.
 */
function verifyTwoFactorCode(int $userId, string $code): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT totp_secret, recovery_codes FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || empty($user['totp_secret'])) {
        return false;
    }

    // Try TOTP code first
    $cleanCode = preg_replace('/\s+/', '', $code);
    if (strlen($cleanCode) === 6 && ctype_digit($cleanCode)) {
        return verifyTotpCode($user['totp_secret'], $cleanCode);
    }

    // Try recovery code
    $recoveryCodes = json_decode($user['recovery_codes'] ?? '[]', true);
    if (!is_array($recoveryCodes)) return false;

    $upperCode = strtoupper($cleanCode);
    foreach ($recoveryCodes as $i => $hashedCode) {
        if (password_verify($upperCode, $hashedCode)) {
            // Consume the recovery code (one-time use)
            unset($recoveryCodes[$i]);
            $db->prepare('UPDATE users SET recovery_codes = ? WHERE id = ?')
                ->execute([json_encode(array_values($recoveryCodes)), $userId]);
            return true;
        }
    }

    return false;
}

/**
 * Get the TOTP secret for a user (for QR code display during setup).
 */
function getTotpSecret(int $userId): ?string {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT totp_secret FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() ?: null;
    } catch (Exception $e) {
        return null;
    }
}

// ============================================================
// Active Session Management
// ============================================================

/**
 * Record a session for the current user.
 */
function recordAdminSession(int $userId): void {
    try {
        $db = getDB();
        $sessionId = session_id();
        $ip = getClientIp();
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 500);

        $db->prepare("INSERT INTO admin_sessions (user_id, session_id, ip_address, user_agent) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE ip_address = VALUES(ip_address), last_active = CURRENT_TIMESTAMP")
            ->execute([$userId, $sessionId, $ip, $ua]);
    } catch (Exception $e) {
        // Table may not exist yet
    }
}

/**
 * Get all active sessions for a user.
 */
function getActiveSessions(int $userId): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM admin_sessions WHERE user_id = ? AND last_active > DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY last_active DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Revoke a specific session.
 */
function revokeAdminSession(int $userId, string $sessionId): bool {
    try {
        $db = getDB();
        return $db->prepare("DELETE FROM admin_sessions WHERE user_id = ? AND session_id = ?")->execute([$userId, $sessionId]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Clean up expired sessions.
 */
function cleanupAdminSessions(): void {
    try {
        $db = getDB();
        $db->exec("DELETE FROM admin_sessions WHERE last_active < DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    } catch (Exception $e) {
        // Ignore
    }
}
