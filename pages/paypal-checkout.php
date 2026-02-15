<?php
/**
 * PayPal Checkout Page — Shows PayPal Buttons for client-side payment
 */

$orderNumber = $_GET['order'] ?? '';
$order = null;
$items = [];

if ($orderNumber) {
    $order = getOrderByNumber($orderNumber);
    if ($order) {
        // Verify order belongs to the current session's pending PayPal checkout
        if (!isset($_SESSION['pending_paypal_order']) || (int)$_SESSION['pending_paypal_order'] !== (int)$order['id']) {
            $_SESSION['flash_message'] = 'Invalid checkout session. Please try again.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=cart');
        }
        $items = getOrderItems($order['id']);
    }
}

if (!$order) {
    $_SESSION['flash_message'] = 'Order not found.';
    $_SESSION['flash_type'] = 'error';
    redirect('index.php?page=cart');
}

$paypalClientId = getSetting('paypal_client_id');
$sandbox = getSetting('paypal_sandbox', '1') === '1';
$currency = strtoupper(getSetting('currency_code', 'USD'));
$csrfToken = generateCSRFToken();
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <a href="<?php echo url('index.php?page=cart'); ?>">Cart</a>
        <span>/</span>
        <span class="current">PayPal Payment</span>
    </div>
</div>

<section class="section">
    <div class="container" style="max-width: 600px;">
        <div class="order-review fade-in" style="margin-bottom: var(--space-2xl);">
            <h3 style="text-align: center; margin-bottom: var(--space-xl);">
                <i class="fab fa-paypal" style="color: #003087;"></i> Complete Your Payment
            </h3>

            <div class="review-items">
                <?php foreach ($items as $item): ?>
                    <div class="review-item">
                        <div class="review-item-info">
                            <span class="review-item-name"><?php echo e($item['product_name']); ?></span>
                            <span class="review-item-qty">x<?php echo $item['quantity']; ?></span>
                        </div>
                        <span class="review-item-price"><?php echo formatCurrency($item['total_price']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-row summary-total">
                <span>Total</span>
                <span><?php echo formatCurrency($order['total']); ?></span>
            </div>
        </div>

        <div id="paypal-button-container" class="fade-in" style="margin-bottom: var(--space-2xl);"></div>

        <div id="paypal-error" style="display: none;" class="flash-message error">
            <i class="fas fa-exclamation-circle"></i>
            <span id="paypal-error-msg">Payment failed. Please try again.</span>
        </div>

        <p style="text-align: center; margin-top: var(--space-lg);">
            <a href="<?php echo url('index.php?page=checkout'); ?>" class="btn btn-outline-dark">
                <i class="fas fa-arrow-left"></i> Back to Checkout
            </a>
        </p>
    </div>
</section>

<script src="https://www.paypal.com/sdk/js?client-id=<?php echo e($paypalClientId); ?>&currency=<?php echo e($currency); ?>"></script>
<script>
paypal.Buttons({
    createOrder: function() {
        return fetch('<?php echo url('admin/api/paypal-create.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo e($csrfToken); ?>'
            },
            body: JSON.stringify({
                order_id: <?php echo (int)$order['id']; ?>,
                csrf_token: '<?php echo e($csrfToken); ?>'
            })
        }).then(function(res) {
            return res.json();
        }).then(function(data) {
            if (data.id) return data.id;
            throw new Error(data.error || 'Failed to create PayPal order');
        });
    },
    onApprove: function(data) {
        return fetch('<?php echo url('admin/api/paypal-capture.php'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': '<?php echo e($csrfToken); ?>'
            },
            body: JSON.stringify({
                paypal_order_id: data.orderID,
                order_id: <?php echo (int)$order['id']; ?>,
                csrf_token: '<?php echo e($csrfToken); ?>'
            })
        }).then(function(res) {
            return res.json();
        }).then(function(data) {
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                document.getElementById('paypal-error').style.display = 'flex';
                document.getElementById('paypal-error-msg').textContent = data.error || 'Payment capture failed.';
            }
        });
    },
    onError: function(err) {
        document.getElementById('paypal-error').style.display = 'flex';
        document.getElementById('paypal-error-msg').textContent = 'An error occurred with PayPal. Please try again.';
        console.error('PayPal error:', err);
    }
}).render('#paypal-button-container');
</script>
