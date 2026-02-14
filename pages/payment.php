<?php
/**
 * Payment Page — SwipeSimple Integration
 */

$hero = getHero('payment') ?: ['title' => 'Make a Payment', 'subtitle' => 'Secure, convenient payment options', 'background_image' => ''];
$swipesimpleLink = getSetting('swipesimple_link');
$swipesimpleEmbed = getSetting('swipesimple_embed');
$companyPhone = getSetting('company_phone');
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title']); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Secure, convenient payment options'); ?></p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="/">Home</a>
        <span>/</span>
        <span class="current">Payment</span>
    </div>
</div>

<!-- Payment Content -->
<section class="section">
    <div class="container" style="max-width: var(--container-lg);">
        <div class="section-header fade-in">
            <h2>Payment Options</h2>
            <p>We accept multiple payment methods for your convenience</p>
        </div>

        <!-- Payment Methods -->
        <div class="payment-methods fade-in">
            <div class="payment-method">
                <div class="icon"><i class="fas fa-credit-card"></i></div>
                <h4>Credit / Debit Card</h4>
                <p style="font-size: var(--text-sm); color: var(--color-gray-600);">Visa, Mastercard, Amex, Discover</p>
            </div>
            <div class="payment-method">
                <div class="icon"><i class="fas fa-money-check-alt"></i></div>
                <h4>Check</h4>
                <p style="font-size: var(--text-sm); color: var(--color-gray-600);">Business and personal checks accepted</p>
            </div>
            <div class="payment-method">
                <div class="icon"><i class="fas fa-money-bill-wave"></i></div>
                <h4>Cash</h4>
                <p style="font-size: var(--text-sm); color: var(--color-gray-600);">Cash accepted at our location</p>
            </div>
        </div>

        <!-- SwipeSimple Integration -->
        <?php if ($swipesimpleLink || $swipesimpleEmbed): ?>
            <div class="fade-in">
                <h3 style="text-align: center; margin-bottom: var(--space-xl);">Pay Online</h3>

                <?php if ($swipesimpleEmbed): ?>
                    <div class="payment-embed">
                        <?php echo $swipesimpleEmbed; // Admin-controlled HTML embed ?>
                    </div>
                <?php elseif ($swipesimpleLink): ?>
                    <div class="payment-embed">
                        <i class="fas fa-lock" style="font-size: var(--text-4xl); color: var(--color-primary); margin-bottom: var(--space-lg);"></i>
                        <h4 style="margin-bottom: var(--space-md);">Secure Online Payment</h4>
                        <p style="color: var(--color-gray-600); margin-bottom: var(--space-xl);">
                            Click the button below to make a secure payment through our SwipeSimple payment portal.
                        </p>
                        <a href="<?php echo e($swipesimpleLink); ?>" target="_blank" rel="noopener" class="btn btn-primary btn-lg">
                            <i class="fas fa-lock"></i> Pay Now — Secure Portal
                        </a>
                        <p style="font-size: var(--text-sm); color: var(--color-gray-400); margin-top: var(--space-lg);">
                            <i class="fas fa-shield-alt"></i> Your payment information is processed securely by SwipeSimple.
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="payment-embed fade-in">
                <i class="fas fa-phone-alt" style="font-size: var(--text-4xl); color: var(--color-primary); margin-bottom: var(--space-lg);"></i>
                <h4 style="margin-bottom: var(--space-md);">Pay by Phone</h4>
                <p style="color: var(--color-gray-600); margin-bottom: var(--space-xl);">
                    To make a payment, please call us and we'll process your payment over the phone securely.
                </p>
                <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-phone"></i> Call <?php echo e($companyPhone); ?>
                </a>
            </div>
        <?php endif; ?>

        <!-- Payment Info -->
        <div style="max-width: 600px; margin: var(--space-3xl) auto 0; text-align: center;" class="fade-in">
            <h4 style="margin-bottom: var(--space-md);">Payment Questions?</h4>
            <p style="color: var(--color-gray-600); font-size: var(--text-sm);">
                If you have questions about your invoice or payment, please don't hesitate to
                <a href="/index.php?page=contact">contact us</a> or call
                <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>"><?php echo e($companyPhone); ?></a>.
            </p>
        </div>
    </div>
</section>
