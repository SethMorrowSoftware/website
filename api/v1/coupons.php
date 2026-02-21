<?php
/**
 * REST API v1 — Coupons Endpoints
 */

function handleListCoupons(): void {
    $db = getDB();
    $coupons = $db->query(
        'SELECT id, code, type, value, minimum_order, maximum_discount, usage_limit, used_count, '
        . 'valid_from, valid_until, applies_to, is_active, created_at '
        . 'FROM coupons ORDER BY created_at DESC'
    )->fetchAll();

    apiResponse($coupons);
}

function handleCreateCoupon(): void {
    $data = getRequestBody();
    $db = getDB();

    if (empty($data['code'])) {
        apiError("Field 'code' is required", 422, 'validation_error');
    }

    // Check uniqueness
    $existing = $db->prepare('SELECT COUNT(*) FROM coupons WHERE code = ?');
    $existing->execute([strtoupper($data['code'])]);
    if ((int)$existing->fetchColumn() > 0) {
        apiError('Coupon code already exists', 409, 'duplicate');
    }

    $stmt = $db->prepare(
        'INSERT INTO coupons (code, type, value, minimum_order, maximum_discount, usage_limit, valid_from, valid_until, applies_to, is_active) '
        . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        strtoupper($data['code']),
        $data['type'] ?? 'percentage',
        (float)($data['value'] ?? 0),
        (float)($data['minimum_order'] ?? 0),
        (float)($data['maximum_discount'] ?? 0),
        (int)($data['usage_limit'] ?? 0),
        $data['valid_from'] ?? null,
        $data['valid_until'] ?? null,
        $data['applies_to'] ?? 'all',
        isset($data['is_active']) ? (int)$data['is_active'] : 1,
    ]);

    $id = (int)$db->lastInsertId();
    $coupon = $db->prepare('SELECT * FROM coupons WHERE id = ?');
    $coupon->execute([$id]);

    apiResponse($coupon->fetch(), 201);
}
