<?php
/**
 * Gallery Page — TheWuzaBus Build Photos
 */

$hero = getHero('gallery');

// Gallery items organized by category
$galleryCategories = [
    [
        'title' => 'Completed Builds',
        'description' => 'Finished bus conversions ready for the road',
        'photos' => [
            ['src' => 'wuzabus_photos/20200615_134615_fx.jpg', 'caption' => 'Shuttle bus conversion exterior with custom roof rack and solar panel setup'],
            ['src' => 'wuzabus_photos/20210508_162602.jpg', 'caption' => 'Custom kitchen with live-edge wood countertop, tile backsplash, and pine paneling'],
            ['src' => 'wuzabus_photos/20210518_213104.jpg', 'caption' => 'Bedroom area with queen bed, mini-split AC, cedar wood paneling, and overhead storage'],
        ],
    ],
    [
        'title' => 'Interior Builds',
        'description' => 'Kitchens, living areas, and sleeping quarters',
        'photos' => [
            ['src' => 'wuzabus_photos/20200508_215108.jpg', 'caption' => 'Cozy bus interior with kitchenette, seating area, and warm wood ceiling'],
            ['src' => 'wuzabus_photos/20210508_162602.jpg', 'caption' => 'Handcrafted kitchen with live-edge countertop and custom cabinetry'],
            ['src' => 'wuzabus_photos/20210518_213104.jpg', 'caption' => 'Comfortable bedroom with cedar paneling and mini-split climate control'],
        ],
    ],
    [
        'title' => 'Electrical & Solar Systems',
        'description' => 'Off-grid power installations with premium components',
        'photos' => [
            ['src' => 'wuzabus_photos/20241129_191724.jpg', 'caption' => 'Victron MultiPlus inverter with SmartSolar MPPT charge controllers'],
            ['src' => 'wuzabus_photos/20240515_151250.jpg', 'caption' => 'SOK lithium battery bank with Victron MultiPlus and MPPT controllers'],
            ['src' => 'wuzabus_photos/20250114_161911.jpg', 'caption' => 'EG4 6000XP inverter with breaker panel installation'],
            ['src' => 'wuzabus_photos/20250420_104130.jpg', 'caption' => 'Complete Victron energy system with multiple MPPT charge controllers'],
        ],
    ],
    [
        'title' => 'Build Process',
        'description' => 'Behind the scenes of our conversion work',
        'photos' => [
            ['src' => 'wuzabus_photos/20201231_153022.jpg', 'caption' => 'Bus conversion in progress at the shop — rear buildout and tool staging'],
            ['src' => 'wuzabus_photos/20230519_100424.jpg', 'caption' => 'MaxxAir vent fan prepared for roof installation'],
            ['src' => 'wuzabus_photos/20211011_123957.jpg', 'caption' => 'Electrical system layout and wiring during build phase'],
        ],
    ],
];
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Our Work'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'See Our Completed Builds & Projects'); ?></p>
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

<!-- Gallery Sections -->
<?php foreach ($galleryCategories as $index => $category): ?>
<section class="section <?php echo $index % 2 === 1 ? 'section-light' : ''; ?>">
    <div class="container">
        <div class="section-header fade-in">
            <h2><?php echo e($category['title']); ?></h2>
            <p><?php echo e($category['description']); ?></p>
        </div>

        <div class="grid grid-3">
            <?php foreach ($category['photos'] as $photo): ?>
                <div class="card fade-in">
                    <div class="card-image">
                        <a href="<?php echo e($photo['src']); ?>" class="lightbox-trigger" data-caption="<?php echo e($photo['caption']); ?>">
                            <img src="<?php echo e($photo['src']); ?>" alt="<?php echo e($photo['caption']); ?>" loading="lazy" style="width:100%; height:250px; object-fit:cover;">
                        </a>
                    </div>
                    <div class="card-body">
                        <p style="font-size: var(--text-sm); color: var(--color-gray-600);"><?php echo e($photo['caption']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endforeach; ?>

<!-- CTA -->
<section class="section section-primary">
    <div class="container text-center">
        <h2 style="color: var(--color-white);" class="fade-in">Ready to Start Your Build?</h2>
        <p style="font-size: var(--text-lg); margin: var(--space-lg) auto; max-width: 700px; opacity: 0.9;" class="fade-in">
            Every project you see here started with a conversation. Tell us about your vision and let's make it happen.
        </p>
        <div class="btn-group fade-in" style="justify-content: center;">
            <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg">Get a Quote</a>
            <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline btn-lg" style="border-color: rgba(255,255,255,0.5); color: var(--color-white);">Contact Us</a>
        </div>
    </div>
</section>
