<?php
/**
 * Migration: Security hardening — 2FA columns, admin sessions table.
 */
return function (PDO $db) {
    require_once BASE_PATH . '/includes/migrations.php';

    // Add 2FA columns to users table
    if (!columnExists($db, 'users', 'totp_secret')) {
        $db->exec("ALTER TABLE users ADD COLUMN totp_secret VARCHAR(100) DEFAULT NULL");
    }
    if (!columnExists($db, 'users', 'recovery_codes')) {
        $db->exec("ALTER TABLE users ADD COLUMN recovery_codes TEXT DEFAULT NULL");
    }

    // Active admin sessions tracking
    $db->exec("
        CREATE TABLE IF NOT EXISTS admin_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(128) NOT NULL,
            ip_address VARCHAR(45) DEFAULT '',
            user_agent VARCHAR(500) DEFAULT '',
            last_active DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_session (session_id),
            INDEX idx_session_user (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");
};
