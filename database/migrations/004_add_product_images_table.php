<?php
/**
 * Migration: Add product images table for multi-image support
 */
return function(PDO $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS product_images (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_id INTEGER NOT NULL,
        image_path TEXT NOT NULL,
        alt_text TEXT,
        sort_order INTEGER DEFAULT 0,
        is_primary INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    )');
};
