<?php
/**
 * Migration: Multi-Vendor Marketplace — vendors, vendor_payouts, product ownership.
 */
return function (PDO $db) {
    require_once BASE_PATH . '/includes/migrations.php';

    // Vendors table
    $db->exec("
        CREATE TABLE IF NOT EXISTS vendors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT DEFAULT NULL,
            store_name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            logo VARCHAR(500) DEFAULT NULL,
            banner VARCHAR(500) DEFAULT NULL,
            commission_rate DECIMAL(5,2) DEFAULT NULL COMMENT 'Override global rate; NULL = use global',
            status ENUM('pending','active','suspended','rejected') DEFAULT 'pending',
            stripe_account_id VARCHAR(255) DEFAULT NULL COMMENT 'For Stripe Connect payouts',
            payout_method VARCHAR(50) DEFAULT 'manual',
            payout_details TEXT DEFAULT NULL,
            total_sales DECIMAL(12,2) DEFAULT 0.00,
            total_commission DECIMAL(12,2) DEFAULT 0.00,
            total_payouts DECIMAL(12,2) DEFAULT 0.00,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uniq_vendor_slug (slug),
            INDEX idx_vendor_status (status),
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
        ) ENGINE=InnoDB
    ");

    // Add vendor_id to products
    if (!columnExists($db, 'products', 'vendor_id')) {
        $db->exec("ALTER TABLE products ADD COLUMN vendor_id INT DEFAULT NULL AFTER id");
        $db->exec("ALTER TABLE products ADD INDEX idx_product_vendor (vendor_id)");
    }

    // Vendor payouts
    $db->exec("
        CREATE TABLE IF NOT EXISTS vendor_payouts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            method VARCHAR(50) DEFAULT 'manual',
            reference VARCHAR(255) DEFAULT NULL,
            status ENUM('pending','processing','completed','failed') DEFAULT 'pending',
            period_start DATE DEFAULT NULL,
            period_end DATE DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            INDEX idx_payout_vendor (vendor_id),
            INDEX idx_payout_status (status),
            FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    // Vendor commission log (per order item)
    $db->exec("
        CREATE TABLE IF NOT EXISTS vendor_commissions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            vendor_id INT NOT NULL,
            order_id INT NOT NULL,
            order_item_id INT NOT NULL,
            sale_amount DECIMAL(10,2) NOT NULL,
            commission_rate DECIMAL(5,2) NOT NULL,
            commission_amount DECIMAL(10,2) NOT NULL,
            vendor_earnings DECIMAL(10,2) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_comm_vendor (vendor_id),
            INDEX idx_comm_order (order_id),
            FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    // Seed marketplace settings
    $settings = [
        ['marketplace_enabled', '0'],
        ['marketplace_commission_rate', '15'],
        ['marketplace_auto_approve_vendors', '0'],
        ['marketplace_vendor_registration', '1'],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }
};
