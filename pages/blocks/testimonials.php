<?php
/**
 * Block: Testimonials
 * Customer testimonials display.
 */
$numTestimonials = (int)($count ?? 3);
$testimonialLayout = $layout ?? 'grid';
$allTestimonials = getTestimonials();
$items = array_slice($allTestimonials, 0, $numTestimonials);

if (empty($items)) return;
?>
<section class="block-testimonials block-testimonials--<?php echo e($testimonialLayout); ?>">
    <div class="container">
        <?php if (!empty($heading)): ?>
            <h2 class="block-heading"><?php echo e($heading); ?></h2>
        <?php endif; ?>
        <div class="block-testimonials-grid">
            <?php foreach ($items as $testimonial): ?>
                <div class="testimonial-card">
                    <?php if (!empty($testimonial['rating'])): ?>
                        <div class="testimonial-rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="<?php echo $i <= $testimonial['rating'] ? 'fas' : 'far'; ?> fa-star" style="color: var(--color-secondary);"></i>
                            <?php endfor; ?>
                        </div>
                    <?php endif; ?>
                    <blockquote><?php echo e($testimonial['quote']); ?></blockquote>
                    <div class="testimonial-author">
                        <strong><?php echo e($testimonial['customer_name']); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
