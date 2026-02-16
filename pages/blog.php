<?php
/**
 * Blog Listing Page
 * Displays blog posts with optional category/tag/search/archive filters.
 * Includes sidebar with categories, popular posts, tags, and product widgets.
 */

$blogPageTitle = getSetting('blog_page_title', 'Blog');
$perPage = (int)getSetting('blog_posts_per_page', '9');
$showSidebar = getSetting('blog_show_sidebar', '1') === '1';
$currentPage = max(1, (int)($_GET['p'] ?? 1));

// Build filters
$filters = [];
$filterLabel = '';

if (!empty($_GET['category'])) {
    $filters['category'] = $_GET['category'];
    $cat = getBlogCategory($_GET['category']);
    if ($cat) $filterLabel = 'Category: ' . e($cat['name']);
}

if (!empty($_GET['tag'])) {
    $filters['tag'] = $_GET['tag'];
    $tag = getTagBySlug($_GET['tag']);
    if ($tag) $filterLabel = 'Tag: ' . e($tag['name']);
}

if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
    $filterLabel = 'Search: "' . e($_GET['search']) . '"';
}

if (!empty($_GET['archive'])) {
    $filters['archive'] = $_GET['archive'];
    $filterLabel = 'Archive: ' . date('F Y', strtotime($_GET['archive'] . '-01'));
}

$result = getBlogPosts($currentPage, $perPage, $filters);
$posts = $result['posts'];
$totalPages = $result['pages'];

// Sidebar data
$categories = getBlogCategoriesWithCounts();
$popularPosts = getPopularPosts(5);
$popularTags = getPopularTags(20);
$archiveMonths = getBlogArchiveMonths();

// Hero section
$hero = null;
$db = getDB();
$heroStmt = $db->prepare("SELECT * FROM hero_sections WHERE page_slug = 'blog' AND is_active = 1 LIMIT 1");
$heroStmt->execute();
$hero = $heroStmt->fetch();
?>

<?php if ($hero): ?>
<section class="hero-section" style="<?php if ($hero['background_image']): ?>background-image:url('<?php echo e($hero['background_image']); ?>');<?php endif; ?>">
    <?php if ($hero['background_video']): ?>
        <video class="hero-video" autoplay muted loop playsinline>
            <source src="<?php echo e($hero['background_video']); ?>" type="video/mp4">
        </video>
    <?php endif; ?>
    <div class="hero-overlay" style="opacity:<?php echo $hero['overlay_opacity'] ?? 0.5; ?>"></div>
    <div class="container hero-content">
        <h1><?php echo e($hero['title'] ?: $blogPageTitle); ?></h1>
        <?php if ($hero['subtitle']): ?>
            <p class="hero-subtitle"><?php echo e($hero['subtitle']); ?></p>
        <?php endif; ?>
    </div>
</section>
<?php else: ?>
<section class="page-header-section">
    <div class="container">
        <h1><?php echo e($blogPageTitle); ?></h1>
        <?php if (!$filterLabel): ?>
            <p class="page-subtitle">Stories, news, and insights</p>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<section class="section blog-section">
    <div class="container">
        <!-- Filter indicator -->
        <?php if ($filterLabel): ?>
            <div class="blog-filter-bar">
                <span class="blog-filter-label"><?php echo $filterLabel; ?></span>
                <a href="<?php echo url('index.php?page=blog'); ?>" class="blog-filter-clear"><i class="fas fa-times"></i> Clear filter</a>
            </div>
        <?php endif; ?>

        <!-- Blog search -->
        <div class="blog-search-bar">
            <form method="GET" action="<?php echo url('index.php'); ?>">
                <input type="hidden" name="page" value="blog">
                <div class="blog-search-input-wrap">
                    <input type="text" name="search" placeholder="Search articles..." value="<?php echo e($_GET['search'] ?? ''); ?>" class="blog-search-input">
                    <button type="submit" class="blog-search-btn"><i class="fas fa-search"></i></button>
                </div>
            </form>
        </div>

        <div class="blog-layout <?php echo $showSidebar ? 'blog-layout-with-sidebar' : ''; ?>">
            <!-- Main Content -->
            <div class="blog-main">
                <?php if (empty($posts)): ?>
                    <div class="blog-empty">
                        <i class="fas fa-newspaper"></i>
                        <h2>No posts found</h2>
                        <p><?php echo $filterLabel ? 'Try a different search or filter.' : 'Check back soon for new content!'; ?></p>
                        <?php if ($filterLabel): ?>
                            <a href="<?php echo url('index.php?page=blog'); ?>" class="btn btn-primary">View All Posts</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="blog-grid">
                        <?php foreach ($posts as $post): ?>
                            <article class="blog-card <?php echo $post['is_featured'] ? 'blog-card-featured' : ''; ?>">
                                <?php if ($post['featured_image']): ?>
                                    <a href="<?php echo url('index.php?page=blog-post&slug=' . e($post['slug'])); ?>" class="blog-card-image">
                                        <img src="<?php echo e($post['featured_image']); ?>" alt="<?php echo e($post['featured_image_alt'] ?: $post['title']); ?>" loading="lazy">
                                        <?php if ($post['is_featured']): ?>
                                            <span class="blog-card-badge blog-card-badge-featured"><i class="fas fa-star"></i> Featured</span>
                                        <?php endif; ?>
                                    </a>
                                <?php endif; ?>
                                <div class="blog-card-body">
                                    <?php if ($post['category_name']): ?>
                                        <a href="<?php echo url('index.php?page=blog&category=' . e($post['category_slug'])); ?>" class="blog-card-category"><?php echo e($post['category_name']); ?></a>
                                    <?php endif; ?>
                                    <h2 class="blog-card-title">
                                        <a href="<?php echo url('index.php?page=blog-post&slug=' . e($post['slug'])); ?>"><?php echo e($post['title']); ?></a>
                                    </h2>
                                    <?php if ($post['excerpt']): ?>
                                        <p class="blog-card-excerpt"><?php echo e(mb_substr(strip_tags($post['excerpt']), 0, 160)); ?></p>
                                    <?php endif; ?>
                                    <div class="blog-card-meta">
                                        <?php if (getSetting('blog_show_author', '1') === '1' && $post['author_name']): ?>
                                            <span><i class="fas fa-user"></i> <?php echo e($post['author_name']); ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($post['published_at'])); ?></span>
                                        <?php if ($post['view_count'] > 0): ?>
                                            <span><i class="fas fa-eye"></i> <?php echo number_format($post['view_count']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav class="blog-pagination" aria-label="Blog pagination">
                            <?php
                            $baseParams = $_GET;
                            unset($baseParams['p']);
                            $baseQuery = http_build_query($baseParams);
                            ?>
                            <?php if ($currentPage > 1): ?>
                                <a href="<?php echo url('index.php?' . $baseQuery . '&p=' . ($currentPage - 1)); ?>" class="blog-page-link"><i class="fas fa-chevron-left"></i> Previous</a>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <?php if ($i === $currentPage): ?>
                                    <span class="blog-page-link blog-page-current"><?php echo $i; ?></span>
                                <?php elseif ($i <= 2 || $i > $totalPages - 2 || abs($i - $currentPage) <= 1): ?>
                                    <a href="<?php echo url('index.php?' . $baseQuery . '&p=' . $i); ?>" class="blog-page-link"><?php echo $i; ?></a>
                                <?php elseif ($i === 3 || $i === $totalPages - 2): ?>
                                    <span class="blog-page-dots">&hellip;</span>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a href="<?php echo url('index.php?' . $baseQuery . '&p=' . ($currentPage + 1)); ?>" class="blog-page-link">Next <i class="fas fa-chevron-right"></i></a>
                            <?php endif; ?>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <?php if ($showSidebar): ?>
            <aside class="blog-sidebar">
                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                <div class="blog-widget">
                    <h3 class="blog-widget-title">Categories</h3>
                    <ul class="blog-category-list">
                        <?php foreach ($categories as $cat): ?>
                            <li>
                                <a href="<?php echo url('index.php?page=blog&category=' . e($cat['slug'])); ?>" class="<?php echo ($_GET['category'] ?? '') === $cat['slug'] ? 'active' : ''; ?>">
                                    <?php echo e($cat['name']); ?>
                                    <span class="blog-category-count"><?php echo (int)$cat['post_count']; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Popular Posts -->
                <?php if (!empty($popularPosts)): ?>
                <div class="blog-widget">
                    <h3 class="blog-widget-title">Popular Posts</h3>
                    <div class="blog-widget-posts">
                        <?php foreach ($popularPosts as $pp): ?>
                            <a href="<?php echo url('index.php?page=blog-post&slug=' . e($pp['slug'])); ?>" class="blog-widget-post">
                                <?php if ($pp['featured_image']): ?>
                                    <img src="<?php echo e($pp['featured_image']); ?>" alt="" class="blog-widget-post-img" loading="lazy">
                                <?php endif; ?>
                                <div>
                                    <div class="blog-widget-post-title"><?php echo e(mb_substr($pp['title'], 0, 60)); ?></div>
                                    <div class="blog-widget-post-date"><?php echo date('M j, Y', strtotime($pp['published_at'])); ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tag Cloud -->
                <?php if (!empty($popularTags)): ?>
                <div class="blog-widget">
                    <h3 class="blog-widget-title">Tags</h3>
                    <div class="blog-tag-cloud">
                        <?php foreach ($popularTags as $tag): ?>
                            <a href="<?php echo url('index.php?page=blog&tag=' . e($tag['slug'])); ?>" class="blog-tag <?php echo ($_GET['tag'] ?? '') === $tag['slug'] ? 'active' : ''; ?>"><?php echo e($tag['name']); ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Archive -->
                <?php if (!empty($archiveMonths)): ?>
                <div class="blog-widget">
                    <h3 class="blog-widget-title">Archive</h3>
                    <ul class="blog-archive-list">
                        <?php foreach ($archiveMonths as $am): ?>
                            <li>
                                <a href="<?php echo url('index.php?page=blog&archive=' . e($am['month'])); ?>">
                                    <?php echo date('F Y', strtotime($am['month'] . '-01')); ?>
                                    <span class="blog-category-count"><?php echo (int)$am['post_count']; ?></span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Featured Products Widget -->
                <?php if (isFeatureEnabled('catalog')): ?>
                    <?php
                    $featuredProducts = $db->query('SELECT * FROM products WHERE is_visible = 1 AND is_available = 1 ORDER BY RANDOM() LIMIT 3')->fetchAll();
                    if (!empty($featuredProducts)):
                    ?>
                    <div class="blog-widget">
                        <h3 class="blog-widget-title">Featured Products</h3>
                        <div class="blog-widget-products">
                            <?php foreach ($featuredProducts as $fp): ?>
                                <a href="<?php echo url('index.php?page=product&slug=' . e($fp['slug'])); ?>" class="blog-widget-product">
                                    <?php if ($fp['image']): ?>
                                        <img src="<?php echo e($fp['image']); ?>" alt="<?php echo e($fp['name']); ?>" loading="lazy">
                                    <?php endif; ?>
                                    <div>
                                        <div class="blog-widget-product-name"><?php echo e($fp['name']); ?></div>
                                        <?php if ($fp['price']): ?>
                                            <div class="blog-widget-product-price"><?php echo formatCurrency((float)$fp['price']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </aside>
            <?php endif; ?>
        </div>
    </div>
</section>
