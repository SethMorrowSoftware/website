<?php
/**
 * Admin Dashboard
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Handle logout
if (isset($_POST['logout']) || isset($_GET['logout'])) {
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
</div>

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
