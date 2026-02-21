<?php
// Customer accounts — schema-checked migration
return function (PDO $db) {
    if (!tableExists($db, 'customers')) {
        $db->exec("CREATE TABLE customers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            first_name VARCHAR(255) NOT NULL,
            last_name VARCHAR(255) NOT NULL,
            phone VARCHAR(50),
            default_shipping_address TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME,
            is_active TINYINT(1) DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    if (!indexExists($db, 'customers', 'idx_customers_email')) {
        $db->exec('CREATE INDEX idx_customers_email ON customers(email)');
    }

    // Link orders to customer accounts
    if (!columnExists($db, 'orders', 'customer_id')) {
        $db->exec('ALTER TABLE orders ADD COLUMN customer_id INT');
        $db->exec('ALTER TABLE orders ADD CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL');
    }
};
