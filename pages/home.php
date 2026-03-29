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
$defaultHeroImage = 'wuzabus_photos/20200615_134615_fx.jpg';
$galleryPage = getPage('gallery');
$showGalleryCta = ($galleryPage && !empty($galleryPage['is_published'])) || file_exists(__DIR__ . '/gallery.php');
?>

<!-- Hero Section -->
<section class="hero hero-home">
    <?php if ($hero && $hero['background_video']): ?>
        <video class="hero-video" autoplay muted loop playsinline poster="<?php echo e(getImageUrl($hero['background_image'] ?: $defaultHeroImage)); ?>">
            <source src="<?php echo e($hero['background_video']); ?>" type="video/mp4">
        </video>
    <?php endif; ?>
    <div class="hero-image" style="background-image: url('<?php echo e(getImageUrl($hero['background_image'] ?? $defaultHeroImage)); ?>');"></div>
    <div class="hero-overlay" style="<?php echo $hero ? 'opacity:' . ($hero['overlay_opacity'] ?? 0.5) : ''; ?>"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? getSetting('company_name', 'TheWuzaBus')); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? getSetting('tagline', 'Custom Bus Conversions & Off-Grid Living Solutions')); ?></p>
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
            <?php if ($showGalleryCta): ?>
                <a href="<?php echo url('index.php?page=gallery'); ?>" class="btn btn-outline btn-lg">View Our Builds</a>
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
                            <img src="<?php echo e(getImageUrl($product['image'])); ?>" alt="<?php echo e($product['name']); ?>">
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

<!-- Why Choose TheWuzaBus -->
<section class="section section-dark">
    <div class="container">
        <div class="section-header fade-in">
            <h2 style="color: var(--color-white);">Why Choose TheWuzaBus?</h2>
        </div>

        <div class="features-grid">
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-hammer"></i></div>
                <h4>Expert Craftsmanship</h4>
                <p>Every build features premium materials and meticulous attention to detail, from live-edge countertops to professional-grade electrical systems.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-solar-panel"></i></div>
                <h4>Off-Grid Specialists</h4>
                <p>We design and install robust solar and electrical systems using industry-leading Victron components and lithium batteries built for life on the road.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-pencil-ruler"></i></div>
                <h4>Custom Designs</h4>
                <p>No cookie-cutter builds here. Every conversion is designed around your lifestyle, travel plans, and personal vision for your home on wheels.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-road"></i></div>
                <h4>Built for the Road</h4>
                <p>Our conversions are engineered to handle the rigors of full-time travel with durable construction that stands the test of time and miles.</p>
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

<?php
// Blog section on homepage
if (isFeatureEnabled('blog')):
    $latestBlogPosts = getRecentPosts(3);
    if (!empty($latestBlogPosts)):
?>
<link rel="stylesheet" href="<?php echo asset('css/blog.css'); ?>">
<section class="blog-home-section">
    <div class="container">
        <h2 class="fade-in">Latest from the Blog</h2>
        <p class="section-subtitle fade-in">News, insights, and stories</p>
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
