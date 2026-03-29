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
        <?php if ($product['category_name']): ?>
            <a href="<?php echo url('index.php?page=catalog'); ?>#<?php echo e($product['category_slug']); ?>"><?php echo e($product['category_name']); ?></a>
            <span>/</span>
        <?php endif; ?>
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
                    $mainImageUrl = getImageUrl($product['image']);
                    if (!empty($images)) {
                        foreach ($images as $img) {
                            if ($img['is_primary']) {
                                $mainImageUrl = getImageUrl($img['image_path']);
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
                                    onclick="document.getElementById('mainProductImage').src='<?php echo e(getImageUrl($img['image_path'])); ?>'; document.querySelectorAll('.product-thumb').forEach(t=>t.classList.remove('active')); this.classList.add('active');">
                                <img src="<?php echo e(getImageUrl($img['image_path'])); ?>" alt="<?php echo e($img['alt_text'] ?? $product['name']); ?>">
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <?php if ($product['category_name']): ?>
                <div class="product-category-label">
                    <i class="fas <?php echo e($product['category_icon'] ?? 'fa-tag'); ?>"></i>
                    <?php echo e($product['category_name']); ?>
                </div>
                <?php endif; ?>

                <h1 class="product-title"><?php echo e($product['name']); ?></h1>

                <?php if ($product['request_quote_only']): ?>
                <div class="product-price-block">
                    <span class="product-price-large">Request a Quote</span>
                </div>
                <?php else: ?>
                <div class="product-price-block">
                    <span class="product-price-large"><?php echo e($product['price'] ?: 'Contact for Pricing'); ?></span>
                    <?php if ($product['unit']): ?>
                        <span class="product-unit"> / <?php echo e($product['unit']); ?></span>
                    <?php endif; ?>
                    <?php if ($product['price_note']): ?>
                        <div class="product-price-note"><?php echo e($product['price_note']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

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
                    <?php if ($product['request_quote_only']): ?>
                        <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane"></i> Request a Quote
                        </a>
                    <?php elseif ($_cartEnabled && $numericPrice > 0 && ($inStock || !$trackingInventory || $product['allow_backorder'])): ?>
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
                            <img src="<?php echo e(getImageUrl($rel['image'])); ?>" alt="<?php echo e($rel['name']); ?>" loading="lazy">
                        <?php else: ?>
                            <div class="placeholder-icon"><i class="fas fa-box"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h4><?php echo e($rel['name']); ?></h4>
                        <p><?php echo e(substr($rel['description'] ?? '', 0, 80)); ?><?php if (strlen($rel['description'] ?? '') > 80) echo '...'; ?></p>
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

<!-- Reviews Section -->
<?php if (isFeatureEnabled('reviews')): ?>
<?php
    $ratingInfo = getProductRating($product['id']);
    $ratingBreakdown = getRatingBreakdown($product['id']);
    $reviews = getProductReviews($product['id']);
    $_canReview = canReview($product['id']);
    $totalReviews = $ratingInfo['count'];
?>
<section class="section" id="reviews">
    <div class="container">
        <div class="section-header">
            <h2>Customer Reviews</h2>
            <?php if ($totalReviews > 0): ?>
                <p><?php echo $totalReviews; ?> review<?php echo $totalReviews !== 1 ? 's' : ''; ?></p>
            <?php endif; ?>
        </div>

        <?php if ($totalReviews > 0): ?>
        <div class="reviews-summary">
            <div class="reviews-average">
                <div class="reviews-average-number"><?php echo $ratingInfo['average']; ?></div>
                <?php echo renderStars($ratingInfo['average']); ?>
                <div class="reviews-average-count"><?php echo $totalReviews; ?> review<?php echo $totalReviews !== 1 ? 's' : ''; ?></div>
            </div>
            <div class="reviews-breakdown">
                <?php foreach ($ratingBreakdown as $stars => $count): ?>
                    <?php $pct = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0; ?>
                    <div class="rating-bar-row">
                        <span class="rating-bar-label"><?php echo $stars; ?> <i class="fas fa-star"></i></span>
                        <div class="rating-bar">
                            <div class="rating-bar-fill" style="width: <?php echo $pct; ?>%;"></div>
                        </div>
                        <span class="rating-bar-count"><?php echo $count; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Review List -->
        <?php if (!empty($reviews)): ?>
        <div class="reviews-list">
            <?php foreach ($reviews as $review): ?>
            <div class="review-card">
                <div class="review-header">
                    <div class="review-meta">
                        <strong class="review-author"><?php echo e($review['customer_name']); ?></strong>
                        <?php if ($review['is_verified_purchase']): ?>
                            <span class="review-verified"><i class="fas fa-check-circle"></i> Verified Purchase</span>
                        <?php endif; ?>
                    </div>
                    <div class="review-rating">
                        <?php echo renderStars((float)$review['rating']); ?>
                        <span class="review-date"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                    </div>
                </div>
                <?php if ($review['title']): ?>
                    <h4 class="review-title"><?php echo e($review['title']); ?></h4>
                <?php endif; ?>
                <p class="review-body"><?php echo nl2br(e($review['body'])); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif ($totalReviews === 0): ?>
            <p class="reviews-empty">No reviews yet. Be the first to review this product!</p>
        <?php endif; ?>

        <!-- Review Form -->
        <?php if ($_canReview): ?>
        <div class="review-form-wrap">
            <h3>Write a Review</h3>
            <form method="POST" action="<?php echo url('index.php'); ?>" class="review-form">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <input type="hidden" name="product_slug" value="<?php echo e($product['slug']); ?>">

                <div class="form-row">
                    <div class="form-group">
                        <label for="reviewer_name">Your Name <span class="required">*</span></label>
                        <input type="text" id="reviewer_name" name="reviewer_name" required class="form-control"
                            <?php if (isCustomerLoggedIn()): ?>value="<?php echo e($_SESSION['customer_name'] ?? ''); ?>"<?php endif; ?>>
                    </div>
                    <div class="form-group">
                        <label for="reviewer_email">Your Email <span class="required">*</span></label>
                        <input type="email" id="reviewer_email" name="reviewer_email" required class="form-control"
                            <?php if (isCustomerLoggedIn()): ?>value="<?php echo e($_SESSION['customer_email'] ?? ''); ?>"<?php endif; ?>>
                    </div>
                </div>

                <div class="form-group">
                    <label>Rating <span class="required">*</span></label>
                    <div class="star-rating-input" id="starRatingInput">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <label class="star-label" data-rating="<?php echo $i; ?>">
                                <input type="radio" name="rating" value="<?php echo $i; ?>" required style="display:none;">
                                <i class="far fa-star"></i>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="form-group">
                    <label for="review_title">Review Title</label>
                    <input type="text" id="review_title" name="review_title" class="form-control" placeholder="Summarize your experience">
                </div>

                <div class="form-group">
                    <label for="review_body">Your Review <span class="required">*</span></label>
                    <textarea id="review_body" name="review_body" required class="form-control" rows="5" minlength="10" placeholder="Share your experience with this product..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Review
                </button>
            </form>
        </div>
        <?php else: ?>
            <p class="reviews-already-reviewed"><i class="fas fa-info-circle"></i> You have already reviewed this product.</p>
        <?php endif; ?>
    </div>
</section>

<style>
/* Reviews Section Styles */
.reviews-summary {
    display: flex;
    gap: 2rem;
    align-items: flex-start;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-light, #f8f9fa);
    border-radius: 8px;
}
.reviews-average {
    text-align: center;
    min-width: 140px;
}
.reviews-average-number {
    font-size: 3rem;
    font-weight: 700;
    line-height: 1;
    color: var(--primary, #333);
}
.reviews-average .star-rating {
    justify-content: center;
    margin: 0.5rem 0;
}
.reviews-average-count {
    color: #666;
    font-size: 0.9rem;
}
.reviews-breakdown {
    flex: 1;
    max-width: 400px;
}
.rating-bar-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.35rem;
}
.rating-bar-label {
    font-size: 0.85rem;
    min-width: 40px;
    text-align: right;
    white-space: nowrap;
    color: #555;
}
.rating-bar-label .fa-star { font-size: 0.7rem; color: #f5a623; }
.rating-bar {
    flex: 1;
    height: 10px;
    background: #e0e0e0;
    border-radius: 5px;
    overflow: hidden;
}
.rating-bar-fill {
    height: 100%;
    background: #f5a623;
    border-radius: 5px;
    transition: width 0.3s ease;
}
.rating-bar-count {
    font-size: 0.85rem;
    min-width: 24px;
    color: #666;
}

/* Star Rating Display */
.star-rating {
    display: inline-flex;
    gap: 2px;
    color: #f5a623;
    font-size: 1rem;
}
.star-rating .far { color: #ccc; }

/* Review Cards */
.reviews-list {
    margin-bottom: 2rem;
}
.review-card {
    padding: 1.25rem 0;
    border-bottom: 1px solid #eee;
}
.review-card:last-child { border-bottom: none; }
.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
}
.review-author { font-size: 1rem; }
.review-verified {
    color: #28a745;
    font-size: 0.8rem;
    margin-left: 0.5rem;
}
.review-rating {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.review-date { color: #999; font-size: 0.85rem; }
.review-title {
    margin: 0.25rem 0 0.5rem;
    font-size: 1.05rem;
}
.review-body {
    color: #444;
    line-height: 1.6;
    margin: 0;
}
.reviews-empty {
    text-align: center;
    color: #888;
    padding: 2rem 0;
}
.reviews-already-reviewed {
    text-align: center;
    color: #666;
    padding: 1rem 0;
}

/* Review Form */
.review-form-wrap {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid #eee;
}
.review-form-wrap h3 {
    margin-bottom: 1rem;
}
.review-form .form-row {
    display: flex;
    gap: 1rem;
}
.review-form .form-row .form-group { flex: 1; }
.review-form .form-group {
    margin-bottom: 1rem;
}
.review-form label {
    display: block;
    margin-bottom: 0.35rem;
    font-weight: 600;
    font-size: 0.9rem;
}
.review-form .required { color: #e74c3c; }

/* Star Rating Input */
.star-rating-input {
    display: inline-flex;
    gap: 4px;
    font-size: 1.5rem;
    cursor: pointer;
}
.star-rating-input .star-label {
    cursor: pointer;
    color: #ccc;
    transition: color 0.15s;
}
.star-rating-input .star-label.active i,
.star-rating-input .star-label.hover i {
    color: #f5a623;
}
.star-rating-input .star-label.active i:before,
.star-rating-input .star-label.hover i:before {
    content: "\f005";
    font-weight: 900;
}

@media (max-width: 600px) {
    .reviews-summary { flex-direction: column; align-items: center; }
    .reviews-breakdown { width: 100%; }
    .review-form .form-row { flex-direction: column; gap: 0; }
    .review-header { flex-direction: column; }
}
</style>

<script>
// Interactive star rating
(function() {
    var container = document.getElementById('starRatingInput');
    if (!container) return;
    var labels = container.querySelectorAll('.star-label');
    var currentRating = 0;

    labels.forEach(function(label) {
        label.addEventListener('mouseenter', function() {
            var rating = parseInt(this.dataset.rating);
            labels.forEach(function(l) {
                l.classList.toggle('hover', parseInt(l.dataset.rating) <= rating);
            });
        });
        label.addEventListener('mouseleave', function() {
            labels.forEach(function(l) { l.classList.remove('hover'); });
        });
        label.addEventListener('click', function() {
            currentRating = parseInt(this.dataset.rating);
            labels.forEach(function(l) {
                l.classList.toggle('active', parseInt(l.dataset.rating) <= currentRating);
            });
        });
    });
})();
</script>
<?php endif; ?>

<?php
// Related Blog Articles for this product
if (isFeatureEnabled('blog') && $product):
    $relatedArticles = getProductBlogPosts($product['id'], 3);
    if (!empty($relatedArticles)):
?>
<link rel="stylesheet" href="<?php echo asset('css/blog.css'); ?>">
<section class="section blog-product-articles">
    <div class="container">
        <h2><i class="fas fa-newspaper"></i> Related Articles</h2>
        <div class="blog-product-articles-grid">
            <?php foreach ($relatedArticles as $article): ?>
                <article class="blog-card">
                    <?php if ($article['featured_image']): ?>
                        <a href="<?php echo url('index.php?page=blog-post&slug=' . e($article['slug'])); ?>" class="blog-card-image">
                            <img src="<?php echo e($article['featured_image']); ?>" alt="<?php echo e($article['title']); ?>" loading="lazy">
                        </a>
                    <?php endif; ?>
                    <div class="blog-card-body">
                        <h3 class="blog-card-title">
                            <a href="<?php echo url('index.php?page=blog-post&slug=' . e($article['slug'])); ?>"><?php echo e($article['title']); ?></a>
                        </h3>
                        <div class="blog-card-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($article['published_at'])); ?></span>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php
    endif;
endif;
?>

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
