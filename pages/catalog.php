<?php
/**
 * Catalog Page — Dynamic product/service listing by category
 */

$hero = getHero('catalog');
$categories = getCategories();
$csrfToken = generateCSRFToken();
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Our Catalog'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Browse Our Full Selection of Products & Services'); ?></p>
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
        <span class="current">Catalog</span>
    </div>
</div>

<!-- Category Tabs & Products -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Browse Our Offerings</h2>
            <p>Select a category below or browse everything we have available</p>
        </div>

        <?php if (count($categories) > 1): ?>
        <div class="category-tabs fade-in" id="categoryTabs">
            <button class="category-tab active" data-category="all">All</button>
            <?php foreach ($categories as $cat): ?>
                <button class="category-tab" data-category="<?php echo e($cat['slug']); ?>"><?php echo e($cat['name']); ?></button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php foreach ($categories as $cat): ?>
            <?php $products = getProductsByCategory($cat['id']); ?>
            <?php if (empty($products)) continue; ?>
            <div class="product-category-section" id="<?php echo e($cat['slug']); ?>" data-category="<?php echo e($cat['slug']); ?>">
                <h3 style="margin-bottom: var(--space-sm); padding-top: var(--space-xl);">
                    <i class="fas <?php echo e($cat['icon'] ?? 'fa-tag'); ?>" style="color: var(--color-secondary);"></i>
                    <?php echo e($cat['name']); ?>
                </h3>
                <p style="color: var(--color-gray-600); margin-bottom: var(--space-xl);"><?php echo e($cat['description']); ?></p>

                <div class="grid grid-3">
                    <?php foreach ($products as $product): ?>
                        <?php $productType = $product['product_type'] ?? 'physical'; ?>
                        <div class="card fade-in">
                            <div class="card-image">
                                <?php if ($productType !== 'physical'): ?>
                                    <span class="product-type-badge badge-<?php echo e($productType); ?>" style="position: absolute; top: var(--space-sm); right: var(--space-sm); z-index: 2;">
                                        <?php if ($productType === 'digital'): ?>
                                            <i class="fas fa-download"></i>
                                        <?php else: ?>
                                            <i class="fas fa-concierge-bell"></i>
                                        <?php endif; ?>
                                        <?php echo e(ucfirst($productType)); ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-icon">
                                        <?php if ($productType === 'digital'): ?>
                                            <i class="fas fa-file-download"></i>
                                        <?php elseif ($productType === 'service'): ?>
                                            <i class="fas fa-concierge-bell"></i>
                                        <?php else: ?>
                                            <i class="fas <?php echo e($cat['icon'] ?? 'fa-tag'); ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h4><?php echo e($product['name']); ?></h4>
                                <p><?php echo e($product['description']); ?></p>
                                <?php if ($product['specifications']): ?>
                                    <span class="dimensions"><i class="fas fa-ruler-combined"></i> <?php echo e($product['specifications']); ?></span>
                                <?php endif; ?>
                                <?php if ($product['features']): ?>
                                    <div style="margin-top: var(--space-sm);">
                                        <ul class="use-cases">
                                            <?php foreach (explode(',', $product['features']) as $feature): ?>
                                                <li><?php echo e(trim($feature)); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <div>
                                    <span class="card-price"><?php echo e($product['price'] ?: 'Call for Pricing'); ?></span>
                                    <?php if ($product['unit']): ?>
                                        <span class="card-unit"> / <?php echo e($product['unit']); ?></span>
                                    <?php endif; ?>
                                    <?php if ($product['price_note']): ?>
                                        <br><span class="card-unit"><?php echo e($product['price_note']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php
                                $numericPrice = parsePrice($product['price']);
                                if ($numericPrice > 0): ?>
                                    <form method="POST" action="<?php echo url('index.php'); ?>" class="add-to-cart-form">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary">
                                            <i class="fas fa-cart-plus"></i> Add to Cart
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-sm btn-primary">Order</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- CTA -->
<section class="section section-light">
    <div class="container">
        <div class="about-content">
            <div class="fade-in">
                <h2>Ready to Order?</h2>
                <p style="color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                    Browse our catalog above and add items to your cart, or submit an order inquiry for custom requests. We offer competitive pricing and reliable service. Contact us for bulk orders, custom requests, or any questions.
                </p>
                <ul style="list-style: none; margin: var(--space-xl) 0;">
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Competitive pricing</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Fast, reliable service</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Digital downloads delivered instantly</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Serving our local community</li>
                </ul>
                <div class="cta-buttons-inline">
                    <a href="<?php echo url('index.php?page=cart'); ?>" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> View Cart</a>
                    <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-outline-dark">Submit an Inquiry</a>
                </div>
            </div>
            <div class="about-image fade-in">
                <div class="placeholder-banner" style="background: linear-gradient(135deg, var(--color-secondary-dark), var(--color-secondary));">
                    <i class="fas fa-store" style="color: rgba(255,255,255,0.3);"></i>
                </div>
            </div>
        </div>
    </div>
</section>
