-- Add tracking fields to orders table
ALTER TABLE orders ADD COLUMN tracking_number TEXT;
ALTER TABLE orders ADD COLUMN tracking_carrier TEXT;
ALTER TABLE orders ADD COLUMN shipped_at DATETIME;
ALTER TABLE orders ADD COLUMN delivered_at DATETIME;
