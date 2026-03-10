<?php
/**
 * Multi-Vendor Marketplace Module
 *
 * Provides:
 * - Vendor registration and management
 * - Product ownership (vendor_id on products)
 * - Commission calculation per order item
 * - Payout tracking
 * - Vendor storefront pages
 */

// ============================================================
// Marketplace Feature Toggle
// ============================================================

function isMarketplaceEnabled(): bool {
    return getSetting('marketplace_enabled', '0') === '1';
}

function getGlobalCommissionRate(): float {
    return (float)getSetting('marketplace_commission_rate', '15');
}

// ============================================================
// Vendor CRUD
// ============================================================

/**
 * Get all vendors with optional status filter.
 */
function getAllVendors(string $status = ''): array {
    try {
        $db = getDB();
        $sql = "SELECT v.*, (SELECT COUNT(*) FROM products WHERE vendor_id = v.id AND is_visible = 1 AND deleted_at IS NULL) AS product_count FROM vendors v";
        $params = [];
        if ($status) {
            $sql .= " WHERE v.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY v.created_at DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get a single vendor by ID.
 */
function getVendor(int $id): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM vendors WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get a vendor by slug (for storefront pages).
 */
function getVendorBySlug(string $slug): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM vendors WHERE slug = ? AND status = 'active'");
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get vendor for a customer account.
 */
function getVendorByCustomer(int $customerId): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM vendors WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Register a new vendor (from customer account).
 */
function registerVendor(int $customerId, string $storeName, string $description = ''): ?int {
    try {
        $db = getDB();
        $slug = createVendorSlug($storeName);
        $autoApprove = getSetting('marketplace_auto_approve_vendors', '0') === '1';
        $status = $autoApprove ? 'active' : 'pending';

        $stmt = $db->prepare("INSERT INTO vendors (customer_id, store_name, slug, description, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$customerId, $storeName, $slug, $description, $status]);
        return (int)$db->lastInsertId();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Create a URL-friendly slug for a vendor.
 */
function createVendorSlug(string $name): string {
    $slug = strtolower(trim(preg_replace('/[^a-z0-9-]/', '-', strtolower($name))));
    $slug = preg_replace('/-+/', '-', $slug);
    $slug = trim($slug, '-');

    // Non-Latin/emoji-only names can normalize into an empty slug, which
    // breaks registration once the first empty slug is used. Always keep a
    // deterministic fallback base to guarantee uniqueness checks can succeed.
    if ($slug === '') {
        $slug = 'vendor';
    }

    // Ensure uniqueness
    $db = getDB();
    $base = $slug;
    $i = 1;
    while (true) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM vendors WHERE slug = ?");
        $stmt->execute([$slug]);
        if ((int)$stmt->fetchColumn() === 0) break;
        $slug = $base . '-' . $i++;
    }
    return $slug;
}

/**
 * Update vendor status (approve, suspend, reject).
 */
function updateVendorStatus(int $vendorId, string $status): bool {
    $valid = ['pending', 'active', 'suspended', 'rejected'];
    if (!in_array($status, $valid)) return false;

    try {
        $db = getDB();
        return $db->prepare("UPDATE vendors SET status = ? WHERE id = ?")
            ->execute([$status, $vendorId]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Update vendor profile.
 */
function updateVendor(int $vendorId, array $data): bool {
    try {
        $db = getDB();
        $fields = [];
        $params = [];
        $allowed = ['store_name', 'description', 'logo', 'banner', 'commission_rate', 'payout_method', 'payout_details'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        if (empty($fields)) return false;

        $params[] = $vendorId;
        return $db->prepare("UPDATE vendors SET " . implode(', ', $fields) . " WHERE id = ?")
            ->execute($params);
    } catch (Exception $e) {
        return false;
    }
}

// ============================================================
// Vendor Products
// ============================================================

/**
 * Get products for a specific vendor.
 */
function getVendorProducts(int $vendorId, bool $visibleOnly = true): array {
    try {
        $db = getDB();
        $sql = "SELECT p.*, pc.name AS category_name FROM products p LEFT JOIN product_categories pc ON p.category_id = pc.id WHERE p.vendor_id = ? AND p.deleted_at IS NULL";
        if ($visibleOnly) $sql .= " AND p.is_visible = 1";
        $sql .= " ORDER BY p.sort_order ASC, p.id DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// ============================================================
// Commission Calculation
// ============================================================

/**
 * Calculate and record commission for an order.
 * Called after an order is completed/paid.
 */
function calculateOrderCommissions(int $orderId): void {
    try {
        $db = getDB();
        $items = $db->prepare("SELECT oi.*, p.vendor_id FROM order_items oi LEFT JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
        $items->execute([$orderId]);
        $orderItems = $items->fetchAll(PDO::FETCH_ASSOC);

        $globalRate = getGlobalCommissionRate();

        foreach ($orderItems as $item) {
            if (empty($item['vendor_id'])) continue;

            $vendor = getVendor($item['vendor_id']);
            if (!$vendor || $vendor['status'] !== 'active') continue;

            $rate = $vendor['commission_rate'] !== null ? (float)$vendor['commission_rate'] : $globalRate;
            $saleAmount = (float)$item['total_price'];
            $commissionAmount = round($saleAmount * ($rate / 100), 2);
            $vendorEarnings = $saleAmount - $commissionAmount;

            // Record commission
            $db->prepare("INSERT INTO vendor_commissions (vendor_id, order_id, order_item_id, sale_amount, commission_rate, commission_amount, vendor_earnings) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$item['vendor_id'], $orderId, $item['id'], $saleAmount, $rate, $commissionAmount, $vendorEarnings]);

            // Update vendor totals
            $db->prepare("UPDATE vendors SET total_sales = total_sales + ?, total_commission = total_commission + ? WHERE id = ?")
                ->execute([$saleAmount, $commissionAmount, $item['vendor_id']]);
        }
    } catch (Exception $e) {
        error_log('[MARKETPLACE] Commission error: ' . $e->getMessage());
    }
}

/**
 * Get commission summary for a vendor.
 */
function getVendorCommissions(int $vendorId, int $limit = 50): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT vc.*, o.order_number FROM vendor_commissions vc LEFT JOIN orders o ON vc.order_id = o.id WHERE vc.vendor_id = ? ORDER BY vc.created_at DESC LIMIT ?");
        $stmt->execute([$vendorId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// ============================================================
// Payouts
// ============================================================

/**
 * Get payouts for a vendor.
 */
function getVendorPayouts(int $vendorId): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM vendor_payouts WHERE vendor_id = ? ORDER BY created_at DESC");
        $stmt->execute([$vendorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Create a payout record.
 */
function createVendorPayout(int $vendorId, float $amount, string $method = 'manual', string $reference = '', string $notes = ''): ?int {
    try {
        if ($amount <= 0) {
            return null;
        }

        $db = getDB();

        // Make payout creation atomic so concurrent admin actions cannot
        // overpay a vendor by racing against each other.
        $db->beginTransaction();

        $vendorStmt = $db->prepare("SELECT total_sales, total_commission, total_payouts FROM vendors WHERE id = ? FOR UPDATE");
        $vendorStmt->execute([$vendorId]);
        $vendor = $vendorStmt->fetch(PDO::FETCH_ASSOC);

        if (!$vendor) {
            $db->rollBack();
            return null;
        }

        $balance = round(((float)$vendor['total_sales'] - (float)$vendor['total_commission']) - (float)$vendor['total_payouts'], 2);
        if ($amount > $balance) {
            $db->rollBack();
            return null;
        }

        $stmt = $db->prepare("INSERT INTO vendor_payouts (vendor_id, amount, method, reference, notes) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$vendorId, $amount, $method, $reference, $notes]);
        $id = (int)$db->lastInsertId();

        $db->commit();
        return $id;
    } catch (Exception $e) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        return null;
    }
}

/**
 * Mark a payout as completed.
 */
function completeVendorPayout(int $payoutId): bool {
    try {
        $db = getDB();

        // Ensure payout completion and vendor total updates happen atomically.
        // Without a lock, concurrent requests can both complete the same
        // payout and double-increment total_payouts.
        $db->beginTransaction();

        $payout = $db->prepare("SELECT * FROM vendor_payouts WHERE id = ? AND status = 'pending' FOR UPDATE");
        $payout->execute([$payoutId]);
        $p = $payout->fetch(PDO::FETCH_ASSOC);
        if (!$p) {
            $db->rollBack();
            return false;
        }

        $updateStmt = $db->prepare("UPDATE vendor_payouts SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ? AND status = 'pending'");
        $updateStmt->execute([$payoutId]);
        if ($updateStmt->rowCount() !== 1) {
            $db->rollBack();
            return false;
        }

        $db->prepare("UPDATE vendors SET total_payouts = total_payouts + ? WHERE id = ?")
            ->execute([$p['amount'], $p['vendor_id']]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
            $db->rollBack();
        }
        return false;
    }
}

/**
 * Get the unpaid balance for a vendor.
 */
function getVendorBalance(int $vendorId): float {
    $vendor = getVendor($vendorId);
    if (!$vendor) return 0;
    return round(($vendor['total_sales'] - $vendor['total_commission']) - $vendor['total_payouts'], 2);
}

// ============================================================
// Marketplace Statistics (for admin dashboard)
// ============================================================

function getMarketplaceStats(): array {
    try {
        $db = getDB();
        $stats = [];

        $stats['total_vendors'] = (int)$db->query("SELECT COUNT(*) FROM vendors")->fetchColumn();
        $stats['active_vendors'] = (int)$db->query("SELECT COUNT(*) FROM vendors WHERE status = 'active'")->fetchColumn();
        $stats['pending_vendors'] = (int)$db->query("SELECT COUNT(*) FROM vendors WHERE status = 'pending'")->fetchColumn();
        $stats['total_commission'] = (float)$db->query("SELECT COALESCE(SUM(total_commission), 0) FROM vendors")->fetchColumn();
        $stats['total_payouts'] = (float)$db->query("SELECT COALESCE(SUM(total_payouts), 0) FROM vendors")->fetchColumn();
        $stats['pending_payouts'] = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM vendor_payouts WHERE status = 'pending'")->fetchColumn();

        return $stats;
    } catch (Exception $e) {
        return ['total_vendors' => 0, 'active_vendors' => 0, 'pending_vendors' => 0, 'total_commission' => 0, 'total_payouts' => 0, 'pending_payouts' => 0];
    }
}
