<?php
/**
 * Admin Header / Layout
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$adminPage = basename($_SERVER['PHP_SELF'], '.php');
$unread = getUnreadCount();
$totalUnread = $unread['contacts'] + $unread['orders'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="base-url" content="<?php echo e(BASE_URL); ?>">
    <meta name="csrf-token" content="<?php echo e(generateCSRFToken()); ?>">
    <title>Admin — <?php echo e(ucfirst($adminPage)); ?> | <?php echo e(getSetting('company_name', SITE_NAME)); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="<?php echo asset('css/admin.css'); ?>">
</head>
<body class="admin-body">

<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="<?php echo url('admin/'); ?>" class="sidebar-logo">
            <i class="fas fa-cog"></i>
            <span>Site Admin</span>
        </a>
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>

    <nav class="sidebar-nav">
        <a href="<?php echo url('admin/'); ?>" class="<?php echo $adminPage === 'index' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="nav-section">Content</div>
        <a href="<?php echo url('admin/pages.php'); ?>" class="<?php echo $adminPage === 'pages' || $adminPage === 'page-edit' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Pages
        </a>
        <a href="<?php echo url('admin/hero.php'); ?>" class="<?php echo $adminPage === 'hero' ? 'active' : ''; ?>">
            <i class="fas fa-image"></i> Hero Sections
        </a>
        <a href="<?php echo url('admin/navigation.php'); ?>" class="<?php echo $adminPage === 'navigation' ? 'active' : ''; ?>">
            <i class="fas fa-bars"></i> Navigation
        </a>

        <?php if (isFeatureEnabled('catalog')): ?>
        <div class="nav-section">Catalog</div>
        <a href="<?php echo url('admin/categories.php'); ?>" class="<?php echo $adminPage === 'categories' || $adminPage === 'category-edit' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="<?php echo url('admin/products.php'); ?>" class="<?php echo $adminPage === 'products' || $adminPage === 'product-edit' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products
        </a>
        <?php endif; ?>
        <?php if (isFeatureEnabled('cart')): ?>
        <a href="<?php echo url('admin/orders.php'); ?>" class="<?php echo $adminPage === 'orders' || $adminPage === 'order-view' ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i> Orders
        </a>
        <a href="<?php echo url('admin/coupons.php'); ?>" class="<?php echo $adminPage === 'coupons' ? 'active' : ''; ?>">
            <i class="fas fa-ticket-alt"></i> Coupons
        </a>
        <a href="<?php echo url('admin/shipping.php'); ?>" class="<?php echo $adminPage === 'shipping' ? 'active' : ''; ?>">
            <i class="fas fa-shipping-fast"></i> Shipping
        </a>
        <a href="<?php echo url('admin/subscriptions.php'); ?>" class="<?php echo $adminPage === 'subscriptions' ? 'active' : ''; ?>">
            <i class="fas fa-sync-alt"></i> Subscriptions
        </a>
        <?php endif; ?>

        <?php if (isFeatureEnabled('blog')): ?>
        <div class="nav-section">Blog</div>
        <a href="<?php echo url('admin/blog-posts.php'); ?>" class="<?php echo $adminPage === 'blog-posts' || $adminPage === 'blog-post-edit' ? 'active' : ''; ?>">
            <i class="fas fa-blog"></i> Blog Posts
        </a>
        <a href="<?php echo url('admin/blog-categories.php'); ?>" class="<?php echo $adminPage === 'blog-categories' ? 'active' : ''; ?>">
            <i class="fas fa-folder-open"></i> Blog Categories
        </a>
        <a href="<?php echo url('admin/blog-comments.php'); ?>" class="<?php echo $adminPage === 'blog-comments' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i> Comments
            <?php $pendingBlogComments = getPendingCommentCount(); if ($pendingBlogComments > 0): ?>
                <span class="badge"><?php echo $pendingBlogComments; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <div class="nav-section">Engagement</div>
        <?php if (isFeatureEnabled('testimonials')): ?>
        <a href="<?php echo url('admin/testimonials.php'); ?>" class="<?php echo $adminPage === 'testimonials' || $adminPage === 'testimonial-edit' ? 'active' : ''; ?>">
            <i class="fas fa-quote-right"></i> Testimonials
        </a>
        <?php endif; ?>
        <?php if (isFeatureEnabled('reviews')): ?>
        <a href="<?php echo url('admin/reviews.php'); ?>" class="<?php echo $adminPage === 'reviews' ? 'active' : ''; ?>">
            <i class="fas fa-star"></i> Reviews
        </a>
        <?php endif; ?>
        <?php if (isFeatureEnabled('contact_form') || isFeatureEnabled('order_inquiry')): ?>
        <a href="<?php echo url('admin/inquiries.php'); ?>" class="<?php echo $adminPage === 'inquiries' || $adminPage === 'inquiry-view' ? 'active' : ''; ?>">
            <i class="fas fa-inbox"></i> Inquiries
            <?php if ($totalUnread > 0): ?>
                <span class="badge"><?php echo $totalUnread; ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>

        <?php // Plugin hook: allow plugins to add admin menu items
        do_action('admin_menu'); ?>

        <div class="nav-section">System</div>
        <a href="<?php echo url('admin/analytics.php'); ?>" class="<?php echo $adminPage === 'analytics' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> Analytics
        </a>
        <a href="<?php echo url('admin/email-templates.php'); ?>" class="<?php echo $adminPage === 'email-templates' ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i> Email & Notifications
        </a>
        <a href="<?php echo url('admin/loyalty.php'); ?>" class="<?php echo $adminPage === 'loyalty' ? 'active' : ''; ?>">
            <i class="fas fa-gift"></i> Loyalty & Marketing
        </a>
        <a href="<?php echo url('admin/plugins.php'); ?>" class="<?php echo $adminPage === 'plugins' ? 'active' : ''; ?>">
            <i class="fas fa-puzzle-piece"></i> Plugins
        </a>
        <a href="<?php echo url('admin/themes.php'); ?>" class="<?php echo $adminPage === 'themes' ? 'active' : ''; ?>">
            <i class="fas fa-palette"></i> Themes
        </a>
        <a href="<?php echo url('admin/api-keys.php'); ?>" class="<?php echo $adminPage === 'api-keys' ? 'active' : ''; ?>">
            <i class="fas fa-key"></i> API Keys
        </a>
        <a href="<?php echo url('admin/media.php'); ?>" class="<?php echo $adminPage === 'media' ? 'active' : ''; ?>">
            <i class="fas fa-photo-video"></i> Media Library
        </a>
        <a href="<?php echo url('admin/export.php'); ?>" class="<?php echo $adminPage === 'export' ? 'active' : ''; ?>">
            <i class="fas fa-download"></i> Export Data
        </a>
        <a href="<?php echo url('admin/backups.php'); ?>" class="<?php echo $adminPage === 'backups' ? 'active' : ''; ?>">
            <i class="fas fa-shield-alt"></i> Backup & Privacy
        </a>
        <a href="<?php echo url('admin/audit-log.php'); ?>" class="<?php echo $adminPage === 'audit-log' ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i> Audit Log
        </a>
        <?php if (hasPermission('manage_users')): ?>
        <a href="<?php echo url('admin/users.php'); ?>" class="<?php echo $adminPage === 'users' ? 'active' : ''; ?>">
            <i class="fas fa-users-cog"></i> Team
        </a>
        <?php endif; ?>
        <a href="<?php echo url('admin/settings.php'); ?>" class="<?php echo $adminPage === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="<?php echo url('admin/profile.php'); ?>" class="<?php echo $adminPage === 'profile' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> My Profile
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="<?php echo url('/'); ?>" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
        <a href="#" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <form id="logoutForm" method="POST" action="<?php echo url('admin/login.php'); ?>" style="display:none;">
            <input type="hidden" name="logout" value="1">
            <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
        </form>
    </div>
</aside>

<!-- Admin Main -->
<div class="admin-main">
    <header class="admin-topbar">
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
        <div class="topbar-right">
            <span class="admin-user"><i class="fas fa-user-circle"></i> <?php echo e(getCurrentUsername()); ?></span>
        </div>
    </header>

    <div class="admin-content">

<?php
// Flash messages in admin
if (isset($_SESSION['admin_flash'])):
    $af = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
?>
    <div class="admin-alert alert-<?php echo e($af['type']); ?>">
        <i class="fas fa-<?php echo $af['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <?php echo e($af['message']); ?>
    </div>
<?php endif; ?>
