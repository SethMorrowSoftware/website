<?php
/**
 * Block: Classic Content
 * Renders legacy WYSIWYG HTML content.
 */
?>
<div class="block-classic">
    <div class="container">
        <div class="page-content">
            <?php echo sanitizeHtml($content ?? ''); ?>
        </div>
    </div>
</div>
