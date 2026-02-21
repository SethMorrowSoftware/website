<?php
/**
 * Migration: Create api_keys and api_rate_limits tables.
 *
 * Part of the REST API system (Phase 2).
 */
return function (PDO $db) {
    if (!tableExists($db, 'api_keys')) {
        $db->exec("CREATE TABLE api_keys (
            id INT AUTO_INCREMENT PRIMARY KEY,
            label VARCHAR(255) NOT NULL,
            api_key VARCHAR(64) UNIQUE NOT NULL,
            secret_hash VARCHAR(255) NOT NULL,
            permissions JSON,
            rate_limit INT DEFAULT 60,
            is_active TINYINT(1) DEFAULT 1,
            last_used_at DATETIME,
            created_by INT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $db->exec("CREATE INDEX idx_api_keys_key ON api_keys(api_key)");
    }

    if (!tableExists($db, 'api_rate_limits')) {
        $db->exec("CREATE TABLE api_rate_limits (
            id INT AUTO_INCREMENT PRIMARY KEY,
            api_key_id INT NOT NULL,
            window_start DATETIME NOT NULL,
            request_count INT DEFAULT 1,
            UNIQUE KEY uq_key_window (api_key_id, window_start),
            FOREIGN KEY (api_key_id) REFERENCES api_keys(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!tableExists($db, 'webhook_subscriptions')) {
        $db->exec("CREATE TABLE webhook_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            url VARCHAR(1000) NOT NULL,
            events JSON NOT NULL,
            secret VARCHAR(255),
            is_active TINYINT(1) DEFAULT 1,
            last_triggered_at DATETIME,
            failure_count INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
};
