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
?>

</main>

<!-- CTA Banner -->
<section class="cta-banner">
    <div class="container">
        <h2>Ready to Get Started?</h2>
        <p>Give us a call or submit an order inquiry — we're here to help!</p>
        <span class="phone-number">
            <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>">
                <i class="fas fa-phone"></i> <?php echo e($companyPhone); ?>
            </a>
        </span>
        <div class="btn-group">
            <a href="/index.php?page=order" class="btn btn-primary btn-lg">Request a Quote</a>
            <a href="/index.php?page=contact" class="btn btn-outline btn-lg">Contact Us</a>
        </div>
    </div>
</section>

<!-- Footer -->
<footer class="site-footer">
    <div class="container">
        <div class="footer-grid">
            <div class="footer-about">
                <div class="footer-logo">
                    <i class="fas fa-recycle"></i> Hudson Valley Supply &amp; Recycling
                </div>
                <p><?php echo e(getSetting('tagline')); ?></p>
                <div style="margin-top: var(--space-lg); display: flex; gap: var(--space-md);">
                    <?php if ($facebookUrl): ?>
                        <a href="<?php echo e($facebookUrl); ?>" target="_blank" rel="noopener" style="color: var(--color-gray-400); font-size: 1.25rem;">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($instagramUrl): ?>
                        <a href="<?php echo e($instagramUrl); ?>" target="_blank" rel="noopener" style="color: var(--color-gray-400); font-size: 1.25rem;">
                            <i class="fab fa-instagram"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($twitterUrl): ?>
                        <a href="<?php echo e($twitterUrl); ?>" target="_blank" rel="noopener" style="color: var(--color-gray-400); font-size: 1.25rem;">
                            <i class="fab fa-twitter"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer-col">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="/">Home</a></li>
                    <li><a href="/index.php?page=about">About Us</a></li>
                    <li><a href="/index.php?page=containers">Containers</a></li>
                    <li><a href="/index.php?page=materials">Materials</a></li>
                    <li><a href="/index.php?page=trucking">Trucking</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Services</h4>
                <ul>
                    <li><a href="/index.php?page=containers">Roll Off Containers</a></li>
                    <li><a href="/index.php?page=materials">Mulch &amp; Stone</a></li>
                    <li><a href="/index.php?page=materials">Topsoil &amp; Sand</a></li>
                    <li><a href="/index.php?page=trucking">Trucking Services</a></li>
                    <li><a href="/index.php?page=order">Order Inquiry</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4>Contact Info</h4>
                <?php if ($companyPhone): ?>
                    <div class="footer-contact-item">
                        <i class="fas fa-phone"></i>
                        <div><a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>" style="color: var(--color-gray-400);"><?php echo e($companyPhone); ?></a></div>
                    </div>
                <?php endif; ?>
                <?php if ($companyEmail): ?>
                    <div class="footer-contact-item">
                        <i class="fas fa-envelope"></i>
                        <div><a href="mailto:<?php echo e($companyEmail); ?>" style="color: var(--color-gray-400);"><?php echo e($companyEmail); ?></a></div>
                    </div>
                <?php endif; ?>
                <?php if ($companyAddress): ?>
                    <div class="footer-contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div><?php echo e($companyAddress); ?></div>
                    </div>
                <?php endif; ?>
                <?php if ($businessHours): ?>
                    <div class="footer-contact-item">
                        <i class="fas fa-clock"></i>
                        <div><?php echo nl2br(e($businessHours)); ?></div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="footer-bottom">
            <p><?php echo $footerText; ?></p>
        </div>
    </div>
</footer>

<script src="/assets/js/main.js"></script>
<script src="/assets/js/forms.js"></script>

</body>
</html>
