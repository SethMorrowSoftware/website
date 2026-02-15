<?php
/**
 * Admin — Order Detail View
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$id) {
    redirect('admin/orders.php');
}

$order = getOrder($id);
if (!$order) {
    $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Order not found.'];
    redirect('admin/orders.php');
}

$items = getOrderItems($id);
$downloads = getDownloadTokens($id);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['update_status'])) {
        $newStatus = $_POST['new_status'] ?? '';
        $validStatuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'];
        if (in_array($newStatus, $validStatuses)) {
            $db->prepare('UPDATE orders SET order_status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$newStatus, $id]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Order status updated.'];
        }
        redirect('admin/order-view.php?id=' . $id);
    }

    if (isset($_POST['generate_downloads'])) {
        $newTokens = generateDownloadTokens($id);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => count($newTokens) . ' download token(s) generated.'];
        redirect('admin/order-view.php?id=' . $id);
    }

    if (isset($_POST['update_tracking'])) {
        $trackingCarrier = trim($_POST['tracking_carrier'] ?? '');
        $trackingNumber = trim($_POST['tracking_number'] ?? '');
        $db->prepare('UPDATE orders SET tracking_carrier = ?, tracking_number = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?')
            ->execute([$trackingCarrier, $trackingNumber, $id]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Tracking information updated.'];
        redirect('admin/order-view.php?id=' . $id);
    }
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-shopping-bag"></i> Order #<?php echo e($order['order_number']); ?></h1>
    <a href="<?php echo url('admin/orders.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Orders</a>
</div>

<div class="admin-grid-2">
    <!-- Order Info -->
    <div class="admin-section">
        <h2>Order Details</h2>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Type</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><strong><?php echo e($item['product_name']); ?></strong></td>
                        <td><span class="badge-status badge-<?php echo $item['product_type'] === 'digital' ? 'active' : 'inactive'; ?>"><?php echo e(ucfirst($item['product_type'])); ?></span></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo formatCurrency($item['unit_price']); ?></td>
                        <td><?php echo formatCurrency($item['total_price']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;">Subtotal</td>
                    <td><strong><?php echo formatCurrency($order['subtotal']); ?></strong></td>
                </tr>
                <?php if ($order['tax'] > 0): ?>
                    <tr>
                        <td colspan="4" style="text-align: right;">Tax</td>
                        <td><?php echo formatCurrency($order['tax']); ?></td>
                    </tr>
                <?php endif; ?>
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Total</strong></td>
                    <td><strong style="font-size: 1.1em;"><?php echo formatCurrency($order['total']); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Customer Info -->
    <div class="admin-section">
        <h2>Customer Information</h2>
        <div class="admin-detail-list">
            <div class="admin-detail-row">
                <span class="admin-detail-label">Name</span>
                <span><?php echo e($order['customer_name']); ?></span>
            </div>
            <div class="admin-detail-row">
                <span class="admin-detail-label">Email</span>
                <a href="mailto:<?php echo e($order['customer_email']); ?>"><?php echo e($order['customer_email']); ?></a>
            </div>
            <?php if ($order['customer_phone']): ?>
            <div class="admin-detail-row">
                <span class="admin-detail-label">Phone</span>
                <span><?php echo e($order['customer_phone']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($order['shipping_address']): ?>
            <div class="admin-detail-row">
                <span class="admin-detail-label">Shipping</span>
                <span><?php echo e($order['shipping_address']); ?></span>
            </div>
            <?php endif; ?>
            <?php if ($order['notes']): ?>
            <div class="admin-detail-row">
                <span class="admin-detail-label">Notes</span>
                <span><?php echo e($order['notes']); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <h3 style="margin-top: var(--space-xl);">Payment & Status</h3>
        <div class="admin-detail-list">
            <div class="admin-detail-row">
                <span class="admin-detail-label">Payment Method</span>
                <span><?php echo e(ucfirst($order['payment_method'] ?: 'None')); ?></span>
            </div>
            <div class="admin-detail-row">
                <span class="admin-detail-label">Payment Status</span>
                <span class="badge-status badge-<?php echo $order['payment_status'] === 'completed' ? 'active' : 'inactive'; ?>">
                    <?php echo e(ucfirst($order['payment_status'])); ?>
                </span>
            </div>
            <?php if ($order['payment_id']): ?>
            <div class="admin-detail-row">
                <span class="admin-detail-label">Payment ID</span>
                <span style="font-family: monospace; font-size: 12px;"><?php echo e($order['payment_id']); ?></span>
            </div>
            <?php endif; ?>
            <div class="admin-detail-row">
                <span class="admin-detail-label">Order Date</span>
                <span><?php echo formatDate($order['created_at']); ?></span>
            </div>
            <div class="admin-detail-row">
                <span class="admin-detail-label">Last Updated</span>
                <span><?php echo formatDate($order['updated_at']); ?></span>
            </div>
        </div>

        <!-- Status Update -->
        <form method="POST" style="margin-top: var(--space-lg);">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="update_status" value="1">
            <div class="form-group">
                <label>Update Order Status</label>
                <div style="display: flex; gap: var(--space-sm);">
                    <select name="new_status" class="form-control">
                        <?php foreach (['pending', 'processing', 'shipped', 'completed', 'cancelled', 'refunded'] as $st): ?>
                            <option value="<?php echo $st; ?>" <?php echo $order['order_status'] === $st ? 'selected' : ''; ?>><?php echo ucfirst($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-admin btn-save">Update</button>
                </div>
            </div>
        </form>

        <!-- Shipping & Tracking -->
        <h3 style="margin-top: var(--space-xl);"><i class="fas fa-shipping-fast"></i> Shipping & Tracking</h3>
        <form method="POST" style="margin-top: var(--space-md);">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="update_tracking" value="1">
            <div class="form-group">
                <label for="tracking_carrier">Tracking Carrier</label>
                <input type="text" id="tracking_carrier" name="tracking_carrier" class="form-control" value="<?php echo e($order['tracking_carrier'] ?? ''); ?>" placeholder="e.g. UPS, FedEx, USPS, DHL">
            </div>
            <div class="form-group">
                <label for="tracking_number">Tracking Number</label>
                <input type="text" id="tracking_number" name="tracking_number" class="form-control" value="<?php echo e($order['tracking_number'] ?? ''); ?>" placeholder="Enter tracking number">
            </div>
            <button type="submit" class="btn-admin btn-save">
                <i class="fas fa-save"></i> Save Tracking Info
            </button>
        </form>
    </div>
</div>

<!-- Download Tokens -->
<?php if (!empty($downloads)): ?>
<div class="admin-section">
    <h2><i class="fas fa-download"></i> Download Tokens</h2>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Token</th>
                    <th>Downloads</th>
                    <th>Expires</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($downloads as $dl): ?>
                    <tr>
                        <td><?php echo e($dl['product_name']); ?></td>
                        <td><code style="font-size: 11px;"><?php echo e(substr($dl['token'], 0, 16)); ?>...</code></td>
                        <td><?php echo $dl['download_count']; ?><?php if ($dl['max_downloads'] > 0): ?> / <?php echo $dl['max_downloads']; ?><?php endif; ?></td>
                        <td><?php echo $dl['expires_at'] ? formatDate($dl['expires_at']) : 'Never'; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <?php
    // Check if order has digital items without tokens
    $hasDigital = false;
    foreach ($items as $item) {
        if ($item['product_type'] === 'digital') { $hasDigital = true; break; }
    }
    if ($hasDigital): ?>
    <div class="admin-section">
        <h2><i class="fas fa-download"></i> Download Tokens</h2>
        <p>No download tokens have been generated for this order.</p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <button type="submit" name="generate_downloads" value="1" class="btn-admin btn-save">
                <i class="fas fa-key"></i> Generate Download Tokens
            </button>
        </form>
    </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
