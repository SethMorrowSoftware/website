<?php
/**
 * About Page
 */

$hero = getHero('about');
$aboutText = getSetting('about_text');
$serviceArea = getSetting('service_area');
$_catalogEnabled = isFeatureEnabled('catalog');
$_contactFormEnabled = isFeatureEnabled('contact_form');
$categories = $_catalogEnabled ? getCategories() : [];
$totalProducts = $_catalogEnabled ? getDB()->query('SELECT COUNT(*) FROM products WHERE is_visible = 1 AND deleted_at IS NULL')->fetchColumn() : 0;
?>

<?php $defaultHeroImage = 'wuzabus_photos/20201231_153022.jpg'; ?>
<!-- Hero -->
<section class="hero">
    <div class="hero-image" style="background-image: url('<?php echo e(getImageUrl($hero['background_image'] ?? $defaultHeroImage)); ?>');"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'About Us'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Locally Owned & Operated — Serving Our Community'); ?></p>
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
                <img src="<?php echo url('wuzabus_photos/20200615_134615_fx.jpg'); ?>" alt="TheWuzaBus - Custom Bus Conversion" style="width:100%; border-radius: var(--radius-lg); object-fit: cover;">
            </div>
            <div class="about-text fade-in">
                <h2>Our Story</h2>
                <p><?php echo nl2br(e($aboutText)); ?></p>
                <p>What started as a personal project — converting our own bus into a home — quickly grew into a passion for helping others achieve the freedom of mobile living. We have been building, wiring, and crafting custom bus conversions since 2020, and every project we take on gets the same care and attention as if it were our own home.</p>
            </div>
        </div>
    </div>
</section>

<?php if ($_catalogEnabled && ($totalProducts > 0 || !empty($categories))): ?>
<!-- Stats -->
<section class="section section-light">
    <div class="container">
        <div class="stats-grid fade-in">
            <div class="stat-item">
                <div class="number"><?php echo count($categories); ?>+</div>
                <div class="label">Service Categories</div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $totalProducts; ?>+</div>
                <div class="label">Services &amp; Packages</div>
            </div>
            <div class="stat-item">
                <div class="number">100%</div>
                <div class="label">Client Satisfaction</div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Mission & Values -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Our Mission</h2>
        </div>
        <div style="max-width: 800px; margin: 0 auto; text-align: center;" class="fade-in">
            <p style="font-size: var(--text-lg); color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                To empower people to live freely on their own terms by building safe, beautiful, and reliable bus conversions. We believe everyone deserves a home that moves with them — built with quality materials, expert craftsmanship, and systems you can depend on wherever the road takes you.
            </p>
        </div>

        <div class="features-grid features-grid-light mt-3">
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-solar-panel"></i></div>
                <h4>Off-Grid Ready</h4>
                <p>Every build is designed for true energy independence with professional-grade solar and battery systems.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-tools"></i></div>
                <h4>Quality Materials</h4>
                <p>We use premium components — Victron electronics, SOK batteries, real wood finishes, and marine-grade hardware.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-heart"></i></div>
                <h4>Passion-Driven</h4>
                <p>We live the bus life ourselves. Every build benefits from real-world experience and genuine love for the lifestyle.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-handshake"></i></div>
                <h4>Client-Focused</h4>
                <p>Your build, your vision. We work closely with every client to create a conversion that fits their unique needs and dreams.</p>
            </div>
        </div>
    </div>
</section>

<?php if ($serviceArea): ?>
<!-- Service Area -->
<section class="section section-primary">
    <div class="container text-center">
        <h2 style="color: var(--color-white);" class="fade-in">Service Area</h2>
        <p style="font-size: var(--text-lg); margin: var(--space-lg) auto; max-width: 700px; opacity: 0.9;" class="fade-in">
            <?php echo e($serviceArea); ?>
        </p>
        <?php if ($_contactFormEnabled): ?>
            <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-primary btn-lg fade-in">Get in Touch</a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
