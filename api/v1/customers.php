<?php
/**
 * REST API v1 — Customers Endpoints
 */

function handleListCustomers(): void {
    $db = getDB();
    $page = max(1, (int)apiParam('page', 1));
    $limit = min(100, max(1, (int)apiParam('limit', 20)));
    $offset = ($page - 1) * $limit;

    $totalCount = (int)$db->query('SELECT COUNT(*) FROM customers')->fetchColumn();

    $stmt = $db->prepare(
        'SELECT id, email, first_name, last_name, phone, created_at, last_login, is_active '
        . 'FROM customers ORDER BY created_at DESC LIMIT ? OFFSET ?'
    );
    $stmt->execute([$limit, $offset]);
    $customers = $stmt->fetchAll();

    apiResponse($customers, 200, [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalCount,
        'total_pages' => ceil($totalCount / $limit),
    ]);
}

function handleGetCustomer(int $id): void {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT id, email, first_name, last_name, phone, default_shipping_address, created_at, last_login, is_active '
        . 'FROM customers WHERE id = ?'
    );
    $stmt->execute([$id]);
    $customer = $stmt->fetch();

    if (!$customer) {
        apiError('Customer not found', 404, 'not_found');
    }

    // Attach order count and total spent
    $stats = $db->prepare(
        "SELECT COUNT(*) as order_count, COALESCE(SUM(total), 0) as total_spent "
        . "FROM orders WHERE customer_id = ? AND payment_status = 'completed'"
    );
    $stats->execute([$id]);
    $s = $stats->fetch();
    $customer['order_count'] = (int)$s['order_count'];
    $customer['total_spent'] = (float)$s['total_spent'];

    apiResponse($customer);
}
