-- Product options (e.g., "Size", "Color")
CREATE TABLE IF NOT EXISTS product_options (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    product_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    sort_order INTEGER DEFAULT 0,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Option values (e.g., "Small", "Medium", "Large")
CREATE TABLE IF NOT EXISTS product_option_values (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    option_id INTEGER NOT NULL,
    value TEXT NOT NULL,
    price_modifier REAL DEFAULT 0,
    sku_suffix TEXT,
    stock_quantity INTEGER DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    is_available INTEGER DEFAULT 1,
    FOREIGN KEY (option_id) REFERENCES product_options(id) ON DELETE CASCADE
);

-- Store variant selections on order items
ALTER TABLE order_items ADD COLUMN variant_info TEXT;
