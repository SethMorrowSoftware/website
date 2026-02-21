<?php
/**
 * Migration: Image optimization settings and variant tracking.
 */
return function (PDO $db) {
    // Image variants table (tracks resized/optimized versions)
    $db->exec("
        CREATE TABLE IF NOT EXISTS image_variants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            original_path VARCHAR(500) NOT NULL,
            variant_size VARCHAR(50) NOT NULL,
            variant_path VARCHAR(500) NOT NULL,
            width INT DEFAULT NULL,
            height INT DEFAULT NULL,
            file_size INT DEFAULT NULL,
            format VARCHAR(10) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_original_size (original_path(191), variant_size),
            INDEX idx_original (original_path(191))
        ) ENGINE=InnoDB
    ");

    // Seed image optimization settings
    $settings = [
        ['image_quality_jpeg', '82'],
        ['image_quality_webp', '80'],
        ['image_max_width', '2000'],
        ['image_max_height', '2000'],
        ['cdn_base_url', ''],
        ['image_lazy_loading', '1'],
    ];
    $stmt = $db->prepare("INSERT IGNORE INTO settings (`key`, `value`) VALUES (?, ?)");
    foreach ($settings as $s) {
        $stmt->execute($s);
    }
};
