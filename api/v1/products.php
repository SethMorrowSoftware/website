<?php
/**
 * REST API v1 — Products Endpoints
 */

function handleListProducts(): void {
    $db = getDB();
    $page = max(1, (int)apiParam('page', 1));
    $limit = min(100, max(1, (int)apiParam('limit', 20)));
    $offset = ($page - 1) * $limit;
    $categoryId = apiParam('category_id');

    $where = 'WHERE p.is_visible = 1 AND p.deleted_at IS NULL';
    $params = [];

    if ($categoryId) {
        $where .= ' AND p.category_id = ?';
        $params[] = (int)$categoryId;
    }

    $total = $db->prepare("SELECT COUNT(*) FROM products p $where");
    $total->execute($params);
    $totalCount = (int)$total->fetchColumn();

    $stmt = $db->prepare(
        "SELECT p.id, p.name, p.slug, p.description, p.image, p.price, p.unit, "
        . "p.product_type, p.is_available, p.request_quote_only, p.category_id, pc.name as category_name, "
        . "p.specifications, p.features, p.created_at "
        . "FROM products p "
        . "JOIN product_categories pc ON p.category_id = pc.id "
        . "$where ORDER BY p.sort_order ASC LIMIT ? OFFSET ?"
    );
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    apiResponse($products, 200, [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalCount,
        'total_pages' => ceil($totalCount / $limit),
    ]);
}

function handleGetProduct(int $id): void {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT p.*, pc.name as category_name, pc.slug as category_slug "
        . "FROM products p "
        . "JOIN product_categories pc ON p.category_id = pc.id "
        . "WHERE p.id = ? AND p.deleted_at IS NULL"
    );
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        apiError('Product not found', 404, 'not_found');
    }

    // Attach images
    $imgs = $db->prepare('SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order');
    $imgs->execute([$id]);
    $product['images'] = $imgs->fetchAll();

    // Attach options
    $opts = $db->prepare('SELECT * FROM product_options WHERE product_id = ? ORDER BY sort_order');
    $opts->execute([$id]);
    $product['options'] = [];
    foreach ($opts->fetchAll() as $opt) {
        $vals = $db->prepare('SELECT * FROM product_option_values WHERE option_id = ? ORDER BY sort_order');
        $vals->execute([$opt['id']]);
        $opt['values'] = $vals->fetchAll();
        $product['options'][] = $opt;
    }

    apiResponse($product);
}

function handleCreateProduct(): void {
    $data = getRequestBody();
    $db = getDB();

    $required = ['name', 'category_id', 'price'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            apiError("Field '$field' is required", 422, 'validation_error');
        }
    }

    $slug = createSlug($data['name']);
    // Ensure unique slug
    $existing = $db->prepare('SELECT COUNT(*) FROM products WHERE slug = ?');
    $existing->execute([$slug]);
    if ((int)$existing->fetchColumn() > 0) {
        $slug .= '-' . substr(bin2hex(random_bytes(3)), 0, 6);
    }

    $stmt = $db->prepare(
        'INSERT INTO products (category_id, name, slug, description, price, unit, product_type, specifications, features, price_note, is_visible, is_available) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        (int)$data['category_id'],
        $data['name'],
        $slug,
        $data['description'] ?? '',
        $data['price'],
        $data['unit'] ?? '',
        $data['product_type'] ?? 'physical',
        $data['specifications'] ?? '',
        $data['features'] ?? '',
        $data['price_note'] ?? '',
        isset($data['is_visible']) ? (int)$data['is_visible'] : 1,
        isset($data['is_available']) ? (int)$data['is_available'] : 1,
    ]);

    $newId = (int)$db->lastInsertId();
    do_action('after_api_product_created', $newId, $data);

    handleGetProduct($newId);
}

function handleUpdateProduct(int $id): void {
    $data = getRequestBody();
    $db = getDB();

    // Check product exists
    $stmt = $db->prepare('SELECT id FROM products WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        apiError('Product not found', 404, 'not_found');
    }

    $allowedFields = ['name', 'description', 'price', 'unit', 'product_type', 'specifications', 'features', 'price_note', 'is_visible', 'is_available', 'request_quote_only', 'category_id'];
    $fields = [];
    $values = [];

    foreach ($allowedFields as $f) {
        if (array_key_exists($f, $data)) {
            $fields[] = "$f = ?";
            $values[] = $data[$f];
        }
    }

    if (empty($fields)) {
        apiError('No valid fields to update', 422, 'validation_error');
    }

    $values[] = $id;
    $db->prepare('UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);

    handleGetProduct($id);
}

function handleDeleteProduct(int $id): void {
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM products WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        apiError('Product not found', 404, 'not_found');
    }

    // Soft delete
    $db->prepare('UPDATE products SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$id]);
    apiResponse(['deleted' => true]);
}
