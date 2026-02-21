<?php
/**
 * REST API v1 — Inventory Endpoints
 */

function handleListInventory(): void {
    $db = getDB();
    $lowStockOnly = apiParam('low_stock');

    $sql = 'SELECT p.id, p.name, p.slug, p.is_available, p.price, pc.name as category_name '
         . 'FROM products p JOIN product_categories pc ON p.category_id = pc.id '
         . 'WHERE p.deleted_at IS NULL';

    $products = $db->query($sql . ' ORDER BY p.name ASC')->fetchAll();

    // Attach stock info
    foreach ($products as &$p) {
        $p['stock_quantity'] = getStockQuantity($p['id']);
    }

    // Filter for low stock if requested
    if ($lowStockOnly) {
        $threshold = (int)getSetting('low_stock_threshold', '5');
        $products = array_values(array_filter($products, fn($p) => $p['stock_quantity'] <= $threshold && $p['stock_quantity'] >= 0));
    }

    apiResponse($products);
}

function handleUpdateInventory(int $id): void {
    $data = getRequestBody();
    $db = getDB();

    $stmt = $db->prepare('SELECT id FROM products WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        apiError('Product not found', 404, 'not_found');
    }

    if (!isset($data['stock_quantity']) && !isset($data['is_available'])) {
        apiError('Provide stock_quantity or is_available', 422, 'validation_error');
    }

    if (isset($data['is_available'])) {
        $db->prepare('UPDATE products SET is_available = ? WHERE id = ?')
           ->execute([(int)$data['is_available'], $id]);
    }

    if (isset($data['stock_quantity'])) {
        // Update stock via product_option_values if variants exist, else just set availability
        $qty = (int)$data['stock_quantity'];
        $hasVariants = $db->prepare('SELECT COUNT(*) FROM product_options WHERE product_id = ?');
        $hasVariants->execute([$id]);

        if ((int)$hasVariants->fetchColumn() > 0) {
            // For variant products, the stock is on option values; update all
            $db->prepare('UPDATE product_option_values SET stock_quantity = ? WHERE option_id IN (SELECT id FROM product_options WHERE product_id = ?)')
               ->execute([$qty, $id]);
        }
        // Set availability based on stock
        $db->prepare('UPDATE products SET is_available = ? WHERE id = ?')
           ->execute([$qty > 0 ? 1 : 0, $id]);
    }

    $availStmt = $db->prepare('SELECT is_available FROM products WHERE id = ?');
    $availStmt->execute([$id]);

    apiResponse([
        'id' => $id,
        'stock_quantity' => getStockQuantity($id),
        'is_available' => (bool)$availStmt->fetchColumn(),
    ]);
}
