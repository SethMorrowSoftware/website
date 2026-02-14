<?php
/**
 * About Page
 */

$hero = getHero('about');
$aboutText = getSetting('about_text');
$serviceArea = getSetting('service_area');
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'About Us'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Locally Owned & Operated — Serving the Hudson Valley'); ?></p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">About Us</span>
    </div>
</div>

<!-- About Content -->
<section class="section">
    <div class="container">
        <div class="about-content">
            <div class="about-image fade-in">
                <div class="placeholder-banner" style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));">
                    <i class="fas fa-recycle"></i>
                </div>
            </div>
            <div class="about-text fade-in">
                <h2>Our Story</h2>
                <p><?php echo nl2br(e($aboutText)); ?></p>
                <p>We take pride in offering top-quality products at competitive prices, backed by the kind of personal service that only a local business can provide. Whether you need a roll-off container for a weekend cleanout, bulk mulch for a landscaping project, or stone delivered to a commercial job site, we're here to help.</p>
            </div>
        </div>
    </div>
</section>

<!-- Stats -->
<section class="section section-light">
    <div class="container">
        <div class="stats-grid fade-in">
            <div class="stat-item">
                <div class="number">5+</div>
                <div class="label">Container Sizes</div>
            </div>
            <div class="stat-item">
                <div class="number">20+</div>
                <div class="label">Products Available</div>
            </div>
            <div class="stat-item">
                <div class="number">100%</div>
                <div class="label">Customer Satisfaction</div>
            </div>
        </div>
    </div>
</section>

<!-- Mission & Values -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Our Mission</h2>
        </div>
        <div style="max-width: 800px; margin: 0 auto; text-align: center;" class="fade-in">
            <p style="font-size: var(--text-lg); color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                To provide the Hudson Valley community with reliable, high-quality supply and recycling services at fair prices. We are committed to environmental responsibility, exceptional customer service, and supporting local growth through dependable partnerships.
            </p>
        </div>

        <div class="features-grid features-grid-light mt-3">
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-leaf"></i></div>
                <h4>Eco-Friendly</h4>
                <p>Committed to sustainable practices and responsible recycling.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
                <h4>Licensed & Insured</h4>
                <p>Fully licensed and insured for your peace of mind.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h4>Community First</h4>
                <p>Proud to serve our neighbors throughout the Hudson Valley.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-truck"></i></div>
                <h4>Reliable Service</h4>
                <p>On-time delivery and pickup you can always depend on.</p>
            </div>
        </div>
    </div>
</section>

<!-- Service Area -->
<section class="section section-primary">
    <div class="container text-center">
        <h2 style="color: var(--color-white);" class="fade-in">Service Area</h2>
        <p style="font-size: var(--text-lg); margin: var(--space-lg) auto; max-width: 700px; opacity: 0.9;" class="fade-in">
            <?php echo e($serviceArea); ?>
        </p>
        <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-primary btn-lg fade-in">Get in Touch</a>
    </div>
</section>
