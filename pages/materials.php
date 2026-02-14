<?php
/**
 * Materials & Products Page
 */

$hero = getHero('materials');
$categories = getCategories();
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Materials & Products'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Premium Mulch, Stone, Topsoil, Sand & Bulk Salt'); ?></p>
        <?php if ($hero && $hero['cta_text']): ?>
            <a href="<?php echo e($hero['cta_link']); ?>" class="btn btn-primary btn-lg"><?php echo e($hero['cta_text']); ?></a>
        <?php endif; ?>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="/">Home</a>
        <span>/</span>
        <span class="current">Materials &amp; Products</span>
    </div>
</div>

<!-- Category Tabs -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Our Products</h2>
            <p>Browse our full selection of premium landscaping and construction materials</p>
        </div>

        <div class="category-tabs fade-in" id="categoryTabs">
            <button class="category-tab active" data-category="all">All Products</button>
            <?php foreach ($categories as $cat): ?>
                <button class="category-tab" data-category="<?php echo e($cat['slug']); ?>"><?php echo e($cat['name']); ?></button>
            <?php endforeach; ?>
        </div>

        <?php foreach ($categories as $cat): ?>
            <?php $products = getProductsByCategory($cat['id']); ?>
            <div class="product-category-section" id="<?php echo e($cat['slug']); ?>" data-category="<?php echo e($cat['slug']); ?>">
                <h3 style="margin-bottom: var(--space-sm); padding-top: var(--space-xl);">
                    <?php
                    $catIcons = [
                        'mulch' => 'fa-leaf',
                        'stone' => 'fa-mountain',
                        'topsoil' => 'fa-seedling',
                        'sand' => 'fa-umbrella-beach',
                        'bulk-salt' => 'fa-snowflake',
                    ];
                    $catIcon = $catIcons[$cat['slug']] ?? 'fa-box';
                    ?>
                    <i class="fas <?php echo $catIcon; ?>" style="color: var(--color-secondary);"></i>
                    <?php echo e($cat['name']); ?>
                </h3>
                <p style="color: var(--color-gray-600); margin-bottom: var(--space-xl);"><?php echo e($cat['description']); ?></p>

                <div class="grid grid-3">
                    <?php foreach ($products as $product): ?>
                        <div class="card fade-in">
                            <div class="card-image">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>">
                                <?php else: ?>
                                    <div class="placeholder-icon">
                                        <i class="fas <?php echo $catIcon; ?>"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h4><?php echo e($product['name']); ?></h4>
                                <p><?php echo e($product['description']); ?></p>
                            </div>
                            <div class="card-footer">
                                <div>
                                    <span class="card-price"><?php echo e($product['price'] ?: 'Call for Pricing'); ?></span>
                                    <?php if ($product['unit']): ?>
                                        <span class="card-unit"> / <?php echo e($product['unit']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <a href="/index.php?page=order" class="btn btn-sm btn-primary">Order</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Delivery Info -->
<section class="section section-light">
    <div class="container">
        <div class="about-content">
            <div class="fade-in">
                <h2>Delivery Available</h2>
                <p style="color: var(--color-gray-600); line-height: var(--leading-relaxed);">
                    All of our materials are available for delivery throughout the Hudson Valley region. We offer prompt, reliable delivery service with our fleet of trucks. Whether you need a single yard of mulch or a full truckload of stone, we'll get it to you on time.
                </p>
                <ul style="list-style: none; margin: var(--space-xl) 0;">
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Same-day delivery available (call early!)</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Accurate, on-time scheduling</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Bulk discounts for large orders</li>
                    <li style="padding: var(--space-sm) 0; color: var(--color-gray-700);"><i class="fas fa-check" style="color: var(--color-primary); margin-right: var(--space-sm);"></i> Serving all of Orange, Dutchess, Ulster &amp; Rockland Counties</li>
                </ul>
                <a href="/index.php?page=order" class="btn btn-primary">Order Materials</a>
            </div>
            <div class="about-image fade-in">
                <div style="width:100%; height:400px; background: linear-gradient(135deg, var(--color-secondary-dark), var(--color-secondary)); display: flex; align-items: center; justify-content: center; border-radius: var(--radius-lg);">
                    <i class="fas fa-truck" style="font-size: 6rem; color: rgba(255,255,255,0.3);"></i>
                </div>
            </div>
        </div>
    </div>
</section>
