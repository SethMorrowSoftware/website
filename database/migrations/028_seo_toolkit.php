<?php
/**
 * Migration: Advanced SEO — per-entity SEO fields and URL redirects.
 */
return function (PDO $db) {
    // SEO fields on products
    if (!columnExists($db, 'products', 'meta_title')) {
        $db->exec("ALTER TABLE products ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL AFTER specifications");
        $db->exec("ALTER TABLE products ADD COLUMN meta_description TEXT DEFAULT NULL AFTER meta_title");
        $db->exec("ALTER TABLE products ADD COLUMN og_image VARCHAR(500) DEFAULT NULL AFTER meta_description");
    }

    // SEO fields on categories
    if (!columnExists($db, 'product_categories', 'meta_title')) {
        $db->exec("ALTER TABLE product_categories ADD COLUMN meta_title VARCHAR(255) DEFAULT NULL");
        $db->exec("ALTER TABLE product_categories ADD COLUMN meta_description TEXT DEFAULT NULL");
    }

    // URL redirects for SEO-safe slug changes
    $db->exec("
        CREATE TABLE IF NOT EXISTS url_redirects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            old_path VARCHAR(500) NOT NULL,
            new_path VARCHAR(500) NOT NULL,
            status_code INT DEFAULT 301,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_old_path (old_path(191))
        ) ENGINE=InnoDB
    ");
};
