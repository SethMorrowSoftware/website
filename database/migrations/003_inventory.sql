-- Inventory management columns on products
ALTER TABLE products ADD COLUMN track_inventory TINYINT(1) DEFAULT 0;
ALTER TABLE products ADD COLUMN stock_quantity INT DEFAULT 0;
ALTER TABLE products ADD COLUMN low_stock_threshold INT DEFAULT 5;
ALTER TABLE products ADD COLUMN allow_backorder TINYINT(1) DEFAULT 0;
