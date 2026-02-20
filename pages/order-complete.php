<?php
/**
 * Order Complete / Confirmation Page
 */

$orderNumber = $_GET['order'] ?? '';
$paymentMethod = $_GET['payment'] ?? '';
$order = null;
$items = [];
$downloads = [];

if ($orderNumber) {
    $order = getOrderByNumber($orderNumber);
    if ($order) {
        // Verify order access: session-based (recent checkout) or customer ownership
        $sessionMatch = isset($_SESSION['recent_order_number']) && $_SESSION['recent_order_number'] === $orderNumber;
        $customerOwns = isCustomerLoggedIn() && !empty($order['customer_id']) && (int)$order['customer_id'] === getCustomerId();

        if (!$sessionMatch && !$customerOwns) {
            $order = null;
        }
    }

    if ($order) {
        $items = getOrderItems($order['id']);
        $downloads = getDownloadTokens($order['id']);

        // Verify Stripe payment if returning from Stripe Checkout
        if ($paymentMethod === 'stripe' && isset($_GET['session_id']) && $order['payment_status'] !== 'completed') {
            $session = verifyStripePayment($_GET['session_id']);
            if ($session && $session['payment_status'] === 'paid') {
                updateOrderPayment($order['id'], 'completed', $session['payment_intent'] ?? $session['id'], 'stripe');

                // Decrement inventory now that payment is confirmed
                processOrderInventory($order['id']);

                // Generate download tokens for digital products
                if (empty($downloads)) {
                    generateDownloadTokens($order['id']);
                }

                // Send confirmation email
                sendOrderConfirmation($order['id']);

                // Re-fetch order and downloads from DB to avoid stale data
                $order = getOrderByNumber($orderNumber);
                $downloads = getDownloadTokens($order['id']);
            }
        }

        // For Square, mark as processing — actual payment confirmation should come
        // via Square webhooks. We do NOT auto-mark as completed on redirect since
        // Square handles capture asynchronously and the redirect alone is not proof.
        if ($paymentMethod === 'square' && $order['payment_status'] === 'pending') {
            updateOrderPayment($order['id'], 'processing', $order['payment_id'] ?? '', 'square');
            $order = getOrderByNumber($orderNumber);
        }

        // For BTCPay, verify the invoice status via the API
        if ($paymentMethod === 'btcpay' && $order['payment_id'] && $order['payment_status'] !== 'completed') {
            $invoice = verifyBTCPayInvoice($order['payment_id']);
            if ($invoice) {
                $btcStatus = $invoice['status'] ?? '';
                // BTCPay statuses: New, Processing, Expired, Invalid, Settled
                if ($btcStatus === 'Settled') {
                    updateOrderPayment($order['id'], 'completed', $order['payment_id'], 'btcpay');

                    if (empty($downloads)) {
                        generateDownloadTokens($order['id']);
                    }
                    sendOrderConfirmation($order['id']);

                    // Re-fetch order and downloads from DB to avoid stale data
                    $order = getOrderByNumber($orderNumber);
                    $downloads = getDownloadTokens($order['id']);
                } elseif ($btcStatus === 'Processing') {
                    updateOrderPayment($order['id'], 'processing', $order['payment_id'], 'btcpay');
                    $order = getOrderByNumber($orderNumber);
                }
            }
        }
    }
}
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Order Confirmation</span>
    </div>
</div>

<section class="section">
    <div class="container" style="max-width: var(--container-lg);">
        <?php if (!$order): ?>
            <div style="text-align: center; padding: var(--space-4xl) 0;" class="fade-in">
                <i class="fas fa-exclamation-circle" style="font-size: 4rem; color: var(--color-gray-300); margin-bottom: var(--space-xl);"></i>
                <h2 style="color: var(--color-gray-500);">Order Not Found</h2>
                <p style="color: var(--color-gray-400); margin-bottom: var(--space-xl);">We couldn't find this order. Please check your order number.</p>
                <a href="<?php echo url('/'); ?>" class="btn btn-primary">Return Home</a>
            </div>
        <?php else: ?>
            <div class="order-confirmation fade-in">
                <div class="confirmation-header">
                    <div class="confirmation-icon">
                        <?php if ($order['payment_status'] === 'completed'): ?>
                            <i class="fas fa-check-circle" style="color: var(--color-success);"></i>
                        <?php else: ?>
                            <i class="fas fa-clock" style="color: var(--color-secondary);"></i>
                        <?php endif; ?>
                    </div>
                    <h2>
                        <?php if ($order['payment_status'] === 'completed'): ?>
                            Order Confirmed!
                        <?php else: ?>
                            Order Received
                        <?php endif; ?>
                    </h2>
                    <p style="color: var(--color-gray-600);">
                        <?php if ($order['payment_status'] === 'completed'): ?>
                            Thank you for your purchase! Your order has been confirmed.
                        <?php else: ?>
                            Your order has been received. We will contact you to complete the payment.
                        <?php endif; ?>
                    </p>
                    <p class="order-number-display">Order #<?php echo e($order['order_number']); ?></p>
                </div>

                <!-- Digital Downloads -->
                <?php if (!empty($downloads)): ?>
                    <div class="downloads-section">
                        <h3><i class="fas fa-download"></i> Your Downloads</h3>
                        <p style="color: var(--color-gray-600); margin-bottom: var(--space-lg);">Your digital products are ready to download. Download links have also been sent to your email.</p>
                        <div class="download-links">
                            <?php foreach ($downloads as $dl): ?>
                                <a href="<?php echo url('index.php?page=download&token=' . $dl['token']); ?>" class="download-link-card">
                                    <i class="fas fa-file-download"></i>
                                    <div>
                                        <strong><?php echo e($dl['product_name']); ?></strong>
                                        <?php if ($dl['expires_at']): ?>
                                            <small>Expires: <?php echo formatDate($dl['expires_at']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <span class="btn btn-sm btn-primary">Download</span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Order Details -->
                <div class="order-details-grid">
                    <div class="order-detail-section">
                        <h4>Order Details</h4>
                        <div class="order-detail-table-wrap">
                        <table class="order-detail-table">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Type</th>
                                    <th>Qty</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td><?php echo e($item['product_name']); ?></td>
                                        <td><span class="product-type-badge badge-<?php echo e($item['product_type']); ?>"><?php echo e(ucfirst($item['product_type'])); ?></span></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td><?php echo formatCurrency($item['total_price']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" style="text-align: right;">Subtotal</td>
                                    <td><?php echo formatCurrency($order['subtotal']); ?></td>
                                </tr>
                                <?php if ($order['tax'] > 0): ?>
                                    <tr>
                                        <td colspan="3" style="text-align: right;">Tax</td>
                                        <td><?php echo formatCurrency($order['tax']); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="total-row">
                                    <td colspan="3" style="text-align: right;"><strong>Total</strong></td>
                                    <td><strong><?php echo formatCurrency($order['total']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                        </div>
                    </div>

                    <div class="order-detail-section">
                        <h4>Customer Information</h4>
                        <div class="detail-list">
                            <div class="detail-item">
                                <span class="detail-label">Name</span>
                                <span><?php echo e($order['customer_name']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Email</span>
                                <span><?php echo e($order['customer_email']); ?></span>
                            </div>
                            <?php if ($order['customer_phone']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Phone</span>
                                    <span><?php echo e($order['customer_phone']); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($order['shipping_address']): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Shipping</span>
                                    <span><?php echo e($order['shipping_address']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <span class="detail-label">Payment</span>
                                <span><?php echo e(ucfirst($order['payment_method'] ?: 'Pending')); ?> &mdash;
                                    <?php if ($order['payment_status'] === 'completed'): ?>
                                        <span style="color: var(--color-success);"><i class="fas fa-check"></i> Paid</span>
                                    <?php else: ?>
                                        <span style="color: var(--color-secondary);"><i class="fas fa-clock"></i> <?php echo e(ucfirst($order['payment_status'])); ?></span>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center cta-buttons-inline" style="margin-top: var(--space-2xl); justify-content: center;">
                    <a href="<?php echo url('/'); ?>" class="btn btn-primary">Return Home</a>
                    <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-outline-dark">Continue Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
