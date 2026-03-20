<?php
/**
 * Site Header
 */

$navigation = getNavigation();
$companyName = getSetting('company_name', SITE_NAME);
$companyPhone = getSetting('company_phone');
$companyEmail = getSetting('company_email');
$logoUrl = getSetting('logo');
$facebookUrl = getSetting('facebook_url');
$instagramUrl = getSetting('instagram_url');
$twitterUrl = getSetting('twitter_url');
$currentPage = $_GET['page'] ?? 'home';
$cartEnabled = isFeatureEnabled('cart');
$showPhoneHeader = isFeatureEnabled('phone_header');
$showEmailHeader = isFeatureEnabled('email_header');

// Get page meta
$pageData = getPage($currentPage);
$pageTitle = $pageData ? ($pageData['title'] ?? '') . ' | ' . $companyName : $companyName;
$metaDescription = ($pageData ? $pageData['meta_description'] : getSetting('tagline')) ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo e(getLocale()); ?>" dir="<?php echo getTextDirection(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="description" content="<?php echo e($metaDescription); ?>">
    <meta name="robots" content="index, follow">
    <title><?php echo e($pageTitle); ?></title>

    <!-- Open Graph -->
    <?php
    $ogType = 'website';
    $ogImage = '';
    // Blog post specific OG tags — cache the result so blog-post.php can reuse it
    if ($currentPage === 'blog-post' && !empty($_GET['slug'])) {
        $GLOBALS['_cached_blog_post'] = getBlogPost($_GET['slug']);
        $ogBlogPost = $GLOBALS['_cached_blog_post'];
        if ($ogBlogPost) {
            $ogType = 'article';
            $pageTitle = e($ogBlogPost['title']) . ' | ' . $companyName;
            $metaDescription = $ogBlogPost['meta_description'] ?: $ogBlogPost['excerpt'] ?: $metaDescription;
            $ogImage = $ogBlogPost['og_image'] ?: $ogBlogPost['featured_image'];
        }
    }
    ?>
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:description" content="<?php echo e($metaDescription); ?>">
    <meta property="og:type" content="<?php echo $ogType; ?>">
    <meta property="og:site_name" content="<?php echo e($companyName); ?>">
    <?php if ($ogImage): ?>
        <meta property="og:image" content="<?php echo e($ogImage); ?>">
    <?php endif; ?>
    <?php if ($ogType === 'article' && isset($ogBlogPost)): ?>
        <meta property="article:published_time" content="<?php echo e($ogBlogPost['published_at']); ?>">
        <meta property="article:modified_time" content="<?php echo e($ogBlogPost['updated_at']); ?>">
    <?php endif; ?>

    <!-- Canonical URL -->
    <?php
    $canonicalUrl = '';
    $baseHost = getCanonicalBaseUrl();
    if ($currentPage === 'blog') {
        // Blog listing: canonical is the base blog URL (strip pagination/filter params)
        $canonicalUrl = $baseHost . url('index.php?page=blog');
        if (!empty($_GET['category'])) $canonicalUrl .= '&category=' . urlencode($_GET['category']);
        elseif (!empty($_GET['tag'])) $canonicalUrl .= '&tag=' . urlencode($_GET['tag']);
    } elseif ($currentPage === 'blog-post' && isset($ogBlogPost)) {
        $canonicalUrl = $baseHost . url('index.php?page=blog-post&slug=' . urlencode($ogBlogPost['slug']));
    }
    if ($canonicalUrl):
    ?>
        <link rel="canonical" href="<?php echo e($canonicalUrl); ?>">
    <?php endif; ?>

    <!-- RSS Feed -->
    <?php if (isFeatureEnabled('blog')): ?>
        <link rel="alternate" type="application/rss+xml" title="<?php echo e($companyName); ?> Blog RSS" href="<?php echo url('rss.php'); ?>">
    <?php endif; ?>

    <!-- Favicon -->
    <?php $faviconUrl = getSetting('favicon'); if ($faviconUrl): ?>
        <link rel="icon" href="<?php echo e($faviconUrl); ?>">
    <?php endif; ?>

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo asset('css/variables.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">
    <?php if ($currentPage === 'blog' || $currentPage === 'blog-post'): ?>
        <link rel="stylesheet" href="<?php echo asset('css/blog.css'); ?>">
    <?php endif; ?>

    <?php
    // Dynamic theme colors from admin settings
    $primaryColor = getSetting('primary_color', '#2563EB');
    $secondaryColor = getSetting('secondary_color', '#F59E0B');
    $themeCssVars = generateThemeCssVariables();
    if ($primaryColor !== '#2563EB' || $secondaryColor !== '#F59E0B' || $themeCssVars):
    ?>
    <style>
        :root {
            --color-primary: <?php echo e($primaryColor); ?>;
            --color-secondary: <?php echo e($secondaryColor); ?>;
<?php echo $themeCssVars; ?>
        }
    </style>
    <?php endif; ?>

    <?php // Theme stylesheet (loaded after core styles so it can override)
    $themeStylesheet = getThemeStylesheet();
    if ($themeStylesheet): ?>
        <link rel="stylesheet" href="<?php echo e($themeStylesheet); ?>">
    <?php endif; ?>

    <?php // Plugin hook: inject styles/meta in <head>
    do_action('wp_head'); ?>

    <!-- Structured Data -->
    <?php
    $schemaType = getBusinessType() === 'online' ? 'Organization' : 'LocalBusiness';
    $schemaData = [
        '@context' => 'https://schema.org',
        '@type' => $schemaType,
        'name' => $companyName,
        'description' => $metaDescription,
    ];
    if ($companyPhone) $schemaData['telephone'] = $companyPhone;
    if ($companyEmail) $schemaData['email'] = $companyEmail;
    if (isFeatureEnabled('address') && getSetting('company_address')) {
        $schemaData['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => getSetting('company_address'),
        ];
    }
    ?>
    <script type="application/ld+json">
    <?php echo json_encode($schemaData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
    </script>
    <?php // WebSite schema with SearchAction for sitelinks search box
    echo getWebSiteSchema(); ?>
</head>
<body class="<?php echo e(getThemeBodyClasses()); ?>">

<!-- Skip to Content (Accessibility) -->
<a href="#mainContent" class="skip-to-content">Skip to main content</a>

<!-- Screen Reader Announcements (ARIA Live Region) -->
<div id="ariaLive" class="sr-only" aria-live="polite" aria-atomic="true"></div>

<!-- Top Bar -->
<?php $hasTopBarContent = ($showPhoneHeader && $companyPhone) || ($showEmailHeader && $companyEmail) || $facebookUrl || $instagramUrl || $twitterUrl; ?>
<?php if ($hasTopBarContent): ?>
<div class="top-bar">
    <div class="container">
        <div class="top-bar-left">
            <?php if ($showPhoneHeader && $companyPhone): ?>
                <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>">
                    <i class="fas fa-phone"></i> <?php echo e($companyPhone); ?>
                </a>
            <?php endif; ?>
            <?php if ($showEmailHeader && $companyEmail): ?>
                <a href="mailto:<?php echo e($companyEmail); ?>">
                    <i class="fas fa-envelope"></i> <?php echo e($companyEmail); ?>
                </a>
            <?php endif; ?>
        </div>
        <div class="top-bar-right">
            <div class="social-links">
                <?php if ($facebookUrl): ?>
                    <a href="<?php echo e($facebookUrl); ?>" target="_blank" rel="noopener" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                <?php endif; ?>
                <?php if ($instagramUrl): ?>
                    <a href="<?php echo e($instagramUrl); ?>" target="_blank" rel="noopener" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                <?php endif; ?>
                <?php if ($twitterUrl): ?>
                    <a href="<?php echo e($twitterUrl); ?>" target="_blank" rel="noopener" aria-label="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Header -->
<header class="site-header" id="siteHeader" role="banner">
    <div class="container">
        <div class="header-inner">
            <a href="<?php echo url('/'); ?>" class="site-logo">
                <?php if ($logoUrl): ?>
                    <img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($companyName); ?>">
                <?php else: ?>
                    <i class="fas fa-bolt" style="font-size: 2rem; color: var(--color-primary);"></i>
                <?php endif; ?>
                <div class="logo-text">
                    <?php echo e($companyName); ?>
                </div>
            </a>

            <button type="button" class="nav-toggle" id="navToggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="mainNav">
                <span></span>
                <span></span>
                <span></span>
            </button>

            <nav class="main-nav" id="mainNav" role="navigation" aria-label="Main navigation">
                <ul class="nav-menu">
                    <?php foreach ($navigation as $item): ?>
                        <?php
                        $isActive = false;
                        $rawUrl = $item['url'] ?? '#';
                        if ($rawUrl === '/' && $currentPage === 'home') $isActive = true;
                        if (strpos($rawUrl, 'page=' . $currentPage) !== false) $isActive = true;
                        // Apply url() to relative paths; leave external URLs as-is
                        $href = (str_starts_with($rawUrl, 'http') || str_starts_with($rawUrl, '#') || str_starts_with($rawUrl, 'mailto:') || str_starts_with($rawUrl, 'tel:'))
                            ? $rawUrl
                            : url($rawUrl);
                        ?>
                        <li>
                            <a href="<?php echo e($href); ?>" class="<?php echo $isActive ? 'active' : ''; ?>"<?php if ($isActive): ?> aria-current="page"<?php endif; ?>>
                                <?php echo e($item['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="nav-actions">
                    <div class="nav-icons">
                        <?php if (isFeatureEnabled('search')): ?>
                            <a href="<?php echo url('index.php?page=search'); ?>" class="nav-icon-link" title="Search">
                                <i class="fas fa-search"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (isFeatureEnabled('wishlists')): ?>
                            <?php $wishlistCount = getWishlistCount(); ?>
                            <a href="<?php echo url('index.php?page=wishlist'); ?>" class="nav-icon-link" title="Wishlist">
                                <i class="fas fa-heart"></i>
                                <?php if ($wishlistCount > 0): ?>
                                    <span class="nav-badge"><?php echo $wishlistCount; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <?php if ($cartEnabled): ?>
                            <?php $cartCount = getCartCount(); ?>
                            <a href="<?php echo url('index.php?page=cart'); ?>" class="nav-icon-link" title="Shopping Cart">
                                <i class="fas fa-shopping-cart"></i>
                                <?php if ($cartCount > 0): ?>
                                    <span class="nav-badge"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                        <?php if (isFeatureEnabled('customer_accounts')): ?>
                            <?php if (isCustomerLoggedIn()): ?>
                                <a href="<?php echo url('index.php?page=account'); ?>" class="nav-icon-link" title="My Account">
                                    <i class="fas fa-user-circle"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo url('index.php?page=login'); ?>" class="nav-icon-link" title="Sign In">
                                    <i class="fas fa-user"></i>
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($showPhoneHeader && $companyPhone): ?>
                        <div class="nav-cta">
                            <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>" class="nav-cta-btn">
                                <i class="fas fa-phone"></i> Call Now
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </div>
</header>

<div class="mobile-overlay" id="mobileOverlay"></div>

<main id="mainContent" role="main">
<?php $flashMessage = $flashMessage ?? null; $flashType = $flashType ?? 'info'; ?>
<?php if ($flashMessage): ?>
    <div class="container" style="padding-top: var(--space-xl);">
        <div class="flash-message <?php echo e($flashType); ?>" role="alert">
            <i class="fas fa-<?php echo $flashType === 'success' ? 'check-circle' : ($flashType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span class="flash-text"><?php echo e($flashMessage); ?></span>
        </div>
    </div>
<?php endif; ?>
