<?php
/**
 * Gallery Page — TheWuzaBus Build Photos & Videos
 * Business showcase gallery with category filtering, video support, and before/after
 */

$hero = getHero('gallery');

$legacyPhotoMeta = [
    '20200615_134615_fx.jpg' => ['caption' => 'Shuttle bus conversion exterior with custom roof rack and solar setup', 'category' => 'completed', 'featured' => true],
    '20210508_162602.jpg' => ['caption' => 'Custom kitchen with live-edge countertop, tile backsplash, and pine paneling', 'category' => 'interiors', 'featured' => true],
    '20210518_213104.jpg' => ['caption' => 'Bedroom area with queen bed, mini-split climate control, and overhead storage', 'category' => 'interiors', 'featured' => true],
    '20200508_215108.jpg' => ['caption' => 'Cozy bus interior with kitchenette and warm wood ceiling', 'category' => 'interiors', 'featured' => false],
    '20241129_191724.jpg' => ['caption' => 'Victron MultiPlus inverter with SmartSolar MPPT charge controllers', 'category' => 'electrical', 'featured' => true],
    '20240515_151250.jpg' => ['caption' => 'SOK lithium battery bank integrated with Victron controls', 'category' => 'electrical', 'featured' => false],
    '20250114_161911.jpg' => ['caption' => 'EG4 6000XP inverter and breaker panel installation', 'category' => 'electrical', 'featured' => false],
    '20250420_104130.jpg' => ['caption' => 'Complete Victron energy system with multiple MPPT controllers', 'category' => 'electrical', 'featured' => false],
    '20201231_153022.jpg' => ['caption' => 'Conversion in progress at the shop with rear buildout staging', 'category' => 'process', 'featured' => true],
    '20230519_100424.jpg' => ['caption' => 'MaxxAir vent fan prepared for roof installation', 'category' => 'process', 'featured' => false],
    '20211011_123957.jpg' => ['caption' => 'Electrical layout and wiring during the build phase', 'category' => 'process', 'featured' => false],
    '20200508_215108 (1).jpg' => ['caption' => 'Open-house walkthrough with clients touring a finished build', 'category' => 'events', 'featured' => false],
];

$galleryItems = [];
$db = getDB();

if (tableExists($db, 'gallery_items')) {
    $stmt = $db->query('SELECT * FROM gallery_items WHERE is_active = 1 ORDER BY sort_order ASC, id DESC');
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $isVideo = ($row['media_type'] ?? 'image') === 'video';
        $galleryItems[] = [
            'type' => $isVideo ? 'video' : 'image',
            'src' => 'uploads/' . ltrim($row['media_path'], '/'),
            'thumbnail' => 'wuzabus_photos/20200615_134615_fx.jpg',
            'caption' => $row['caption'] ?: 'TheWuzaBus project media',
            'category' => $row['category'] ?: ($isVideo ? 'videos' : 'completed'),
            'featured' => !empty($row['featured']),
        ];
    }
}

// Backward-compatible fallback for existing installs with static gallery assets only.
if (empty($galleryItems)) {
    $photoSourceDirs = [
        __DIR__ . '/../uploads/images/wuzabus_photos' => 'uploads/images/wuzabus_photos/',
        __DIR__ . '/../wuzabus_photos' => 'wuzabus_photos/',
    ];
    $photoMap = [];
    foreach ($photoSourceDirs as $diskDir => $webPrefix) {
        $photoFiles = glob($diskDir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
        sort($photoFiles);
        foreach ($photoFiles as $photoFile) {
            $baseName = basename($photoFile);
            if (!isset($photoMap[$baseName])) {
                $photoMap[$baseName] = $webPrefix . $baseName;
            }
        }
    }
    ksort($photoMap);
    foreach ($photoMap as $baseName => $webPath) {
        $meta = $legacyPhotoMeta[$baseName] ?? [
            'caption' => 'TheWuzaBus project photo: ' . pathinfo($baseName, PATHINFO_FILENAME),
            'category' => 'completed',
            'featured' => false,
        ];
        $galleryItems[] = [
            'type' => 'image',
            'src' => $webPath,
            'caption' => $meta['caption'],
            'category' => $meta['category'],
            'featured' => (bool)($meta['featured'] ?? false),
        ];
    }

    $videoFiles = glob(__DIR__ . '/../uploads/videos/*.{mp4,webm,mov,m4v,MP4,WEBM,MOV,M4V}', GLOB_BRACE) ?: [];
    sort($videoFiles);
    foreach ($videoFiles as $videoFile) {
        $baseName = basename($videoFile);
        $galleryItems[] = [
            'type' => 'video',
            'src' => 'uploads/videos/' . $baseName,
            'thumbnail' => 'wuzabus_photos/20200615_134615_fx.jpg',
            'caption' => 'TheWuzaBus build video: ' . str_replace(['_', '-'], ' ', pathinfo($baseName, PATHINFO_FILENAME)),
            'category' => 'videos',
            'featured' => false,
        ];
    }
}

$categories = [
    'all' => ['label' => 'All Media', 'icon' => 'fa-th'],
    'completed' => ['label' => 'Completed Builds', 'icon' => 'fa-bus'],
    'interiors' => ['label' => 'Interiors', 'icon' => 'fa-couch'],
    'electrical' => ['label' => 'Solar & Electrical', 'icon' => 'fa-bolt'],
    'process' => ['label' => 'Build Process', 'icon' => 'fa-hammer'],
    'events' => ['label' => 'Events', 'icon' => 'fa-calendar-alt'],
    'videos' => ['label' => 'Videos', 'icon' => 'fa-play-circle'],
];

$totalPhotos = count(array_filter($galleryItems, fn($i) => $i['type'] === 'image'));
$totalVideos = count(array_filter($galleryItems, fn($i) => $i['type'] === 'video'));
?>

<link rel="stylesheet" href="<?php echo asset('css/gallery.css'); ?>">

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e(getImageUrl($hero['background_image'])); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Our Work'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Builds, Events & Behind the Scenes'); ?></p>
        <div class="gallery-hero-stats">
            <div class="gallery-hero-stat">
                <span class="gallery-hero-stat-number"><?php echo $totalPhotos; ?></span>
                <span class="gallery-hero-stat-label">Photos</span>
            </div>
            <div class="gallery-hero-stat">
                <span class="gallery-hero-stat-number"><?php echo $totalVideos; ?></span>
                <span class="gallery-hero-stat-label">Videos</span>
            </div>
            <div class="gallery-hero-stat">
                <span class="gallery-hero-stat-number">20+</span>
                <span class="gallery-hero-stat-label">Builds Completed</span>
            </div>
        </div>
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

<!-- Featured Showcase -->
<section class="section gallery-featured-section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Featured Work</h2>
            <p>Highlights from our latest builds and projects</p>
        </div>

        <div class="gallery-featured-grid fade-in">
            <?php
            $featuredItems = array_values(array_filter($galleryItems, fn($i) => $i['featured']));
            $featuredCount = 0;
            foreach ($featuredItems as $idx => $item):
                if ($featuredCount >= 5) break;
                $featuredCount++;
                $isVideo = $item['type'] === 'video';
                $imgSrc = $isVideo ? ($item['thumbnail'] ?? '') : $item['src'];
                $lightboxType = $isVideo ? 'video' : 'image';
                $lightboxSrc = $isVideo ? ($item['src'] ?? '') : $imgSrc;
                $sizeClass = $idx === 0 ? 'gallery-featured-large' : 'gallery-featured-small';
            ?>
                <div class="gallery-featured-item <?php echo $sizeClass; ?>">
                    <div class="gallery-featured-media">
                        <?php if ($isVideo): ?>
                            <div class="gallery-video-badge"><i class="fas fa-play"></i> <?php echo e($item['duration'] ?? ''); ?></div>
                        <?php endif; ?>
                        <a href="<?php echo e(url($lightboxSrc)); ?>" class="lightbox-trigger" data-lightbox-type="<?php echo e($lightboxType); ?>" data-caption="<?php echo e($item['caption']); ?>">
                            <img src="<?php echo e(url($imgSrc)); ?>" alt="<?php echo e($item['caption']); ?>" loading="lazy">
                        </a>
                        <div class="gallery-featured-overlay">
                            <span class="gallery-featured-category">
                                <i class="fas <?php echo e($categories[$item['category']]['icon'] ?? 'fa-tag'); ?>"></i>
                                <?php echo e($categories[$item['category']]['label'] ?? $item['category']); ?>
                            </span>
                            <p class="gallery-featured-caption"><?php echo e($item['caption']); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Category Filter Tabs + Gallery Grid -->
<section class="section section-light" id="galleryBrowse">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Browse Gallery</h2>
            <p>Filter by category or media type</p>
            <?php if ($totalVideos === 0): ?>
                <p class="gallery-video-hint"><i class="fas fa-info-circle"></i> No videos uploaded yet. Add MP4/WebM files in <code>uploads/videos</code> and they will appear automatically.</p>
            <?php endif; ?>
        </div>

        <div class="gallery-filter-bar fade-in">
            <?php foreach ($categories as $key => $cat): ?>
                <button class="gallery-filter-btn <?php echo $key === 'all' ? 'active' : ''; ?>" data-filter="<?php echo e($key); ?>">
                    <i class="fas <?php echo e($cat['icon']); ?>"></i>
                    <span><?php echo e($cat['label']); ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="gallery-masonry" id="galleryGrid">
            <?php foreach ($galleryItems as $index => $item):
                $isVideo = $item['type'] === 'video';
                $imgSrc = $isVideo ? ($item['thumbnail'] ?? '') : $item['src'];
                $lightboxType = $isVideo ? 'video' : 'image';
                $lightboxSrc = $isVideo ? ($item['src'] ?? '') : $imgSrc;
            ?>
                <div class="gallery-item fade-in" data-category="<?php echo e($item['category']); ?>" data-type="<?php echo e($item['type']); ?>">
                    <div class="gallery-item-inner">
                        <?php if ($isVideo): ?>
                            <div class="gallery-video-badge"><i class="fas fa-play"></i> Video</div>
                        <?php endif; ?>
                        <a href="<?php echo e(url($lightboxSrc)); ?>" class="lightbox-trigger" data-lightbox-type="<?php echo e($lightboxType); ?>" data-caption="<?php echo e($item['caption']); ?>">
                            <img src="<?php echo e(url($imgSrc)); ?>" alt="<?php echo e($item['caption']); ?>" loading="lazy">
                        </a>
                        <div class="gallery-item-info">
                            <span class="gallery-item-type">
                                <i class="fas <?php echo $isVideo ? 'fa-video' : 'fa-camera'; ?>"></i>
                            </span>
                            <p class="gallery-item-caption"><?php echo e($item['caption']); ?></p>
                            <span class="gallery-item-cat">
                                <i class="fas <?php echo e($categories[$item['category']]['icon'] ?? 'fa-tag'); ?>"></i>
                                <?php echo e($categories[$item['category']]['label'] ?? ''); ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="gallery-empty" id="galleryEmpty" style="display: none;">
            <i class="fas fa-images"></i>
            <h3>No items match this filter</h3>
            <p>Try selecting a different category above</p>
        </div>
    </div>
</section>

<!-- Before & After Showcase -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Before & After</h2>
            <p>See the transformation from bare bus to beautiful home</p>
        </div>

        <div class="gallery-before-after-grid">
            <div class="gallery-ba-card fade-in">
                <div class="gallery-ba-header">
                    <h3><i class="fas fa-bus"></i> Shuttle Bus Exterior</h3>
                </div>
                <div class="gallery-ba-images">
                    <div class="gallery-ba-side">
                        <span class="gallery-ba-label gallery-ba-before">Before</span>
                        <img src="<?php echo e(url('wuzabus_photos/20201231_153022.jpg')); ?>" alt="Bus before conversion" loading="lazy">
                    </div>
                    <div class="gallery-ba-divider">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="gallery-ba-side">
                        <span class="gallery-ba-label gallery-ba-after">After</span>
                        <img src="<?php echo e(url('wuzabus_photos/20200615_134615_fx.jpg')); ?>" alt="Bus after conversion" loading="lazy">
                    </div>
                </div>
            </div>

            <div class="gallery-ba-card fade-in">
                <div class="gallery-ba-header">
                    <h3><i class="fas fa-utensils"></i> Interior Kitchen Build</h3>
                </div>
                <div class="gallery-ba-images">
                    <div class="gallery-ba-side">
                        <span class="gallery-ba-label gallery-ba-before">Before</span>
                        <img src="<?php echo e(url('wuzabus_photos/20211011_123957.jpg')); ?>" alt="Interior before buildout" loading="lazy">
                    </div>
                    <div class="gallery-ba-divider">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="gallery-ba-side">
                        <span class="gallery-ba-label gallery-ba-after">After</span>
                        <img src="<?php echo e(url('wuzabus_photos/20210508_162602.jpg')); ?>" alt="Kitchen after buildout" loading="lazy">
                    </div>
                </div>
            </div>

            <div class="gallery-ba-card fade-in">
                <div class="gallery-ba-header">
                    <h3><i class="fas fa-bolt"></i> Electrical System</h3>
                </div>
                <div class="gallery-ba-images">
                    <div class="gallery-ba-side">
                        <span class="gallery-ba-label gallery-ba-before">Before</span>
                        <img src="<?php echo e(url('wuzabus_photos/20211011_123957.jpg')); ?>" alt="Empty electrical bay" loading="lazy">
                    </div>
                    <div class="gallery-ba-divider">
                        <i class="fas fa-arrow-right"></i>
                    </div>
                    <div class="gallery-ba-side">
                        <span class="gallery-ba-label gallery-ba-after">After</span>
                        <img src="<?php echo e(url('wuzabus_photos/20241129_191724.jpg')); ?>" alt="Complete Victron energy system" loading="lazy">
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Project Spotlight -->
<section class="section section-dark">
    <div class="container">
        <div class="section-header fade-in">
            <h2 style="color: var(--color-white);">Project Spotlight</h2>
            <p style="color: rgba(255,255,255,0.7);">A closer look at one of our favorite builds</p>
        </div>

        <div class="gallery-spotlight fade-in">
            <div class="gallery-spotlight-main">
                <img src="<?php echo e(url('wuzabus_photos/20200615_134615_fx.jpg')); ?>" alt="Featured shuttle bus conversion" loading="lazy">
            </div>
            <div class="gallery-spotlight-details">
                <h3>The Adventure Shuttle</h3>
                <p class="gallery-spotlight-subtitle">2018 Ford Shuttle Bus — Full Off-Grid Conversion</p>
                <p>This shuttle bus was transformed from a retired airport shuttle into a fully self-contained off-grid home. Every inch was designed for full-time living on the road.</p>
                <ul class="gallery-spotlight-specs">
                    <li><i class="fas fa-solar-panel"></i> <strong>Solar:</strong> 800W rooftop array with Victron MPPT</li>
                    <li><i class="fas fa-battery-full"></i> <strong>Batteries:</strong> 400Ah SOK LiFePO4 bank</li>
                    <li><i class="fas fa-bolt"></i> <strong>Inverter:</strong> Victron MultiPlus 3000W</li>
                    <li><i class="fas fa-utensils"></i> <strong>Kitchen:</strong> Live-edge walnut counters, propane cooktop</li>
                    <li><i class="fas fa-bed"></i> <strong>Sleeping:</strong> Queen bed with under-storage</li>
                    <li><i class="fas fa-snowflake"></i> <strong>Climate:</strong> Mini-split + MaxxAir fans</li>
                    <li><i class="fas fa-ruler-combined"></i> <strong>Length:</strong> 25 ft</li>
                    <li><i class="fas fa-clock"></i> <strong>Build Time:</strong> 4 months</li>
                </ul>
                <div style="margin-top: var(--space-xl);">
                    <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg" style="background: var(--color-secondary); border-color: var(--color-secondary);">
                        <i class="fas fa-clipboard-list"></i> Get a Quote for Your Build
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="section section-primary">
    <div class="container text-center">
        <h2 style="color: var(--color-white);" class="fade-in">Like What You See?</h2>
        <p style="font-size: var(--text-lg); margin: var(--space-lg) auto; max-width: 700px; opacity: 0.9;" class="fade-in">
            Every project you see here started with a conversation. Tell us about your vision and let's make it happen.
        </p>
        <div class="btn-group fade-in" style="justify-content: center; gap: var(--space-md);">
            <a href="<?php echo url('index.php?page=order'); ?>" class="btn btn-primary btn-lg" style="background: var(--color-secondary); border-color: var(--color-secondary);">Get a Free Quote</a>
            <a href="<?php echo url('index.php?page=catalog'); ?>" class="btn btn-outline btn-lg" style="border-color: rgba(255,255,255,0.5); color: var(--color-white);">Browse Services</a>
            <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-outline btn-lg" style="border-color: rgba(255,255,255,0.5); color: var(--color-white);">Contact Us</a>
        </div>
    </div>
</section>

<!-- Gallery Filter JS -->
<script>
(function() {
    var filterBtns = document.querySelectorAll('.gallery-filter-btn');
    var galleryItems = document.querySelectorAll('.gallery-item');
    var emptyState = document.getElementById('galleryEmpty');

    filterBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            filterBtns.forEach(function(b) { b.classList.remove('active'); });
            btn.classList.add('active');
            var filter = btn.getAttribute('data-filter');
            var visibleCount = 0;

            galleryItems.forEach(function(item) {
                var category = item.getAttribute('data-category');
                var type = item.getAttribute('data-type');
                var show = false;

                if (filter === 'all') show = true;
                else if (filter === 'videos') show = (type === 'video');
                else show = (category === filter);

                item.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            emptyState.style.display = visibleCount === 0 ? '' : 'none';
        });
    });
})();
</script>
