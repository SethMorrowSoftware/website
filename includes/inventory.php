<?php
/**
 * Inventory management functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Check if a product is in stock
 */
function isInStock(int $productId, int $quantity = 1): bool {
    $db = getDB();
    $stmt = $db->prepare('SELECT track_inventory, stock_quantity, allow_backorder FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) return false;
    if (!$product['track_inventory']) return true; // Not tracking = always in stock
    if ($product['allow_backorder']) return true;
    return $product['stock_quantity'] >= $quantity;
}

/**
 * Get available stock quantity for a product
 */
function getStockQuantity(int $productId): ?int {
    $db = getDB();
    $stmt = $db->prepare('SELECT track_inventory, stock_quantity FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || !$product['track_inventory']) return null; // null = not tracking
    return (int)$product['stock_quantity'];
}

/**
 * Decrement stock after a successful order
 */
function decrementStock(int $productId, int $quantity): bool {
    $db = getDB();
    $stmt = $db->prepare('UPDATE products SET stock_quantity = MAX(0, stock_quantity - ?) WHERE id = ? AND track_inventory = 1');
    return $stmt->execute([$quantity, $productId]);
}

/**
 * Increment stock (for cancellations/refunds)
 */
function incrementStock(int $productId, int $quantity): bool {
    $db = getDB();
    $stmt = $db->prepare('UPDATE products SET stock_quantity = stock_quantity + ? WHERE id = ? AND track_inventory = 1');
    return $stmt->execute([$quantity, $productId]);
}

/**
 * Get products with low stock
 */
function getLowStockProducts(): array {
    $db = getDB();
    $stmt = $db->query('SELECT p.*, pc.name as category_name FROM products p JOIN product_categories pc ON p.category_id = pc.id WHERE p.track_inventory = 1 AND p.stock_quantity <= p.low_stock_threshold AND p.is_visible = 1 ORDER BY p.stock_quantity ASC');
    return $stmt->fetchAll();
}

/**
 * Update stock quantity for a product
 */
function updateStockQuantity(int $productId, int $quantity): bool {
    $db = getDB();
    $stmt = $db->prepare('UPDATE products SET stock_quantity = ? WHERE id = ?');
    return $stmt->execute([$quantity, $productId]);
}

/**
 * Process stock decrements for an entire order
 */
function processOrderInventory(int $orderId): void {
    $db = getDB();
    $stmt = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        if ($item['product_id']) {
            decrementStock($item['product_id'], $item['quantity']);
        }
    }
}

/**
 * Restore stock for a cancelled/refunded order
 */
function restoreOrderInventory(int $orderId): void {
    $db = getDB();
    $stmt = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ?');
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        if ($item['product_id']) {
            incrementStock($item['product_id'], $item['quantity']);
        }
    }
}

/**
 * Validate stock for all items in the current cart
 * Returns array of out-of-stock items, or empty array if all OK
 */
function validateCartStock(): array {
    $cart = getCart();
    $outOfStock = [];

    foreach ($cart as $item) {
        if (!isInStock($item['product_id'], $item['quantity'])) {
            $available = getStockQuantity($item['product_id']);
            $outOfStock[] = [
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'requested' => $item['quantity'],
                'available' => $available ?? 0,
            ];
        }
    }

    return $outOfStock;
}
