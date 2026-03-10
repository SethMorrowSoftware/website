<?php
/**
 * Search Results Page
 */

$query = trim($_GET['q'] ?? '');
$results = [];
$csrfToken = generateCSRFToken();
$_cartEnabled = isFeatureEnabled('cart');

if ($query) {
    $results = searchProducts($query);
}

$catalogPageTitle = getSetting('catalog_page_title', 'Our Catalog');
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Search Results</span>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h1>Search Results</h1>
            <?php if ($query): ?>
                <p><?php echo count($results); ?> result<?php echo count($results) !== 1 ? 's' : ''; ?> for "<?php echo e($query); ?>"</p>
            <?php endif; ?>
        </div>

        <!-- Search Form -->
        <div class="search-form-container">
            <form method="GET" action="<?php echo url('index.php'); ?>" class="search-form-large">
                <input type="hidden" name="page" value="search">
                <div class="search-input-group">
                    <input type="text" name="q" value="<?php echo e($query); ?>" placeholder="Search products, categories..." class="search-input-large" autofocus>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                </div>
            </form>
        </div>

        <?php if ($query && !empty($results)): ?>
            <div class="grid grid-3">
                <?php foreach ($results as $product): ?>
                    <?php $numericPrice = parsePrice($product['price']); ?>
                    <a href="<?php echo url('index.php?page=product&slug=' . e($product['slug'])); ?>" class="card card-link fade-in">
                        <div class="card-image">
                            <?php if ($product['product_type'] !== 'physical'): ?>
                                <span class="product-type-badge badge-<?php echo e($product['product_type']); ?>" style="position: absolute; top: var(--space-sm); right: var(--space-sm); z-index: 2;">
                                    <i class="fas fa-<?php echo $product['product_type'] === 'digital' ? 'download' : 'concierge-bell'; ?>"></i>
                                    <?php echo e(ucfirst($product['product_type'])); ?>
                                </span>
                            <?php endif; ?>
                            <?php if ($product['image']): ?>
                                <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>" loading="lazy">
                            <?php else: ?>
                                <div class="placeholder-icon">
                                    <i class="fas <?php echo e($product['category_icon'] ?? 'fa-tag'); ?>"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <div class="card-category"><i class="fas <?php echo e($product['category_icon'] ?? 'fa-tag'); ?>"></i> <?php echo e($product['category_name']); ?></div>
                            <h4><?php echo e($product['name']); ?></h4>
                            <p><?php echo e(substr($product['description'] ?? '', 0, 100)); ?><?php if (strlen($product['description'] ?? '') > 100) echo '...'; ?></p>
                        </div>
                        <div class="card-footer">
                            <span class="card-price"><?php echo e($product['price'] ?: 'Contact for Pricing'); ?></span>
                            <?php if ($product['unit']): ?>
                                <span class="card-unit"> / <?php echo e($product['unit']); ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php elseif ($query): ?>
            <div class="empty-state">
                <i class="fas fa-search" style="font-size: 3rem; color: var(--color-gray-400); margin-bottom: var(--space-lg);"></i>
                <h3>No results found</h3>
                <p>Try different keywords or browse our <a href="<?php echo url('index.php?page=catalog'); ?>"><?php echo e($catalogPageTitle); ?></a>.</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-search" style="font-size: 3rem; color: var(--color-gray-400); margin-bottom: var(--space-lg);"></i>
                <h3>What are you looking for?</h3>
                <p>Enter a search term above to find products and services.</p>
            </div>
        <?php endif; ?>
    </div>
</section>
