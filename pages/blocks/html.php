<?php
/**
 * Block: HTML Embed
 * Raw HTML (admin-only, not user-submitted).
 */
?>
<div class="block-html">
    <div class="container">
        <?php echo sanitizeHtml($content ?? ''); ?>
    </div>
</div>
