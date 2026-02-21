<?php
/**
 * Block: FAQ Accordion
 * Expandable question/answer pairs.
 */
$faqItems = $items ?? [];
if (empty($faqItems)) return;
?>
<section class="block-faq">
    <div class="container">
        <?php if (!empty($heading)): ?>
            <h2 class="block-heading"><?php echo e($heading); ?></h2>
        <?php endif; ?>
        <div class="faq-accordion">
            <?php foreach ($faqItems as $i => $item): ?>
                <div class="faq-item">
                    <button class="faq-question" aria-expanded="false" onclick="this.setAttribute('aria-expanded', this.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'); this.nextElementSibling.style.display = this.nextElementSibling.style.display === 'block' ? 'none' : 'block';">
                        <span><?php echo e($item['question'] ?? ''); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer" style="display: none;">
                        <p><?php echo e($item['answer'] ?? ''); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
