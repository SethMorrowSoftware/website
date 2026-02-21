<?php
// Shipping zones and methods — schema-checked migration
return function (PDO $db) {
    if (!tableExists($db, 'shipping_zones')) {
        $db->exec("CREATE TABLE shipping_zones (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            countries TEXT,
            states TEXT,
            is_default TINYINT(1) DEFAULT 0,
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!tableExists($db, 'shipping_methods')) {
        $db->exec("CREATE TABLE shipping_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            zone_id INT NOT NULL,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'flat_rate',
            cost DECIMAL(10,2) DEFAULT 0,
            free_threshold DECIMAL(10,2) DEFAULT 0,
            min_weight DOUBLE DEFAULT 0,
            max_weight DOUBLE DEFAULT 0,
            min_price DECIMAL(10,2) DEFAULT 0,
            max_price DECIMAL(10,2) DEFAULT 0,
            estimated_days VARCHAR(100) DEFAULT '',
            sort_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Add shipping columns to orders
    if (!columnExists($db, 'orders', 'shipping_method')) {
        $db->exec('ALTER TABLE orders ADD COLUMN shipping_method VARCHAR(255)');
    }
    if (!columnExists($db, 'orders', 'shipping_cost')) {
        $db->exec('ALTER TABLE orders ADD COLUMN shipping_cost DECIMAL(10,2) DEFAULT 0');
    }

    // Insert default shipping zone if none exists
    $count = (int)$db->query('SELECT COUNT(*) FROM shipping_zones WHERE is_default = 1')->fetchColumn();
    if ($count === 0) {
        $db->exec("INSERT INTO shipping_zones (name, is_default, sort_order) VALUES ('Default', 1, 0)");
        $db->exec("INSERT INTO shipping_methods (zone_id, name, type, cost, free_threshold, estimated_days, sort_order)
            VALUES (
                (SELECT id FROM shipping_zones WHERE is_default = 1 LIMIT 1),
                'Standard Shipping', 'flat_rate', 0, 0, '5-7 business days', 0
            )");
    }
};
