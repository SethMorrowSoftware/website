<?php
/**
 * Block: Divider
 * Horizontal rule separator.
 */
$allowedStyles = ['solid', 'dashed', 'dotted'];
$divStyle = in_array($style ?? 'solid', $allowedStyles) ? ($style ?? 'solid') : 'solid';
$divWidth = $width ?? 'full';
$widthMap = ['full' => '100%', 'medium' => '60%', 'short' => '30%'];
$w = $widthMap[$divWidth] ?? '100%';
?>
<div class="block-divider" style="text-align: center;">
    <hr style="border-style: <?php echo e($divStyle); ?>; width: <?php echo $w; ?>; margin: 2rem auto; border-color: var(--color-gray-200);">
</div>
