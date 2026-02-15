<?php
/**
 * Site Footer
 */

$companyName = getSetting('company_name', SITE_NAME);
$companyPhone = getSetting('company_phone');
$companyEmail = getSetting('company_email');
$companyAddress = getSetting('company_address');
$businessHours = getSetting('business_hours');
$footerText = getSetting('footer_text', '&copy; ' . date('Y') . ' ' . $companyName . '. All Rights Reserved.');
$facebookUrl = getSetting('facebook_url');
$instagramUrl = getSetting('instagram_url');
$twitterUrl = getSetting('twitter_url');

$_catalogEnabled = isFeatureEnabled('catalog');
$_orderInquiryEnabled = isFeatureEnabled('order_inquiry');
$_contactFormEnabled = isFeatureEnabled('contact_form');
$_aboutEnabled = isFeatureEnabled('about_page');
$_showAddress = isFeatureEnabled('address');
$_showHours = isFeatureEnabled('business_hours');
$_showPhone = isFeatureEnabled('phone_header');
$_showEmail = isFeatureEnabled('email_header');
$_cartEnabled = isFeatureEnabled('cart');

$footerCategories = $_catalogEnabled ? getCategories() : [];

$ctaHeading = getSetting('cta_heading', 'Ready to Get Started?');
$ctaSubtext = getSetting('cta_subtext');
if (!$ctaSubtext) {
    // Auto-generate CTA subtext based on enabled features
    $parts = [];
    if ($_showPhone && $companyPhone) $parts[] = 'give us a call';
    if ($_orderInquiryEnabled) $parts[] = 'submit an inquiry';
    if ($_contactFormEnabled) $parts[] = 'send us a message';
    if ($_cartEnabled && $_catalogEnabled) $parts[] = 'shop our catalog';
    $ctaSubtext = !empty($parts) ? ucfirst(implode(', ', array_slice($parts, 0, 2))) . " \u{2014} we're here to help!" : "We're here to help!";
}

$catalogPageTitle = getSetting('catalog_page_title', 'Our Catalog');
$orderInquiryTitle = getSetting('order_inquiry_title', 'Order Inquiry');
?>

</main>

<!-- CTA Banner -->
<section class="cta-banner">
    <div class="container">
        <h2><?php echo e($ctaHeading); ?></h2>
        <p><?php echo e($ctaSubtext); ?></p>
        <?php if ($_showPhone && $companyPhone): ?>
            <span class="phone-number">
                <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>">
                    <i class="fas fa-phone"></i> <?php echo e($companyPhone); ?>
                </a>
            </span>
        <?php endif; ?>
        <div class="btn-group">
            <?php if ($_orderInquiryEnabled): ?>
                <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg"><?php echo e($orderInquiryTitle === 'Order Inquiry' ? 'Request a Quote' : $orderInquiryTitle); ?></a>
            <?php elseif ($_cartEnabled && $_catalogEnabled): ?>
                <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-primary btn-lg">Shop Now</a>
            <?php endif; ?>
            <?php if ($_contactFormEnabled): ?>
                <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline btn-lg">Contact Us</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-logo">
                    <i class="fas fa-building"></i> <?php echo e($companyName); ?>
                </div>
                <p><?php echo e(getSetting('tagline')); ?></p>
                <div class="footer-social-links">
                    <?php if ($facebookUrl): ?>
                        <a href="<?php echo e($facebookUrl); ?>" target="_blank" rel="noopener" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($instagramUrl): ?>
                        <a href="<?php echo e($instagramUrl); ?>" target="_blank" rel="noopener" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($twitterUrl): ?>
                        <a href="<?php echo e($twitterUrl); ?>" target="_blank" rel="noopener" aria-label="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo url('/'); ?>">Home</a></li>
                    <?php if ($_aboutEnabled): ?>
                        <li><a href="<?php echo url('index.php?page=about'); ?>">About Us</a></li>
                    <?php endif; ?>
                    <?php if ($_catalogEnabled): ?>
                        <li><a href="<?php echo url('index.php?page=catalog'); ?>"><?php echo e($catalogPageTitle); ?></a></li>
                    <?php endif; ?>
                    <?php if ($_contactFormEnabled): ?>
                        <li><a href="<?php echo url('index.php?page=contact'); ?>">Contact</a></li>
                    <?php endif; ?>
                    <?php if ($_orderInquiryEnabled): ?>
                        <li><a href="<?php echo url('index.php?page=order'); ?>"><?php echo e($orderInquiryTitle); ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <?php if ($_catalogEnabled && !empty($footerCategories)): ?>
            <div class="footer-col">
                <h4><?php echo e($catalogPageTitle); ?></h4>
                <ul>
                    <?php foreach ($footerCategories as $fCat): ?>
                        <li><a href="<?php echo url('index.php?page=catalog'); ?>#<?php echo e($fCat['slug']); ?>"><?php echo e($fCat['name']); ?></a></li>
                    <?php endforeach; ?>
                    <?php if ($_orderInquiryEnabled): ?>
                        <li><a href="<?php echo url('index.php?page=order'); ?>">Request a Quote</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="footer-col">
                <h4>Contact Info</h4>
                <?php if ($_showPhone && $companyPhone): ?>
                    <div class="footer-contact-item">
                        <i class="fas fa-phone"></i>
                        <div><a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>"><?php echo e($companyPhone); ?></a></div>
                    </div>
                <?php endif; ?>
                <?php if ($_showEmail && $companyEmail): ?>
                    <div class="footer-contact-item">
                        <i class="fas fa-envelope"></i>
                        <div><a href="mailto:<?php echo e($companyEmail); ?>"><?php echo e($companyEmail); ?></a></div>
                    </div>
                <?php endif; ?>
                <?php if ($_showAddress && $companyAddress): ?>
                    <div class="footer-contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div><?php echo e($companyAddress); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($_showHours && $businessHours): ?>
                    <div class="footer-contact-item">
                        <i class="fas fa-clock"></i>
                        <div><?php echo nl2br(e($businessHours)); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-bottom">
            <p><?php echo sanitizeHtml($footerText); ?></p>
        </div>
    </div>
</footer>

<script src="<?php echo asset('js/main.js'); ?>"></script>
<script src="<?php echo asset('js/forms.js'); ?>"></script>
<script src="<?php echo asset('js/lightbox.js'); ?>"></script>
<script src="<?php echo asset('js/ajax-cart.js'); ?>"></script>

<!-- Mobile Bottom Nav -->
<nav class="mobile-bottom-nav" id="mobileBottomNav">
    <a href="<?php echo url('/'); ?>" class="mobile-nav-item <?php echo ($currentPage ?? 'home') === 'home' ? 'active' : ''; ?>">
        <i class="fas fa-home"></i><span>Home</span>
    </a>
    <?php if (isFeatureEnabled('search')): ?>
    <a href="<?php echo url('index.php?page=search'); ?>" class="mobile-nav-item <?php echo ($currentPage ?? '') === 'search' ? 'active' : ''; ?>">
        <i class="fas fa-search"></i><span>Search</span>
    </a>
    <?php endif; ?>
    <?php if (isFeatureEnabled('cart')): ?>
    <a href="<?php echo url('index.php?page=cart'); ?>" class="mobile-nav-item <?php echo ($currentPage ?? '') === 'cart' ? 'active' : ''; ?>">
        <i class="fas fa-shopping-cart"></i><span>Cart<?php $mc = getCartCount(); if ($mc > 0): ?> (<?php echo $mc; ?>)<?php endif; ?></span>
    </a>
    <?php endif; ?>
    <?php if (isFeatureEnabled('customer_accounts')): ?>
    <a href="<?php echo url(isCustomerLoggedIn() ? 'index.php?page=account' : 'index.php?page=login'); ?>" class="mobile-nav-item <?php echo in_array($currentPage ?? '', ['account','login','register']) ? 'active' : ''; ?>">
        <i class="fas fa-user"></i><span><?php echo isCustomerLoggedIn() ? 'Account' : 'Sign In'; ?></span>
    </a>
    <?php endif; ?>
</nav>

</body>
</html>
