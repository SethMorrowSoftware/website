<?php
/**
 * Migration 035: Add request_quote_only flag to products
 *
 * Adds a per-product boolean column that marks a product as "Request a Quote"
 * only — hiding cart/pricing behavior on the storefront and preventing
 * add-to-cart server-side.
 *
 * Also backfills existing products whose price text indicates they were
 * already quote-driven (e.g. "Call for Pricing", "Contact for Quote").
 */

$db = getDB();

// Add the column (safe with IF NOT EXISTS-style check)
$cols = $db->query("SHOW COLUMNS FROM products LIKE 'request_quote_only'")->fetchAll();
if (empty($cols)) {
    $db->exec("ALTER TABLE products ADD COLUMN request_quote_only TINYINT(1) NOT NULL DEFAULT 0 AFTER is_available");
}

// Backfill: flag products whose price string already signals quote-only
$db->exec(
    "UPDATE products SET request_quote_only = 1 "
    . "WHERE request_quote_only = 0 "
    . "AND deleted_at IS NULL "
    . "AND ("
    . "  LOWER(price) LIKE '%call%' "
    . "  OR LOWER(price) LIKE '%contact%' "
    . "  OR LOWER(price) LIKE '%quote%' "
    . "  OR LOWER(price) LIKE '%request%' "
    . "  OR price IS NULL "
    . "  OR TRIM(price) = ''"
    . ")"
);
