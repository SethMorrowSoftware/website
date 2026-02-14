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

// Get page meta
$pageData = getPage($currentPage);
$pageTitle = $pageData ? $pageData['title'] . ' | ' . $companyName : $companyName;
$metaDescription = $pageData ? $pageData['meta_description'] : getSetting('tagline');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo e($metaDescription); ?>">
    <meta name="robots" content="index, follow">
    <title><?php echo e($pageTitle); ?></title>

    <!-- Open Graph -->
    <meta property="og:title" content="<?php echo e($pageTitle); ?>">
    <meta property="og:description" content="<?php echo e($metaDescription); ?>">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?php echo e($companyName); ?>">

    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Styles -->
    <link rel="stylesheet" href="<?php echo asset('css/variables.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo asset('css/responsive.css'); ?>">

    <?php
    // Dynamic theme colors from admin settings
    $primaryColor = getSetting('primary_color', '#1B4D3E');
    $secondaryColor = getSetting('secondary_color', '#D4A843');
    if ($primaryColor !== '#1B4D3E' || $secondaryColor !== '#D4A843'):
    ?>
    <style>
        :root {
            --color-primary: <?php echo e($primaryColor); ?>;
            --color-secondary: <?php echo e($secondaryColor); ?>;
        }
    </style>
    <?php endif; ?>

    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "<?php echo e($companyName); ?>",
        "telephone": "<?php echo e($companyPhone); ?>",
        "email": "<?php echo e($companyEmail); ?>",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "<?php echo e(getSetting('company_address')); ?>"
        },
        "description": "<?php echo e($metaDescription); ?>"
    }
    </script>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="container">
        <div class="top-bar-left">
            <?php if ($companyPhone): ?>
                <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>">
                    <i class="fas fa-phone"></i> <?php echo e($companyPhone); ?>
                </a>
            <?php endif; ?>
            <?php if ($companyEmail): ?>
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

<!-- Header -->
<header class="site-header" id="siteHeader">
    <div class="container">
        <div class="header-inner">
            <a href="<?php echo url('/'); ?>" class="site-logo">
                <?php if ($logoUrl): ?>
                    <img src="<?php echo e($logoUrl); ?>" alt="<?php echo e($companyName); ?>">
                <?php else: ?>
                    <i class="fas fa-recycle" style="font-size: 2rem; color: var(--color-primary);"></i>
                <?php endif; ?>
                <div class="logo-text">
                    Hudson Valley<br>Supply &amp; Recycling
                    <small>LLC</small>
                </div>
            </a>

            <div class="nav-toggle" id="navToggle" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </div>

            <nav class="main-nav" id="mainNav">
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
                            <a href="<?php echo e($href); ?>" class="<?php echo $isActive ? 'active' : ''; ?>">
                                <?php echo e($item['label']); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="nav-cta">
                    <a href="tel:<?php echo e(preg_replace('/[^0-9+]/', '', $companyPhone)); ?>">
                        <i class="fas fa-phone"></i> Call Now
                    </a>
                </div>
            </nav>
        </div>
    </div>
</header>

<div class="mobile-overlay" id="mobileOverlay"></div>

<main>
<?php if ($flashMessage): ?>
    <div class="container" style="padding-top: var(--space-xl);">
        <div class="flash-message <?php echo e($flashType); ?>">
            <i class="fas fa-<?php echo $flashType === 'success' ? 'check-circle' : ($flashType === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <?php echo e($flashMessage); ?>
        </div>
    </div>
<?php endif; ?>
