<?php
/**
 * Block: Call to Action
 * Attention-grabbing CTA banner.
 */
$ctaStyle = $style ?? 'primary';
?>
<section class="block-cta block-cta--<?php echo e($ctaStyle); ?>">
    <div class="container">
        <div class="block-cta-inner">
            <?php if (!empty($heading)): ?>
                <h2><?php echo e($heading); ?></h2>
            <?php endif; ?>
            <?php if (!empty($subtext)): ?>
                <p><?php echo e($subtext); ?></p>
            <?php endif; ?>
            <?php if (!empty($button_text) && !empty($button_link)): ?>
                <a href="<?php echo e($button_link); ?>" class="btn btn-lg btn-cta"><?php echo e($button_text); ?></a>
            <?php endif; ?>
        </div>
    </div>
</section>
