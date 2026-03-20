<?php
/**
 * Home Page — Wuzabus Off-Grid Electrical
 */

$hero = getHero('home');

$_catalogEnabled = isFeatureEnabled('catalog');
$_cartEnabled = isFeatureEnabled('cart');
$_orderInquiryEnabled = isFeatureEnabled('order_inquiry');
$_contactFormEnabled = isFeatureEnabled('contact_form');
$_testimonialsEnabled = isFeatureEnabled('testimonials');

$categories = $_catalogEnabled ? getCategories() : [];
$testimonials = $_testimonialsEnabled ? getTestimonials() : [];
$featuredProducts = $_catalogEnabled ? getFeaturedProducts(6) : [];

$offeringsHeading = getSetting('homepage_offerings_heading', 'What We Build');
$offeringsSubtext = getSetting('homepage_offerings_subtext', 'Off-grid electrical systems built right the first time');
$featuredHeading = getSetting('homepage_featured_heading', 'Our Services');
$featuredSubtext = getSetting('homepage_featured_subtext', 'Everything you need to go off-grid with confidence');
$orderInquiryTitle = getSetting('order_inquiry_title', 'Get a Quote');
?>

<!-- Hero Section -->
<section class="hero hero-home">
    <?php if ($hero && $hero['background_video']): ?>
        <video class="hero-video" autoplay muted loop playsinline poster="<?php echo e($hero['background_image'] ?? ''); ?>">
            <source src="<?php echo e($hero['background_video']); ?>" type="video/mp4">
        </video>
    <?php endif; ?>
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php else: ?>
        <div class="hero-image" style="background-image: url('<?php echo e(url('uploads/images/wuzabus/bus-exterior-solar.jpg')); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay" style="<?php echo $hero ? 'opacity:' . ($hero['overlay_opacity'] ?? 0.5) : 'opacity:0.55'; ?>"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? getSetting('company_name', 'Wuzabus')); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? getSetting('tagline', 'Your electrical system shouldn\'t be the reason your trip ends early.')); ?></p>
        <div class="btn-group">
            <?php if ($hero && $hero['cta_text'] && isHeroCtaLinkEnabled($hero['cta_link'] ?: 'index.php?page=order')): ?>
                <a href="<?php echo e(url($hero['cta_link'] ?: 'index.php?page=order')); ?>" class="btn btn-primary btn-lg">
                    <?php echo e($hero['cta_text']); ?>
                </a>
            <?php elseif ($_orderInquiryEnabled): ?>
                <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg">Get a Free Quote</a>
            <?php endif; ?>
            <?php if ($_contactFormEnabled): ?>
                <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline btn-lg">Message Me</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- What I Build -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>I Build Off-Grid Electrical Systems For</h2>
            <p>Clean, reliable power — designed for real-world use</p>
        </div>

        <div class="features-grid features-grid-light">
            <div class="feature-item fade-in" style="background: var(--color-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); padding: var(--space-2xl);">
                <div class="icon" style="color: var(--color-primary);"><i class="fas fa-bus"></i></div>
                <h4>Buses</h4>
                <p>Skoolie and shuttle bus conversions with full electrical systems designed for full-time living.</p>
            </div>
            <div class="feature-item fade-in" style="background: var(--color-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); padding: var(--space-2xl);">
                <div class="icon" style="color: var(--color-primary);"><i class="fas fa-shuttle-van"></i></div>
                <h4>Vans</h4>
                <p>Sprinter, Transit, ProMaster — compact but capable electrical systems for van life.</p>
            </div>
            <div class="feature-item fade-in" style="background: var(--color-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); padding: var(--space-2xl);">
                <div class="icon" style="color: var(--color-primary);"><i class="fas fa-truck"></i></div>
                <h4>Box Trucks</h4>
                <p>Box truck conversions with the power capacity to run everything you need off-grid.</p>
            </div>
            <div class="feature-item fade-in" style="background: var(--color-white); border-radius: var(--radius-lg); box-shadow: var(--shadow-card); padding: var(--space-2xl);">
                <div class="icon" style="color: var(--color-primary);"><i class="fas fa-music"></i></div>
                <h4>Mobile Stages</h4>
                <p>High-capacity electrical for mobile stages and event vehicles. Built to handle the load.</p>
            </div>
        </div>
    </div>
</section>

<!-- Services Showcase with Photos -->
<section class="section section-light">
    <div class="container">
        <div class="section-header fade-in">
            <h2><?php echo e($offeringsHeading); ?></h2>
            <p><?php echo e($offeringsSubtext); ?></p>
        </div>

        <div class="grid grid-3" style="gap: var(--space-xl);">
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/battery-bank-victron.jpg')); ?>" alt="Lithium battery bank with Victron components" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>Lithium Battery Systems</h3>
                    <p>Custom LiFePO4 battery banks. SOK, Battle Born, and other top brands — properly sized, fused, and wired.</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/bus-exterior-solar.jpg')); ?>" alt="Solar panels installed on converted bus" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>Solar Installs</h3>
                    <p>Rooftop solar with MPPT charge controllers. Maximum output in real-world conditions.</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/victron-multiplus-rack.jpg')); ?>" alt="Victron MultiPlus inverter rack installation" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>Inverters & Shore Power</h3>
                    <p>Victron MultiPlus, EG4, and quality inverter/charger systems with shore power hookup.</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/electrical-victron-panel.jpg')); ?>" alt="Clean Victron electrical panel installation" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>System Upgrades & Fixes</h3>
                    <p>Upgrading outdated systems or fixing someone else's wiring. Clean, safe, done right.</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/full-system-sok-batteries.jpg')); ?>" alt="Complete off-grid electrical system with SOK batteries" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>Full Build Electrical</h3>
                    <p>Complete electrical from scratch — batteries, solar, inverter, shore power, distribution, everything.</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 240px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/desert-inverter-install.jpg')); ?>" alt="Off-grid inverter installation in desert setting" loading="lazy">
                </div>
                <div class="card-body">
                    <h3>Off-Grid Ready</h3>
                    <p>Systems built for real off-grid use. No second chances when your power fails out there.</p>
                </div>
            </div>
        </div>

        <?php if ($_catalogEnabled): ?>
        <div class="text-center mt-3">
            <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-outline-dark btn-lg">View All Services</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Why Choose Wuzabus -->
<section class="section section-dark">
    <div class="container">
        <div class="section-header fade-in">
            <h2 style="color: var(--color-white);">Why Wuzabus?</h2>
        </div>

        <div class="features-grid">
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-bolt"></i></div>
                <h4>Clean Wiring</h4>
                <p>Every wire routed, labeled, and secured. No rats' nests, no guesswork. It looks as good as it works.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
                <h4>Proper Fusing</h4>
                <p>Every circuit properly fused and protected. No cutting corners on safety — your build and your life depend on it.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-campground"></i></div>
                <h4>Built for Real Use</h4>
                <p>Systems designed for actual off-grid living, not just showroom looks. Tested in desert heat and mountain cold.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-check-double"></i></div>
                <h4>Done Right the First Time</h4>
                <p>No callbacks, no rework. Quality components, proper installation, and a system you can trust from day one.</p>
            </div>
        </div>
    </div>
</section>

<!-- Photo Showcase -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Recent Work</h2>
            <p>Real builds. Real results.</p>
        </div>

        <div class="grid grid-3" style="gap: var(--space-xl);">
            <div class="card fade-in">
                <div class="card-image" style="height: 280px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/bus-interior-kitchen.jpg')); ?>" alt="Converted bus interior with live-edge countertop" loading="lazy">
                </div>
                <div class="card-body" style="padding: var(--space-md) var(--space-lg);">
                    <p style="margin: 0; font-size: var(--text-sm); color: var(--color-gray-600);">Bus interior — live-edge countertop, pine walls</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 280px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/bus-interior-bedroom.jpg')); ?>" alt="Bus conversion bedroom with mini-split AC" loading="lazy">
                </div>
                <div class="card-body" style="padding: var(--space-md) var(--space-lg);">
                    <p style="margin: 0; font-size: var(--text-sm); color: var(--color-gray-600);">Bedroom with mini-split AC — off-grid powered</p>
                </div>
            </div>
            <div class="card fade-in">
                <div class="card-image" style="height: 280px;">
                    <img src="<?php echo e(url('uploads/images/wuzabus/bus-workshop-build.jpg')); ?>" alt="Bus conversion in workshop" loading="lazy">
                </div>
                <div class="card-body" style="padding: var(--space-md) var(--space-lg);">
                    <p style="margin: 0; font-size: var(--text-sm); color: var(--color-gray-600);">Build in progress — fully loaded and ready</p>
                </div>
            </div>
        </div>

        <div class="text-center mt-3">
            <a href="<?php echo url('index.php?page=gallery'); ?>" class="btn btn-outline-dark btn-lg">View Full Gallery</a>
        </div>
    </div>
</section>

<!-- Testimonials -->
<?php if (!empty($testimonials)): ?>
<section class="section section-light">
    <div class="container">
        <div class="section-header fade-in">
            <h2>What Builders Say</h2>
        </div>

        <div class="testimonials-slider" id="testimonialSlider">
            <div class="testimonials-track" id="testimonialTrack">
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-card">
                        <div class="quote">
                            <p><?php echo e($testimonial['quote']); ?></p>
                        </div>
                        <div class="author">
                            <i class="fas fa-user-circle"></i> <?php echo e($testimonial['customer_name']); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="testimonial-dots" id="testimonialDots">
                <?php foreach ($testimonials as $index => $t): ?>
                    <button class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>" aria-label="Testimonial <?php echo $index + 1; ?>"></button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Blog section on homepage
if (isFeatureEnabled('blog')):
    $latestBlogPosts = getRecentPosts(3);
    if (!empty($latestBlogPosts)):
?>
<link rel="stylesheet" href="<?php echo asset('css/blog.css'); ?>">
<section class="blog-home-section">
    <div class="container">
        <h2 class="fade-in">From the Workshop</h2>
        <p class="section-subtitle fade-in">Build tips, off-grid stories, and project updates</p>
        <div class="blog-home-grid">
            <?php foreach ($latestBlogPosts as $bp): ?>
                <article class="blog-card fade-in">
                    <?php if ($bp['featured_image']): ?>
                        <a href="<?php echo url('index.php?page=blog-post&slug=' . e($bp['slug'])); ?>" class="blog-card-image">
                            <img src="<?php echo e($bp['featured_image']); ?>" alt="<?php echo e($bp['title']); ?>" loading="lazy">
                        </a>
                    <?php endif; ?>
                    <div class="blog-card-body">
                        <?php if ($bp['category_name']): ?>
                            <a href="<?php echo url('index.php?page=blog&category=' . e($bp['category_slug'])); ?>" class="blog-card-category"><?php echo e($bp['category_name']); ?></a>
                        <?php endif; ?>
                        <h3 class="blog-card-title">
                            <a href="<?php echo url('index.php?page=blog-post&slug=' . e($bp['slug'])); ?>"><?php echo e($bp['title']); ?></a>
                        </h3>
                        <div class="blog-card-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($bp['published_at'])); ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div class="blog-home-more">
            <a href="<?php echo url('index.php?page=blog'); ?>" class="btn btn-primary">View All Posts</a>
        </div>
    </div>
</section>
<?php
    endif;
endif;
?>
