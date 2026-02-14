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

// Stats
$totalProducts = $db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalContainers = $db->query('SELECT COUNT(*) FROM containers')->fetchColumn();
$totalPages = $db->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$totalMedia = $db->query('SELECT COUNT(*) FROM media')->fetchColumn();
$recentContacts = $db->query('SELECT * FROM contact_submissions ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recentOrders = $db->query('SELECT * FROM order_inquiries ORDER BY created_at DESC LIMIT 5')->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?php echo e(getCurrentUsername()); ?>!</p>
</div>

<!-- Stats Cards -->
<div class="stats-cards">
    <div class="stat-card">
        <div class="stat-icon bg-primary"><i class="fas fa-inbox"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $unread['contacts']; ?></div>
            <div class="stat-label">Unread Contacts</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-warning"><i class="fas fa-clipboard-list"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $unread['orders']; ?></div>
            <div class="stat-label">Unread Orders</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success"><i class="fas fa-box"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $totalProducts; ?></div>
            <div class="stat-label">Products</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-info"><i class="fas fa-dumpster"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $totalContainers; ?></div>
            <div class="stat-label">Containers</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="admin-section">
    <h2>Quick Actions</h2>
    <div class="quick-actions">
        <a href="<?php echo url('admin/product-edit.php'); ?>" class="quick-action"><i class="fas fa-plus"></i> Add Product</a>
        <a href="<?php echo url('admin/container-edit.php'); ?>" class="quick-action"><i class="fas fa-plus"></i> Add Container</a>
        <a href="<?php echo url('admin/page-edit.php'); ?>" class="quick-action"><i class="fas fa-plus"></i> Add Page</a>
        <a href="<?php echo url('admin/testimonial-edit.php'); ?>" class="quick-action"><i class="fas fa-plus"></i> Add Testimonial</a>
        <a href="<?php echo url('admin/settings.php'); ?>" class="quick-action"><i class="fas fa-cog"></i> Site Settings</a>
        <a href="<?php echo url('/'); ?>" target="_blank" class="quick-action"><i class="fas fa-eye"></i> View Website</a>
    </div>
</div>

<!-- Recent Inquiries -->
<div class="admin-grid-2">
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
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
