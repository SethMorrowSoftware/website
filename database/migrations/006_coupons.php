<?php
// Discount coupon system — schema-checked migration
return function (PDO $db) {
    if (!tableExists($db, 'coupons')) {
        $db->exec("CREATE TABLE coupons (
            id INT AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(255) UNIQUE NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'percentage',
            value DECIMAL(10,2) NOT NULL DEFAULT 0,
            minimum_order DECIMAL(10,2) DEFAULT 0,
            maximum_discount DECIMAL(10,2) DEFAULT 0,
            usage_limit INT DEFAULT 0,
            used_count INT DEFAULT 0,
            valid_from DATETIME,
            valid_until DATETIME,
            applies_to VARCHAR(50) DEFAULT 'all',
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!tableExists($db, 'coupon_products')) {
        $db->exec("CREATE TABLE coupon_products (
            coupon_id INT NOT NULL,
            product_id INT NOT NULL,
            PRIMARY KEY (coupon_id, product_id),
            FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!tableExists($db, 'coupon_categories')) {
        $db->exec("CREATE TABLE coupon_categories (
            coupon_id INT NOT NULL,
            category_id INT NOT NULL,
            PRIMARY KEY (coupon_id, category_id),
            FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Track coupon usage on orders
    if (!columnExists($db, 'orders', 'coupon_id')) {
        $db->exec('ALTER TABLE orders ADD COLUMN coupon_id INT');
        $db->exec('ALTER TABLE orders ADD CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL');
    }
    if (!columnExists($db, 'orders', 'discount_amount')) {
        $db->exec('ALTER TABLE orders ADD COLUMN discount_amount DECIMAL(10,2) DEFAULT 0');
    }
    if (!columnExists($db, 'orders', 'coupon_code')) {
        $db->exec('ALTER TABLE orders ADD COLUMN coupon_code VARCHAR(255)');
    }
};
