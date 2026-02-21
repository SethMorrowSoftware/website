<?php
/**
 * REST API v1 — Analytics Endpoints
 */

function handleSalesAnalytics(): void {
    $db = getDB();
    $period = apiParam('period', '30d'); // 7d, 30d, 90d, all

    $dateFilter = '';
    switch ($period) {
        case '7d':
            $dateFilter = "AND date(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            break;
        case '30d':
            $dateFilter = "AND date(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            break;
        case '90d':
            $dateFilter = "AND date(created_at) >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)";
            break;
        // 'all' = no filter
    }

    // Revenue summary
    $summary = $db->query(
        "SELECT "
        . "COUNT(*) as total_orders, "
        . "COALESCE(SUM(total), 0) as total_revenue, "
        . "COALESCE(AVG(total), 0) as avg_order_value, "
        . "COALESCE(SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END), 0) as cancelled_orders "
        . "FROM orders WHERE payment_status = 'completed' AND deleted_at IS NULL $dateFilter"
    )->fetch();

    // Daily revenue
    $daily = $db->query(
        "SELECT date(created_at) as date, COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue "
        . "FROM orders WHERE payment_status = 'completed' AND deleted_at IS NULL $dateFilter "
        . "GROUP BY date(created_at) ORDER BY date ASC"
    )->fetchAll();

    // Top products
    $topProducts = $db->query(
        "SELECT oi.product_name, SUM(oi.quantity) as total_quantity, SUM(oi.total_price) as total_revenue "
        . "FROM order_items oi "
        . "JOIN orders o ON oi.order_id = o.id "
        . "WHERE o.payment_status = 'completed' AND o.deleted_at IS NULL $dateFilter "
        . "GROUP BY oi.product_name ORDER BY total_revenue DESC LIMIT 10"
    )->fetchAll();

    // Revenue by payment method
    $byPaymentMethod = $db->query(
        "SELECT payment_method, COUNT(*) as orders, COALESCE(SUM(total), 0) as revenue "
        . "FROM orders WHERE payment_status = 'completed' AND deleted_at IS NULL $dateFilter "
        . "GROUP BY payment_method ORDER BY revenue DESC"
    )->fetchAll();

    apiResponse([
        'period' => $period,
        'summary' => $summary,
        'daily_revenue' => $daily,
        'top_products' => $topProducts,
        'revenue_by_payment_method' => $byPaymentMethod,
    ]);
}
