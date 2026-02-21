<?php
// Inventory management columns — schema-checked migration
return function (PDO $db) {
    if (!columnExists($db, 'products', 'track_inventory')) {
        $db->exec('ALTER TABLE products ADD COLUMN track_inventory TINYINT(1) DEFAULT 0');
    }
    if (!columnExists($db, 'products', 'stock_quantity')) {
        $db->exec('ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0');
    }
    if (!columnExists($db, 'products', 'low_stock_threshold')) {
        $db->exec('ALTER TABLE products ADD COLUMN low_stock_threshold INT DEFAULT 5');
    }
    if (!columnExists($db, 'products', 'allow_backorder')) {
        $db->exec('ALTER TABLE products ADD COLUMN allow_backorder TINYINT(1) DEFAULT 0');
    }
};
