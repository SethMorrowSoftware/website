<?php
/**
 * Dynamic XML Sitemap Generator
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$baseUrl = getCanonicalBaseUrl();

$db = getDB();

// System pages — only include enabled features
$systemPages = ['home'];
if (isFeatureEnabled('about_page')) $systemPages[] = 'about';
if (isFeatureEnabled('catalog')) $systemPages[] = 'catalog';
if (isFeatureEnabled('contact_form')) $systemPages[] = 'contact';
if (isFeatureEnabled('order_inquiry')) $systemPages[] = 'order';

// Custom published pages
$customPages = $db->query("SELECT slug, updated_at FROM pages WHERE is_published = 1 AND is_system = 0")->fetchAll();

// Product pages (visible and available)
$products = [];
$categories = [];
if (isFeatureEnabled('catalog')) {
    $products = $db->query("SELECT slug, created_at FROM products WHERE is_visible = 1 AND is_available = 1")->fetchAll();
    $categories = $db->query("SELECT slug FROM product_categories WHERE is_visible = 1")->fetchAll();
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/'); ?></loc>
        <priority>1.0</priority>
    </url>
<?php foreach ($systemPages as $slug): if ($slug === 'home') continue; ?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/' . $slug); ?></loc>
        <priority>0.8</priority>
    </url>
<?php endforeach; ?>
<?php foreach ($categories as $cat): ?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/catalog?category=' . urlencode($cat['slug'])); ?></loc>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>
<?php foreach ($products as $product): ?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/product/' . $product['slug']); ?></loc>
        <?php if ($product['created_at']): ?>
        <lastmod><?php echo date('Y-m-d', strtotime($product['created_at'])); ?></lastmod>
        <?php endif; ?>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>
<?php foreach ($customPages as $cp): ?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/' . $cp['slug']); ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($cp['updated_at'])); ?></lastmod>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>
<?php
// Blog pages
if (isFeatureEnabled('blog')):
    $blogPosts = $db->query("SELECT slug, updated_at, published_at FROM blog_posts WHERE " . BLOG_PUBLISHED_CONDITION . " AND published_at <= NOW() ORDER BY published_at DESC")->fetchAll();
    $blogCategories = $db->query("SELECT slug FROM blog_categories WHERE is_visible = 1")->fetchAll();
?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/index.php?page=blog'); ?></loc>
        <priority>0.8</priority>
    </url>
<?php foreach ($blogCategories as $bc): ?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/index.php?page=blog&category=' . urlencode($bc['slug'])); ?></loc>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>
<?php foreach ($blogPosts as $bp): ?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/index.php?page=blog-post&slug=' . urlencode($bp['slug'])); ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($bp['updated_at'] ?: $bp['published_at'])); ?></lastmod>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>
<?php endif; ?>
</urlset>
