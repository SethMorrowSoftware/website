<?php
/**
 * Home Page
 */

$hero = getHero('home');
$categories = getCategories();
$testimonials = getTestimonials();
$containers = getContainers();
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
        <h1><?php echo e($hero['title'] ?? 'Hudson Valley Supply & Recycling'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Your Trusted Source for Containers, Materials & Hauling Services'); ?></p>
        <div class="btn-group">
            <?php if ($hero && $hero['cta_text']): ?>
                <a href="<?php echo e(url($hero['cta_link'] ?: 'index.php?page=order')); ?>" class="btn btn-primary btn-lg">
                    <?php echo e($hero['cta_text']); ?>
                </a>
            <?php else: ?>
                <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg">Get a Free Quote</a>
            <?php endif; ?>
            <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline btn-lg">Contact Us</a>
        </div>
    </div>
</section>

<!-- Services Overview -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Our Services</h2>
            <p>From roll-off containers to premium landscaping materials, we've got you covered.</p>
        </div>

        <div class="services-grid">
            <a href="<?php echo url('index.php?page=containers'); ?>" class="service-card fade-in" style="text-decoration:none; color:inherit;">
                <div class="icon">
                    <i class="fas fa-dumpster"></i>
                </div>
                <h3>Roll Off Containers</h3>
                <p>Available in 10, 15, 20, 30, and 40 yard sizes for residential and commercial projects. Flexible rental terms.</p>
                <span class="btn btn-sm btn-outline-dark">Learn More</span>
            </a>

            <a href="<?php echo url('index.php?page=materials'); ?>" class="service-card fade-in" style="text-decoration:none; color:inherit;">
                <div class="icon">
                    <i class="fas fa-leaf"></i>
                </div>
                <h3>Materials &amp; Products</h3>
                <p>Premium mulch, stone, topsoil, sand, and bulk salt. Available for pickup or delivery throughout the Hudson Valley.</p>
                <span class="btn btn-sm btn-outline-dark">View Products</span>
            </a>

            <a href="<?php echo url('index.php?page=trucking'); ?>" class="service-card fade-in" style="text-decoration:none; color:inherit;">
                <div class="icon">
                    <i class="fas fa-truck"></i>
                </div>
                <h3>Trucking Services</h3>
                <p>Professional hauling and delivery services for mulch, stone, and more. Reliable, on-time service you can count on.</p>
                <span class="btn btn-sm btn-outline-dark">Learn More</span>
            </a>
        </div>
    </div>
</section>

<!-- Container Sizes Preview -->
<section class="section section-light">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Roll Off Container Sizes</h2>
            <p>Choose the right size container for your project</p>
        </div>

        <div class="grid grid-3">
            <?php
            $displayContainers = array_slice($containers, 0, 3);
            foreach ($displayContainers as $container):
            ?>
                <div class="container-card fade-in">
                    <div class="card-image">
                        <?php if ($container['image']): ?>
                            <img src="<?php echo e($container['image']); ?>" alt="<?php echo e($container['name']); ?>">
                        <?php else: ?>
                            <div class="size-badge"><?php echo e($container['size']); ?> <span>YD</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h3><?php echo e($container['name']); ?></h3>
                        <?php if ($container['dimensions']): ?>
                            <span class="dimensions"><i class="fas fa-ruler-combined"></i> <?php echo e($container['dimensions']); ?></span>
                        <?php endif; ?>
                        <p><?php echo e($container['description']); ?></p>
                    </div>
                    <div class="card-footer">
                        <span class="card-price"><?php echo e($container['price'] ?: 'Call for Pricing'); ?></span>
                        <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-sm btn-primary">Order Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-3">
            <a href="<?php echo url('index.php?page=containers'); ?>" class="btn btn-outline-dark btn-lg">View All Container Sizes</a>
        </div>
    </div>
</section>

<!-- Product Categories -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Materials &amp; Products</h2>
            <p>Quality materials for every landscaping and construction need</p>
        </div>

        <div class="grid grid-3">
            <?php foreach ($categories as $cat): ?>
                <div class="card fade-in">
                    <div class="card-image">
                        <?php if ($cat['image']): ?>
                            <img src="<?php echo e($cat['image']); ?>" alt="<?php echo e($cat['name']); ?>">
                        <?php else: ?>
                            <div class="placeholder-icon">
                                <?php
                                $icons = [
                                    'mulch' => 'fa-leaf',
                                    'stone' => 'fa-mountain',
                                    'topsoil' => 'fa-seedling',
                                    'sand' => 'fa-umbrella-beach',
                                    'bulk-salt' => 'fa-snowflake',
                                ];
                                $icon = $icons[$cat['slug']] ?? 'fa-box';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h3><?php echo e($cat['name']); ?></h3>
                        <p><?php echo e($cat['description']); ?></p>
                        <a href="<?php echo url('index.php?page=materials'); ?>#<?php echo e($cat['slug']); ?>" class="btn btn-sm btn-outline-dark">
                            View Products <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

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
                <p>A family-run business proudly serving the Hudson Valley community.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h4>Fast Delivery</h4>
                <p>Same-day and next-day delivery available on most products.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-dollar-sign"></i></div>
                <h4>Competitive Pricing</h4>
                <p>Fair, transparent pricing with no hidden fees or surprises.</p>
            </div>
            <div class="feature-item fade-in">
                <div class="icon"><i class="fas fa-star"></i></div>
                <h4>Quality Guaranteed</h4>
                <p>Premium materials and dependable service, every single time.</p>
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
