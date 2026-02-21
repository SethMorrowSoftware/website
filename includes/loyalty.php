<?php
/**
 * Customer Loyalty & Marketing Module
 *
 * Provides:
 * - Points earning on purchases
 * - Points redemption at checkout
 * - Referral program with unique codes
 * - Flash sales with scheduled pricing
 */

// ============================================================
// Loyalty Points
// ============================================================

/**
 * Check if loyalty program is enabled.
 */
function isLoyaltyEnabled(): bool {
    return getSetting('loyalty_enabled', '0') === '1';
}

/**
 * Get points balance for a customer.
 */
function getPointsBalance(int $customerId): int {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT points_balance FROM loyalty_points WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        $balance = $stmt->fetchColumn();
        return $balance !== false ? (int)$balance : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get lifetime points for a customer.
 */
function getLifetimePoints(int $customerId): int {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT lifetime_points FROM loyalty_points WHERE customer_id = ?');
        $stmt->execute([$customerId]);
        $pts = $stmt->fetchColumn();
        return $pts !== false ? (int)$pts : 0;
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Award points to a customer.
 */
function awardPoints(int $customerId, int $points, string $type, string $description = '', ?int $orderId = null): bool {
    if ($points <= 0) return false;
    try {
        $db = getDB();
        // Ensure row exists
        $db->prepare("INSERT IGNORE INTO loyalty_points (customer_id, points_balance, lifetime_points) VALUES (?, 0, 0)")->execute([$customerId]);
        // Update balance
        $db->prepare("UPDATE loyalty_points SET points_balance = points_balance + ?, lifetime_points = lifetime_points + ? WHERE customer_id = ?")->execute([$points, $points, $customerId]);
        // Log transaction
        $db->prepare("INSERT INTO loyalty_transactions (customer_id, points, type, description, order_id) VALUES (?, ?, ?, ?, ?)")->execute([$customerId, $points, $type, $description, $orderId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Redeem points from a customer's balance.
 */
function redeemPoints(int $customerId, int $points, string $description = '', ?int $orderId = null): bool {
    if ($points <= 0) return false;
    $balance = getPointsBalance($customerId);
    if ($balance < $points) return false;

    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE loyalty_points SET points_balance = points_balance - ? WHERE customer_id = ? AND points_balance >= ?");
        $stmt->execute([$points, $customerId, $points]);
        if ($stmt->rowCount() === 0) {
            return false; // Balance was insufficient (possible concurrent drain)
        }
        $db->prepare("INSERT INTO loyalty_transactions (customer_id, points, type, description, order_id) VALUES (?, ?, 'redeem', ?, ?)")->execute([$customerId, -$points, $description, $orderId]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Adjust points (admin manual adjustment).
 */
function adjustPoints(int $customerId, int $points, string $description = ''): bool {
    try {
        $db = getDB();
        $db->prepare("INSERT IGNORE INTO loyalty_points (customer_id, points_balance, lifetime_points) VALUES (?, 0, 0)")->execute([$customerId]);
        $db->prepare("UPDATE loyalty_points SET points_balance = GREATEST(0, points_balance + ?) WHERE customer_id = ?")->execute([$points, $customerId]);
        if ($points > 0) {
            $db->prepare("UPDATE loyalty_points SET lifetime_points = lifetime_points + ? WHERE customer_id = ?")->execute([$points, $customerId]);
        }
        $db->prepare("INSERT INTO loyalty_transactions (customer_id, points, type, description) VALUES (?, ?, 'adjust', ?)")->execute([$customerId, $points, $description]);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Calculate points earned for an order total.
 */
function calculateOrderPoints(float $orderTotal): int {
    $rate = (int)getSetting('loyalty_points_per_dollar', '1');
    return (int)floor($orderTotal * $rate);
}

/**
 * Calculate dollar value of points.
 */
function pointsToDollars(int $points): float {
    $rate = (int)getSetting('loyalty_redemption_rate', '100');
    if ($rate <= 0) return 0;
    return round($points / $rate, 2);
}

/**
 * Calculate points needed for a dollar amount.
 */
function dollarsToPoints(float $amount): int {
    $rate = (int)getSetting('loyalty_redemption_rate', '100');
    return (int)ceil($amount * $rate);
}

/**
 * Award welcome bonus to new customer.
 */
function awardWelcomeBonus(int $customerId): void {
    if (!isLoyaltyEnabled()) return;
    $bonus = (int)getSetting('loyalty_welcome_bonus', '0');
    if ($bonus > 0) {
        awardPoints($customerId, $bonus, 'bonus', 'Welcome bonus');
    }
}

/**
 * Award order points (called after payment).
 */
function awardOrderPoints(int $customerId, float $orderTotal, int $orderId): void {
    if (!isLoyaltyEnabled()) return;
    $points = calculateOrderPoints($orderTotal);
    if ($points > 0) {
        awardPoints($customerId, $points, 'earn', 'Purchase reward', $orderId);
    }
}

/**
 * Get transaction history for a customer.
 */
function getPointsHistory(int $customerId, int $limit = 20): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM loyalty_transactions WHERE customer_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$customerId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// ============================================================
// Referral Program
// ============================================================

/**
 * Generate a unique referral code for a customer.
 */
function getOrCreateReferralCode(int $customerId): string {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT referral_code FROM referrals WHERE referrer_customer_id = ? AND status = 'pending' LIMIT 1");
        $stmt->execute([$customerId]);
        $code = $stmt->fetchColumn();
        if ($code) return $code;

        // Generate new code
        $code = 'REF-' . strtoupper(substr(md5($customerId . time()), 0, 8));
        $rewardPoints = (int)getSetting('referral_reward_points', '500');
        $db->prepare("INSERT INTO referrals (referrer_customer_id, referral_code, reward_type, reward_value) VALUES (?, ?, 'points', ?)")->execute([$customerId, $code, $rewardPoints]);
        return $code;
    } catch (Exception $e) {
        return '';
    }
}

/**
 * Look up a referral by code.
 */
function getReferralByCode(string $code): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM referrals WHERE referral_code = ?");
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Complete a referral (called when referred customer makes first purchase).
 */
function completeReferral(string $code, int $referredCustomerId): bool {
    if (getSetting('referral_enabled', '0') !== '1') return false;

    $referral = getReferralByCode($code);
    if (!$referral || $referral['status'] !== 'pending') return false;

    try {
        $db = getDB();
        $db->prepare("UPDATE referrals SET referred_customer_id = ?, status = 'completed', completed_at = NOW() WHERE id = ?")->execute([$referredCustomerId, $referral['id']]);

        // Reward both parties
        $rewardPoints = (int)$referral['reward_value'];
        if ($rewardPoints > 0) {
            awardPoints($referral['referrer_customer_id'], $rewardPoints, 'bonus', 'Referral reward');
            awardPoints($referredCustomerId, (int)($rewardPoints / 2), 'bonus', 'Referral welcome bonus');
            $db->prepare("UPDATE referrals SET status = 'rewarded' WHERE id = ?")->execute([$referral['id']]);
        }

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get referral stats for a customer.
 */
function getReferralStats(int $customerId): array {
    try {
        $db = getDB();
        $total = $db->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_customer_id = ?");
        $total->execute([$customerId]);
        $completed = $db->prepare("SELECT COUNT(*) FROM referrals WHERE referrer_customer_id = ? AND status IN ('completed','rewarded')");
        $completed->execute([$customerId]);
        return [
            'total_referrals' => (int)$total->fetchColumn(),
            'completed_referrals' => (int)$completed->fetchColumn(),
        ];
    } catch (Exception $e) {
        return ['total_referrals' => 0, 'completed_referrals' => 0];
    }
}

// ============================================================
// Flash Sales
// ============================================================

/**
 * Get the active flash sale price for a product.
 * Returns the sale price or null if no active sale.
 */
function getFlashSalePrice(int $productId): ?float {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT sale_price FROM flash_sales
            WHERE product_id = ? AND is_active = 1 AND NOW() BETWEEN starts_at AND ends_at
            ORDER BY sale_price ASC LIMIT 1
        ");
        $stmt->execute([$productId]);
        $price = $stmt->fetchColumn();
        return $price !== false ? (float)$price : null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get all active flash sales.
 */
function getActiveFlashSales(): array {
    try {
        $db = getDB();
        return $db->query("
            SELECT fs.*, p.name as product_name, p.price as original_price, p.image
            FROM flash_sales fs
            JOIN products p ON fs.product_id = p.id
            WHERE fs.is_active = 1 AND NOW() BETWEEN fs.starts_at AND fs.ends_at
            ORDER BY fs.ends_at ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all flash sales (admin).
 */
function getAllFlashSales(): array {
    try {
        $db = getDB();
        return $db->query("
            SELECT fs.*, p.name as product_name, p.price as original_price
            FROM flash_sales fs
            JOIN products p ON fs.product_id = p.id
            ORDER BY fs.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Create a flash sale.
 */
function createFlashSale(int $productId, float $salePrice, string $startsAt, string $endsAt): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO flash_sales (product_id, sale_price, starts_at, ends_at) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$productId, $salePrice, $startsAt, $endsAt]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete a flash sale.
 */
function deleteFlashSale(int $id): bool {
    try {
        $db = getDB();
        return $db->prepare("DELETE FROM flash_sales WHERE id = ?")->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}
