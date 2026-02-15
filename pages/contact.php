<?php
/**
 * Contact Page
 */

$hero = getHero('contact');
$companyPhone = getSetting('company_phone');
$companyEmail = getSetting('company_email');
$companyAddress = getSetting('company_address');
$businessHours = getSetting('business_hours');
$mapsEmbed = getSetting('google_maps_embed');
$csrfToken = generateCSRFToken();

$_showPhone = isFeatureEnabled('phone_header');
$_showEmail = isFeatureEnabled('email_header');
$_showAddress = isFeatureEnabled('address');
$_showHours = isFeatureEnabled('business_hours');
$_showMap = isFeatureEnabled('map');
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Contact Us'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'We\'re Here to Help — Reach Out Today'); ?></p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Contact Us</span>
    </div>
</div>

<!-- Contact Content -->
<section class="section">
    <div class="container">
        <div class="contact-grid">
            <!-- Contact Form -->
            <div class="fade-in">
                <h2 style="margin-bottom: var(--space-xl);">Send Us a Message</h2>
                <form method="POST" action="<?php echo url('index.php'); ?>" id="contactForm">
                    <input type="hidden" name="action" value="contact">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="form-group">
                        <label for="name">Full Name <span class="required">*</span></label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="Your full name">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="your@email.com">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="(555) 000-0000">
                    </div>

                    <div class="form-group">
                        <label for="message">Message <span class="required">*</span></label>
                        <textarea id="message" name="message" class="form-control" required placeholder="How can we help you?" rows="5"></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
                </form>
            </div>

            <!-- Contact Info -->
            <div class="fade-in">
                <h2 style="margin-bottom: var(--space-xl);">Get In Touch</h2>

                <?php if ($_showPhone && $companyPhone): ?>
                    <div class="contact-info-card">
                        <div class="icon"><i class="fas fa-phone"></i></div>
                        <div>
                            <h4>Phone</h4>
                            <p><a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>"><?php echo e($companyPhone); ?></a></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($_showEmail && $companyEmail): ?>
                    <div class="contact-info-card">
                        <div class="icon"><i class="fas fa-envelope"></i></div>
                        <div>
                            <h4>Email</h4>
                            <p><a href="mailto:<?php echo e($companyEmail); ?>"><?php echo e($companyEmail); ?></a></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($_showAddress && $companyAddress): ?>
                    <div class="contact-info-card">
                        <div class="icon"><i class="fas fa-map-marker-alt"></i></div>
                        <div>
                            <h4>Address</h4>
                            <p><?php echo e($companyAddress); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($_showHours && $businessHours): ?>
                    <div class="contact-info-card">
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <div>
                            <h4>Business Hours</h4>
                            <p><?php echo nl2br(e($businessHours)); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($_showMap && $mapsEmbed): ?>
                    <div class="map-container">
                        <iframe src="<?php echo e($mapsEmbed); ?>" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Our Location"></iframe>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
