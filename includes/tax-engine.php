<?php
/**
 * Advanced Shipping & Tax Engine
 *
 * Provides:
 * - Multi-zone tax rules with country/state/zip matching
 * - Tax-inclusive pricing toggle
 * - Weight-based shipping calculation
 * - Dimensional weight support
 */

// ============================================================
// Tax Calculation
// ============================================================

/**
 * Calculate tax for an order based on shipping address.
 *
 * Checks tax rules from most specific to least specific:
 * country+state+zip > country+state > country > global rate
 *
 * @param float  $subtotal     Order subtotal
 * @param string $country      2-letter country code
 * @param string $state        State/province
 * @param string $zip          ZIP/postal code
 * @param string $productType  Product type for differentiated rates
 * @return array ['rate' => float, 'amount' => float, 'rule_name' => string]
 */
function calculateTax(float $subtotal, string $country = '', string $state = '', string $zip = '', string $productType = 'all'): array {
    // Try tax rules first
    $rule = findMatchingTaxRule($country, $state, $zip, $productType);
    if ($rule) {
        $rate = (float)$rule['rate'];
        $amount = round($subtotal * ($rate / 100), 2);
        return ['rate' => $rate, 'amount' => $amount, 'rule_name' => $rule['name']];
    }

    // Fall back to global tax rate
    $globalRate = (float)getSetting('tax_rate', '0');
    $amount = round($subtotal * ($globalRate / 100), 2);
    return ['rate' => $globalRate, 'amount' => $amount, 'rule_name' => 'Default'];
}

/**
 * Find the most specific matching tax rule.
 */
function findMatchingTaxRule(string $country, string $state, string $zip, string $productType): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM tax_rules
            WHERE is_active = 1
              AND (product_type = 'all' OR product_type = ?)
              AND (country IS NULL OR country = '' OR country = ?)
              AND (state IS NULL OR state = '' OR state = ?)
              AND (zip_pattern IS NULL OR zip_pattern = '' OR ? LIKE zip_pattern)
            ORDER BY priority DESC,
                     (country IS NOT NULL AND country != '') DESC,
                     (state IS NOT NULL AND state != '') DESC,
                     (zip_pattern IS NOT NULL AND zip_pattern != '') DESC
            LIMIT 1
        ");
        $stmt->execute([$productType, $country, $state, $zip]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Check if pricing includes tax.
 */
function isTaxInclusive(): bool {
    return getSetting('tax_inclusive_pricing', '0') === '1';
}

/**
 * Extract tax from a tax-inclusive price.
 */
function extractTaxFromPrice(float $price, float $taxRate): array {
    $taxAmount = round($price - ($price / (1 + $taxRate / 100)), 2);
    return ['net_price' => $price - $taxAmount, 'tax' => $taxAmount];
}

// ============================================================
// Tax Rules CRUD
// ============================================================

/**
 * Get all tax rules.
 */
function getAllTaxRules(): array {
    try {
        $db = getDB();
        return $db->query("SELECT * FROM tax_rules ORDER BY priority DESC, country, state")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Create a tax rule.
 */
function createTaxRule(array $data): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO tax_rules (name, country, state, zip_pattern, rate, product_type, priority, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'], $data['country'] ?? '', $data['state'] ?? '',
            $data['zip_pattern'] ?? '', $data['rate'], $data['product_type'] ?? 'all',
            $data['priority'] ?? 0, isset($data['is_active']) ? 1 : 0,
        ]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete a tax rule.
 */
function deleteTaxRule(int $id): bool {
    try {
        $db = getDB();
        return $db->prepare("DELETE FROM tax_rules WHERE id = ?")->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}

// ============================================================
// Weight-Based Shipping
// ============================================================

/**
 * Calculate total weight for cart items.
 */
function calculateCartWeight(array $cartItems): float {
    $db = getDB();
    $totalWeight = 0.0;
    foreach ($cartItems as $item) {
        $stmt = $db->prepare("SELECT weight FROM products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $weight = $stmt->fetchColumn();
        $totalWeight += ((float)$weight ?: 0) * ($item['quantity'] ?? 1);
    }
    return $totalWeight;
}

/**
 * Calculate dimensional weight.
 */
function calculateDimensionalWeight(float $length, float $width, float $height, int $divisor = 5000): float {
    return ($length * $width * $height) / $divisor;
}

/**
 * Get the billable weight (greater of actual vs dimensional).
 */
function getBillableWeight(float $actualWeight, float $length, float $width, float $height): float {
    $dimWeight = calculateDimensionalWeight($length, $width, $height);
    return max($actualWeight, $dimWeight);
}
