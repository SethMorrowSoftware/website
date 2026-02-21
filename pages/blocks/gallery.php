<?php
/**
 * Block: Image Gallery
 * Grid of images.
 */
$galleryImages = $images ?? [];
$cols = max(1, min(6, (int)($columns ?? 3)));
if (empty($galleryImages)) return;
?>
<section class="block-gallery">
    <div class="container">
        <?php if (!empty($heading)): ?>
            <h2 class="block-heading"><?php echo e($heading); ?></h2>
        <?php endif; ?>
        <div class="block-gallery-grid" style="grid-template-columns: repeat(<?php echo $cols; ?>, 1fr);">
            <?php foreach ($galleryImages as $img): ?>
                <figure class="block-gallery-item">
                    <img src="<?php echo e($img['url'] ?? ''); ?>" alt="<?php echo e($img['alt'] ?? ''); ?>" loading="lazy">
                    <?php if (!empty($img['caption'])): ?>
                        <figcaption><?php echo e($img['caption']); ?></figcaption>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    </div>
</section>
