<?php
/**
 * Blog RSS Feed
 * Standard RSS 2.0 feed of latest published blog posts.
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Check if blog is enabled
if (!isFeatureEnabled('blog')) {
    http_response_code(404);
    exit;
}

$companyName = getSetting('company_name', SITE_NAME);
$tagline = getSetting('tagline', '');
$baseUrl = rtrim(getCanonicalBaseUrl(), '/');

// Get latest 20 published posts
$result = getBlogPosts(1, 20);
$posts = $result['posts'];

header('Content-Type: application/rss+xml; charset=UTF-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">
    <channel>
        <title><?php echo htmlspecialchars($companyName . ' Blog', ENT_XML1, 'UTF-8'); ?></title>
        <link><?php echo htmlspecialchars($baseUrl . '/index.php?page=blog', ENT_XML1, 'UTF-8'); ?></link>
        <description><?php echo htmlspecialchars($tagline ?: $companyName . ' Blog', ENT_XML1, 'UTF-8'); ?></description>
        <language>en-us</language>
        <lastBuildDate><?php echo date('r'); ?></lastBuildDate>
        <atom:link href="<?php echo htmlspecialchars($baseUrl . '/rss.php', ENT_XML1, 'UTF-8'); ?>" rel="self" type="application/rss+xml"/>
        <generator>Business Website CMS</generator>

        <?php foreach ($posts as $post): ?>
        <item>
            <title><?php echo htmlspecialchars($post['title'], ENT_XML1, 'UTF-8'); ?></title>
            <link><?php echo htmlspecialchars($baseUrl . '/index.php?page=blog-post&slug=' . urlencode($post['slug']), ENT_XML1, 'UTF-8'); ?></link>
            <guid isPermaLink="true"><?php echo htmlspecialchars($baseUrl . '/index.php?page=blog-post&slug=' . urlencode($post['slug']), ENT_XML1, 'UTF-8'); ?></guid>
            <description><?php echo htmlspecialchars($post['excerpt'] ?: mb_substr(strip_tags($post['content']), 0, 300), ENT_XML1, 'UTF-8'); ?></description>
            <pubDate><?php echo date('r', strtotime($post['published_at'])); ?></pubDate>
            <?php if ($post['author_name']): ?>
                <dc:creator><?php echo htmlspecialchars($post['author_name'], ENT_XML1, 'UTF-8'); ?></dc:creator>
            <?php endif; ?>
            <?php if ($post['category_name']): ?>
                <category><?php echo htmlspecialchars($post['category_name'], ENT_XML1, 'UTF-8'); ?></category>
            <?php endif; ?>
            <?php if ($post['featured_image']): ?>
                <enclosure url="<?php echo htmlspecialchars($baseUrl . '/' . ltrim($post['featured_image'], '/'), ENT_XML1, 'UTF-8'); ?>" type="image/jpeg"/>
            <?php endif; ?>
        </item>
        <?php endforeach; ?>
    </channel>
</rss>
