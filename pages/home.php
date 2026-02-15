<?php
/**
 * Home Page
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

$offeringsHeading = getSetting('homepage_offerings_heading', 'What We Offer');
$offeringsSubtext = getSetting('homepage_offerings_subtext', 'Explore our products and services');
$featuredHeading = getSetting('homepage_featured_heading', 'Featured Products & Services');
$featuredSubtext = getSetting('homepage_featured_subtext', 'A selection of what we have to offer');
$orderInquiryTitle = getSetting('order_inquiry_title', 'Order Inquiry');
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
    <?php endif; ?>
    <div class="hero-overlay" style="<?php echo $hero ? 'opacity:' . ($hero['overlay_opacity'] ?? 0.5) : ''; ?>"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? getSetting('company_name', 'Your Business Name')); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? getSetting('tagline', 'Quality Products & Services You Can Count On')); ?></p>
        <div class="btn-group">
            <?php if ($hero && $hero['cta_text'] && isHeroCtaLinkEnabled($hero['cta_link'] ?: 'index.php?page=order')): ?>
                <a href="<?php echo e(url($hero['cta_link'] ?: 'index.php?page=order')); ?>" class="btn btn-primary btn-lg">
                    <?php echo e($hero['cta_text']); ?>
                </a>
            <?php elseif ($_orderInquiryEnabled): ?>
                <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg">Get a Free Quote</a>
            <?php elseif ($_cartEnabled && $_catalogEnabled): ?>
                <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-primary btn-lg">Shop Now</a>
            <?php elseif ($_catalogEnabled): ?>
                <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-primary btn-lg">View Our Catalog</a>
            <?php endif; ?>
            <?php if ($_contactFormEnabled): ?>
                <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline btn-lg">Contact Us</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Categories Overview -->
<?php if ($_catalogEnabled && !empty($categories)): ?>
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2><?php echo e($offeringsHeading); ?></h2>
            <p><?php echo e($offeringsSubtext); ?></p>
        </div>

        <div class="services-grid">
            <?php foreach ($categories as $cat): ?>
                <a href="<?php echo url('index.php?page=catalog'); ?>#<?php echo e($cat['slug']); ?>" class="service-card fade-in">
                    <div class="icon">
                        <i class="fas <?php echo e($cat['icon'] ?? 'fa-tag'); ?>"></i>
                    </div>
                    <h3><?php echo e($cat['name']); ?></h3>
                    <p><?php echo e($cat['description']); ?></p>
                    <span class="btn btn-sm btn-outline-dark">Learn More</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Featured Products -->
<?php if ($_catalogEnabled && !empty($featuredProducts)): ?>
<section class="section section-light">
    <div class="container">
        <div class="section-header fade-in">
            <h2><?php echo e($featuredHeading); ?></h2>
            <p><?php echo e($featuredSubtext); ?></p>
        </div>

        <div class="grid grid-3">
            <?php $csrfToken = generateCSRFToken(); ?>
            <?php foreach ($featuredProducts as $product): ?>
                <div class="card fade-in">
                    <div class="card-image">
                        <?php if ($product['image']): ?>
                            <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                        <?php else: ?>
                            <div class="placeholder-icon">
                                <i class="fas <?php echo e($product['category_icon'] ?? 'fa-tag'); ?>"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h3><?php echo e($product['name']); ?></h3>
                        <p><?php echo e($product['description']); ?></p>
                    </div>
                    <div class="card-footer">
                        <span class="card-price"><?php echo e($product['price'] ?: 'Call for Pricing'); ?></span>
                        <?php
                        $numericPrice = parsePrice($product['price']);
                        if ($_cartEnabled && $numericPrice > 0): ?>
                            <form method="POST" action="<?php echo url('index.php'); ?>" class="add-to-cart-form">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-cart-plus"></i> Add to Cart
                                </button>
                            </form>
                        <?php elseif ($_orderInquiryEnabled): ?>
                            <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-sm btn-primary">Order Now</a>
                        <?php elseif ($_contactFormEnabled): ?>
                            <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-sm btn-primary">Inquire</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-3">
            <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-outline-dark btn-lg">View Full <?php echo e(getSetting('catalog_page_title', 'Catalog')); ?></a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Why Choose Us -->
<section class="section section-dark">
    <div class="container">
        <div class="section-header fade-in">
            <h2 style="color: var(--color-white);">Why Choose Us?</h2>
        </div>

        <div class="features-grid">
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-handshake"></i></div>
                <h4>Locally Owned</h4>
                <p>A locally owned business proudly serving our community.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h4>Fast Service</h4>
                <p>Quick turnaround and reliable service you can count on.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <h4>Competitive Pricing</h4>
                <p>Fair, transparent pricing with no hidden fees or surprises.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-star"></i></div>
                <h4>Quality Guaranteed</h4>
                <p>Premium products and dependable service, every single time.</p>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials -->
<?php if (!empty($testimonials)): ?>
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>What Our Customers Say</h2>
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
