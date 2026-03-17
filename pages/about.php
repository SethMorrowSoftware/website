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

$wuzabusAboutPhoto = '';
$wuzabusAboutPatterns = [
    __DIR__ . '/../wuzabus photos/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}',
    __DIR__ . '/../wuzabus_photos/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}',
    __DIR__ . '/../uploads/images/wuzabus photos/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}',
    __DIR__ . '/../uploads/images/wuzabus_photos/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}',
];
foreach ($wuzabusAboutPatterns as $pattern) {
    $matches = glob($pattern, GLOB_BRACE) ?: [];
    if (!empty($matches)) {
        sort($matches);
        $relative = str_replace(realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR, '', $matches[0]);
        $parts = array_map('rawurlencode', explode(DIRECTORY_SEPARATOR, $relative));
        $wuzabusAboutPhoto = url(implode('/', $parts));
        break;
    }
}
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'About Wuzabus'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Craftsmanship for Life on the Road'); ?></p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">About Wuzabus</span>
    </div>
</div>

<!-- About Content -->
<section class="section">
    <div class="container">
        <div class="about-content">
            <div class="about-image fade-in">
                <?php if ($wuzabusAboutPhoto): ?>
                    <img src="<?php echo e($wuzabusAboutPhoto); ?>" alt="Wuzabus conversion interior" style="width: 100%; border-radius: var(--radius-lg); box-shadow: var(--shadow-xl); max-height: 460px; object-fit: cover;">
                <?php else: ?>
                    <div class="placeholder-banner" style="background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark));">
                        <i class="fas fa-building"></i>
                    </div>
                <?php endif; ?>
            </div>
            <div class="about-text fade-in">
                <h2>Our Build Philosophy</h2>
                <p><?php echo nl2br(e($aboutText)); ?></p>
                <p>Every Wuzabus conversion balances comfort, storage, durability, and serviceability—so your rig works in the real world, not just in photos.</p>
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
                <div class="label">Categories</div>
            </div>
            <div class="stat-item">
                <div class="number"><?php echo $totalProducts; ?>+</div>
                <div class="label">Products &amp; Services</div>
            </div>
            <div class="stat-item">
                <div class="number">100%</div>
                <div class="label">Customer Satisfaction</div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Mission & Values -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Our Mission at Wuzabus</h2>
        </div>
        <div style="max-width: 800px; margin: 0 auto; text-align: center;" class="fade-in">
            <p style="font-size: var(--text-lg); color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                To transform buses, trucks, and RVs into livable spaces that feel like home—built with honest craftsmanship, dependable systems, and thoughtful design.
            </p>
        </div>

        <div class="features-grid features-grid-light mt-3">
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-leaf"></i></div>
                <h4>Smart Energy Design</h4>
                <p>Efficient power systems, solar integration, and energy-aware planning for longer off-grid stays.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
                <h4>Professional Craftsmanship</h4>
                <p>Precision cabinetry, finish carpentry, and systems integration completed with workshop discipline.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-users"></i></div>
                <h4>Client-First Process</h4>
                <p>Collaborative planning, clear communication, and practical recommendations at every stage.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-thumbs-up"></i></div>
                <h4>Road-Tested Reliability</h4>
                <p>Builds designed to handle everyday travel demands with serviceable, maintainable components.</p>
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
