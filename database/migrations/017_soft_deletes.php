<?php
/**
 * Migration 017: Add soft delete columns to orders and products tables.
 *
 * Instead of hard-deleting orders and products (which can break foreign key
 * references and destroy financial records), we add a deleted_at timestamp.
 * Rows with deleted_at IS NOT NULL are treated as deleted.
 */

return function (PDO $db) {
    if (!columnExists($db, 'orders', 'deleted_at')) {
        $db->exec('ALTER TABLE orders ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL');
        $db->exec('CREATE INDEX idx_orders_deleted_at ON orders(deleted_at)');
    }

    if (!columnExists($db, 'products', 'deleted_at')) {
        $db->exec('ALTER TABLE products ADD COLUMN deleted_at DATETIME NULL DEFAULT NULL');
        $db->exec('CREATE INDEX idx_products_deleted_at ON products(deleted_at)');
    }
};
