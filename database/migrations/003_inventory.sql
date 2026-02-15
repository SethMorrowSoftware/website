-- Inventory management columns on products
ALTER TABLE products ADD COLUMN track_inventory INTEGER DEFAULT 0;
ALTER TABLE products ADD COLUMN stock_quantity INTEGER DEFAULT 0;
ALTER TABLE products ADD COLUMN low_stock_threshold INTEGER DEFAULT 5;
ALTER TABLE products ADD COLUMN allow_backorder INTEGER DEFAULT 0;
