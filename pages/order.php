<?php
/**
 * Order Inquiry Page — Multi-Step Form
 */

$hero = getHero('order');
$categories = getCategories();
$allProducts = getAllProducts();
$containers = getContainers();
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
                    <span class="step-label">Service Type</span>
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

                <!-- Step 1: Service Type -->
                <div class="form-step active" data-step="1">
                    <h3>What service do you need?</h3>
                    <div class="service-options">
                        <label class="service-option" data-value="container">
                            <input type="radio" name="service_type" value="container" required>
                            <div class="icon"><i class="fas fa-dumpster"></i></div>
                            <h4>Roll Off Container</h4>
                            <p style="font-size: var(--text-sm); color: var(--color-gray-500);">Rent a container for your project</p>
                        </label>

                        <label class="service-option" data-value="material">
                            <input type="radio" name="service_type" value="material">
                            <div class="icon"><i class="fas fa-mountain"></i></div>
                            <h4>Material Delivery</h4>
                            <p style="font-size: var(--text-sm); color: var(--color-gray-500);">Mulch, stone, sand, topsoil, salt</p>
                        </label>

                        <label class="service-option" data-value="trucking">
                            <input type="radio" name="service_type" value="trucking">
                            <div class="icon"><i class="fas fa-truck"></i></div>
                            <h4>Trucking Service</h4>
                            <p style="font-size: var(--text-sm); color: var(--color-gray-500);">Hauling and delivery services</p>
                        </label>
                    </div>
                </div>

                <!-- Step 2: Product Details -->
                <div class="form-step" data-step="2">
                    <h3>Tell us the details</h3>

                    <!-- Container options -->
                    <div id="containerOptions" class="hidden">
                        <div class="form-group">
                            <label>Select Container Size <span class="required">*</span></label>
                            <select name="product_details[container_size]" class="form-control">
                                <option value="">-- Choose a size --</option>
                                <?php foreach ($containers as $c): ?>
                                    <option value="<?php echo e($c['name']); ?>"><?php echo e($c['name']); ?> (<?php echo e($c['dimensions']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>What will you be putting in the container?</label>
                            <textarea name="product_details[container_contents]" class="form-control" placeholder="e.g., Construction debris, household junk, yard waste..." rows="3"></textarea>
                        </div>
                    </div>

                    <!-- Material options -->
                    <div id="materialOptions" class="hidden">
                        <div class="form-group">
                            <label>Select Product <span class="required">*</span></label>
                            <select name="product_details[product]" class="form-control">
                                <option value="">-- Choose a product --</option>
                                <?php
                                $currentCat = '';
                                foreach ($allProducts as $p):
                                    if ($p['category_name'] !== $currentCat):
                                        if ($currentCat !== '') echo '</optgroup>';
                                        $currentCat = $p['category_name'];
                                        echo '<optgroup label="' . e($currentCat) . '">';
                                    endif;
                                ?>
                                    <option value="<?php echo e($p['name']); ?>"><?php echo e($p['name']); ?> (<?php echo e($p['price'] ?: 'Call for pricing'); ?>)</option>
                                <?php endforeach; ?>
                                <?php if ($currentCat !== '') echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="text" name="product_details[quantity]" class="form-control" placeholder="e.g., 5 yards, 3 tons, etc.">
                        </div>
                    </div>

                    <!-- Trucking options -->
                    <div id="truckingOptions" class="hidden">
                        <div class="form-group">
                            <label>Describe what you need hauled or delivered <span class="required">*</span></label>
                            <textarea name="product_details[trucking_details]" class="form-control" placeholder="Please describe your trucking needs..." rows="4"></textarea>
                        </div>
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
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg);">
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
                        <label>Access Notes</label>
                        <textarea name="product_details[access_notes]" class="form-control" placeholder="Any special instructions for delivery access? (gate codes, narrow roads, etc.)" rows="3"></textarea>
                    </div>
                </div>

                <!-- Step 4: Contact Info -->
                <div class="form-step" data-step="4">
                    <h3>Your Contact Information</h3>
                    <div class="form-group">
                        <label for="order_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="order_name" name="name" class="form-control" required placeholder="Your full name">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg);">
                        <div class="form-group">
                            <label for="order_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="order_email" name="email" class="form-control" required placeholder="your@email.com">
                        </div>
                        <div class="form-group">
                            <label for="order_phone">Phone Number <span class="required">*</span></label>
                            <input type="tel" id="order_phone" name="phone" class="form-control" required placeholder="(845) 555-0000">
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
