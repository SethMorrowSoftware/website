<?php
/**
 * Payment Page — Shows available payment methods and options
 */

$hero = getHero('payment');
$enabledProviders = getEnabledPaymentProviders();
$companyPhone = getSetting('company_phone');
$swipesimpleLink = getSetting('swipesimple_link');
$swipesimpleEmbed = getSetting('swipesimple_embed');
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Payment'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Secure Payment Options'); ?></p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Payment</span>
    </div>
</div>

<section class="section">
    <div class="container container-narrow">
        <div class="section-header fade-in">
            <h2>Payment Methods</h2>
            <p>We offer multiple secure payment options for your convenience</p>
        </div>

        <!-- Payment Methods Grid -->
        <div class="payment-methods fade-in">
            <?php if (in_array('stripe', $enabledProviders)): ?>
                <div class="payment-method">
                    <div class="icon"><i class="fab fa-stripe-s"></i></div>
                    <h4>Credit / Debit Card</h4>
                    <p>Powered by Stripe. Accepts Visa, Mastercard, AMEX, and more.</p>
                </div>
            <?php endif; ?>

            <?php if (in_array('paypal', $enabledProviders)): ?>
                <div class="payment-method">
                    <div class="icon"><i class="fab fa-paypal"></i></div>
                    <h4>PayPal</h4>
                    <p>Pay securely with your PayPal account or linked cards.</p>
                </div>
            <?php endif; ?>

            <?php if (in_array('square', $enabledProviders)): ?>
                <div class="payment-method">
                    <div class="icon"><i class="fas fa-square"></i></div>
                    <h4>Square</h4>
                    <p>Accept credit and debit cards via Square's secure checkout.</p>
                </div>
            <?php endif; ?>

            <?php if (in_array('btcpay', $enabledProviders)): ?>
                <div class="payment-method">
                    <div class="icon"><i class="fab fa-bitcoin"></i></div>
                    <h4>Bitcoin</h4>
                    <p>Pay with Bitcoin on-chain or via Lightning Network. Self-custodial via BTCPay Server.</p>
                </div>
            <?php endif; ?>

            <div class="payment-method">
                <div class="icon"><i class="fas fa-credit-card"></i></div>
                <h4>Credit Card</h4>
                <p>We accept all major credit and debit cards.</p>
            </div>

            <div class="payment-method">
                <div class="icon"><i class="fas fa-money-check-alt"></i></div>
                <h4>Check</h4>
                <p>Personal or business checks accepted.</p>
            </div>

            <div class="payment-method">
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                <h4>Cash</h4>
                <p>Cash payments accepted for in-person transactions.</p>
            </div>
        </div>

        <?php if (!empty($enabledProviders)): ?>
            <div class="text-center fade-in" style="margin: var(--space-2xl) 0;">
                <p style="color: var(--color-gray-600); margin-bottom: var(--space-lg);">
                    Add items to your cart and proceed to checkout to pay with any of our online payment options.
                </p>
                <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-store"></i> Browse Catalog
                </a>
                <a href="<?php echo url('index.php?page=cart'); ?>" class="btn btn-outline-dark btn-lg" style="margin-left: var(--space-sm);">
                    <i class="fas fa-shopping-cart"></i> View Cart
                </a>
            </div>
        <?php endif; ?>

        <!-- SwipeSimple Integration (legacy) -->
        <?php if ($swipesimpleEmbed): ?>
            <div class="payment-embed fade-in">
                <h3 style="margin-bottom: var(--space-lg);">Pay Online</h3>
                <?php echo $swipesimpleEmbed; ?>
            </div>
        <?php elseif ($swipesimpleLink): ?>
            <div class="payment-embed fade-in">
                <h3 style="margin-bottom: var(--space-md);">Pay Online</h3>
                <p style="color: var(--color-gray-600); margin-bottom: var(--space-lg);">Click below to make a secure online payment.</p>
                <a href="<?php echo e($swipesimpleLink); ?>" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                    <i class="fas fa-external-link-alt"></i> Make a Payment
                </a>
            </div>
        <?php endif; ?>

        <?php if (empty($enabledProviders) && !$swipesimpleLink && !$swipesimpleEmbed): ?>
            <div class="payment-embed fade-in">
                <h3 style="margin-bottom: var(--space-md);">Call to Pay</h3>
                <p style="color: var(--color-gray-600); margin-bottom: var(--space-lg);">
                    Give us a call to make a payment over the phone. We're happy to help!
                </p>
                <?php if ($companyPhone): ?>
                    <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-phone"></i> <?php echo e($companyPhone); ?>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Security Note -->
        <div class="text-center fade-in" style="margin-top: var(--space-3xl);">
            <p style="color: var(--color-gray-500); font-size: var(--text-sm);">
                <i class="fas fa-lock" style="color: var(--color-primary);"></i>
                All transactions are secured with industry-standard encryption. Your payment information is never stored on our servers.
            </p>
        </div>
    </div>
</section>
