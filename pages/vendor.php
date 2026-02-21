<?php
/**
 * Vendor Storefront Page
 *
 * Displays a vendor's store page with their products.
 */

if (!isMarketplaceEnabled()) {
    redirect('/');
}

$vendorSlug = $_GET['slug'] ?? '';
$vendor = getVendorBySlug($vendorSlug);

if (!$vendor) {
    http_response_code(404);
    echo '<div class="container" style="padding: var(--space-section) 0; text-align: center;">';
    echo '<h1>Vendor Not Found</h1>';
    echo '<p>The vendor you are looking for does not exist.</p>';
    echo '<a href="' . url('/') . '" class="btn btn-primary">Back to Home</a>';
    echo '</div>';
    return;
}

$vendorProducts = getVendorProducts($vendor['id']);
?>

<!-- Vendor Banner -->
<?php if ($vendor['banner']): ?>
<div style="height: 200px; background: url('<?php echo e($vendor['banner']); ?>') center/cover no-repeat; position: relative;">
    <div style="position: absolute; inset: 0; background: linear-gradient(transparent, rgba(0,0,0,0.5));"></div>
</div>
<?php endif; ?>

<div class="container" style="padding: var(--space-xl) 0;">
    <!-- Vendor Header -->
    <div style="display: flex; align-items: center; gap: var(--space-lg); margin-bottom: var(--space-xl);">
        <?php if ($vendor['logo']): ?>
            <img src="<?php echo e($vendor['logo']); ?>" alt="<?php echo e($vendor['store_name']); ?>"
                 style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--color-primary);">
        <?php else: ?>
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--color-primary); display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2rem;">
                <i class="fas fa-store"></i>
            </div>
        <?php endif; ?>
        <div>
            <h1 style="margin-bottom: 0.25rem;"><?php echo e($vendor['store_name']); ?></h1>
            <?php if ($vendor['description']): ?>
                <p style="color: var(--color-gray-500); max-width: 600px;"><?php echo e($vendor['description']); ?></p>
            <?php endif; ?>
            <small style="color: var(--color-gray-400);"><?php echo count($vendorProducts); ?> product<?php echo count($vendorProducts) !== 1 ? 's' : ''; ?></small>
        </div>
    </div>

    <!-- Vendor Products -->
    <?php if (!empty($vendorProducts)): ?>
    <div class="product-grid">
        <?php foreach ($vendorProducts as $product): ?>
            <div class="product-card">
                <?php if ($product['image']): ?>
                    <div class="product-image">
                        <a href="<?php echo url('index.php?page=product&slug=' . urlencode($product['slug'])); ?>">
                            <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>" loading="lazy">
                        </a>
                    </div>
                <?php endif; ?>
                <div class="product-info">
                    <h3 class="product-name">
                        <a href="<?php echo url('index.php?page=product&slug=' . urlencode($product['slug'])); ?>">
                            <?php echo e($product['name']); ?>
                        </a>
                    </h3>
                    <?php if ($product['price']): ?>
                        <div class="product-price"><?php echo formatCurrency($product['price']); ?></div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
        <p style="text-align: center; color: var(--color-gray-400); padding: var(--space-xl) 0;">
            This vendor has no products listed yet.
        </p>
    <?php endif; ?>
</div>
