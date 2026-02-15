<?php
/**
 * Checkout Page — Collect info and select payment method
 */

$cart = getCart();
$totals = getCartTotals();
$csrfToken = generateCSRFToken();
$enabledProviders = getEnabledPaymentProviders();
$hasPhysical = cartHasPhysicalItems();
$companyPhone = getSetting('company_phone');

// Redirect to cart if empty
if (empty($cart)) {
    $_SESSION['flash_message'] = 'Your cart is empty. Add items before checking out.';
    $_SESSION['flash_type'] = 'info';
    redirect('index.php?page=cart');
}
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <a href="<?php echo url('index.php?page=cart'); ?>">Cart</a>
        <span>/</span>
        <span class="current">Checkout</span>
    </div>
</div>

<?php if (isset($_GET['cancelled'])): ?>
    <div class="container" style="padding-top: var(--space-lg);">
        <div class="flash-message info" role="alert">
            <i class="fas fa-info-circle"></i>
            <span class="flash-text">Payment was cancelled. You can try again or choose a different payment method.</span>
        </div>
    </div>
<?php endif; ?>

<section class="section">
    <div class="container" style="max-width: var(--container-lg);">
        <div class="section-header fade-in">
            <h2>Checkout</h2>
            <p>Complete your order below</p>
        </div>

        <form method="POST" action="<?php echo url('index.php'); ?>" id="checkoutForm" class="checkout-layout fade-in">
            <input type="hidden" name="action" value="checkout">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="checkout-main">
                <!-- Contact Information -->
                <div class="form-section">
                    <h3><i class="fas fa-user"></i> Contact Information</h3>
                    <div class="form-group">
                        <label for="checkout_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="checkout_name" name="name" class="form-control" required placeholder="Your full name">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="checkout_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="checkout_email" name="email" class="form-control" required placeholder="your@email.com">
                        </div>
                        <div class="form-group">
                            <label for="checkout_phone">Phone Number</label>
                            <input type="tel" id="checkout_phone" name="phone" class="form-control" placeholder="(555) 000-0000">
                        </div>
                    </div>
                </div>

                <!-- Shipping Address (only for physical products) -->
                <?php if ($hasPhysical): ?>
                    <div class="form-section">
                        <h3><i class="fas fa-truck"></i> Shipping Address</h3>
                        <div class="form-group">
                            <label for="checkout_address">Full Address <span class="required">*</span></label>
                            <textarea id="checkout_address" name="shipping_address" class="form-control" rows="3" required placeholder="Street address, city, state, ZIP code"></textarea>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Additional Notes -->
                <div class="form-section">
                    <h3><i class="fas fa-comment"></i> Order Notes</h3>
                    <div class="form-group">
                        <label for="checkout_notes">Additional Notes (optional)</label>
                        <textarea id="checkout_notes" name="notes" class="form-control" rows="3" placeholder="Special instructions or notes for your order..."></textarea>
                    </div>
                </div>

                <!-- Payment Method Selection -->
                <div class="form-section">
                    <h3><i class="fas fa-credit-card"></i> Payment Method</h3>

                    <?php if (empty($enabledProviders)): ?>
                        <div class="payment-notice">
                            <i class="fas fa-info-circle"></i>
                            <p>Submit your order and we will contact you to arrange payment. You can also call us at
                            <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>"><?php echo e($companyPhone); ?></a>.</p>
                        </div>
                        <input type="hidden" name="payment_method" value="manual">
                    <?php else: ?>
                        <div class="payment-options">
                            <?php if (in_array('stripe', $enabledProviders)): ?>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="stripe" checked>
                                    <div class="payment-option-content">
                                        <div class="payment-icon"><i class="fab fa-stripe-s"></i></div>
                                        <div>
                                            <strong>Credit / Debit Card</strong>
                                            <small>Powered by Stripe</small>
                                        </div>
                                    </div>
                                </label>
                            <?php endif; ?>

                            <?php if (in_array('paypal', $enabledProviders)): ?>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="paypal" <?php echo !in_array('stripe', $enabledProviders) ? 'checked' : ''; ?>>
                                    <div class="payment-option-content">
                                        <div class="payment-icon"><i class="fab fa-paypal"></i></div>
                                        <div>
                                            <strong>PayPal</strong>
                                            <small>Pay with your PayPal account</small>
                                        </div>
                                    </div>
                                </label>
                            <?php endif; ?>

                            <?php if (in_array('square', $enabledProviders)): ?>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="square" <?php echo !in_array('stripe', $enabledProviders) && !in_array('paypal', $enabledProviders) ? 'checked' : ''; ?>>
                                    <div class="payment-option-content">
                                        <div class="payment-icon"><i class="fas fa-square"></i></div>
                                        <div>
                                            <strong>Square</strong>
                                            <small>Credit card via Square</small>
                                        </div>
                                    </div>
                                </label>
                            <?php endif; ?>

                            <?php if (in_array('btcpay', $enabledProviders)): ?>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="btcpay" <?php echo !in_array('stripe', $enabledProviders) && !in_array('paypal', $enabledProviders) && !in_array('square', $enabledProviders) ? 'checked' : ''; ?>>
                                    <div class="payment-option-content">
                                        <div class="payment-icon"><i class="fab fa-bitcoin"></i></div>
                                        <div>
                                            <strong>Bitcoin</strong>
                                            <small>On-chain or Lightning via BTCPay</small>
                                        </div>
                                    </div>
                                </label>
                            <?php endif; ?>

                            <label class="payment-option">
                                <input type="radio" name="payment_method" value="manual">
                                <div class="payment-option-content">
                                    <div class="payment-icon"><i class="fas fa-phone"></i></div>
                                    <div>
                                        <strong>Pay Later</strong>
                                        <small>We'll contact you to arrange payment</small>
                                    </div>
                                </div>
                            </label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="checkout-sidebar">
                <div class="order-review">
                    <h3>Order Summary</h3>

                    <div class="review-items">
                        <?php foreach ($cart as $item): ?>
                            <div class="review-item">
                                <div class="review-item-info">
                                    <span class="review-item-name"><?php echo e($item['name']); ?></span>
                                    <span class="review-item-qty">x<?php echo $item['quantity']; ?></span>
                                </div>
                                <span class="review-item-price"><?php echo formatCurrency($item['price'] * $item['quantity']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($totals['subtotal']); ?></span>
                    </div>
                    <?php if ($totals['tax'] > 0): ?>
                        <div class="summary-row">
                            <span>Tax (<?php echo $totals['tax_rate']; ?>%)</span>
                            <span><?php echo formatCurrency($totals['tax']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="summary-row summary-total">
                        <span>Total</span>
                        <span><?php echo formatCurrency($totals['total']); ?></span>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: var(--space-lg);">
                        <i class="fas fa-lock"></i> Place Order
                    </button>

                    <p style="font-size: var(--text-xs); color: var(--color-gray-500); margin-top: var(--space-md); text-align: center;">
                        <i class="fas fa-shield-alt"></i> Your information is secured with industry-standard encryption.
                    </p>
                </div>
            </div>
        </form>
    </div>
</section>
