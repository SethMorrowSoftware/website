<?php
/**
 * Photo Gallery Page — Wuzabus Off-Grid Electrical
 */

$hero = getHero('gallery');

// Gallery images with categories and captions
$galleryImages = [
    [
        'src' => 'uploads/images/wuzabus/bus-exterior-solar.jpg',
        'alt' => 'Converted Chevy bus with rooftop solar panels and custom build-out',
        'caption' => 'Full bus conversion with rooftop solar array',
        'category' => 'builds',
    ],
    [
        'src' => 'uploads/images/wuzabus/full-system-sok-batteries.jpg',
        'alt' => 'Complete off-grid electrical system with SOK lithium batteries and Victron components',
        'caption' => 'Complete system: 4x SOK batteries, Victron MultiPlus, dual MPPT controllers',
        'category' => 'electrical',
    ],
    [
        'src' => 'uploads/images/wuzabus/victron-multiplus-rack.jpg',
        'alt' => 'Victron MultiPlus 12|2000|80 inverter with Lynx Distributor and SmartSolar MPPT controllers',
        'caption' => 'Victron MultiPlus rack with Lynx Distributor and dual MPPT 150|85',
        'category' => 'electrical',
    ],
    [
        'src' => 'uploads/images/wuzabus/battery-bank-victron.jpg',
        'alt' => 'Clean battery bank installation with Victron MultiPlus and MPPT charge controllers',
        'caption' => 'Battery bank with Victron MultiPlus and MPPT — clean wiring, proper fusing',
        'category' => 'electrical',
    ],
    [
        'src' => 'uploads/images/wuzabus/desert-inverter-install.jpg',
        'alt' => 'EG4 6000XP inverter and breaker panel installed in desert off-grid setting',
        'caption' => 'EG4 6000XP inverter install — desert off-grid setup with EG4 batteries',
        'category' => 'electrical',
    ],
    [
        'src' => 'uploads/images/wuzabus/electrical-victron-panel.jpg',
        'alt' => 'Victron solar charge controllers and electrical components mounted on panel',
        'caption' => 'Victron charge controllers and component panel — properly mounted and wired',
        'category' => 'electrical',
    ],
    [
        'src' => 'uploads/images/wuzabus/bus-interior-kitchen.jpg',
        'alt' => 'Bus conversion interior with live-edge countertop, pine walls, and tile backsplash',
        'caption' => 'Bus interior — live-edge countertop, pine walls, full electrical throughout',
        'category' => 'interiors',
    ],
    [
        'src' => 'uploads/images/wuzabus/bus-interior-bedroom.jpg',
        'alt' => 'Bus conversion bedroom with mini-split AC, pine ceiling, and overhead cabinets',
        'caption' => 'Bedroom area with mini-split AC — powered by the off-grid electrical system',
        'category' => 'interiors',
    ],
    [
        'src' => 'uploads/images/wuzabus/bus-interior-living.jpg',
        'alt' => 'Bus conversion living area and kitchen with warm lighting',
        'caption' => 'Living and kitchen area — 12V LED lighting and full appliance power',
        'category' => 'interiors',
    ],
    [
        'src' => 'uploads/images/wuzabus/bus-workshop-build.jpg',
        'alt' => 'Bus conversion in progress in workshop with tools and equipment',
        'caption' => 'Build in progress — tools of the trade loaded up and ready',
        'category' => 'builds',
    ],
    [
        'src' => 'uploads/images/wuzabus/roof-vent-install.jpg',
        'alt' => 'MaxxFan roof vent prepared for installation on bus conversion',
        'caption' => 'Roof vent prepped for install — ventilation is part of every good build',
        'category' => 'builds',
    ],
];

$categories = [
    'all' => 'All Photos',
    'electrical' => 'Electrical Systems',
    'interiors' => 'Interiors',
    'builds' => 'Build Process',
];
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php else: ?>
        <div class="hero-image" style="background-image: url('<?php echo e(url('uploads/images/wuzabus/bus-interior-kitchen.jpg')); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Gallery'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Real Builds. Real Results. No Stock Photos.'); ?></p>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Gallery</span>
    </div>
</div>

<!-- Gallery -->
<section class="section">
    <div class="container">
        <!-- Filter Buttons -->
        <div class="text-center" style="margin-bottom: var(--space-2xl);">
            <?php foreach ($categories as $key => $label): ?>
                <button class="btn btn-sm <?php echo $key === 'all' ? 'btn-primary' : 'btn-outline-dark'; ?> gallery-filter-btn" data-filter="<?php echo e($key); ?>" style="margin: 4px;">
                    <?php echo e($label); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Gallery Grid -->
        <div class="grid grid-3" id="galleryGrid" style="gap: var(--space-xl);">
            <?php foreach ($galleryImages as $index => $image): ?>
                <div class="card fade-in gallery-item" data-category="<?php echo e($image['category']); ?>">
                    <div class="card-image" style="height: 260px; cursor: pointer;" data-lightbox="<?php echo e(url($image['src'])); ?>" data-caption="<?php echo e($image['caption']); ?>">
                        <img src="<?php echo e(url($image['src'])); ?>" alt="<?php echo e($image['alt']); ?>" loading="lazy">
                    </div>
                    <div class="card-body" style="padding: var(--space-md) var(--space-lg);">
                        <p style="margin:0; font-size: var(--text-sm); color: var(--color-gray-600); line-height: var(--leading-normal);"><?php echo e($image['caption']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section section-dark">
    <div class="container text-center">
        <h2 style="color: var(--color-white);" class="fade-in">Want Your Build to Look Like This?</h2>
        <p style="color: var(--color-gray-300); font-size: var(--text-lg); margin: var(--space-lg) auto; max-width: 600px;" class="fade-in">
            Every project in this gallery started with a message. Let's talk about yours.
        </p>
        <div class="btn-group fade-in" style="justify-content: center;">
            <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg">Get a Quote</a>
            <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline btn-lg">Message Me</a>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterBtns = document.querySelectorAll('.gallery-filter-btn');
    const items = document.querySelectorAll('.gallery-item');

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const filter = this.dataset.filter;

            // Update active button
            filterBtns.forEach(function(b) {
                b.classList.remove('btn-primary');
                b.classList.add('btn-outline-dark');
            });
            this.classList.remove('btn-outline-dark');
            this.classList.add('btn-primary');

            // Filter items
            items.forEach(function(item) {
                if (filter === 'all' || item.dataset.category === filter) {
                    item.style.display = '';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>
