<?php
/**
 * Trucking Services Page
 */

$hero = getHero('trucking');
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Trucking Services'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Reliable Delivery & Hauling Throughout the Hudson Valley'); ?></p>
        <?php if ($hero && $hero['cta_text']): ?>
            <a href="<?php echo e(url($hero['cta_link'])); ?>" class="btn btn-primary btn-lg"><?php echo e($hero['cta_text']); ?></a>
        <?php endif; ?>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Trucking Services</span>
    </div>
</div>

<!-- Services Overview -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Our Trucking Services</h2>
            <p>Professional hauling and delivery services you can count on</p>
        </div>

        <div class="trucking-features fade-in">
            <div>
                <h3 style="margin-bottom: var(--space-lg);">What We Deliver</h3>
                <ul class="feature-list">
                    <li>All types of mulch (hardwood, dyed, playground)</li>
                    <li>Stone &amp; gravel (bluestone, river rock, pea gravel, crushed stone)</li>
                    <li>Topsoil (screened, unscreened, garden mix)</li>
                    <li>Sand (mason, concrete, fill)</li>
                    <li>Bulk road salt &amp; treated salt</li>
                    <li>Recycled concrete &amp; aggregates</li>
                    <li>Roll-off container delivery &amp; pickup</li>
                </ul>
            </div>
            <div>
                <div style="width:100%; height:350px; background: linear-gradient(135deg, var(--color-primary), var(--color-primary-dark)); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-truck-moving" style="font-size: 6rem; color: rgba(255,255,255,0.2);"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services Cards -->
<section class="section section-light">
    <div class="container">
        <div class="grid grid-3">
            <div class="service-card fade-in">
                <div class="icon">
                    <i class="fas fa-truck-loading"></i>
                </div>
                <h3>Material Delivery</h3>
                <p>Bulk delivery of mulch, stone, topsoil, sand, and salt directly to your job site or residence. Available in various truck sizes to match your order.</p>
            </div>

            <div class="service-card fade-in">
                <div class="icon">
                    <i class="fas fa-dumpster"></i>
                </div>
                <h3>Container Hauling</h3>
                <p>Roll-off container delivery and pickup service. We drop it off, you fill it up, and we haul it away. Simple, reliable, and affordable.</p>
            </div>

            <div class="service-card fade-in">
                <div class="icon">
                    <i class="fas fa-hard-hat"></i>
                </div>
                <h3>Job Site Services</h3>
                <p>Construction site supply delivery, debris removal, and material hauling for contractors and builders throughout the region.</p>
            </div>
        </div>
    </div>
</section>

<!-- Service Area -->
<section class="section">
    <div class="container">
        <div class="about-content">
            <div class="fade-in">
                <h2>Our Service Area</h2>
                <p style="color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                    <?php echo e(getSetting('service_area')); ?>
                </p>
                <p style="color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                    Our fleet of well-maintained trucks ensures your materials arrive safely and on time. We work with both residential homeowners and commercial contractors to provide flexible scheduling that fits your timeline.
                </p>

                <div class="stats-grid mt-2">
                    <div class="stat-item">
                        <div class="number"><i class="fas fa-map-marked-alt" style="font-size: var(--text-3xl);"></i></div>
                        <div class="label">Multiple Counties Served</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><i class="fas fa-clock" style="font-size: var(--text-3xl);"></i></div>
                        <div class="label">Same-Day Available</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><i class="fas fa-truck" style="font-size: var(--text-3xl);"></i></div>
                        <div class="label">Professional Fleet</div>
                    </div>
                </div>
            </div>
            <div class="about-image fade-in">
                <div style="width:100%; height:400px; background: linear-gradient(135deg, #2d5a4e, #1B4D3E); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-map-marked-alt" style="font-size: 6rem; color: rgba(255,255,255,0.15);"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section section-primary">
    <div class="container text-center">
        <h2 style="color: var(--color-white);" class="fade-in">Need a Delivery?</h2>
        <p style="font-size: var(--text-lg); opacity: 0.9; margin-bottom: var(--space-xl);" class="fade-in">
            Contact us today for a free quote on material delivery or trucking services.
        </p>
        <div class="btn-group fade-in">
            <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg">Request a Quote</a>
            <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline btn-lg">Contact Us</a>
        </div>
    </div>
</section>
