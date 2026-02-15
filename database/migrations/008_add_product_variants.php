<?php
/**
 * Migration: Add product variants/options system
 */
return function(PDO $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS product_options (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        name TEXT NOT NULL,
        sort_order INTEGER DEFAULT 0,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )');

    $db->exec('CREATE TABLE IF NOT EXISTS product_option_values (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        option_id INTEGER NOT NULL,
        value TEXT NOT NULL,
        price_modifier REAL DEFAULT 0,
        sku_suffix TEXT,
        sort_order INTEGER DEFAULT 0,
        is_available INTEGER DEFAULT 1,
        stock_quantity INTEGER DEFAULT 0,
        FOREIGN KEY (option_id) REFERENCES product_options(id) ON DELETE CASCADE
    )');

    // Add variant info storage to order_items
    $cols = $db->query("PRAGMA table_info(order_items)")->fetchAll();
    $colNames = array_column($cols, 'name');
    if (!in_array('variant_info', $colNames)) {
        $db->exec('ALTER TABLE order_items ADD COLUMN variant_info TEXT');
    }
};
