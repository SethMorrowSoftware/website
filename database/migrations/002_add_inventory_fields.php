<?php
/**
 * Migration: Add inventory tracking fields to products
 */
return function(PDO $db) {
    $cols = $db->query("PRAGMA table_info(products)")->fetchAll();
    $colNames = array_column($cols, 'name');

    if (!in_array('track_inventory', $colNames)) {
        $db->exec("ALTER TABLE products ADD COLUMN track_inventory INTEGER DEFAULT 0");
    }
    if (!in_array('stock_quantity', $colNames)) {
        $db->exec("ALTER TABLE products ADD COLUMN stock_quantity INTEGER DEFAULT 0");
    }
    if (!in_array('low_stock_threshold', $colNames)) {
        $db->exec("ALTER TABLE products ADD COLUMN low_stock_threshold INTEGER DEFAULT 5");
    }
    if (!in_array('allow_backorder', $colNames)) {
        $db->exec("ALTER TABLE products ADD COLUMN allow_backorder INTEGER DEFAULT 0");
    }
};
