<?php
/**
 * Custom Page Template — for admin-created pages
 *
 * Supports both legacy HTML content and block-based content.
 * Pages with blocks use the block renderer; others fall back to sanitizeHtml().
 */

$pageSlug = $_GET['page'] ?? '';
$customPage = $customPage ?? getPage($pageSlug);

if (!$customPage) {
    echo '<section class="section"><div class="container"><h2>Page Not Found</h2><p>The page you are looking for does not exist.</p><a href="' . url('/') . '" class="btn btn-primary">Go Home</a></div></section>';
    return;
}

$hero = getHero($pageSlug);
$hasBlocks = pageHasBlocks($customPage['blocks'] ?? null);
?>

<!-- Hero -->
<?php if ($hero): ?>
<section class="hero">
    <?php if ($hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? $customPage['title']); ?></h1>
        <?php if ($hero['subtitle']): ?>
            <p><?php echo e($hero['subtitle']); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php elseif (!$hasBlocks): ?>
<section class="hero" style="min-height: 30vh;">
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($customPage['title']); ?></h1>
    </div>
</section>
<?php endif; ?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current"><?php echo e($customPage['title']); ?></span>
    </div>
</div>

<!-- Page Content -->
<?php if ($hasBlocks): ?>
    <div class="page-blocks">
        <?php echo renderBlocks(parseBlocks($customPage['blocks'])); ?>
    </div>
<?php else: ?>
    <section class="page-content">
        <div class="container" style="max-width: var(--container-lg);">
            <?php echo sanitizeHtml($customPage['content']); ?>
        </div>
    </section>
<?php endif; ?>
