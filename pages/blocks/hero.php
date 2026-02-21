<?php
/**
 * Block: Hero Banner
 * Full-width hero section with background image.
 */
$overlayMap = ['none' => '0', 'light' => '0.3', 'medium' => '0.5', 'dark' => '0.7'];
$opacity = $overlayMap[$overlay_opacity ?? 'medium'] ?? '0.5';
?>
<section class="block-hero" style="background-image: url('<?php echo e($background_image ?? ''); ?>');">
    <div class="block-hero-overlay" style="opacity: <?php echo $opacity; ?>;"></div>
    <div class="block-hero-content">
        <?php if (!empty($heading)): ?>
            <h1 class="block-hero-heading"><?php echo e($heading); ?></h1>
        <?php endif; ?>
        <?php if (!empty($subheading)): ?>
            <p class="block-hero-subheading"><?php echo e($subheading); ?></p>
        <?php endif; ?>
        <?php if (!empty($cta_text) && !empty($cta_link)): ?>
            <a href="<?php echo e($cta_link); ?>" class="btn btn-primary btn-lg"><?php echo e($cta_text); ?></a>
        <?php endif; ?>
    </div>
</section>
