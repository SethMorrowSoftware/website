<?php
/**
 * Gallery Items table for admin-managed gallery content.
 */
return function (PDO $db) {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS gallery_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            media_id INT NULL,
            media_path VARCHAR(500) NOT NULL,
            media_type VARCHAR(20) NOT NULL DEFAULT "image",
            caption TEXT NULL,
            category VARCHAR(50) NOT NULL DEFAULT "completed",
            featured TINYINT(1) NOT NULL DEFAULT 0,
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_gallery_active_sort (is_active, sort_order, id),
            INDEX idx_gallery_category (category),
            CONSTRAINT fk_gallery_media
                FOREIGN KEY (media_id) REFERENCES media(id)
                ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
};
