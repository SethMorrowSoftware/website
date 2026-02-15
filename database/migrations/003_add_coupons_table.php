<?php
/**
 * Migration: Add coupons/discount system
 */
return function(PDO $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS coupons (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        code TEXT UNIQUE NOT NULL,
        type TEXT NOT NULL DEFAULT \'percentage\',
        value REAL NOT NULL DEFAULT 0,
        minimum_order REAL DEFAULT 0,
        maximum_discount REAL DEFAULT 0,
        usage_limit INTEGER DEFAULT 0,
        used_count INTEGER DEFAULT 0,
        valid_from DATETIME,
        valid_until DATETIME,
        applies_to TEXT DEFAULT \'all\',
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS coupon_products (
        coupon_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        PRIMARY KEY (coupon_id, product_id),
        FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS coupon_categories (
        coupon_id INTEGER NOT NULL,
        category_id INTEGER NOT NULL,
        PRIMARY KEY (coupon_id, category_id),
        FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
    )');

    // Add coupon reference to orders
    $cols = $db->query("PRAGMA table_info(orders)")->fetchAll();
    $colNames = array_column($cols, 'name');
    if (!in_array('coupon_code', $colNames)) {
        $db->exec('ALTER TABLE orders ADD COLUMN coupon_code TEXT');
    }
    if (!in_array('discount', $colNames)) {
        $db->exec('ALTER TABLE orders ADD COLUMN discount REAL DEFAULT 0');
    }
};
