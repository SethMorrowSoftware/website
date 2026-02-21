<?php
/**
 * Block: Text Section
 * Rich text with optional heading.
 */
$align = $alignment ?? 'left';
?>
<section class="block-text" style="text-align: <?php echo e($align); ?>;">
    <div class="container">
        <?php if (!empty($heading)): ?>
            <h2 class="block-heading"><?php echo e($heading); ?></h2>
        <?php endif; ?>
        <div class="block-text-content">
            <?php echo sanitizeHtml($content ?? ''); ?>
        </div>
    </div>
</section>
