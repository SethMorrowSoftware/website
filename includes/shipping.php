<?php
/**
 * Shipping Module — zones, methods, and calculation
 */

/**
 * Get all active shipping zones
 */
function getShippingZones(): array {
    $db = getDB();
    return $db->query('SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY sort_order')->fetchAll();
}

/**
 * Get all shipping zones (including inactive) for admin
 */
function getAllShippingZones(): array {
    $db = getDB();
    return $db->query('SELECT * FROM shipping_zones ORDER BY sort_order')->fetchAll();
}

/**
 * Get shipping methods for a zone
 */
function getShippingMethods(int $zoneId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM shipping_methods WHERE zone_id = ? AND is_active = 1 ORDER BY sort_order');
    $stmt->execute([$zoneId]);
    return $stmt->fetchAll();
}

/**
 * Get all methods for a zone (admin)
 */
function getAllShippingMethods(int $zoneId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM shipping_methods WHERE zone_id = ? ORDER BY sort_order');
    $stmt->execute([$zoneId]);
    return $stmt->fetchAll();
}

/**
 * Get available shipping options for current cart
 * Returns methods from the default zone (simplified — no geo-matching)
 */
function getAvailableShippingMethods(): array {
    $db = getDB();
    $totals = getCartTotals();
    $subtotal = $totals['subtotal'];

    // Get default zone (or first active zone)
    $zone = $db->query('SELECT * FROM shipping_zones WHERE is_default = 1 AND is_active = 1 LIMIT 1')->fetch();
    if (!$zone) {
        $zone = $db->query('SELECT * FROM shipping_zones WHERE is_active = 1 ORDER BY sort_order LIMIT 1')->fetch();
    }
    if (!$zone) return [];

    $stmt = $db->prepare('SELECT * FROM shipping_methods WHERE zone_id = ? AND is_active = 1 ORDER BY sort_order');
    $stmt->execute([$zone['id']]);
    $methods = $stmt->fetchAll();

    $available = [];
    foreach ($methods as $method) {
        // Check price range constraints
        if ($method['min_price'] > 0 && $subtotal < $method['min_price']) continue;
        if ($method['max_price'] > 0 && $subtotal > $method['max_price']) continue;

        // Calculate effective cost
        $cost = (float)$method['cost'];

        // Free shipping threshold
        if ($method['free_threshold'] > 0 && $subtotal >= $method['free_threshold']) {
            $cost = 0;
        }

        // Free shipping type is always free
        if ($method['type'] === 'free') {
            $cost = 0;
        }

        $available[] = [
            'id' => $method['id'],
            'name' => $method['name'],
            'type' => $method['type'],
            'cost' => $cost,
            'original_cost' => (float)$method['cost'],
            'free_threshold' => (float)$method['free_threshold'],
            'estimated_days' => $method['estimated_days'],
        ];
    }

    return $available;
}

/**
 * Get a specific shipping method by ID
 */
function getShippingMethod(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM shipping_methods WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

/**
 * Calculate shipping cost for a specific method
 */
function calculateShipping(int $methodId): float {
    $method = getShippingMethod($methodId);
    if (!$method) return 0;

    $totals = getCartTotals();
    $subtotal = $totals['subtotal'];

    if ($method['type'] === 'free') return 0;
    if ($method['free_threshold'] > 0 && $subtotal >= $method['free_threshold']) return 0;

    return (float)$method['cost'];
}

/**
 * Get cart totals including shipping
 */
function getCartTotalsWithShipping(int $shippingMethodId = 0): array {
    $totals = function_exists('getCartTotalsWithCoupon') ? getCartTotalsWithCoupon() : getCartTotals();

    $shippingCost = 0;
    $shippingName = '';

    if ($shippingMethodId > 0) {
        $shippingCost = calculateShipping($shippingMethodId);
        $method = getShippingMethod($shippingMethodId);
        $shippingName = $method ? $method['name'] : '';
    }

    $totals['shipping'] = $shippingCost;
    $totals['shipping_name'] = $shippingName;
    $totals['total'] = $totals['total'] + $shippingCost;

    return $totals;
}

/**
 * Save a shipping zone
 */
function saveShippingZone(array $data): int {
    $db = getDB();
    if (!empty($data['id'])) {
        $stmt = $db->prepare('UPDATE shipping_zones SET name = ?, countries = ?, states = ?, is_default = ?, is_active = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([$data['name'], $data['countries'] ?? '', $data['states'] ?? '', $data['is_default'] ?? 0, $data['is_active'] ?? 1, $data['sort_order'] ?? 0, $data['id']]);
        return (int)$data['id'];
    }
    $stmt = $db->prepare('INSERT INTO shipping_zones (name, countries, states, is_default, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$data['name'], $data['countries'] ?? '', $data['states'] ?? '', $data['is_default'] ?? 0, $data['is_active'] ?? 1, $data['sort_order'] ?? 0]);
    return (int)$db->lastInsertId();
}

/**
 * Save a shipping method
 */
function saveShippingMethod(array $data): int {
    $db = getDB();
    if (!empty($data['id'])) {
        $stmt = $db->prepare('UPDATE shipping_methods SET zone_id = ?, name = ?, type = ?, cost = ?, free_threshold = ?, min_price = ?, max_price = ?, estimated_days = ?, is_active = ?, sort_order = ? WHERE id = ?');
        $stmt->execute([$data['zone_id'], $data['name'], $data['type'], $data['cost'] ?? 0, $data['free_threshold'] ?? 0, $data['min_price'] ?? 0, $data['max_price'] ?? 0, $data['estimated_days'] ?? '', $data['is_active'] ?? 1, $data['sort_order'] ?? 0, $data['id']]);
        return (int)$data['id'];
    }
    $stmt = $db->prepare('INSERT INTO shipping_methods (zone_id, name, type, cost, free_threshold, min_price, max_price, estimated_days, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$data['zone_id'], $data['name'], $data['type'], $data['cost'] ?? 0, $data['free_threshold'] ?? 0, $data['min_price'] ?? 0, $data['max_price'] ?? 0, $data['estimated_days'] ?? '', $data['is_active'] ?? 1, $data['sort_order'] ?? 0]);
    return (int)$db->lastInsertId();
}

/**
 * Delete a shipping method
 */
function deleteShippingMethod(int $id): bool {
    $db = getDB();
    return $db->prepare('DELETE FROM shipping_methods WHERE id = ?')->execute([$id]);
}

/**
 * Delete a shipping zone and its methods
 */
function deleteShippingZone(int $id): bool {
    $db = getDB();
    // Delete associated shipping methods first to avoid orphaned records
    $db->prepare('DELETE FROM shipping_methods WHERE zone_id = ?')->execute([$id]);
    return $db->prepare('DELETE FROM shipping_zones WHERE id = ?')->execute([$id]);
}
