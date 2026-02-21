<?php
/**
 * Migration: Loyalty points, referrals, and flash sales.
 */
return function (PDO $db) {
    // Loyalty points balance
    $db->exec("
        CREATE TABLE IF NOT EXISTS loyalty_points (
            customer_id INT PRIMARY KEY,
            points_balance INT DEFAULT 0,
            lifetime_points INT DEFAULT 0,
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    // Loyalty transactions log
    $db->exec("
        CREATE TABLE IF NOT EXISTS loyalty_transactions (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            points INT NOT NULL,
            type ENUM('earn', 'redeem', 'bonus', 'adjust', 'expire') NOT NULL,
            description VARCHAR(255) DEFAULT NULL,
            order_id INT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_loyalty_customer (customer_id, created_at),
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    // Referral tracking
    $db->exec("
        CREATE TABLE IF NOT EXISTS referrals (
            id INT AUTO_INCREMENT PRIMARY KEY,
            referrer_customer_id INT NOT NULL,
            referred_customer_id INT DEFAULT NULL,
            referral_code VARCHAR(50) UNIQUE NOT NULL,
            status ENUM('pending', 'completed', 'rewarded') DEFAULT 'pending',
            reward_type VARCHAR(50) DEFAULT 'points',
            reward_value INT DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            completed_at DATETIME DEFAULT NULL,
            INDEX idx_referral_code (referral_code),
            FOREIGN KEY (referrer_customer_id) REFERENCES customers(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    // Flash sales / scheduled pricing
    $db->exec("
        CREATE TABLE IF NOT EXISTS flash_sales (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT NOT NULL,
            sale_price DECIMAL(10,2) NOT NULL,
            starts_at DATETIME NOT NULL,
            ends_at DATETIME NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_flash_active (is_active, starts_at, ends_at),
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB
    ");

    // Seed loyalty settings
    $settings = [
        ['loyalty_enabled', '0'],
        ['loyalty_points_per_dollar', '1'],
        ['loyalty_redemption_rate', '100'],
        ['loyalty_welcome_bonus', '0'],
        ['referral_enabled', '0'],
        ['referral_reward_points', '500'],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }
};
