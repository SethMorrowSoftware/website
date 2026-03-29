<?php
/**
 * Shopping Cart Page
 */

$cart = getCart();
$totals = getCartTotalsWithCoupon();
$appliedCoupon = getAppliedCoupon();
$csrfToken = generateCSRFToken();
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Shopping Cart</span>
    </div>
</div>

<section class="section">
    <div class="container" style="max-width: var(--container-lg);">
        <div class="section-header fade-in">
            <h2>Shopping Cart</h2>
            <p><?php echo $totals['item_count']; ?> item<?php echo $totals['item_count'] !== 1 ? 's' : ''; ?> in your cart</p>
        </div>

        <?php if (empty($cart)): ?>
            <div class="empty-cart fade-in" style="text-align: center; padding: var(--space-4xl) 0;">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: var(--color-gray-300); margin-bottom: var(--space-xl);"></i>
                <h3 style="color: var(--color-gray-500); margin-bottom: var(--space-lg);">Your cart is empty</h3>
                <p style="color: var(--color-gray-400); margin-bottom: var(--space-xl);">Browse our catalog and add items to get started.</p>
                <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-primary">Browse Catalog</a>
            </div>
        <?php else: ?>
            <div class="cart-layout fade-in">
                <div class="cart-items">
                    <?php foreach ($cart as $key => $item): ?>
                        <div class="cart-item" data-product-id="<?php echo (int)$item['product_id']; ?>">
                            <div class="cart-item-image">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo e(getImageUrl($item['image'])); ?>" alt="<?php echo e($item['name']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-icon">
                                        <?php if (($item['product_type'] ?? 'physical') === 'digital'): ?>
                                            <i class="fas fa-file-download"></i>
                                        <?php elseif (($item['product_type'] ?? 'physical') === 'service'): ?>
                                            <i class="fas fa-concierge-bell"></i>
                                        <?php else: ?>
                                            <i class="fas fa-box"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="cart-item-details">
                                <h4><?php echo e($item['name']); ?></h4>
                                <p class="cart-item-meta">
                                    <span class="product-type-badge badge-<?php echo e($item['product_type'] ?? 'physical'); ?>"><?php echo e(ucfirst($item['product_type'] ?? 'physical')); ?></span>
                                    <?php if ($item['category']): ?>
                                        <span style="color: var(--color-gray-500);"><?php echo e($item['category']); ?></span>
                                    <?php endif; ?>
                                </p>
                                <div class="cart-item-price"><?php echo formatCurrency($item['price']); ?><?php if ($item['unit']): ?> <span class="card-unit">/ <?php echo e($item['unit']); ?></span><?php endif; ?></div>
                            </div>
                            <div class="cart-item-quantity">
                                <form method="POST" action="<?php echo url('index.php'); ?>" class="quantity-form">
                                    <input type="hidden" name="action" value="update_cart">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                    <button type="submit" name="quantity" value="<?php echo max(0, $item['quantity'] - 1); ?>" class="qty-btn" aria-label="Decrease">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <span class="qty-value"><?php echo $item['quantity']; ?></span>
                                    <button type="submit" name="quantity" value="<?php echo $item['quantity'] + 1; ?>" class="qty-btn" aria-label="Increase">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </form>
                            </div>
                            <div class="cart-item-total">
                                <strong><?php echo formatCurrency($item['price'] * $item['quantity']); ?></strong>
                            </div>
                            <form method="POST" action="<?php echo url('index.php'); ?>" class="cart-item-remove">
                                <input type="hidden" name="action" value="remove_from_cart">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="product_id" value="<?php echo (int)$item['product_id']; ?>">
                                <button type="submit" class="btn-remove" title="Remove item" aria-label="Remove">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="cart-summary">
                    <h3>Order Summary</h3>
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span><?php echo formatCurrency($totals['subtotal']); ?></span>
                    </div>
                    <?php if ($appliedCoupon): ?>
                        <div class="summary-row summary-discount">
                            <span>
                                Coupon: <strong><?php echo e($appliedCoupon['code']); ?></strong>
                                <form method="POST" action="<?php echo url('index.php'); ?>" style="display:inline;">
                                    <input type="hidden" name="action" value="remove_coupon">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <button type="submit" class="btn-text-link" title="Remove coupon" style="color: var(--color-error); font-size: 0.8rem;">&times;</button>
                                </form>
                            </span>
                            <span style="color: var(--color-success);">-<?php echo formatCurrency($totals['discount']); ?></span>
                        </div>
                    <?php endif; ?>
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

                    <!-- Coupon Code -->
                    <?php if (!$appliedCoupon): ?>
                        <div class="coupon-form" style="margin-top: var(--space-lg);">
                            <form method="POST" action="<?php echo url('index.php'); ?>">
                                <input type="hidden" name="action" value="apply_coupon">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <div class="coupon-input-group">
                                    <input type="text" name="coupon_code" placeholder="Coupon code" class="form-control" style="text-transform: uppercase;">
                                    <button type="submit" class="btn btn-outline-dark btn-sm">Apply</button>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo url('index.php?page=checkout'); ?>" class="btn btn-primary btn-lg" style="width: 100%; margin-top: var(--space-lg);">
                        <i class="fas fa-lock"></i> Proceed to Checkout
                    </a>

                    <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-outline-dark" style="width: 100%; margin-top: var(--space-md);">
                        <i class="fas fa-arrow-left"></i> Continue Shopping
                    </a>

                    <div style="margin-top: var(--space-lg); text-align: center;">
                        <p style="font-size: var(--text-sm); color: var(--color-gray-500);">
                            <i class="fas fa-shield-alt"></i> Secure checkout with encrypted payment
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
