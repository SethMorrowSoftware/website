<?php
/**
 * Order Inquiry Page — Multi-Step Form
 */

$hero = getHero('order');
$categories = getCategories();
$allProducts = getAllProducts();
$csrfToken = generateCSRFToken();
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Order Inquiry'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Tell Us What You Need — We\'ll Get Back to You Fast'); ?></p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Order Inquiry</span>
    </div>
</div>

<!-- Multi-Step Form -->
<section class="section">
    <div class="container" style="max-width: var(--container-lg);">
        <div class="multi-step-form" id="orderForm">
            <!-- Progress Bar -->
            <div class="step-progress">
                <div class="step-indicator active" data-step="1">
                    <span class="step-num">1</span>
                    <span class="step-label">Category</span>
                </div>
                <div class="step-indicator" data-step="2">
                    <span class="step-num">2</span>
                    <span class="step-label">Details</span>
                </div>
                <div class="step-indicator" data-step="3">
                    <span class="step-num">3</span>
                    <span class="step-label">Delivery</span>
                </div>
                <div class="step-indicator" data-step="4">
                    <span class="step-num">4</span>
                    <span class="step-label">Contact</span>
                </div>
                <div class="step-indicator" data-step="5">
                    <span class="step-num">5</span>
                    <span class="step-label">Review</span>
                </div>
            </div>

            <form method="POST" action="<?php echo url('index.php'); ?>" id="orderInquiryForm">
                <input type="hidden" name="action" value="order_inquiry">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                <!-- Step 1: Category Selection -->
                <div class="form-step active" data-step="1">
                    <h3>What are you looking for?</h3>
                    <div class="service-options">
                        <?php foreach ($categories as $cat): ?>
                            <label class="service-option" data-value="<?php echo e($cat['slug']); ?>">
                                <input type="radio" name="service_type" value="<?php echo e($cat['slug']); ?>" required>
                                <div class="icon"><i class="fas <?php echo e($cat['icon'] ?? 'fa-tag'); ?>"></i></div>
                                <h4><?php echo e($cat['name']); ?></h4>
                                <p><?php echo e($cat['description']); ?></p>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Step 2: Product Details -->
                <div class="form-step" data-step="2">
                    <h3>Tell us the details</h3>

                    <!-- Dynamic product options per category -->
                    <?php foreach ($categories as $cat): ?>
                        <?php
                        $catProducts = array_filter($allProducts, function($p) use ($cat) {
                            return $p['category_slug'] === $cat['slug'];
                        });
                        ?>
                        <div id="categoryOptions_<?php echo e($cat['slug']); ?>" class="category-options hidden" data-category="<?php echo e($cat['slug']); ?>">
                            <?php if (!empty($catProducts)): ?>
                            <div class="form-group">
                                <label>Select <?php echo e($cat['name']); ?> <span class="required">*</span></label>
                                <select name="product_details[product]" class="form-control category-product-select" data-category="<?php echo e($cat['slug']); ?>">
                                    <option value="">-- Choose an option --</option>
                                    <?php foreach ($catProducts as $p): ?>
                                        <option value="<?php echo e($p['name']); ?>"><?php echo e($p['name']); ?> (<?php echo e($p['price'] ?: 'Call for pricing'); ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <div class="form-group">
                                <label>Quantity / Details</label>
                                <input type="text" name="product_details[quantity]" class="form-control category-quantity-input" data-category="<?php echo e($cat['slug']); ?>" placeholder="e.g., quantity, size, specifications...">
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="form-group">
                        <label>Describe your needs</label>
                        <textarea name="product_details[description]" class="form-control" placeholder="Tell us more about what you need..." rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Additional Notes</label>
                        <textarea name="product_details[notes]" class="form-control" placeholder="Any other details we should know..." rows="3"></textarea>
                    </div>
                </div>

                <!-- Step 3: Delivery Info -->
                <div class="form-step" data-step="3">
                    <h3>Delivery Information</h3>
                    <div class="form-group">
                        <label for="delivery_address">Delivery Address <span class="required">*</span></label>
                        <input type="text" id="delivery_address" name="delivery_address" class="form-control" required placeholder="Full street address">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="preferred_date">Preferred Date</label>
                            <input type="date" id="preferred_date" name="preferred_date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Preferred Time</label>
                            <select name="product_details[preferred_time]" class="form-control">
                                <option value="">No preference</option>
                                <option value="morning">Morning (7am - 12pm)</option>
                                <option value="afternoon">Afternoon (12pm - 5pm)</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Special Instructions</label>
                        <textarea name="product_details[access_notes]" class="form-control" placeholder="Any special instructions for delivery or service? (gate codes, parking, etc.)" rows="3"></textarea>
                    </div>
                </div>

                <!-- Step 4: Contact Info -->
                <div class="form-step" data-step="4">
                    <h3>Your Contact Information</h3>
                    <div class="form-group">
                        <label for="order_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="order_name" name="name" class="form-control" required placeholder="Your full name">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="order_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="order_email" name="email" class="form-control" required placeholder="your@email.com">
                        </div>
                        <div class="form-group">
                            <label for="order_phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="order_phone" name="phone" class="form-control" required placeholder="(555) 000-0000">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="order_notes">Anything else?</label>
                        <textarea id="order_notes" name="notes" class="form-control" placeholder="Any additional information..." rows="3"></textarea>
                    </div>
                </div>

                <!-- Step 5: Review -->
                <div class="form-step" data-step="5">
                    <h3>Review Your Inquiry</h3>
                    <div id="orderReview" style="background: var(--color-light); padding: var(--space-xl); border-radius: var(--radius-md); margin-bottom: var(--space-xl);">
                        <p style="color: var(--color-gray-500);">Please review your information above before submitting.</p>
                    </div>
                    <p style="font-size: var(--text-sm); color: var(--color-gray-500);">
                        <i class="fas fa-info-circle"></i>
                        By submitting this inquiry, you are not committing to a purchase. We will contact you within 24 hours to discuss your needs and provide a quote.
                    </p>
                </div>

                <!-- Navigation Buttons -->
                <div class="form-nav">
                    <button type="button" class="btn btn-outline-dark" id="prevStep" style="display: none;">
                        <i class="fas fa-arrow-left"></i> Previous
                    </button>
                    <div></div>
                    <button type="button" class="btn btn-primary" id="nextStep">
                        Next <i class="fas fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-primary btn-lg" id="submitOrder" style="display: none;">
                        <i class="fas fa-paper-plane"></i> Submit Inquiry
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
