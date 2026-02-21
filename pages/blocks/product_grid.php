<?php
/**
 * Block: Product Grid
 * Display products from a category or featured.
 */
$catId = (int)($category_id ?? 0);
$numProducts = (int)($count ?? 6);
$cols = max(1, min(6, (int)($columns ?? 3)));

if ($catId > 0) {
    $products = getProductsByCategory($catId);
    $products = array_slice($products, 0, $numProducts);
} else {
    $products = getFeaturedProducts($numProducts);
}

if (empty($products)) return;
?>
<section class="block-product-grid">
    <div class="container">
        <?php if (!empty($heading)): ?>
            <h2 class="block-heading"><?php echo e($heading); ?></h2>
        <?php endif; ?>
        <div class="block-products" style="grid-template-columns: repeat(<?php echo $cols; ?>, 1fr);">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if ($product['image']): ?>
                        <div class="product-card-image">
                            <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>" loading="lazy">
                        </div>
                    <?php endif; ?>
                    <div class="product-card-body">
                        <h3><?php echo e($product['name']); ?></h3>
                        <?php if ($product['price']): ?>
                            <p class="product-card-price"><?php echo e($product['price']); ?></p>
                        <?php endif; ?>
                        <?php if (!empty($product['slug'])): ?>
                            <a href="<?php echo url('index.php?page=product&slug=' . urlencode($product['slug'])); ?>" class="btn btn-sm btn-outline">View Details</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
