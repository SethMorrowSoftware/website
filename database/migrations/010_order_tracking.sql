-- Add tracking fields to orders table
ALTER TABLE orders ADD COLUMN tracking_number VARCHAR(255);
ALTER TABLE orders ADD COLUMN tracking_carrier VARCHAR(100);
ALTER TABLE orders ADD COLUMN shipped_at DATETIME;
ALTER TABLE orders ADD COLUMN delivered_at DATETIME;
