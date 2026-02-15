<?php
/**
 * Product Detail Page
 */

$slug = $_GET['slug'] ?? '';
$product = $slug ? getProductBySlug($slug) : null;

if (!$product) {
    http_response_code(404);
    $_SESSION['flash_message'] = 'Product not found.';
    $_SESSION['flash_type'] = 'error';
    redirect('index.php?page=catalog');
}

$images = getProductImages($product['id']);
$options = getProductOptions($product['id']);
$related = getRelatedProducts($product['id'], $product['category_id']);
$csrfToken = generateCSRFToken();
$_cartEnabled = isFeatureEnabled('cart');
$_wishlistEnabled = isFeatureEnabled('wishlists');
$numericPrice = parsePrice($product['price']);
$inWishlist = $_wishlistEnabled ? isInWishlist($product['id']) : false;

// Inventory
$stockQty = getStockQuantity($product['id']);
$inStock = isInStock($product['id']);
$trackingInventory = $product['track_inventory'] ?? 0;

// Page meta
$pageTitle = $product['name'] . ' | ' . getSetting('company_name', SITE_NAME);
$metaDescription = substr(strip_tags($product['description']), 0, 160);
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <a href="<?php echo url('index.php?page=catalog'); ?>"><?php echo e(getSetting('catalog_page_title', 'Our Catalog')); ?></a>
        <span>/</span>
        <a href="<?php echo url('index.php?page=catalog'); ?>#<?php echo e($product['category_slug']); ?>"><?php echo e($product['category_name']); ?></a>
        <span>/</span>
        <span class="current"><?php echo e($product['name']); ?></span>
    </div>
</div>

<!-- Product Detail -->
<section class="section">
    <div class="container">
        <div class="product-detail">
            <!-- Product Images -->
            <div class="product-gallery">
                <div class="product-main-image" id="mainImage">
                    <?php
                    $mainImageUrl = $product['image'] ?: asset('images/placeholders/default.jpg');
                    if (!empty($images)) {
                        foreach ($images as $img) {
                            if ($img['is_primary']) {
                                $mainImageUrl = $img['image_path'];
                                break;
                            }
                        }
                    }
                    ?>
                    <img src="<?php echo e($mainImageUrl); ?>" alt="<?php echo e($product['name']); ?>" id="mainProductImage">
                    <?php if ($product['product_type'] !== 'physical'): ?>
                        <span class="product-type-badge badge-<?php echo e($product['product_type']); ?>">
                            <i class="fas fa-<?php echo $product['product_type'] === 'digital' ? 'download' : 'concierge-bell'; ?>"></i>
                            <?php echo e(ucfirst($product['product_type'])); ?>
                        </span>
                    <?php endif; ?>
                </div>
                <?php if (!empty($images) && count($images) > 1): ?>
                    <div class="product-thumbnails">
                        <?php foreach ($images as $img): ?>
                            <button class="product-thumb <?php echo $img['is_primary'] ? 'active' : ''; ?>"
                                    onclick="document.getElementById('mainProductImage').src='<?php echo e($img['image_path']); ?>'; document.querySelectorAll('.product-thumb').forEach(t=>t.classList.remove('active')); this.classList.add('active');">
                                <img src="<?php echo e($img['image_path']); ?>" alt="<?php echo e($img['alt_text'] ?? $product['name']); ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <div class="product-category-label">
                    <i class="fas <?php echo e($product['category_icon'] ?? 'fa-tag'); ?>"></i>
                    <?php echo e($product['category_name']); ?>
                </div>

                <h1 class="product-title"><?php echo e($product['name']); ?></h1>

                <div class="product-price-block">
                    <span class="product-price-large"><?php echo e($product['price'] ?: 'Contact for Pricing'); ?></span>
                    <?php if ($product['unit']): ?>
                        <span class="product-unit"> / <?php echo e($product['unit']); ?></span>
                    <?php endif; ?>
                    <?php if ($product['price_note']): ?>
                        <div class="product-price-note"><?php echo e($product['price_note']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Stock Status -->
                <?php if ($trackingInventory): ?>
                    <div class="stock-status <?php echo $inStock ? 'in-stock' : 'out-of-stock'; ?>">
                        <i class="fas fa-<?php echo $inStock ? 'check-circle' : 'times-circle'; ?>"></i>
                        <?php if ($inStock): ?>
                            <?php if ($stockQty !== null && $stockQty <= ($product['low_stock_threshold'] ?? 5)): ?>
                                Only <?php echo $stockQty; ?> left in stock
                            <?php else: ?>
                                In Stock
                            <?php endif; ?>
                        <?php else: ?>
                            Out of Stock
                            <?php if ($product['allow_backorder']): ?>
                                <span class="backorder-note">(Available on backorder)</span>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if ($product['description']): ?>
                    <div class="product-description">
                        <p><?php echo nl2br(e($product['description'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Product Options (Variants) -->
                <?php if (!empty($options)): ?>
                    <div class="product-options">
                        <?php foreach ($options as $option): ?>
                            <div class="product-option-group">
                                <label class="option-label"><?php echo e($option['name']); ?></label>
                                <select class="form-control option-select" name="options[<?php echo $option['id']; ?>]" data-option-id="<?php echo $option['id']; ?>">
                                    <?php foreach ($option['values'] as $val): ?>
                                        <option value="<?php echo $val['id']; ?>"
                                                data-price-modifier="<?php echo $val['price_modifier']; ?>"
                                                <?php echo !$val['is_available'] ? 'disabled' : ''; ?>>
                                            <?php echo e($val['value']); ?>
                                            <?php if ($val['price_modifier'] != 0): ?>
                                                (<?php echo $val['price_modifier'] > 0 ? '+' : ''; ?><?php echo formatCurrency($val['price_modifier']); ?>)
                                            <?php endif; ?>
                                            <?php if (!$val['is_available']): ?> — Unavailable<?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Add to Cart / Actions -->
                <div class="product-actions">
                    <?php if ($_cartEnabled && $numericPrice > 0 && ($inStock || !$trackingInventory || $product['allow_backorder'])): ?>
                        <form method="POST" action="<?php echo url('index.php'); ?>" class="add-to-cart-form product-add-form">
                            <input type="hidden" name="action" value="add_to_cart">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <?php if (!empty($options)): ?>
                                <input type="hidden" name="variant_info" id="variantInfo" value="">
                            <?php endif; ?>
                            <div class="quantity-selector">
                                <button type="button" class="qty-btn qty-minus" onclick="this.parentNode.querySelector('input[name=quantity]').stepDown(); this.parentNode.querySelector('input[name=quantity]').dispatchEvent(new Event('change'));">-</button>
                                <input type="number" name="quantity" value="1" min="1" <?php if ($trackingInventory && $stockQty !== null): ?>max="<?php echo $stockQty; ?>"<?php endif; ?> class="qty-input">
                                <button type="button" class="qty-btn qty-plus" onclick="this.parentNode.querySelector('input[name=quantity]').stepUp(); this.parentNode.querySelector('input[name=quantity]').dispatchEvent(new Event('change'));">+</button>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-cart-plus"></i> Add to Cart
                            </button>
                        </form>
                    <?php elseif ($_cartEnabled && !$inStock && $trackingInventory && !$product['allow_backorder']): ?>
                        <button class="btn btn-primary btn-lg" disabled>
                            <i class="fas fa-times"></i> Out of Stock
                        </button>
                    <?php elseif (isFeatureEnabled('order_inquiry')): ?>
                        <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Request a Quote
                        </a>
                    <?php elseif (isFeatureEnabled('contact_form')): ?>
                        <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-envelope"></i> Contact Us
                        </a>
                    <?php endif; ?>

                    <?php if ($_wishlistEnabled): ?>
                        <form method="POST" action="<?php echo url('index.php'); ?>" class="wishlist-form">
                            <input type="hidden" name="action" value="<?php echo $inWishlist ? 'remove_wishlist' : 'add_wishlist'; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <button type="submit" class="btn btn-outline wishlist-btn <?php echo $inWishlist ? 'wishlisted' : ''; ?>" title="<?php echo $inWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                <i class="fa<?php echo $inWishlist ? 's' : 'r'; ?> fa-heart"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <!-- Specifications & Features -->
                <?php if ($product['specifications']): ?>
                    <div class="product-specs">
                        <h3><i class="fas fa-ruler-combined"></i> Specifications</h3>
                        <p><?php echo e($product['specifications']); ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($product['features']): ?>
                    <div class="product-features">
                        <h3><i class="fas fa-list-check"></i> Features</h3>
                        <ul>
                            <?php foreach (explode(',', $product['features']) as $feature): ?>
                                <li><i class="fas fa-check"></i> <?php echo e(trim($feature)); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Related Products -->
<?php if (!empty($related)): ?>
<section class="section section-light">
    <div class="container">
        <div class="section-header">
            <h2>Related Products</h2>
            <p>You might also be interested in</p>
        </div>
        <div class="grid grid-4">
            <?php foreach ($related as $rel): ?>
                <a href="<?php echo url('index.php?page=product&slug=' . e($rel['slug'])); ?>" class="card card-link fade-in">
                    <div class="card-image">
                        <?php if ($rel['image']): ?>
                            <img src="<?php echo e($rel['image']); ?>" alt="<?php echo e($rel['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="placeholder-icon"><i class="fas fa-box"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h4><?php echo e($rel['name']); ?></h4>
                        <p><?php echo e(substr($rel['description'], 0, 80)); ?><?php if (strlen($rel['description']) > 80) echo '...'; ?></p>
                    </div>
                    <div class="card-footer">
                        <span class="card-price"><?php echo e($rel['price'] ?: 'Contact for Pricing'); ?></span>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<script>
// Collect variant info into hidden field before submit
document.querySelectorAll('.product-add-form').forEach(function(form) {
    form.addEventListener('submit', function() {
        var info = {};
        document.querySelectorAll('.option-select').forEach(function(sel) {
            var opt = sel.options[sel.selectedIndex];
            info[sel.dataset.optionId] = {
                value_id: sel.value,
                label: sel.closest('.product-option-group').querySelector('.option-label').textContent,
                value: opt.textContent.trim().split('(')[0].trim()
            };
        });
        var field = document.getElementById('variantInfo');
        if (field) field.value = JSON.stringify(info);
    });
});
</script>
