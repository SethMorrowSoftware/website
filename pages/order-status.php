<?php
/**
 * Order Status / Tracking Page
 */

$csrfToken = generateCSRFToken();
$order = null;
$orderItems = [];
$errorMsg = '';

// Check if order number provided via GET or POST
$orderNumber = trim($_GET['order'] ?? $_POST['order_number'] ?? '');
$email = trim($_GET['email'] ?? $_POST['email'] ?? '');

if ($orderNumber) {
    $db = getDB();

    // If customer is logged in, just need order number
    if (isCustomerLoggedIn()) {
        $stmt = $db->prepare('SELECT * FROM orders WHERE order_number = ? AND customer_id = ?');
        $stmt->execute([$orderNumber, getCustomerId()]);
        $order = $stmt->fetch();
        if (!$order) {
            $errorMsg = 'Order not found in your account. Please check the order number.';
        }
    } elseif ($email) {
        // Guest lookup requires email match
        $stmt = $db->prepare('SELECT * FROM orders WHERE order_number = ? AND customer_email = ?');
        $stmt->execute([$orderNumber, $email]);
        $order = $stmt->fetch();
        if (!$order) {
            $errorMsg = 'No order found matching that order number and email address.';
        }
    } else {
        $errorMsg = 'Please enter your email address to look up your order.';
    }

    if ($order) {
        $stmt = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $stmt->execute([$order['id']]);
        $orderItems = $stmt->fetchAll();
    }
}
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Order Status</span>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card" style="max-width: 700px;">
                <h1><i class="fas fa-truck"></i> Track Your Order</h1>
                <p class="auth-subtitle">Enter your order number to check the status of your order.</p>

                <form method="GET" action="<?php echo url('index.php'); ?>" class="auth-form">
                    <input type="hidden" name="page" value="order-status">
                    <div class="form-group">
                        <label for="order_number">Order Number <span class="required">*</span></label>
                        <input type="text" id="order_number" name="order" class="form-control" value="<?php echo e($orderNumber); ?>" required placeholder="e.g. ORD-ABC123">
                    </div>
                    <?php if (!isCustomerLoggedIn()): ?>
                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo e($email); ?>" required placeholder="The email used for your order">
                    </div>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-search"></i> Track Order
                    </button>
                </form>

                <?php if ($errorMsg): ?>
                    <div class="flash-message error" role="alert" style="margin-top: var(--space-lg);">
                        <i class="fas fa-exclamation-circle"></i>
                        <span class="flash-text"><?php echo e($errorMsg); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order): ?>
            <div class="order-tracking-result" style="max-width: 700px; margin: var(--space-xl) auto 0;">
                <!-- Status Timeline -->
                <div class="order-timeline">
                    <?php
                    $statuses = ['pending', 'processing', 'shipped', 'delivered'];
                    $currentIdx = array_search($order['order_status'], $statuses);
                    if ($currentIdx === false) $currentIdx = ($order['order_status'] === 'completed') ? 3 : -1;
                    $isCancelled = $order['order_status'] === 'cancelled';
                    ?>
                    <?php if ($isCancelled): ?>
                        <div class="timeline-cancelled">
                            <i class="fas fa-times-circle"></i>
                            <h3>Order Cancelled</h3>
                            <p>This order has been cancelled.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline-steps">
                            <?php foreach ($statuses as $idx => $status): ?>
                                <div class="timeline-step <?php echo $idx <= $currentIdx ? 'completed' : ''; ?> <?php echo $idx === $currentIdx ? 'current' : ''; ?>">
                                    <div class="timeline-icon">
                                        <?php if ($idx < $currentIdx): ?>
                                            <i class="fas fa-check"></i>
                                        <?php elseif ($idx === $currentIdx): ?>
                                            <i class="fas fa-<?php echo ['clock', 'cog', 'shipping-fast', 'check-double'][$idx]; ?>"></i>
                                        <?php else: ?>
                                            <i class="fas fa-<?php echo ['clock', 'cog', 'shipping-fast', 'check-double'][$idx]; ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <span class="timeline-label"><?php echo e(ucfirst($status)); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Order Details -->
                <div class="admin-card" style="margin-top: var(--space-lg);">
                    <h3 style="margin-bottom: var(--space-md);">Order #<?php echo e($order['order_number']); ?></h3>
                    <div class="order-meta-grid">
                        <div class="order-meta-item">
                            <span class="meta-label">Date Placed</span>
                            <span class="meta-value"><?php echo formatDate($order['created_at']); ?></span>
                        </div>
                        <div class="order-meta-item">
                            <span class="meta-label">Payment Status</span>
                            <span class="status-badge status-<?php echo e($order['payment_status']); ?>"><?php echo e(ucfirst($order['payment_status'])); ?></span>
                        </div>
                        <div class="order-meta-item">
                            <span class="meta-label">Order Status</span>
                            <span class="status-badge status-<?php echo e($order['order_status']); ?>"><?php echo e(ucfirst($order['order_status'])); ?></span>
                        </div>
                        <div class="order-meta-item">
                            <span class="meta-label">Total</span>
                            <span class="meta-value" style="font-weight: 700;"><?php echo formatCurrency($order['total']); ?></span>
                        </div>
                    </div>

                    <?php if ($order['tracking_number'] ?? ''): ?>
                        <div class="tracking-info" style="margin-top: var(--space-md); padding: var(--space-md); background: var(--color-gray-50); border-radius: var(--radius-md);">
                            <strong><i class="fas fa-shipping-fast"></i> Tracking:</strong>
                            <?php echo e($order['tracking_carrier'] ?? 'Carrier'); ?> — <?php echo e($order['tracking_number']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($orderItems)): ?>
                        <h4 style="margin-top: var(--space-lg); margin-bottom: var(--space-sm);">Items</h4>
                        <table class="orders-table" style="font-size: var(--text-sm);">
                            <thead>
                                <tr><th>Product</th><th>Qty</th><th>Price</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td><?php echo e($item['product_name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatCurrency($item['total_price']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>
