<?php
/**
 * Analytics & Reporting Module
 *
 * Provides traffic tracking, sales aggregation, customer insights,
 * inventory reports, and report caching.
 */

// ============================================================
// Page View Tracking
// ============================================================

/**
 * Record a page view (lightweight, privacy-friendly).
 */
function trackPageView(string $pagePath): void {
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO page_views (page_path, referrer, user_agent, session_id) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            substr($pagePath, 0, 255),
            isset($_SERVER['HTTP_REFERER']) ? substr($_SERVER['HTTP_REFERER'], 0, 500) : null,
            isset($_SERVER['HTTP_USER_AGENT']) ? substr($_SERVER['HTTP_USER_AGENT'], 0, 500) : null,
            session_id() ?: null,
        ]);
    } catch (Exception $e) {
        // Silently fail — tracking should never break the site
    }
}

// ============================================================
// Report Cache
// ============================================================

/**
 * Get a cached report or generate it.
 *
 * @param string   $key      Cache key
 * @param callable $generator Function that returns report data array
 * @param int      $ttlMinutes Cache TTL in minutes
 * @return array Report data
 */
function getCachedReport(string $key, callable $generator, int $ttlMinutes = 60): array {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT data_json, generated_at FROM report_cache WHERE report_key = ?');
        $stmt->execute([$key]);
        $cached = $stmt->fetch();

        if ($cached) {
            $age = time() - strtotime($cached['generated_at']);
            if ($age < $ttlMinutes * 60) {
                return json_decode($cached['data_json'], true) ?: [];
            }
        }
    } catch (Exception $e) {
        // Table might not exist yet
    }

    $data = $generator();

    try {
        $db = getDB();
        $stmt = $db->prepare('REPLACE INTO report_cache (report_key, data_json, generated_at) VALUES (?, ?, NOW())');
        $stmt->execute([$key, json_encode($data)]);
    } catch (Exception $e) {
        // Cache write failure is non-critical
    }

    return $data;
}

/**
 * Invalidate a cached report.
 */
function invalidateReport(string $key): void {
    try {
        $db = getDB();
        $db->prepare('DELETE FROM report_cache WHERE report_key = ?')->execute([$key]);
    } catch (Exception $e) {}
}

// ============================================================
// Sales Analytics
// ============================================================

/**
 * Get revenue over time (daily buckets).
 */
function getRevenueTimeSeries(string $startDate, string $endDate): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT DATE(created_at) as day,
               COALESCE(SUM(total), 0) as revenue,
               COUNT(*) as order_count
        FROM orders
        WHERE payment_status = 'completed'
          AND deleted_at IS NULL
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY day
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get order count by status.
 */
function getOrdersByStatus(): array {
    $db = getDB();
    return $db->query("
        SELECT order_status, COUNT(*) as count
        FROM orders WHERE deleted_at IS NULL
        GROUP BY order_status
        ORDER BY count DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get revenue by payment method.
 */
function getRevenueByPaymentMethod(string $startDate, string $endDate): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COALESCE(payment_method, 'unknown') as method,
               SUM(total) as revenue,
               COUNT(*) as order_count
        FROM orders
        WHERE payment_status = 'completed'
          AND deleted_at IS NULL
          AND DATE(created_at) BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY revenue DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get revenue by category.
 */
function getRevenueByCategory(string $startDate, string $endDate): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT COALESCE(pc.name, 'Uncategorized') as category_name,
               SUM(oi.total_price) as revenue,
               SUM(oi.quantity) as qty
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        LEFT JOIN products p ON oi.product_id = p.id
        LEFT JOIN product_categories pc ON p.category_id = pc.id
        WHERE o.payment_status = 'completed'
          AND o.deleted_at IS NULL
          AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY pc.name
        ORDER BY revenue DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get top selling products.
 */
function getTopProducts(string $startDate, string $endDate, int $limit = 10): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT oi.product_name,
               SUM(oi.quantity) as total_qty,
               SUM(oi.total_price) as total_revenue
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE o.payment_status = 'completed'
          AND o.deleted_at IS NULL
          AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY oi.product_name
        ORDER BY total_revenue DESC
        LIMIT " . (int)$limit . "
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get coupon usage stats.
 */
function getCouponUsageStats(string $startDate, string $endDate): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT c.code,
               COUNT(o.id) as times_used,
               SUM(o.discount_amount) as total_discount,
               SUM(o.total) as total_order_value
        FROM orders o
        JOIN coupons c ON o.coupon_id = c.id
        WHERE o.payment_status = 'completed'
          AND o.deleted_at IS NULL
          AND DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY c.code
        ORDER BY times_used DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get summary KPIs for a date range.
 */
function getSalesKPIs(string $startDate, string $endDate): array {
    $db = getDB();

    $stmt = $db->prepare("
        SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(total), 0) as total_revenue,
            COALESCE(AVG(total), 0) as avg_order_value,
            COALESCE(SUM(discount_amount), 0) as total_discounts,
            COALESCE(SUM(tax), 0) as total_tax
        FROM orders
        WHERE payment_status = 'completed'
          AND deleted_at IS NULL
          AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $kpis = $stmt->fetch(PDO::FETCH_ASSOC);

    // Cancelled/refunded
    $stmt = $db->prepare("
        SELECT COUNT(*) as cancelled_count
        FROM orders
        WHERE deleted_at IS NULL
          AND order_status = 'cancelled'
          AND DATE(created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate, $endDate]);
    $kpis['cancelled_orders'] = $stmt->fetchColumn();

    return $kpis;
}

// ============================================================
// Customer Analytics
// ============================================================

/**
 * Get customer growth over time.
 */
function getCustomerGrowth(string $startDate, string $endDate): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT DATE(created_at) as day, COUNT(*) as new_customers
        FROM customers
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY day
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get top customers by revenue.
 */
function getTopCustomers(int $limit = 10): array {
    $db = getDB();
    return $db->query("
        SELECT c.id, c.first_name, c.last_name, c.email,
               COUNT(o.id) as order_count,
               COALESCE(SUM(o.total), 0) as total_spent
        FROM customers c
        LEFT JOIN orders o ON o.customer_id = c.id AND o.payment_status = 'completed' AND o.deleted_at IS NULL
        GROUP BY c.id
        HAVING order_count > 0
        ORDER BY total_spent DESC
        LIMIT " . (int)$limit . "
    ")->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get new vs returning customer order breakdown.
 */
function getNewVsReturning(string $startDate, string $endDate): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            SUM(CASE WHEN sub.prev_orders = 0 THEN 1 ELSE 0 END) as new_customers,
            SUM(CASE WHEN sub.prev_orders > 0 THEN 1 ELSE 0 END) as returning_customers
        FROM (
            SELECT o.id,
                   (SELECT COUNT(*) FROM orders o2
                    WHERE o2.customer_email = o.customer_email
                      AND o2.id < o.id
                      AND o2.payment_status = 'completed'
                      AND o2.deleted_at IS NULL) as prev_orders
            FROM orders o
            WHERE o.payment_status = 'completed'
              AND o.deleted_at IS NULL
              AND DATE(o.created_at) BETWEEN ? AND ?
        ) sub
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['new_customers' => 0, 'returning_customers' => 0];
}

// ============================================================
// Traffic Analytics
// ============================================================

/**
 * Get page view stats.
 */
function getPageViewStats(string $startDate, string $endDate): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT page_path,
               COUNT(*) as views,
               COUNT(DISTINCT session_id) as unique_sessions
        FROM page_views
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY page_path
        ORDER BY views DESC
        LIMIT 20
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get daily traffic counts.
 */
function getDailyTraffic(string $startDate, string $endDate): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT DATE(created_at) as day,
               COUNT(*) as views,
               COUNT(DISTINCT session_id) as unique_visitors
        FROM page_views
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY day
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get top referrers.
 */
function getTopReferrers(string $startDate, string $endDate, int $limit = 10): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT
            CASE
                WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1)
            END as source,
            COUNT(*) as visits
        FROM page_views
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY source
        ORDER BY visits DESC
        LIMIT " . (int)$limit . "
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================================
// Inventory Analytics
// ============================================================

/**
 * Get inventory summary stats.
 */
function getInventoryStats(): array {
    $db = getDB();

    $totalTracked = $db->query("SELECT COUNT(*) FROM products WHERE track_inventory = 1 AND deleted_at IS NULL")->fetchColumn();
    $outOfStock = $db->query("SELECT COUNT(*) FROM products WHERE track_inventory = 1 AND stock_quantity <= 0 AND deleted_at IS NULL")->fetchColumn();
    $lowStock = $db->query("SELECT COUNT(*) FROM products WHERE track_inventory = 1 AND stock_quantity > 0 AND stock_quantity <= low_stock_threshold AND deleted_at IS NULL")->fetchColumn();
    $totalStockValue = $db->query("SELECT COALESCE(SUM(CAST(price AS DECIMAL(10,2)) * stock_quantity), 0) FROM products WHERE track_inventory = 1 AND deleted_at IS NULL AND stock_quantity > 0")->fetchColumn();
    $neverOrdered = $db->query("SELECT COUNT(*) FROM products p WHERE p.deleted_at IS NULL AND NOT EXISTS (SELECT 1 FROM order_items oi WHERE oi.product_id = p.id)")->fetchColumn();

    return [
        'total_tracked' => $totalTracked,
        'out_of_stock' => $outOfStock,
        'low_stock' => $lowStock,
        'total_stock_value' => $totalStockValue,
        'never_ordered' => $neverOrdered,
    ];
}

// ============================================================
// CSV Export
// ============================================================

/**
 * Generate CSV from array data.
 */
function generateCSV(array $data, array $headers): string {
    $output = fopen('php://temp', 'r+');
    fputcsv($output, $headers);
    foreach ($data as $row) {
        fputcsv($output, array_values($row));
    }
    rewind($output);
    $csv = stream_get_contents($output);
    fclose($output);
    return $csv;
}

/**
 * Send CSV as download.
 */
function downloadCSV(string $csv, string $filename): void {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($csv));
    echo $csv;
    exit;
}
