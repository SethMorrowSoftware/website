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
    <title>Admin — <?php echo e(ucfirst($adminPage)); ?> | <?php echo e(getSetting('company_name', SITE_NAME)); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Open+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-body">

<!-- Admin Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="/admin/" class="sidebar-logo">
            <i class="fas fa-recycle"></i>
            <span>HV Supply Admin</span>
        </a>
        <button class="sidebar-close" id="sidebarClose"><i class="fas fa-times"></i></button>
    </div>

    <nav class="sidebar-nav">
        <a href="/admin/" class="<?php echo $adminPage === 'index' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>

        <div class="nav-section">Content</div>
        <a href="/admin/pages.php" class="<?php echo $adminPage === 'pages' || $adminPage === 'page-edit' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Pages
        </a>
        <a href="/admin/hero.php" class="<?php echo $adminPage === 'hero' ? 'active' : ''; ?>">
            <i class="fas fa-image"></i> Hero Sections
        </a>
        <a href="/admin/navigation.php" class="<?php echo $adminPage === 'navigation' ? 'active' : ''; ?>">
            <i class="fas fa-bars"></i> Navigation
        </a>

        <div class="nav-section">Products</div>
        <a href="/admin/categories.php" class="<?php echo $adminPage === 'categories' || $adminPage === 'category-edit' ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i> Categories
        </a>
        <a href="/admin/products.php" class="<?php echo $adminPage === 'products' || $adminPage === 'product-edit' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i> Products
        </a>
        <a href="/admin/containers.php" class="<?php echo $adminPage === 'containers' || $adminPage === 'container-edit' ? 'active' : ''; ?>">
            <i class="fas fa-dumpster"></i> Containers
        </a>

        <div class="nav-section">Engagement</div>
        <a href="/admin/testimonials.php" class="<?php echo $adminPage === 'testimonials' || $adminPage === 'testimonial-edit' ? 'active' : ''; ?>">
            <i class="fas fa-quote-right"></i> Testimonials
        </a>
        <a href="/admin/inquiries.php" class="<?php echo $adminPage === 'inquiries' || $adminPage === 'inquiry-view' ? 'active' : ''; ?>">
            <i class="fas fa-inbox"></i> Inquiries
            <?php if ($totalUnread > 0): ?>
                <span class="badge"><?php echo $totalUnread; ?></span>
            <?php endif; ?>
        </a>

        <div class="nav-section">System</div>
        <a href="/admin/media.php" class="<?php echo $adminPage === 'media' ? 'active' : ''; ?>">
            <i class="fas fa-photo-video"></i> Media Library
        </a>
        <a href="/admin/settings.php" class="<?php echo $adminPage === 'settings' ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i> Settings
        </a>
        <a href="/admin/profile.php" class="<?php echo $adminPage === 'profile' ? 'active' : ''; ?>">
            <i class="fas fa-user-shield"></i> My Profile
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/" target="_blank"><i class="fas fa-external-link-alt"></i> View Site</a>
        <a href="/admin/login.php?logout=1" onclick="event.preventDefault(); document.getElementById('logoutForm').submit();">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
        <form id="logoutForm" method="POST" action="/admin/login.php" style="display:none;">
            <input type="hidden" name="logout" value="1">
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
