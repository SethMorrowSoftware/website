<?php
/**
 * Admin — Hero Sections Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_hero');

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $heroId = (int)($_POST['hero_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $subtitle = trim($_POST['subtitle'] ?? '');
    $cta_text = trim($_POST['cta_text'] ?? '');
    $cta_link = trim($_POST['cta_link'] ?? '');
    $overlay_opacity = floatval($_POST['overlay_opacity'] ?? 0.5);

    // Handle image upload
    $background_image = $_POST['current_background_image'] ?? '';
    if (!empty($_FILES['background_image']['name'])) {
        $uploaded = handleUpload($_FILES['background_image']);
        if ($uploaded) $background_image = $uploaded;
    }

    // Handle video upload
    $background_video = $_POST['current_background_video'] ?? '';
    if (!empty($_FILES['background_video']['name'])) {
        $uploaded = handleUpload($_FILES['background_video']);
        if ($uploaded) $background_video = $uploaded;
    }

    if ($heroId) {
        $stmt = $db->prepare('UPDATE hero_sections SET title=?, subtitle=?, cta_text=?, cta_link=?, background_image=?, background_video=?, overlay_opacity=? WHERE id=?');
        $stmt->execute([$title, $subtitle, $cta_text, $cta_link, $background_image, $background_video, $overlay_opacity, $heroId]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Hero section updated!'];
    }
    redirect('admin/hero.php');
}

$heroes = $db->query('SELECT * FROM hero_sections ORDER BY id')->fetchAll();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-image"></i> Hero Sections</h1>
    <p>Manage the hero banner for each page</p>
</div>

<?php foreach ($heroes as $hero): ?>
<form method="POST" enctype="multipart/form-data" class="admin-form" style="margin-bottom: 2rem;">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
    <input type="hidden" name="hero_id" value="<?php echo $hero['id']; ?>">
    <input type="hidden" name="current_background_image" value="<?php echo e($hero['background_image']); ?>">
    <input type="hidden" name="current_background_video" value="<?php echo e($hero['background_video']); ?>">

    <div class="form-section">
        <h3 style="text-transform: capitalize;">
            <i class="fas fa-file"></i> <?php echo e($hero['page_slug']); ?> Page Hero
        </h3>

        <div class="form-row">
            <div class="form-group">
                <label>Title</label>
                <input type="text" name="title" class="form-control" value="<?php echo e($hero['title']); ?>">
            </div>
            <div class="form-group">
                <label>Subtitle</label>
                <input type="text" name="subtitle" class="form-control" value="<?php echo e($hero['subtitle']); ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>CTA Button Text</label>
                <input type="text" name="cta_text" class="form-control" value="<?php echo e($hero['cta_text']); ?>" placeholder="e.g., Get a Quote">
            </div>
            <div class="form-group">
                <label>CTA Button Link</label>
                <input type="text" name="cta_link" class="form-control" value="<?php echo e($hero['cta_link']); ?>" placeholder="index.php?page=order">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Background Image</label>
                <?php if ($hero['background_image']): ?>
                    <div class="current-image"><img src="<?php echo e($hero['background_image']); ?>" alt="" style="max-height: 100px;"></div>
                <?php endif; ?>
                <input type="file" name="background_image" class="form-control" accept="image/*">
            </div>
            <div class="form-group">
                <label>Background Video (MP4/WebM)</label>
                <?php if ($hero['background_video']): ?>
                    <div class="current-image" style="color: var(--color-gray-600); font-size: 0.875rem;">
                        <i class="fas fa-video"></i> Video uploaded: <?php echo e(basename($hero['background_video'])); ?>
                    </div>
                <?php endif; ?>
                <input type="file" name="background_video" class="form-control" accept="video/mp4,video/webm">
            </div>
        </div>

        <div class="form-group" style="max-width: 200px;">
            <label>Overlay Opacity (0-1)</label>
            <input type="number" name="overlay_opacity" class="form-control" value="<?php echo e($hero['overlay_opacity']); ?>" min="0" max="1" step="0.05">
        </div>

        <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Hero</button>
    </div>
</form>
<?php endforeach; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
