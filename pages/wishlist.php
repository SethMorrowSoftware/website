<?php
/**
 * Wishlist Page
 */

$wishlistItems = getWishlistItems();
$csrfToken = generateCSRFToken();
$_cartEnabled = isFeatureEnabled('cart');
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Wishlist</span>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="section-header">
            <h1><i class="fas fa-heart"></i> My Wishlist</h1>
            <p><?php echo count($wishlistItems); ?> item<?php echo count($wishlistItems) !== 1 ? 's' : ''; ?> saved</p>
        </div>

        <?php if (empty($wishlistItems)): ?>
            <div class="empty-state">
                <i class="far fa-heart" style="font-size: 3rem; color: var(--color-gray-400); margin-bottom: var(--space-lg);"></i>
                <h3>Your wishlist is empty</h3>
                <p>Browse our catalog and click the heart icon to save items for later.</p>
                <?php if (isFeatureEnabled('catalog')): ?>
                    <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Browse Catalog</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($wishlistItems as $item): ?>
                    <?php $numericPrice = parsePrice($item['price']); ?>
                    <div class="card fade-in">
                        <div class="card-image">
                            <a href="<?php echo url('index.php?page=product&slug=' . e($item['slug'])); ?>">
                                <?php if ($item['image']): ?>
                                    <img src="<?php echo e(getImageUrl($item['image'])); ?>" alt="<?php echo e($item['name']); ?>" loading="lazy">
                                <?php else: ?>
                                    <div class="placeholder-icon"><i class="fas fa-box"></i></div>
                                <?php endif; ?>
                            </a>
                        </div>
                        <div class="card-body">
                            <div class="card-category"><?php echo e($item['category_name']); ?></div>
                            <h4><a href="<?php echo url('index.php?page=product&slug=' . e($item['slug'])); ?>"><?php echo e($item['name']); ?></a></h4>
                            <p><?php echo e(substr($item['description'], 0, 80)); ?></p>
                        </div>
                        <div class="card-footer">
                            <span class="card-price"><?php echo e($item['price'] ?: 'Contact'); ?></span>
                            <div class="card-actions">
                                <?php if ($_cartEnabled && $numericPrice > 0): ?>
                                    <form method="POST" action="<?php echo url('index.php'); ?>" style="display:inline;">
                                        <input type="hidden" name="action" value="add_to_cart">
                                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-cart-plus"></i></button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" action="<?php echo url('index.php'); ?>" style="display:inline;">
                                    <input type="hidden" name="action" value="remove_wishlist">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline" title="Remove"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
