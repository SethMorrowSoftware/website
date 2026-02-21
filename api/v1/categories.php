<?php
/**
 * REST API v1 — Categories Endpoint
 */

function handleListCategories(): void {
    $db = getDB();
    $stmt = $db->query(
        'SELECT id, name, slug, description, image, icon, sort_order, is_visible '
        . 'FROM product_categories WHERE is_visible = 1 ORDER BY sort_order ASC'
    );
    $categories = $stmt->fetchAll();

    // Attach product count per category
    foreach ($categories as &$cat) {
        $cnt = $db->prepare('SELECT COUNT(*) FROM products WHERE category_id = ? AND is_visible = 1 AND deleted_at IS NULL');
        $cnt->execute([$cat['id']]);
        $cat['product_count'] = (int)$cnt->fetchColumn();
    }

    apiResponse($categories);
}
