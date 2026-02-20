<?php
/**
 * Discount & coupon system functions
 */

require_once __DIR__ . '/../config.php';

/**
 * Validate a coupon code and return coupon data or error
 */
function validateCoupon(string $code, float $subtotal = 0, array $cartItems = []): array {
    $db = getDB();
    $code = strtoupper(trim($code));

    $stmt = $db->prepare('SELECT * FROM coupons WHERE code = ? AND is_active = 1');
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        return ['valid' => false, 'error' => 'Invalid coupon code.'];
    }

    // Check date validity
    if ($coupon['valid_from'] && strtotime($coupon['valid_from']) > time()) {
        return ['valid' => false, 'error' => 'This coupon is not yet active.'];
    }
    if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < time()) {
        return ['valid' => false, 'error' => 'This coupon has expired.'];
    }

    // Check usage limit
    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        return ['valid' => false, 'error' => 'This coupon has reached its usage limit.'];
    }

    // Check minimum order
    if ($coupon['minimum_order'] > 0 && $subtotal < $coupon['minimum_order']) {
        $symbol = getSetting('currency_symbol', '$');
        return ['valid' => false, 'error' => "Minimum order of {$symbol}" . number_format($coupon['minimum_order'], 2) . ' required.'];
    }

    // Check product/category restrictions
    if ($coupon['applies_to'] === 'specific_products') {
        $productIds = $db->prepare('SELECT product_id FROM coupon_products WHERE coupon_id = ?');
        $productIds->execute([$coupon['id']]);
        $allowedProducts = array_column($productIds->fetchAll(), 'product_id');

        $hasEligible = false;
        foreach ($cartItems as $item) {
            if (in_array($item['product_id'], $allowedProducts)) {
                $hasEligible = true;
                break;
            }
        }
        if (!$hasEligible) {
            return ['valid' => false, 'error' => 'This coupon is not valid for the items in your cart.'];
        }
    }

    if ($coupon['applies_to'] === 'specific_categories') {
        $categoryIds = $db->prepare('SELECT category_id FROM coupon_categories WHERE coupon_id = ?');
        $categoryIds->execute([$coupon['id']]);
        $allowedCategories = array_column($categoryIds->fetchAll(), 'category_id');

        // Need to check each cart item's category
        $hasEligible = false;
        foreach ($cartItems as $item) {
            $prodStmt = $db->prepare('SELECT category_id FROM products WHERE id = ?');
            $prodStmt->execute([$item['product_id']]);
            $prod = $prodStmt->fetch();
            if ($prod && in_array($prod['category_id'], $allowedCategories)) {
                $hasEligible = true;
                break;
            }
        }
        if (!$hasEligible) {
            return ['valid' => false, 'error' => 'This coupon is not valid for the items in your cart.'];
        }
    }

    return ['valid' => true, 'coupon' => $coupon];
}

/**
 * Calculate discount amount for a coupon
 */
function calculateDiscount(array $coupon, float $subtotal): float {
    $discount = 0;

    switch ($coupon['type']) {
        case 'percentage':
            $discount = $subtotal * ($coupon['value'] / 100);
            break;
        case 'fixed':
            $discount = $coupon['value'];
            break;
        case 'free_shipping':
            $discount = 0; // Handled separately
            break;
    }

    // Apply maximum discount cap
    if ($coupon['maximum_discount'] > 0 && $discount > $coupon['maximum_discount']) {
        $discount = $coupon['maximum_discount'];
    }

    // Don't exceed subtotal
    $discount = min($discount, $subtotal);

    return round($discount, 2);
}

/**
 * Increment coupon usage count
 */
function incrementCouponUsage(int $couponId): void {
    $db = getDB();
    $db->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?')->execute([$couponId]);
}

/**
 * Apply coupon to session cart
 */
function applyCouponToCart(string $code): array {
    if (session_status() === PHP_SESSION_NONE) session_start();

    $cart = getCart();
    $totals = getCartTotals();
    $result = validateCoupon($code, $totals['subtotal'], $cart);

    if (!$result['valid']) {
        return $result;
    }

    $coupon = $result['coupon'];
    $discount = calculateDiscount($coupon, $totals['subtotal']);

    $_SESSION['coupon'] = [
        'id' => $coupon['id'],
        'code' => $coupon['code'],
        'type' => $coupon['type'],
        'value' => $coupon['value'],
        'discount' => $discount,
    ];

    return ['valid' => true, 'discount' => $discount, 'coupon' => $coupon];
}

/**
 * Remove coupon from session
 */
function removeCouponFromCart(): void {
    if (session_status() === PHP_SESSION_NONE) session_start();
    unset($_SESSION['coupon']);
}

/**
 * Get currently applied coupon from session
 */
function getAppliedCoupon(): ?array {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['coupon'] ?? null;
}

/**
 * Get cart totals including coupon discount
 */
function getCartTotalsWithCoupon(): array {
    $totals = getCartTotals();
    $coupon = getAppliedCoupon();

    $totals['discount'] = 0;
    $totals['coupon_code'] = null;

    if ($coupon) {
        // Recalculate discount against current subtotal (not the stale cached amount)
        // so that removing items from cart correctly reduces the discount
        $recalculated = calculateDiscount($coupon, $totals['subtotal']);
        $totals['discount'] = $recalculated;
        $totals['coupon_code'] = $coupon['code'];
        $totals['total'] = max(0, $totals['subtotal'] - $totals['discount'] + $totals['tax']);

        // Update the session with the recalculated discount
        $_SESSION['coupon']['discount'] = $recalculated;
    }

    return $totals;
}

/**
 * Get a coupon by ID
 */
function getCoupon(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM coupons WHERE id = ?');
    $stmt->execute([$id]);
    $coupon = $stmt->fetch();
    return $coupon ?: null;
}

/**
 * Get all coupons for admin
 */
function getAllCoupons(): array {
    $db = getDB();
    return $db->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();
}
