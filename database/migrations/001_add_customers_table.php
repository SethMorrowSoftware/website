<?php
/**
 * Migration: Add customers table for customer accounts
 */
return function(PDO $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS customers (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        first_name TEXT NOT NULL,
        last_name TEXT NOT NULL,
        phone TEXT,
        default_shipping_address TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME,
        is_active INTEGER DEFAULT 1
    )');

    // Add customer_id to orders table
    $cols = $db->query("PRAGMA table_info(orders)")->fetchAll();
    $colNames = array_column($cols, 'name');
    if (!in_array('customer_id', $colNames)) {
        $db->exec('ALTER TABLE orders ADD COLUMN customer_id INTEGER REFERENCES customers(id)');
    }
};
