<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Handle logout (POST only, CSRF protected)
if (isset($_POST['logout']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    logout();
    redirect('admin/login.php');
}

$db = getDB();
$unread = getUnreadCount();

$_catalogEnabled = isFeatureEnabled('catalog');
$_cartEnabled = isFeatureEnabled('cart');
$_orderInquiryEnabled = isFeatureEnabled('order_inquiry');
$_contactFormEnabled = isFeatureEnabled('contact_form');

// Stats
$totalProducts = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalCategories = $db->query('SELECT COUNT(*) FROM product_categories')->fetchColumn();
$totalPages = $db->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$totalMedia = $db->query('SELECT COUNT(*) FROM media')->fetchColumn();
$totalOrders = $db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders = $db->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending','processing')")->fetchColumn();
$recentContacts = $_contactFormEnabled ? $db->query('SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 5')->fetchAll() : [];
$recentOrders = $_orderInquiryEnabled ? $db->query('SELECT * FROM order_inquiries ORDER BY created_at DESC LIMIT 5')->fetchAll() : [];
$recentPurchases = $_cartEnabled ? $db->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 5')->fetchAll() : [];
$lowStockProducts = getLowStockProducts();

// Store type info for display
$storeTypeLabels = [
    'products_and_services' => 'Products & Services',
    'products_only' => 'Products Only',
    'services_only' => 'Services Only',
    'digital_only' => 'Digital Products',
    'informational' => 'Informational',
];
$storeType = getStoreType();
$storeTypeLabel = $storeTypeLabels[$storeType] ?? ucfirst($storeType);

// Sales Analytics
$today = date('Y-m-d');
$thirtyDaysAgo = date('Y-m-d', strtotime('-30 days'));
$sevenDaysAgo = date('Y-m-d', strtotime('-7 days'));

// Revenue stats
$todayRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'completed' AND date(created_at) = '$today'")->fetchColumn();
$weekRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'completed' AND date(created_at) >= '$sevenDaysAgo'")->fetchColumn();
$monthRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'completed' AND date(created_at) >= '$thirtyDaysAgo'")->fetchColumn();
$totalRevenue = $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE payment_status = 'completed'")->fetchColumn();
$avgOrderValue = $db->query("SELECT COALESCE(AVG(total), 0) FROM orders WHERE payment_status = 'completed'")->fetchColumn();

// Daily revenue for chart (last 30 days)
$dailyRevenue = $db->query("SELECT date(created_at) as day, COALESCE(SUM(total), 0) as revenue, COUNT(*) as orders FROM orders WHERE payment_status = 'completed' AND date(created_at) >= '$thirtyDaysAgo' GROUP BY date(created_at) ORDER BY day")->fetchAll();

// Top selling products
$topProducts = $db->query("SELECT oi.product_name, SUM(oi.quantity) as total_qty, SUM(oi.total_price) as total_revenue FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.payment_status = 'completed' GROUP BY oi.product_name ORDER BY total_revenue DESC LIMIT 5")->fetchAll();

// Total customers
$totalCustomers = $db->query('SELECT COUNT(*) FROM customers')->fetchColumn();
$newCustomersMonth = $db->query("SELECT COUNT(*) FROM customers WHERE date(created_at) >= '$thirtyDaysAgo'")->fetchColumn();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?php echo e(getCurrentUsername()); ?>! <span style="color:var(--color-gray-400);">Store mode: <?php echo e($storeTypeLabel); ?></span></p>
</div>

<!-- Stats Cards -->
<div class="stats-cards">
    <?php if ($_contactFormEnabled): ?>
    <div class="stat-card">
        <div class="stat-icon bg-primary"><i class="fas fa-inbox"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $unread['contacts']; ?></div>
            <div class="stat-label">Unread Contacts</div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($_orderInquiryEnabled): ?>
    <div class="stat-card">
        <div class="stat-icon bg-warning"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $unread['orders']; ?></div>
            <div class="stat-label">Unread Inquiries</div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($_catalogEnabled): ?>
    <div class="stat-card">
        <div class="stat-icon bg-success"><i class="fas fa-box"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $totalProducts; ?></div>
            <div class="stat-label">Products</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-info"><i class="fas fa-tags"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $totalCategories; ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>
    <?php endif; ?>
    <?php if ($_cartEnabled): ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:#6366f1;color:#fff;"><i class="fas fa-shopping-bag"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $pendingOrders; ?> / <?php echo $totalOrders; ?></div>
            <div class="stat-label">Active / Total Orders</div>
        </div>
    </div>
    <?php endif; ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:#10b981;color:#fff;"><i class="fas fa-file-alt"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $totalPages; ?></div>
            <div class="stat-label">Pages</div>
        </div>
    </div>
    <?php if (isFeatureEnabled('blog')): ?>
    <?php $blogStats = getBlogStats(); ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:#8b5cf6;color:#fff;"><i class="fas fa-blog"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $blogStats['published_posts']; ?> / <?php echo $blogStats['total_posts']; ?></div>
            <div class="stat-label">Published / Total Posts</div>
        </div>
    </div>
    <?php if ($blogStats['pending_comments'] > 0): ?>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f59e0b;color:#fff;"><i class="fas fa-comments"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $blogStats['pending_comments']; ?></div>
            <div class="stat-label">Pending Comments</div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Low Stock Alert -->
<?php if (!empty($lowStockProducts)): ?>
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i> Low Stock Alert</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Stock</th>
                    <th>Threshold</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lowStockProducts as $lsp): ?>
                <tr>
                    <td><strong><?php echo e($lsp['name']); ?></strong></td>
                    <td><?php echo e($lsp['category_name']); ?></td>
                    <td><span style="color: <?php echo $lsp['stock_quantity'] == 0 ? '#ef4444' : '#f59e0b'; ?>; font-weight: bold;"><?php echo $lsp['stock_quantity']; ?></span></td>
                    <td><?php echo $lsp['low_stock_threshold']; ?></td>
                    <td><a href="<?php echo url('admin/product-edit.php?id=' . $lsp['id']); ?>" class="btn-admin btn-sm btn-edit"><i class="fas fa-edit"></i> Restock</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Revenue Stats -->
<?php if ($_cartEnabled): ?>
<div class="revenue-stats">
    <div class="revenue-stat-card">
        <div class="stat-value"><?php echo formatCurrency($todayRevenue); ?></div>
        <div class="stat-label">Today's Revenue</div>
    </div>
    <div class="revenue-stat-card">
        <div class="stat-value"><?php echo formatCurrency($weekRevenue); ?></div>
        <div class="stat-label">This Week</div>
    </div>
    <div class="revenue-stat-card">
        <div class="stat-value"><?php echo formatCurrency($monthRevenue); ?></div>
        <div class="stat-label">This Month</div>
    </div>
    <div class="revenue-stat-card">
        <div class="stat-value"><?php echo formatCurrency($totalRevenue); ?></div>
        <div class="stat-label">All Time</div>
    </div>
</div>
<?php endif; ?>

<!-- Revenue Chart (Last 30 Days) -->
<?php if ($_cartEnabled && !empty($dailyRevenue)): ?>
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-chart-bar"></i> Revenue — Last 30 Days</h2>
    <div class="admin-card" style="margin-top: var(--space-md); overflow-x: auto;">
        <div class="revenue-chart">
            <?php
            $maxRevenue = max(array_column($dailyRevenue, 'revenue'));
            if ($maxRevenue == 0) $maxRevenue = 1;
            foreach ($dailyRevenue as $day):
                $pct = ($day['revenue'] / $maxRevenue) * 100;
            ?>
                <div class="chart-bar-container" title="<?php echo e($day['day'] . ': ' . formatCurrency($day['revenue']) . ' (' . $day['orders'] . ' orders)'); ?>">
                    <div class="chart-bar" style="height: <?php echo max(2, $pct); ?>%;"></div>
                    <span class="chart-label"><?php echo date('j', strtotime($day['day'])); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="text-align: center; margin-top: var(--space-sm); color: var(--color-gray-500); font-size: var(--text-sm);">
            Average Order Value: <strong><?php echo formatCurrency($avgOrderValue); ?></strong> &middot;
            Total Customers: <strong><?php echo $totalCustomers; ?></strong>
            <?php if ($newCustomersMonth > 0): ?> (<?php echo $newCustomersMonth; ?> new this month)<?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Top Selling Products -->
<?php if (!empty($topProducts)): ?>
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-trophy"></i> Top Selling Products</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
            <tbody>
                <?php foreach ($topProducts as $tp): ?>
                <tr>
                    <td><strong><?php echo e($tp['product_name']); ?></strong></td>
                    <td><?php echo $tp['total_qty']; ?></td>
                    <td><?php echo formatCurrency($tp['total_revenue']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Quick Actions -->
<div class="admin-section">
    <h2>Quick Actions</h2>
    <div class="quick-actions">
        <?php if ($_catalogEnabled): ?>
            <a href="<?php echo url('admin/product-edit.php'); ?>" class="quick-action"><i class="fas fa-plus"></i> Add Product</a>
            <a href="<?php echo url('admin/category-edit.php'); ?>" class="quick-action"><i class="fas fa-plus"></i> Add Category</a>
        <?php endif; ?>
        <?php if ($_cartEnabled): ?>
            <a href="<?php echo url('admin/orders.php'); ?>" class="quick-action"><i class="fas fa-shopping-bag"></i> View Orders</a>
        <?php endif; ?>
        <a href="<?php echo url('admin/page-edit.php'); ?>" class="quick-action"><i class="fas fa-plus"></i> Add Page</a>
        <a href="<?php echo url('admin/settings.php'); ?>" class="quick-action"><i class="fas fa-cog"></i> Site Settings</a>
        <a href="<?php echo url('/'); ?>" target="_blank" class="quick-action"><i class="fas fa-eye"></i> View Website</a>
    </div>
</div>

<!-- Recent Inquiries -->
<?php if ($_contactFormEnabled || $_orderInquiryEnabled): ?>
<div class="admin-grid-2">
    <?php if ($_contactFormEnabled): ?>
    <div class="admin-section">
        <div class="section-head">
            <h2>Recent Contact Messages</h2>
            <a href="<?php echo url('admin/inquiries.php?type=contacts'); ?>" class="btn-link">View All</a>
        </div>
        <?php if (empty($recentContacts)): ?>
            <p class="empty-state">No contact submissions yet.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentContacts as $c): ?>
                            <tr>
                                <td><strong><?php echo e($c['name']); ?></strong></td>
                                <td><?php echo e($c['email']); ?></td>
                                <td><?php echo formatDate($c['created_at']); ?></td>
                                <td>
                                    <?php if ($c['is_read']): ?>
                                        <span class="badge-status badge-read">Read</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-unread">New</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($_orderInquiryEnabled): ?>
    <div class="admin-section">
        <div class="section-head">
            <h2>Recent Order Inquiries</h2>
            <a href="<?php echo url('admin/inquiries.php?type=orders'); ?>" class="btn-link">View All</a>
        </div>
        <?php if (empty($recentOrders)): ?>
            <p class="empty-state">No order inquiries yet.</p>
        <?php else: ?>
            <div class="admin-table-wrap">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Service</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $o): ?>
                            <tr>
                                <td><strong><?php echo e($o['name']); ?></strong></td>
                                <td><?php echo e(ucfirst($o['service_type'])); ?></td>
                                <td><?php echo formatDate($o['created_at']); ?></td>
                                <td>
                                    <?php if ($o['is_read']): ?>
                                        <span class="badge-status badge-read">Read</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-unread">New</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Recent Purchases (from cart checkout) -->
<?php if ($_cartEnabled): ?>
<div class="admin-section">
    <div class="section-head">
        <h2>Recent Orders (Purchases)</h2>
        <a href="<?php echo url('admin/orders.php'); ?>" class="btn-link">View All</a>
    </div>
    <?php if (empty($recentPurchases)): ?>
        <p class="empty-state">No orders yet. Orders placed through the shopping cart will appear here.</p>
    <?php else: ?>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentPurchases as $p): ?>
                        <tr>
                            <td><strong><a href="<?php echo url('admin/order-view.php?id=' . $p['id']); ?>"><?php echo e($p['order_number']); ?></a></strong></td>
                            <td><?php echo e($p['customer_name']); ?></td>
                            <td><?php echo formatCurrency($p['total']); ?></td>
                            <td><?php echo e(ucfirst($p['payment_method'] ?: 'pending')); ?></td>
                            <td>
                                <span class="badge-status badge-<?php echo $p['order_status'] === 'completed' ? 'active' : ($p['order_status'] === 'cancelled' ? 'inactive' : 'unread'); ?>">
                                    <?php echo e(ucfirst($p['order_status'])); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($p['created_at']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
