<?php
/**
 * Block: Image + Text
 * Image alongside text content.
 */
$layoutClass = ($layout ?? 'image-left') === 'image-right' ? 'block-image-text--reverse' : '';
?>
<section class="block-image-text <?php echo $layoutClass; ?>">
    <div class="container">
        <div class="block-image-text-grid">
            <div class="block-image-text-image">
                <?php if (!empty($image)): ?>
                    <img src="<?php echo e($image); ?>" alt="<?php echo e($image_alt ?? ''); ?>" loading="lazy">
                <?php endif; ?>
            </div>
            <div class="block-image-text-content">
                <?php if (!empty($heading)): ?>
                    <h2 class="block-heading"><?php echo e($heading); ?></h2>
                <?php endif; ?>
                <?php echo sanitizeHtml($content ?? ''); ?>
            </div>
        </div>
    </div>
</section>
