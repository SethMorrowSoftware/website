-- Shipping zones and methods
CREATE TABLE IF NOT EXISTS shipping_zones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    countries TEXT DEFAULT '',
    states TEXT DEFAULT '',
    is_default INTEGER DEFAULT 0,
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS shipping_methods (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    zone_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    type TEXT NOT NULL DEFAULT 'flat_rate',
    cost REAL DEFAULT 0,
    free_threshold REAL DEFAULT 0,
    min_weight REAL DEFAULT 0,
    max_weight REAL DEFAULT 0,
    min_price REAL DEFAULT 0,
    max_price REAL DEFAULT 0,
    estimated_days TEXT DEFAULT '',
    sort_order INTEGER DEFAULT 0,
    is_active INTEGER DEFAULT 1,
    FOREIGN KEY (zone_id) REFERENCES shipping_zones(id) ON DELETE CASCADE
);

-- Add shipping_method and shipping_cost to orders
ALTER TABLE orders ADD COLUMN shipping_method TEXT;
ALTER TABLE orders ADD COLUMN shipping_cost REAL DEFAULT 0;

-- Insert default shipping zone with free shipping
INSERT INTO shipping_zones (name, is_default, sort_order) VALUES ('Default', 1, 0);
INSERT INTO shipping_methods (zone_id, name, type, cost, free_threshold, estimated_days, sort_order)
    VALUES (
        (SELECT id FROM shipping_zones WHERE is_default = 1 LIMIT 1),
        'Standard Shipping', 'flat_rate', 0, 0, '5-7 business days', 0
    );
