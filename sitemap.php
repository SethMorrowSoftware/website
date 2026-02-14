<?php
/**
 * Dynamic XML Sitemap Generator
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$baseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;

$db = getDB();

// System pages
$systemPages = ['home', 'about', 'containers', 'materials', 'trucking', 'contact', 'order', 'payment'];

// Custom published pages
$customPages = $db->query("SELECT slug, updated_at FROM pages WHERE is_published = 1 AND is_system = 0")->fetchAll();

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
<?php foreach ($customPages as $cp): ?>
    <url>
        <loc><?php echo htmlspecialchars($baseUrl . '/' . $cp['slug']); ?></loc>
        <lastmod><?php echo date('Y-m-d', strtotime($cp['updated_at'])); ?></lastmod>
        <priority>0.6</priority>
    </url>
<?php endforeach; ?>
</urlset>
