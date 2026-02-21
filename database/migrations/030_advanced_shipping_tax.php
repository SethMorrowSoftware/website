<?php
/**
 * Migration: Weight-based shipping, tax rules, product dimensions.
 */
return function (PDO $db) {
    // Product weight and dimensions
    if (!columnExists($db, 'products', 'weight')) {
        $db->exec("ALTER TABLE products ADD COLUMN weight DECIMAL(8,2) DEFAULT NULL");
        $db->exec("ALTER TABLE products ADD COLUMN length_cm DECIMAL(8,2) DEFAULT NULL");
        $db->exec("ALTER TABLE products ADD COLUMN width_cm DECIMAL(8,2) DEFAULT NULL");
        $db->exec("ALTER TABLE products ADD COLUMN height_cm DECIMAL(8,2) DEFAULT NULL");
    }

    // Tax rules (multi-zone taxation)
    $db->exec("
        CREATE TABLE IF NOT EXISTS tax_rules (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            country VARCHAR(2) DEFAULT NULL,
            state VARCHAR(100) DEFAULT NULL,
            zip_pattern VARCHAR(20) DEFAULT NULL,
            rate DECIMAL(6,4) NOT NULL,
            product_type VARCHAR(50) DEFAULT 'all',
            priority INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_tax_country_state (country, state)
        ) ENGINE=InnoDB
    ");

    // Seed settings
    $settings = [
        ['tax_inclusive_pricing', '0'],
        ['weight_unit', 'kg'],
        ['dimension_unit', 'cm'],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }
};
