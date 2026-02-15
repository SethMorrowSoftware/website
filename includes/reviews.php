<?php
/**
 * Reviews & Ratings Module
 */

function getProductReviews(int $productId, int $limit = 20): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM reviews WHERE product_id = ? AND is_approved = 1 ORDER BY created_at DESC LIMIT ?');
    $stmt->execute([$productId, $limit]);
    return $stmt->fetchAll();
}

function getProductRating(int $productId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) as count, COALESCE(AVG(rating), 0) as average FROM reviews WHERE product_id = ? AND is_approved = 1');
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    return [
        'count' => (int)$result['count'],
        'average' => round((float)$result['average'], 1),
    ];
}

function getRatingBreakdown(int $productId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT rating, COUNT(*) as count FROM reviews WHERE product_id = ? AND is_approved = 1 GROUP BY rating ORDER BY rating DESC');
    $stmt->execute([$productId]);
    $results = $stmt->fetchAll();
    $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    foreach ($results as $row) {
        $breakdown[(int)$row['rating']] = (int)$row['count'];
    }
    return $breakdown;
}

function submitReview(int $productId, array $data): bool {
    $db = getDB();
    $isVerified = 0;
    $customerId = null;

    // Check if customer is logged in and has purchased this product
    if (isCustomerLoggedIn()) {
        $customerId = getCustomerId();
        $stmt = $db->prepare('SELECT COUNT(*) FROM orders o JOIN order_items oi ON o.id = oi.order_id WHERE o.customer_id = ? AND oi.product_id = ? AND o.payment_status = ?');
        $stmt->execute([$customerId, $productId, 'paid']);
        $isVerified = (int)$stmt->fetchColumn() > 0 ? 1 : 0;
    }

    $stmt = $db->prepare('INSERT INTO reviews (product_id, customer_id, customer_name, customer_email, rating, title, body, is_verified_purchase, is_approved, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, datetime("now"))');
    return $stmt->execute([
        $productId,
        $customerId,
        $data['name'],
        $data['email'],
        (int)$data['rating'],
        $data['title'],
        $data['body'],
        $isVerified,
    ]);
}

function canReview(int $productId): bool {
    // Prevent duplicate reviews from same customer
    if (isCustomerLoggedIn()) {
        $db = getDB();
        $stmt = $db->prepare('SELECT COUNT(*) FROM reviews WHERE product_id = ? AND customer_id = ?');
        $stmt->execute([$productId, getCustomerId()]);
        return (int)$stmt->fetchColumn() === 0;
    }
    return true; // Guests can always try (rate-limited by form submission)
}

function getAllReviewsAdmin(string $filter = 'all'): array {
    $db = getDB();
    $where = '';
    if ($filter === 'pending') $where = 'WHERE r.is_approved = 0';
    elseif ($filter === 'approved') $where = 'WHERE r.is_approved = 1';
    $stmt = $db->query("SELECT r.*, p.name as product_name FROM reviews r LEFT JOIN products p ON r.product_id = p.id $where ORDER BY r.created_at DESC");
    return $stmt->fetchAll();
}

function approveReview(int $id): bool {
    $db = getDB();
    return $db->prepare('UPDATE reviews SET is_approved = 1 WHERE id = ?')->execute([$id]);
}

function deleteReview(int $id): bool {
    $db = getDB();
    return $db->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
}

function renderStars(float $rating, bool $interactive = false): string {
    $html = '<div class="star-rating' . ($interactive ? ' star-rating-interactive' : '') . '">';
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($rating >= $i - 0.5) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}
