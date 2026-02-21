<?php
/**
 * Block: Spacer
 * Vertical spacing.
 */
$heightMap = ['small' => '2rem', 'medium' => '4rem', 'large' => '6rem', 'xlarge' => '8rem'];
$h = $heightMap[$height ?? 'medium'] ?? '4rem';
?>
<div class="block-spacer" style="height: <?php echo $h; ?>;"></div>
