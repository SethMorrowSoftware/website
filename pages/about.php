<?php
/**
 * About Page — Wuzabus Off-Grid Electrical
 */

$hero = getHero('about');
$aboutText = getSetting('about_text');
$serviceArea = getSetting('service_area');
$_catalogEnabled = isFeatureEnabled('catalog');
$_contactFormEnabled = isFeatureEnabled('contact_form');
$_orderInquiryEnabled = isFeatureEnabled('order_inquiry');
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php else: ?>
        <div class="hero-image" style="background-image: url('<?php echo e(url('uploads/images/wuzabus/bus-workshop-build.jpg')); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'About Wuzabus'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Built From Experience. Powered by Passion.'); ?></p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">About</span>
    </div>
</div>

<!-- About Content -->
<section class="section">
    <div class="container">
        <div class="about-content">
            <div class="about-image fade-in">
                <img src="<?php echo e(url('uploads/images/wuzabus/bus-exterior-solar.jpg')); ?>" alt="Wuzabus converted bus with solar panels">
            </div>
            <div class="about-text fade-in">
                <h2>The Story</h2>
                <?php if ($aboutText): ?>
                    <p><?php echo nl2br(e($aboutText)); ?></p>
                <?php else: ?>
                    <p>Wuzabus designs and installs clean, reliable off-grid electrical systems for buses, vans, box trucks, and mobile stage builds.</p>
                    <p>From lithium battery banks to full solar installs, inverters, shore power, and system upgrades — every build is done safely and correctly the first time. No shortcuts. No guesswork. Just stress-free power for life on the road.</p>
                <?php endif; ?>
                <p>Living the off-grid life means your electrical system better be built right. Out here there's no second chance if your power setup fails.</p>
            </div>
        </div>
    </div>
</section>

<!-- What I Work With -->
<section class="section section-light">
    <div class="container">
        <div class="section-header fade-in">
            <h2>What I Work With</h2>
        </div>

        <div class="grid grid-3" style="gap: var(--space-xl);">
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/battery-bank-victron.jpg')); ?>" alt="Victron battery system with MPPT controllers" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>Victron Energy</h3>
                    <p>MultiPlus inverter/chargers, SmartSolar MPPT controllers, Lynx distributors, and Cerbo GX monitoring.</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/full-system-sok-batteries.jpg')); ?>" alt="SOK lithium batteries with Victron system" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>SOK & LiFePO4 Batteries</h3>
                    <p>SOK, Battle Born, and other quality lithium iron phosphate batteries. Sized right for your build.</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/desert-inverter-install.jpg')); ?>" alt="EG4 6000XP inverter installation" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>EG4 & More</h3>
                    <p>EG4 6000XP inverters, breaker panels, shore power inlets, and everything needed for a complete system.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Mission -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>The Approach</h2>
        </div>
        <div style="max-width: 800px; margin: 0 auto; text-align: center;" class="fade-in">
            <p style="font-size: var(--text-lg); color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                Every system I build starts with understanding how you actually use your rig. There's no one-size-fits-all when it comes to off-grid power. I design around your real needs — your appliances, your travel style, your climate — and build a system that just works.
            </p>
        </div>

        <div class="features-grid features-grid-light mt-3">
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-solar-panel"></i></div>
                <h4>Solar Done Right</h4>
                <p>Properly mounted panels with MPPT controllers sized to your battery bank. Maximum harvest, minimum headaches.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
                <h4>Safety First</h4>
                <p>Every circuit fused. Every connection torqued. Proper wire gauge throughout. Your safety isn't optional.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-tools"></i></div>
                <h4>Quality Components</h4>
                <p>Victron, SOK, Blue Sea, and other brands that hold up. No cheap Amazon mystery brands in your electrical system.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-road"></i></div>
                <h4>Road Tested</h4>
                <p>I live this life. Every system I build is something I'd trust in my own rig, out in the middle of nowhere.</p>
            </div>
        </div>
    </div>
</section>

<?php if ($serviceArea): ?>
<!-- Service Area -->
<section class="section section-dark">
    <div class="container text-center">
        <h2 style="color: var(--color-white);" class="fade-in">Service Area</h2>
        <p style="font-size: var(--text-lg); margin: var(--space-lg) auto; max-width: 700px; color: var(--color-gray-300);" class="fade-in">
            <?php echo e($serviceArea); ?>
        </p>
        <?php if ($_orderInquiryEnabled): ?>
            <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg fade-in">Get a Quote</a>
        <?php elseif ($_contactFormEnabled): ?>
            <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-primary btn-lg fade-in">Get in Touch</a>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>
