-- Product options (e.g., "Size", "Color")
CREATE TABLE IF NOT EXISTS product_options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Option values (e.g., "Small", "Medium", "Large")
CREATE TABLE IF NOT EXISTS product_option_values (
    id INT AUTO_INCREMENT PRIMARY KEY,
    option_id INT NOT NULL,
    value VARCHAR(255) NOT NULL,
    price_modifier DECIMAL(10,2) DEFAULT 0,
    sku_suffix VARCHAR(100),
    stock_quantity INT DEFAULT 0,
    sort_order INT DEFAULT 0,
    is_available TINYINT(1) DEFAULT 1,
    FOREIGN KEY (option_id) REFERENCES product_options(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Store variant selections on order items
ALTER TABLE order_items ADD COLUMN variant_info TEXT;
