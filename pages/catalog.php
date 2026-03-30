<?php
/**
 * Catalog Page — Dynamic product/service listing by category
 */

$hero = getHero('catalog');
$categories = getCategories();
$csrfToken = generateCSRFToken();

$_cartEnabled = isFeatureEnabled('cart');
$_orderInquiryEnabled = isFeatureEnabled('order_inquiry');
$_contactFormEnabled = isFeatureEnabled('contact_form');

$catalogPageTitle = getSetting('catalog_page_title', 'Our Catalog');
$catalogSectionTitle = getSetting('catalog_section_title', 'Browse Our Offerings');
$orderInquiryTitle = getSetting('order_inquiry_title', 'Order Inquiry');
$wuzabusPlaceholderProducts = [
    ['name' => 'Complete Skoolie Conversion', 'category' => 'Bus Conversion', 'price' => null, 'price_note' => 'Request Quote', 'request_quote_only' => true, 'image' => 'wuzabus_photos/20210508_162602.jpg'],
    ['name' => 'Shuttle Bus Conversion Consult', 'category' => 'Bus Conversion', 'price' => '$350', 'price_note' => 'Flat planning session', 'request_quote_only' => false, 'image' => 'wuzabus_photos/20200615_134615_fx.jpg'],
    ['name' => 'Victron Energy System Package', 'category' => 'Electrical', 'price' => null, 'price_note' => 'Request Quote', 'request_quote_only' => true, 'image' => 'wuzabus_photos/20241129_191724.jpg'],
    ['name' => 'MaxxAir Fan Install', 'category' => 'Electrical', 'price' => '$650', 'price_note' => 'Parts + labor', 'request_quote_only' => false, 'image' => 'wuzabus_photos/20230519_100424.jpg'],
    ['name' => 'Custom Kitchen Build', 'category' => 'Interior Build', 'price' => null, 'price_note' => 'Request Quote', 'request_quote_only' => true, 'image' => 'wuzabus_photos/20200508_215108.jpg'],
    ['name' => 'Solar Array Roof Prep Kit', 'category' => 'Interior Build', 'price' => '$249', 'price_note' => 'Parts included', 'request_quote_only' => false, 'image' => 'wuzabus_photos/20250420_104130.jpg'],
];
$hasVisibleProducts = false;
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e(getImageUrl($hero['background_image'])); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? $catalogPageTitle); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? $catalogSectionTitle); ?></p>
        <?php if ($hero && $hero['cta_text'] && isHeroCtaLinkEnabled($hero['cta_link'])): ?>
            <a href="<?php echo e(url($hero['cta_link'])); ?>" class="btn btn-primary btn-lg"><?php echo e($hero['cta_text']); ?></a>
        <?php endif; ?>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current"><?php echo e($catalogPageTitle); ?></span>
    </div>
</div>

<!-- Category Tabs & Products -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2><?php echo e($catalogSectionTitle); ?></h2>
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
            <?php $hasVisibleProducts = true; ?>
            <div class="product-category-section" id="<?php echo e($cat['slug']); ?>" data-category="<?php echo e($cat['slug']); ?>">
                <h3 style="margin-bottom: var(--space-sm); padding-top: var(--space-xl);">
                    <i class="fas <?php echo e($cat['icon'] ?? 'fa-tag'); ?>" style="color: var(--color-secondary);"></i>
                    <?php echo e($cat['name']); ?>
                </h3>
                <?php if ($cat['description']): ?>
                    <p style="color: var(--color-gray-600); margin-bottom: var(--space-xl);"><?php echo e($cat['description']); ?></p>
                <?php endif; ?>

                <div class="grid grid-3">
                    <?php foreach ($products as $product): ?>
                        <?php $productType = $product['product_type'] ?? 'physical'; ?>
                        <div class="card fade-in">
                            <a href="<?php echo url('index.php?page=product&slug=' . e($product['slug'])); ?>" class="card-image-link">
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
                                    <img src="<?php echo e(getImageUrl($product['image'])); ?>" alt="<?php echo e($product['name']); ?>" loading="lazy">
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
                            </a>
                            <div class="card-body">
                                <h4><a href="<?php echo url('index.php?page=product&slug=' . e($product['slug'])); ?>"><?php echo e($product['name']); ?></a></h4>
                                <p><?php echo e(substr($product['description'] ?? '', 0, 120)); ?><?php if (strlen($product['description'] ?? '') > 120) echo '...'; ?></p>
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
                                <?php if ($product['request_quote_only']): ?>
                                    <div>
                                        <span class="card-price">Request a Quote</span>
                                    </div>
                                    <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-paper-plane"></i> Request a Quote
                                    </a>
                                <?php else: ?>
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
                                    <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-sm btn-primary"><?php echo e($orderInquiryTitle === 'Order Inquiry' ? 'Order' : $orderInquiryTitle); ?></a>
                                <?php elseif ($_contactFormEnabled): ?>
                                    <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-sm btn-primary">Inquire</a>
                                <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if (!$hasVisibleProducts): ?>
            <div class="product-category-section" data-category="all">
                <h3 style="margin-bottom: var(--space-sm); padding-top: var(--space-xl);">
                    <i class="fas fa-store" style="color: var(--color-secondary);"></i>
                    Our Services
                </h3>
                <p style="color: var(--color-gray-600); margin-bottom: var(--space-xl);">Explore our bus conversion and off-grid living services below.</p>
                <div class="grid grid-3">
                    <?php foreach ($wuzabusPlaceholderProducts as $placeholder): ?>
                        <div class="card fade-in">
                            <div class="card-image">
                                <img src="<?php echo e(getImageUrl($placeholder['image'])); ?>" alt="<?php echo e($placeholder['name']); ?>" loading="lazy">
                            </div>
                            <div class="card-body">
                                <h4><?php echo e($placeholder['name']); ?></h4>
                                <p><?php echo e($placeholder['category']); ?></p>
                            </div>
                            <div class="card-footer">
                                <div>
                                    <span class="card-price"><?php echo e($placeholder['price'] ?: 'Request a Quote'); ?></span>
                                    <?php if (!empty($placeholder['price_note']) && empty($placeholder['request_quote_only'])): ?>
                                        <br><span class="card-unit"><?php echo e($placeholder['price_note']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($placeholder['request_quote_only'])): ?>
                                    <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-sm btn-primary">Request a Quote</a>
                                <?php else: ?>
                                    <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-sm btn-primary">Inquire</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- CTA -->
<section class="section section-light">
    <div class="container">
        <div class="about-content">
            <div class="fade-in">
                <h2>Ready to Order?</h2>
                <p style="color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                    <?php if ($_cartEnabled && $_orderInquiryEnabled): ?>
                        Browse our catalog above and add items to your cart, or submit an order inquiry for custom requests. We offer competitive pricing and reliable service. Contact us for bulk orders, custom requests, or any questions.
                    <?php elseif ($_cartEnabled): ?>
                        Browse our catalog above and add items to your cart. We offer competitive pricing and reliable service.
                    <?php elseif ($_orderInquiryEnabled): ?>
                        Browse our catalog above and submit an inquiry for any items you're interested in. We offer competitive pricing and reliable service.
                    <?php else: ?>
                        Browse our catalog above and contact us about any items you're interested in. We offer competitive pricing and reliable service.
                    <?php endif; ?>
                </p>
                <ul style="list-style: none; margin: var(--space-xl) 0;">
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Competitive pricing</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Fast, reliable service</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Digital downloads delivered instantly</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Serving our local community</li>
                </ul>
                <div class="cta-buttons-inline">
                    <?php if ($_cartEnabled): ?>
                        <a href="<?php echo url('index.php?page=cart'); ?>" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> View Cart</a>
                    <?php endif; ?>
                    <?php if ($_orderInquiryEnabled): ?>
                        <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-outline-dark">Submit an Inquiry</a>
                    <?php elseif ($_contactFormEnabled): ?>
                        <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline-dark">Contact Us</a>
                    <?php endif; ?>
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
