<?php
/**
 * Admin — Advanced Analytics & Reporting Dashboard
 *
 * Provides sales, customer, traffic, and inventory reports
 * with Chart.js visualizations and CSV export.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/analytics.php';

requireLogin();
requirePermission('view_analytics');

$db = getDB();

// Date range from query params
$period = $_GET['period'] ?? '30d';
$periodMap = [
    '7d'  => 7,
    '30d' => 30,
    '90d' => 90,
    '12m' => 365,
    'all' => null,
];
$days = $periodMap[$period] ?? 30;

$endDate = date('Y-m-d');
$startDate = $days ? date('Y-m-d', strtotime("-{$days} days")) : '2000-01-01';

// Handle CSV export
if (isset($_GET['export'])) {
    $exportType = $_GET['export'];
    switch ($exportType) {
        case 'revenue':
            $data = getRevenueTimeSeries($startDate, $endDate);
            downloadCSV(generateCSV($data, ['Date', 'Revenue', 'Orders']), 'revenue-report.csv');
            break;
        case 'products':
            $data = getTopProducts($startDate, $endDate, 50);
            downloadCSV(generateCSV($data, ['Product', 'Qty Sold', 'Revenue']), 'top-products.csv');
            break;
        case 'customers':
            $data = getTopCustomers(50);
            downloadCSV(generateCSV($data, ['ID', 'First Name', 'Last Name', 'Email', 'Orders', 'Total Spent']), 'top-customers.csv');
            break;
        case 'traffic':
            $data = getPageViewStats($startDate, $endDate);
            downloadCSV(generateCSV($data, ['Page', 'Views', 'Unique Visitors']), 'traffic-report.csv');
            break;
    }
}

// Gather data
$kpis = getSalesKPIs($startDate, $endDate);
$revenueTimeSeries = getRevenueTimeSeries($startDate, $endDate);
$ordersByStatus = getOrdersByStatus();
$revenueByMethod = getRevenueByPaymentMethod($startDate, $endDate);
$revenueByCategory = getRevenueByCategory($startDate, $endDate);
$topProducts = getTopProducts($startDate, $endDate, 10);
$couponStats = getCouponUsageStats($startDate, $endDate);
$newVsReturning = getNewVsReturning($startDate, $endDate);
$topCustomers = getTopCustomers(10);
$inventoryStats = getInventoryStats();

// Traffic data (may not have table yet)
$pageViews = [];
$dailyTraffic = [];
$topReferrers = [];
try {
    $pageViews = getPageViewStats($startDate, $endDate);
    $dailyTraffic = getDailyTraffic($startDate, $endDate);
    $topReferrers = getTopReferrers($startDate, $endDate);
} catch (Exception $e) {}

// Compare to previous period for KPI deltas
$prevEndDate = date('Y-m-d', strtotime($startDate . ' -1 day'));
$prevStartDate = $days ? date('Y-m-d', strtotime("-" . ($days * 2) . " days")) : '2000-01-01';
$prevKpis = getSalesKPIs($prevStartDate, $prevEndDate);

function kpiDelta(float $current, float $previous): string {
    if ($previous == 0) return $current > 0 ? '+100%' : '0%';
    $pct = (($current - $previous) / $previous) * 100;
    $sign = $pct >= 0 ? '+' : '';
    return $sign . round($pct, 1) . '%';
}

function deltaClass(float $current, float $previous): string {
    if ($current > $previous) return 'kpi-up';
    if ($current < $previous) return 'kpi-down';
    return 'kpi-neutral';
}

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-chart-line"></i> Analytics & Reports</h1>
    <div class="analytics-period-selector">
        <?php foreach (['7d' => '7 Days', '30d' => '30 Days', '90d' => '90 Days', '12m' => '12 Months', 'all' => 'All Time'] as $key => $label): ?>
            <a href="<?php echo url('admin/analytics.php?period=' . $key); ?>" class="btn-admin btn-sm <?php echo $period === $key ? 'btn-save' : 'btn-outline'; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<!-- KPI Cards -->
<div class="analytics-kpis">
    <div class="kpi-card">
        <div class="kpi-value"><?php echo formatCurrency($kpis['total_revenue']); ?></div>
        <div class="kpi-label">Total Revenue</div>
        <div class="kpi-delta <?php echo deltaClass($kpis['total_revenue'], $prevKpis['total_revenue']); ?>">
            <?php echo kpiDelta($kpis['total_revenue'], $prevKpis['total_revenue']); ?> vs prev period
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value"><?php echo number_format($kpis['total_orders']); ?></div>
        <div class="kpi-label">Orders</div>
        <div class="kpi-delta <?php echo deltaClass($kpis['total_orders'], $prevKpis['total_orders']); ?>">
            <?php echo kpiDelta($kpis['total_orders'], $prevKpis['total_orders']); ?> vs prev period
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value"><?php echo formatCurrency($kpis['avg_order_value']); ?></div>
        <div class="kpi-label">Avg Order Value</div>
        <div class="kpi-delta <?php echo deltaClass($kpis['avg_order_value'], $prevKpis['avg_order_value']); ?>">
            <?php echo kpiDelta($kpis['avg_order_value'], $prevKpis['avg_order_value']); ?> vs prev period
        </div>
    </div>
    <div class="kpi-card">
        <div class="kpi-value"><?php echo number_format($kpis['cancelled_orders']); ?></div>
        <div class="kpi-label">Cancelled</div>
        <div class="kpi-delta <?php echo deltaClass($prevKpis['cancelled_orders'], $kpis['cancelled_orders']); ?>">
            <?php echo kpiDelta($kpis['cancelled_orders'], $prevKpis['cancelled_orders']); ?>
        </div>
    </div>
</div>

<!-- Revenue Chart -->
<div class="analytics-row">
    <div class="analytics-card analytics-card--wide">
        <div class="analytics-card-header">
            <h3><i class="fas fa-chart-area"></i> Revenue Over Time</h3>
            <a href="<?php echo url('admin/analytics.php?period=' . e($period) . '&export=revenue'); ?>" class="btn-admin btn-sm btn-outline"><i class="fas fa-download"></i> CSV</a>
        </div>
        <canvas id="revenueChart" height="80"></canvas>
    </div>
</div>

<!-- Orders by Status + Revenue by Payment Method -->
<div class="analytics-row">
    <div class="analytics-card">
        <div class="analytics-card-header"><h3><i class="fas fa-tasks"></i> Orders by Status</h3></div>
        <canvas id="orderStatusChart" height="200"></canvas>
    </div>
    <div class="analytics-card">
        <div class="analytics-card-header"><h3><i class="fas fa-credit-card"></i> Revenue by Payment Method</h3></div>
        <canvas id="paymentMethodChart" height="200"></canvas>
    </div>
</div>

<!-- Revenue by Category + New vs Returning -->
<div class="analytics-row">
    <div class="analytics-card">
        <div class="analytics-card-header"><h3><i class="fas fa-tags"></i> Revenue by Category</h3></div>
        <canvas id="categoryChart" height="200"></canvas>
    </div>
    <div class="analytics-card">
        <div class="analytics-card-header"><h3><i class="fas fa-users"></i> New vs Returning Customers</h3></div>
        <canvas id="customerTypeChart" height="200"></canvas>
        <div style="text-align:center; margin-top: 1rem; font-size: 0.9rem; color: var(--color-gray-500);">
            New: <?php echo (int)$newVsReturning['new_customers']; ?> &middot;
            Returning: <?php echo (int)$newVsReturning['returning_customers']; ?>
        </div>
    </div>
</div>

<!-- Top Products -->
<div class="analytics-row">
    <div class="analytics-card analytics-card--wide">
        <div class="analytics-card-header">
            <h3><i class="fas fa-trophy"></i> Top Products</h3>
            <a href="<?php echo url('admin/analytics.php?period=' . e($period) . '&export=products'); ?>" class="btn-admin btn-sm btn-outline"><i class="fas fa-download"></i> CSV</a>
        </div>
        <table class="admin-table">
            <thead><tr><th>#</th><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
            <tbody>
                <?php foreach ($topProducts as $i => $tp): ?>
                <tr>
                    <td><?php echo $i + 1; ?></td>
                    <td><strong><?php echo e($tp['product_name']); ?></strong></td>
                    <td><?php echo number_format($tp['total_qty']); ?></td>
                    <td><?php echo formatCurrency($tp['total_revenue']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($topProducts)): ?>
                <tr><td colspan="4" style="text-align:center; color:var(--color-gray-400);">No sales data for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Top Customers + Coupon Usage -->
<div class="analytics-row">
    <div class="analytics-card">
        <div class="analytics-card-header">
            <h3><i class="fas fa-user-crown"></i> Top Customers</h3>
            <a href="<?php echo url('admin/analytics.php?period=' . e($period) . '&export=customers'); ?>" class="btn-admin btn-sm btn-outline"><i class="fas fa-download"></i> CSV</a>
        </div>
        <table class="admin-table">
            <thead><tr><th>Customer</th><th>Orders</th><th>Spent</th></tr></thead>
            <tbody>
                <?php foreach ($topCustomers as $tc): ?>
                <tr>
                    <td><strong><?php echo e($tc['first_name'] . ' ' . $tc['last_name']); ?></strong><br><small><?php echo e($tc['email']); ?></small></td>
                    <td><?php echo $tc['order_count']; ?></td>
                    <td><?php echo formatCurrency($tc['total_spent']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($topCustomers)): ?>
                <tr><td colspan="3" style="text-align:center; color:var(--color-gray-400);">No customer data.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="analytics-card">
        <div class="analytics-card-header"><h3><i class="fas fa-ticket-alt"></i> Coupon Usage</h3></div>
        <table class="admin-table">
            <thead><tr><th>Code</th><th>Used</th><th>Discount</th></tr></thead>
            <tbody>
                <?php foreach ($couponStats as $cs): ?>
                <tr>
                    <td><code><?php echo e($cs['code']); ?></code></td>
                    <td><?php echo $cs['times_used']; ?>x</td>
                    <td><?php echo formatCurrency($cs['total_discount']); ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($couponStats)): ?>
                <tr><td colspan="3" style="text-align:center; color:var(--color-gray-400);">No coupons used.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Inventory Summary -->
<div class="analytics-row">
    <div class="analytics-card analytics-card--wide">
        <div class="analytics-card-header"><h3><i class="fas fa-warehouse"></i> Inventory Summary</h3></div>
        <div class="analytics-kpis" style="margin:0;">
            <div class="kpi-card kpi-card--sm">
                <div class="kpi-value"><?php echo $inventoryStats['total_tracked']; ?></div>
                <div class="kpi-label">Tracked Products</div>
            </div>
            <div class="kpi-card kpi-card--sm">
                <div class="kpi-value" style="color: var(--color-error);"><?php echo $inventoryStats['out_of_stock']; ?></div>
                <div class="kpi-label">Out of Stock</div>
            </div>
            <div class="kpi-card kpi-card--sm">
                <div class="kpi-value" style="color: var(--color-warning);"><?php echo $inventoryStats['low_stock']; ?></div>
                <div class="kpi-label">Low Stock</div>
            </div>
            <div class="kpi-card kpi-card--sm">
                <div class="kpi-value"><?php echo formatCurrency($inventoryStats['total_stock_value']); ?></div>
                <div class="kpi-label">Stock Value</div>
            </div>
            <div class="kpi-card kpi-card--sm">
                <div class="kpi-value"><?php echo $inventoryStats['never_ordered']; ?></div>
                <div class="kpi-label">Never Ordered</div>
            </div>
        </div>
    </div>
</div>

<!-- Traffic Analytics -->
<?php if (!empty($dailyTraffic) || !empty($pageViews)): ?>
<div class="analytics-row">
    <div class="analytics-card analytics-card--wide">
        <div class="analytics-card-header">
            <h3><i class="fas fa-eye"></i> Site Traffic</h3>
            <a href="<?php echo url('admin/analytics.php?period=' . e($period) . '&export=traffic'); ?>" class="btn-admin btn-sm btn-outline"><i class="fas fa-download"></i> CSV</a>
        </div>
        <canvas id="trafficChart" height="80"></canvas>
    </div>
</div>

<div class="analytics-row">
    <div class="analytics-card">
        <div class="analytics-card-header"><h3><i class="fas fa-file"></i> Top Pages</h3></div>
        <table class="admin-table">
            <thead><tr><th>Page</th><th>Views</th><th>Unique</th></tr></thead>
            <tbody>
                <?php foreach (array_slice($pageViews, 0, 10) as $pv): ?>
                <tr>
                    <td><?php echo e($pv['page_path']); ?></td>
                    <td><?php echo number_format($pv['views']); ?></td>
                    <td><?php echo number_format($pv['unique_sessions']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="analytics-card">
        <div class="analytics-card-header"><h3><i class="fas fa-share-alt"></i> Top Referrers</h3></div>
        <table class="admin-table">
            <thead><tr><th>Source</th><th>Visits</th></tr></thead>
            <tbody>
                <?php foreach ($topReferrers as $ref): ?>
                <tr>
                    <td><?php echo e($ref['source']); ?></td>
                    <td><?php echo number_format($ref['visits']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Chart.js from CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
(function() {
    var chartColors = ['#2563EB', '#F59E0B', '#10B981', '#8B5CF6', '#EF4444', '#06B6D4', '#F97316', '#EC4899'];

    // Revenue time series
    var revenueData = <?php echo json_encode($revenueTimeSeries); ?>;
    if (revenueData.length > 0) {
        new Chart(document.getElementById('revenueChart'), {
            type: 'line',
            data: {
                labels: revenueData.map(function(d) { return d.day; }),
                datasets: [{
                    label: 'Revenue',
                    data: revenueData.map(function(d) { return parseFloat(d.revenue); }),
                    borderColor: '#2563EB',
                    backgroundColor: 'rgba(37,99,235,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2
                }, {
                    label: 'Orders',
                    data: revenueData.map(function(d) { return d.order_count; }),
                    borderColor: '#F59E0B',
                    backgroundColor: 'transparent',
                    yAxisID: 'y1',
                    tension: 0.3,
                    pointRadius: 2
                }]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                scales: {
                    y: { beginAtZero: true, position: 'left', title: { display: true, text: 'Revenue' } },
                    y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Orders' } }
                }
            }
        });
    }

    // Orders by status (doughnut)
    var statusData = <?php echo json_encode($ordersByStatus); ?>;
    if (statusData.length > 0) {
        new Chart(document.getElementById('orderStatusChart'), {
            type: 'doughnut',
            data: {
                labels: statusData.map(function(d) { return d.order_status; }),
                datasets: [{ data: statusData.map(function(d) { return d.count; }), backgroundColor: chartColors }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Revenue by payment method (pie)
    var methodData = <?php echo json_encode($revenueByMethod); ?>;
    if (methodData.length > 0) {
        new Chart(document.getElementById('paymentMethodChart'), {
            type: 'pie',
            data: {
                labels: methodData.map(function(d) { return d.method; }),
                datasets: [{ data: methodData.map(function(d) { return parseFloat(d.revenue); }), backgroundColor: chartColors }]
            },
            options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
        });
    }

    // Revenue by category (bar)
    var categoryData = <?php echo json_encode($revenueByCategory); ?>;
    if (categoryData.length > 0) {
        new Chart(document.getElementById('categoryChart'), {
            type: 'bar',
            data: {
                labels: categoryData.map(function(d) { return d.category_name; }),
                datasets: [{ label: 'Revenue', data: categoryData.map(function(d) { return parseFloat(d.revenue); }), backgroundColor: chartColors }]
            },
            options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });
    }

    // New vs returning (doughnut)
    var nvrData = <?php echo json_encode($newVsReturning); ?>;
    new Chart(document.getElementById('customerTypeChart'), {
        type: 'doughnut',
        data: {
            labels: ['New Customers', 'Returning Customers'],
            datasets: [{ data: [parseInt(nvrData.new_customers) || 0, parseInt(nvrData.returning_customers) || 0], backgroundColor: ['#2563EB', '#10B981'] }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    // Traffic chart
    var trafficData = <?php echo json_encode($dailyTraffic); ?>;
    var trafficCanvas = document.getElementById('trafficChart');
    if (trafficCanvas && trafficData.length > 0) {
        new Chart(trafficCanvas, {
            type: 'line',
            data: {
                labels: trafficData.map(function(d) { return d.day; }),
                datasets: [{
                    label: 'Page Views',
                    data: trafficData.map(function(d) { return d.views; }),
                    borderColor: '#8B5CF6',
                    backgroundColor: 'rgba(139,92,246,0.1)',
                    fill: true,
                    tension: 0.3,
                    pointRadius: 2
                }, {
                    label: 'Unique Visitors',
                    data: trafficData.map(function(d) { return d.unique_visitors; }),
                    borderColor: '#06B6D4',
                    backgroundColor: 'transparent',
                    tension: 0.3,
                    pointRadius: 2
                }]
            },
            options: { responsive: true, scales: { y: { beginAtZero: true } } }
        });
    }
})();
</script>

<style>
.analytics-period-selector { display: flex; gap: 0.35rem; flex-wrap: wrap; }
.analytics-kpis { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1.5rem; }
.kpi-card { background: var(--color-white); border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
.kpi-card--sm { padding: 0.75rem; text-align: center; }
.kpi-value { font-size: 1.6rem; font-weight: 700; color: var(--color-dark); line-height: 1.2; }
.kpi-card--sm .kpi-value { font-size: 1.3rem; }
.kpi-label { font-size: 0.85rem; color: var(--color-gray-500); margin-top: 0.25rem; }
.kpi-delta { font-size: 0.8rem; margin-top: 0.4rem; }
.kpi-up { color: var(--color-success); }
.kpi-down { color: var(--color-error); }
.kpi-neutral { color: var(--color-gray-400); }

.analytics-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 1.5rem; }
.analytics-card { background: var(--color-white); border-radius: 8px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden; }
.analytics-card--wide { grid-column: 1 / -1; }
.analytics-card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; }
.analytics-card-header h3 { margin: 0; font-size: 1rem; }

@media (max-width: 768px) {
    .analytics-row { grid-template-columns: 1fr; }
    .analytics-kpis { grid-template-columns: repeat(2, 1fr); }
}
</style>

<?php require_once __DIR__ . '/footer.php'; ?>
