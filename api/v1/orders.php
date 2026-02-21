<?php
/**
 * REST API v1 — Orders Endpoints
 */

function handleListOrders(): void {
    $db = getDB();
    $page = max(1, (int)apiParam('page', 1));
    $limit = min(100, max(1, (int)apiParam('limit', 20)));
    $offset = ($page - 1) * $limit;
    $status = apiParam('status');

    $where = 'WHERE o.deleted_at IS NULL';
    $params = [];

    if ($status) {
        $where .= ' AND o.order_status = ?';
        $params[] = $status;
    }

    $total = $db->prepare("SELECT COUNT(*) FROM orders o $where");
    $total->execute($params);
    $totalCount = (int)$total->fetchColumn();

    $stmt = $db->prepare(
        "SELECT o.id, o.order_number, o.customer_name, o.customer_email, o.customer_phone, "
        . "o.subtotal, o.tax, o.total, o.payment_method, o.payment_status, o.order_status, "
        . "o.created_at, o.updated_at "
        . "FROM orders o $where ORDER BY o.created_at DESC LIMIT ? OFFSET ?"
    );
    $params[] = $limit;
    $params[] = $offset;
    $stmt->execute($params);
    $orders = $stmt->fetchAll();

    apiResponse($orders, 200, [
        'page' => $page,
        'limit' => $limit,
        'total' => $totalCount,
        'total_pages' => ceil($totalCount / $limit),
    ]);
}

function handleGetOrder(int $id): void {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        apiError('Order not found', 404, 'not_found');
    }

    // Attach line items
    $items = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $items->execute([$id]);
    $order['items'] = $items->fetchAll();

    apiResponse($order);
}

function handleUpdateOrder(int $id): void {
    $data = getRequestBody();
    $db = getDB();

    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? AND deleted_at IS NULL');
    $stmt->execute([$id]);
    $order = $stmt->fetch();

    if (!$order) {
        apiError('Order not found', 404, 'not_found');
    }

    $allowedFields = ['order_status', 'payment_status', 'notes'];
    $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
    $validPaymentStatuses = ['pending', 'completed', 'failed', 'refunded'];

    if (isset($data['order_status']) && !in_array($data['order_status'], $validStatuses)) {
        apiError('Invalid order_status. Allowed: ' . implode(', ', $validStatuses), 422, 'validation_error');
    }
    if (isset($data['payment_status']) && !in_array($data['payment_status'], $validPaymentStatuses)) {
        apiError('Invalid payment_status. Allowed: ' . implode(', ', $validPaymentStatuses), 422, 'validation_error');
    }

    $fields = [];
    $values = [];

    foreach ($allowedFields as $f) {
        if (array_key_exists($f, $data)) {
            $fields[] = "$f = ?";
            $values[] = $data[$f];
        }
    }

    if (empty($fields)) {
        apiError('No valid fields to update', 422, 'validation_error');
    }

    $values[] = $id;
    $db->prepare('UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($values);

    if (isset($data['order_status'])
        && in_array($data['order_status'], ['cancelled', 'refunded'])
        && !in_array($order['order_status'], ['cancelled', 'refunded'])) {
        restoreOrderInventory($id);
    }

    do_action('after_api_order_updated', $id, $data);

    handleGetOrder($id);
}
