<?php
/**
 * Migration: Backup tracking and GDPR data export/deletion logs.
 */
return function (PDO $db) {
    // Backup history
    $db->exec("
        CREATE TABLE IF NOT EXISTS backups (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(500) NOT NULL,
            type ENUM('database', 'files', 'full') NOT NULL,
            file_size BIGINT DEFAULT NULL,
            created_by INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");

    // GDPR data requests
    $db->exec("
        CREATE TABLE IF NOT EXISTS gdpr_requests (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            request_type ENUM('export', 'delete') NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'denied') DEFAULT 'pending',
            completed_at DATETIME DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_gdpr_customer (customer_id),
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    // Seed settings
    $settings = [
        ['backup_retention_count', '10'],
        ['privacy_policy_page', ''],
        ['cookie_consent_enabled', '0'],
        ['gdpr_self_service', '1'],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }
};
