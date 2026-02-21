<?php
/**
 * Block: Contact Form
 * Embedded contact form (reuses existing contact form logic).
 */
$showPhone = ($show_phone ?? 'yes') === 'yes';
?>
<section class="block-contact-form">
    <div class="container">
        <?php if (!empty($heading)): ?>
            <h2 class="block-heading"><?php echo e($heading); ?></h2>
        <?php endif; ?>
        <form method="POST" action="<?php echo url('index.php?page=' . ($_GET['page'] ?? 'home')); ?>" class="contact-form">
            <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
            <input type="hidden" name="block_contact_form" value="1">
            <div class="form-group">
                <label for="block-contact-name">Name *</label>
                <input type="text" id="block-contact-name" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="block-contact-email">Email *</label>
                <input type="email" id="block-contact-email" name="email" class="form-control" required>
            </div>
            <?php if ($showPhone): ?>
            <div class="form-group">
                <label for="block-contact-phone">Phone</label>
                <input type="tel" id="block-contact-phone" name="phone" class="form-control">
            </div>
            <?php endif; ?>
            <div class="form-group">
                <label for="block-contact-message">Message *</label>
                <textarea id="block-contact-message" name="message" class="form-control" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Send Message</button>
        </form>
    </div>
</section>
