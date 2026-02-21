<?php
/**
 * Block: Map
 * Google Maps embed.
 */
$mapUrl = $embed_url ?? getSetting('google_maps_embed', '');
$mapHeight = $height ?? 'medium';
$heightMap = ['small' => '250px', 'medium' => '400px', 'large' => '550px'];
$h = $heightMap[$mapHeight] ?? '400px';

if (!$mapUrl) return;
?>
<section class="block-map">
    <div class="container">
        <?php if (!empty($heading)): ?>
            <h2 class="block-heading"><?php echo e($heading); ?></h2>
        <?php endif; ?>
        <div class="block-map-wrapper" style="height: <?php echo $h; ?>;">
            <iframe src="<?php echo e($mapUrl); ?>" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        </div>
    </div>
</section>
