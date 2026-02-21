<?php
/**
 * Product search functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Search products by query string
 * Searches name, description, category name, features, specifications
 */
function searchProducts(string $query, int $limit = 50): array {
    $db = getDB();
    $query = trim($query);
    if (strlen($query) < 2) return [];

    $searchTerm = '%' . $query . '%';

    $stmt = $db->prepare('
        SELECT p.*, pc.name as category_name, pc.slug as category_slug, pc.icon as category_icon
        FROM products p
        JOIN product_categories pc ON p.category_id = pc.id
        WHERE p.is_visible = 1 AND p.deleted_at IS NULL
          AND (
            p.name LIKE ? OR
            p.description LIKE ? OR
            p.specifications LIKE ? OR
            p.features LIKE ? OR
            pc.name LIKE ?
          )
        ORDER BY
            CASE WHEN p.name LIKE ? THEN 1
                 WHEN pc.name LIKE ? THEN 2
                 ELSE 3
            END,
            p.sort_order
        LIMIT ?
    ');

    $stmt->execute([
        $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm,
        $searchTerm, $searchTerm,
        $limit
    ]);

    return $stmt->fetchAll();
}

/**
 * Get search suggestions (for autocomplete)
 * Returns product names and category names matching query
 */
function getSearchSuggestions(string $query, int $limit = 8): array {
    $db = getDB();
    $query = trim($query);
    if (strlen($query) < 2) return [];

    $searchTerm = '%' . $query . '%';
    $suggestions = [];

    // Product names
    $stmt = $db->prepare('SELECT DISTINCT name FROM products WHERE is_visible = 1 AND deleted_at IS NULL AND name LIKE ? ORDER BY name LIMIT ?');
    $stmt->execute([$searchTerm, $limit]);
    foreach ($stmt->fetchAll() as $row) {
        $suggestions[] = ['type' => 'product', 'text' => $row['name']];
    }

    // Category names
    $stmt = $db->prepare('SELECT DISTINCT name FROM product_categories WHERE is_visible = 1 AND name LIKE ? ORDER BY name LIMIT ?');
    $stmt->execute([$searchTerm, min(3, $limit)]);
    foreach ($stmt->fetchAll() as $row) {
        $suggestions[] = ['type' => 'category', 'text' => $row['name']];
    }

    return array_slice($suggestions, 0, $limit);
}
