<?php
// Order tracking fields — schema-checked migration
return function (PDO $db) {
    if (!columnExists($db, 'orders', 'tracking_number')) {
        $db->exec('ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(255)');
    }
    if (!columnExists($db, 'orders', 'tracking_carrier')) {
        $db->exec('ALTER TABLE orders ADD COLUMN tracking_carrier VARCHAR(100)');
    }
    if (!columnExists($db, 'orders', 'shipped_at')) {
        $db->exec('ALTER TABLE orders ADD COLUMN shipped_at DATETIME');
    }
    if (!columnExists($db, 'orders', 'delivered_at')) {
        $db->exec('ALTER TABLE orders ADD COLUMN delivered_at DATETIME');
    }
};
