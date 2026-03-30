<?php
/**
 * Admin — Gallery Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_media');

$db = getDB();
$csrfToken = generateCSRFToken();

$categories = [
    'completed' => 'Completed Builds',
    'interiors' => 'Interiors',
    'electrical' => 'Solar & Electrical',
    'process' => 'Build Process',
    'events' => 'Events',
    'videos' => 'Videos',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    // Upload new gallery files
    if (!empty($_FILES['gallery_files'])) {
        $files = $_FILES['gallery_files'];
        $uploadCount = 0;

        for ($i = 0; $i < count($files['name']); $i++) {
            $file = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            $uploadedPath = handleUpload($file);
            if (!$uploadedPath) continue;

            $relativePath = ltrim(str_replace(UPLOADS_URL, '', $uploadedPath), '/');
            $stmt = $db->prepare('SELECT id, mime_type FROM media WHERE filename = ? ORDER BY id DESC LIMIT 1');
            $stmt->execute([$relativePath]);
            $media = $stmt->fetch();
            if (!$media) continue;

            $isVideo = str_starts_with($media['mime_type'] ?? '', 'video/');
            $defaultCategory = $isVideo ? 'videos' : ($_POST['default_category'] ?? 'completed');
            $stmt = $db->prepare('INSERT INTO gallery_items (media_id, media_path, media_type, caption, category, featured, sort_order, is_active) VALUES (?, ?, ?, ?, ?, 0, 0, 1)');
            $stmt->execute([
                (int)$media['id'],
                $relativePath,
                $isVideo ? 'video' : 'image',
                pathinfo($files['name'][$i], PATHINFO_FILENAME),
                $defaultCategory,
            ]);
            $uploadCount++;
        }

        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "$uploadCount file(s) uploaded to gallery."];
        redirect('admin/gallery.php');
    }

    // Update item metadata
    if (isset($_POST['gallery_item_id'])) {
        $itemId = (int)$_POST['gallery_item_id'];
        $caption = trim($_POST['caption'] ?? '');
        $category = $_POST['category'] ?? 'completed';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $sortOrder = (int)($_POST['sort_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if (!array_key_exists($category, $categories)) {
            $category = 'completed';
        }

        $stmt = $db->prepare('UPDATE gallery_items SET caption = ?, category = ?, featured = ?, sort_order = ?, is_active = ? WHERE id = ?');
        $stmt->execute([$caption, $category, $featured, $sortOrder, $isActive, $itemId]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Gallery item updated.'];
        redirect('admin/gallery.php');
    }

    // Delete item + file
    if (isset($_POST['delete_gallery_item'])) {
        $itemId = (int)$_POST['delete_gallery_item'];
        $stmt = $db->prepare('SELECT gi.*, m.id as linked_media_id FROM gallery_items gi LEFT JOIN media m ON m.id = gi.media_id WHERE gi.id = ?');
        $stmt->execute([$itemId]);
        $item = $stmt->fetch();

        if ($item) {
            $filePath = UPLOADS_PATH . '/' . ltrim($item['media_path'], '/');
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            if (!empty($item['linked_media_id'])) {
                $db->prepare('DELETE FROM media WHERE id = ?')->execute([(int)$item['linked_media_id']]);
            }
            $db->prepare('DELETE FROM gallery_items WHERE id = ?')->execute([$itemId]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Gallery item deleted.'];
        }

        redirect('admin/gallery.php');
    }
}

$galleryItems = $db->query('SELECT gi.*, m.original_name, m.mime_type FROM gallery_items gi LEFT JOIN media m ON m.id = gi.media_id ORDER BY gi.sort_order ASC, gi.id DESC')->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-images"></i> Gallery Manager</h1>
    <p>Upload and manage gallery photos/videos shown on the public gallery page.</p>
</div>

<div class="admin-section">
    <h2>Upload to Gallery</h2>
    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
        <div class="form-row">
            <div class="form-group">
                <label>Default Category</label>
                <select name="default_category" class="form-control">
                    <?php foreach ($categories as $key => $label): if ($key === 'videos') continue; ?>
                        <option value="<?php echo e($key); ?>"><?php echo e($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="align-self: end;">
                <input type="file" name="gallery_files[]" multiple accept="image/*,video/mp4,video/webm" class="form-control">
            </div>
        </div>
        <button type="submit" class="btn-admin btn-save"><i class="fas fa-upload"></i> Upload</button>
    </form>
</div>

<div class="admin-section">
    <h2>Gallery Items (<?php echo count($galleryItems); ?>)</h2>
    <?php if (empty($galleryItems)): ?>
        <p class="empty-state">No gallery items yet. Upload files above to start building your gallery.</p>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($galleryItems as $item): $isVideo = ($item['media_type'] === 'video'); ?>
                <div class="media-item">
                    <div class="media-preview">
                        <?php if ($isVideo): ?>
                            <video src="<?php echo e(url('uploads/' . $item['media_path'])); ?>" muted></video>
                            <div class="media-type-badge"><i class="fas fa-video"></i></div>
                        <?php else: ?>
                            <img src="<?php echo e(url('uploads/' . $item['media_path'])); ?>" alt="<?php echo e($item['caption'] ?: 'Gallery image'); ?>">
                        <?php endif; ?>
                    </div>
                    <form method="POST" class="media-info" style="padding: 0.75rem;">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                        <input type="hidden" name="gallery_item_id" value="<?php echo (int)$item['id']; ?>">

                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label style="font-size:0.75rem;">Caption</label>
                            <input type="text" name="caption" class="form-control" value="<?php echo e($item['caption'] ?? ''); ?>">
                        </div>
                        <div class="form-group" style="margin-bottom: 0.5rem;">
                            <label style="font-size:0.75rem;">Category</label>
                            <select name="category" class="form-control">
                                <?php foreach ($categories as $key => $label): ?>
                                    <option value="<?php echo e($key); ?>" <?php echo $item['category'] === $key ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-row" style="gap:0.5rem;">
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label style="font-size:0.75rem;">Sort</label>
                                <input type="number" name="sort_order" class="form-control" value="<?php echo (int)$item['sort_order']; ?>">
                            </div>
                            <div class="form-group" style="display:flex; align-items:center; gap:0.5rem; margin: 1.4rem 0 0;">
                                <label><input type="checkbox" name="featured" <?php echo !empty($item['featured']) ? 'checked' : ''; ?>> Featured</label>
                            </div>
                            <div class="form-group" style="display:flex; align-items:center; gap:0.5rem; margin: 1.4rem 0 0;">
                                <label><input type="checkbox" name="is_active" <?php echo !empty($item['is_active']) ? 'checked' : ''; ?>> Active</label>
                            </div>
                        </div>
                        <div class="media-actions" style="justify-content: space-between;">
                            <button type="submit" class="btn-admin btn-sm btn-save"><i class="fas fa-save"></i> Save</button>
                        </div>
                    </form>
                    <div class="media-actions" style="padding:0 0.75rem 0.75rem;">
                        <form method="POST" onsubmit="return confirm('Delete this gallery item and file?')">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="delete_gallery_item" value="<?php echo (int)$item['id']; ?>">
                            <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
