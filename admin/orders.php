<?php
/**
 * Admin — Orders Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_orders');

$db = getDB();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['update_status'])) {
        $orderId = (int)$_POST['update_status'];
        $newStatus = $_POST['new_status'] ?? '';
        $validStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'];
        if (in_array($newStatus, $validStatuses)) {
            $db->prepare('UPDATE orders SET order_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$newStatus, $orderId]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Order status updated.'];
        }
        redirect('admin/orders.php');
    }

    if (isset($_POST['delete'])) {
        $orderId = (int)$_POST['delete'];
        $db->prepare('UPDATE orders SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$orderId]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Order deleted.'];
        redirect('admin/orders.php');
    }
}

// Filter
$statusFilter = $_GET['status'] ?? 'all';
$query = 'SELECT * FROM orders WHERE deleted_at IS NULL';
$params = [];
if ($statusFilter !== 'all') {
    $query .= ' AND order_status = ?';
    $params[] = $statusFilter;
}
$query .= ' ORDER BY created_at DESC';

$stmt = $db->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-shopping-bag"></i> Orders</h1>
    <div>
        <span style="margin-right: var(--space-md); color: #666;"><?php echo count($orders); ?> order<?php echo count($orders) !== 1 ? 's' : ''; ?></span>
    </div>
</div>

<!-- Filter Tabs -->
<div class="admin-tabs" style="margin-bottom: var(--space-xl);">
    <a href="<?php echo url('admin/orders.php'); ?>" class="admin-tab <?php echo $statusFilter === 'all' ? 'active' : ''; ?>">All</a>
    <a href="<?php echo url('admin/orders.php?status=pending'); ?>" class="admin-tab <?php echo $statusFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
    <a href="<?php echo url('admin/orders.php?status=processing'); ?>" class="admin-tab <?php echo $statusFilter === 'processing' ? 'active' : ''; ?>">Processing</a>
    <a href="<?php echo url('admin/orders.php?status=shipped'); ?>" class="admin-tab <?php echo $statusFilter === 'shipped' ? 'active' : ''; ?>">Shipped</a>
    <a href="<?php echo url('admin/orders.php?status=completed'); ?>" class="admin-tab <?php echo $statusFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
    <a href="<?php echo url('admin/orders.php?status=cancelled'); ?>" class="admin-tab <?php echo $statusFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
</div>

<?php if (empty($orders)): ?>
    <div class="empty-state"><p>No orders found.</p></div>
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
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td><strong><a href="<?php echo url('admin/order-view.php?id=' . $o['id']); ?>"><?php echo e($o['order_number']); ?></a></strong></td>
                        <td>
                            <?php echo e($o['customer_name']); ?>
                            <br><small style="color: #888;"><?php echo e($o['customer_email']); ?></small>
                        </td>
                        <td><strong><?php echo formatCurrency($o['total']); ?></strong></td>
                        <td>
                            <span class="badge-status badge-<?php echo $o['payment_status'] === 'completed' ? 'active' : 'inactive'; ?>">
                                <?php echo e(ucfirst($o['payment_method'] ?: 'pending')); ?> &mdash; <?php echo e(ucfirst($o['payment_status'])); ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="update_status" value="<?php echo $o['id']; ?>">
                                <select name="new_status" onchange="this.form.submit()" class="form-control" style="padding: 4px 8px; font-size: 12px; width: auto; min-width: 110px;">
                                    <?php foreach (['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'] as $st): ?>
                                        <option value="<?php echo $st; ?>" <?php echo $o['order_status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </form>
                        </td>
                        <td><small><?php echo formatDate($o['created_at']); ?></small></td>
                        <td class="actions">
                            <a href="<?php echo url('admin/order-view.php?id=' . $o['id']); ?>" class="btn-icon" title="View"><i class="fas fa-eye"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this order?')">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="delete" value="<?php echo $o['id']; ?>">
                                <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
