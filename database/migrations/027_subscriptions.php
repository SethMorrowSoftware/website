<?php
/**
 * Migration: Subscription plans and recurring billing.
 */
return function (PDO $db) {
    // Subscription plans (linked to products)
    $db->exec("
        CREATE TABLE IF NOT EXISTS subscription_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_id INT DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            price DECIMAL(10,2) NOT NULL,
            billing_interval ENUM('weekly','monthly','quarterly','yearly') NOT NULL DEFAULT 'monthly',
            interval_count INT DEFAULT 1,
            trial_days INT DEFAULT 0,
            setup_fee DECIMAL(10,2) DEFAULT 0.00,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Customer subscriptions
    $db->exec("
        CREATE TABLE IF NOT EXISTS subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT NOT NULL,
            plan_id INT NOT NULL,
            status ENUM('trialing','active','paused','past_due','cancelled','expired') DEFAULT 'active',
            current_period_start DATETIME NOT NULL,
            current_period_end DATETIME NOT NULL,
            next_billing_date DATETIME DEFAULT NULL,
            payment_method VARCHAR(100) DEFAULT NULL,
            payment_token TEXT DEFAULT NULL,
            trial_ends_at DATETIME DEFAULT NULL,
            cancelled_at DATETIME DEFAULT NULL,
            cancel_reason TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_sub_customer (customer_id),
            INDEX idx_sub_status (status),
            INDEX idx_sub_next_billing (next_billing_date),
            FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Subscription billing history
    $db->exec("
        CREATE TABLE IF NOT EXISTS subscription_invoices (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            subscription_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('paid','failed','pending','refunded') DEFAULT 'pending',
            payment_id VARCHAR(255) DEFAULT NULL,
            billing_period_start DATETIME NOT NULL,
            billing_period_end DATETIME NOT NULL,
            attempt_count INT DEFAULT 0,
            last_attempt_at DATETIME DEFAULT NULL,
            paid_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_invoice_sub (subscription_id),
            FOREIGN KEY (subscription_id) REFERENCES subscriptions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
};
