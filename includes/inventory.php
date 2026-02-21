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
 * Decrement stock after a successful order.
 *
 * Uses an atomic guard to prevent overselling: for products that do NOT
 * allow backorder, the UPDATE only succeeds when stock_quantity >= the
 * requested quantity.  This eliminates the race condition where two
 * concurrent checkouts both pass the pre-check and both decrement.
 *
 * Returns true if the product doesn't track inventory, allows backorder,
 * or the guarded update affected a row.  Returns false if insufficient
 * stock (non-backorder product).
 */
function decrementStock(int $productId, int $quantity): bool {
    $db = getDB();

    // Check product inventory settings
    $check = $db->prepare('SELECT track_inventory, allow_backorder FROM products WHERE id = ?');
    $check->execute([$productId]);
    $product = $check->fetch();

    if (!$product || !$product['track_inventory']) {
        return true; // Not tracking inventory — always OK
    }

    if ($product['allow_backorder']) {
        // Backorders allowed — decrement without guard (negative stock is fine)
        $stmt = $db->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND track_inventory = 1');
        $stmt->execute([$quantity, $productId]);
        return $stmt->rowCount() > 0;
    }

    // Atomic guard: only decrement if sufficient stock
    $stmt = $db->prepare(
        'UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ? AND track_inventory = 1 AND stock_quantity >= ?'
    );
    $stmt->execute([$quantity, $productId, $quantity]);
    return $stmt->rowCount() > 0;
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
    $stmt = $db->query('SELECT p.*, pc.name as category_name FROM products p JOIN product_categories pc ON p.category_id = pc.id WHERE p.track_inventory = 1 AND p.stock_quantity <= p.low_stock_threshold AND p.is_visible = 1 AND p.deleted_at IS NULL ORDER BY p.stock_quantity ASC');
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
 * Process stock decrements for an entire order.
 *
 * Returns true if all decrements succeeded.  Logs warnings for any
 * products where stock was insufficient (oversell guard fired), but
 * does NOT fail the entire order — payment may already be captured.
 */
function processOrderInventory(int $orderId): bool {
    $db = getDB();
    // ORDER BY product_id ensures deterministic lock acquisition order,
    // preventing deadlocks when concurrent orders share overlapping products.
    $stmt = $db->prepare('SELECT product_id, quantity FROM order_items WHERE order_id = ? ORDER BY product_id ASC');
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();

    $allOk = true;
    foreach ($items as $item) {
        if ($item['product_id']) {
            if (!decrementStock($item['product_id'], $item['quantity'])) {
                error_log('[INVENTORY WARNING] Failed to decrement stock for product #' . $item['product_id']
                    . ' qty ' . $item['quantity'] . ' (order #' . $orderId . '): insufficient stock');
                $allOk = false;
            }
        }
    }
    return $allOk;
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
