<?php
/**
 * Admin — Media Library
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$csrfToken = generateCSRFToken();

// Handle upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (!empty($_FILES['media_files'])) {
        $files = $_FILES['media_files'];
        $uploadCount = 0;
        for ($i = 0; $i < count($files['name']); $i++) {
            $file = [
                'name' => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
            $result = handleUpload($file);
            if ($result) $uploadCount++;
        }
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "$uploadCount file(s) uploaded successfully!"];
    }
    redirect('/admin/media.php');
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $stmt = $db->prepare('SELECT * FROM media WHERE id = ?');
    $stmt->execute([(int)$_GET['delete']]);
    $media = $stmt->fetch();
    if ($media) {
        $filepath = UPLOADS_PATH . '/' . $media['filename'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        $db->prepare('DELETE FROM media WHERE id = ?')->execute([$media['id']]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'File deleted.'];
    }
    redirect('/admin/media.php');
}

$allMedia = $db->query('SELECT * FROM media ORDER BY uploaded_at DESC')->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-photo-video"></i> Media Library</h1>
</div>

<!-- Upload Form -->
<div class="admin-section">
    <h2>Upload Files</h2>
    <form method="POST" enctype="multipart/form-data" class="upload-form">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
        <div class="upload-area" id="uploadArea">
            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--color-gray-400); margin-bottom: 1rem;"></i>
            <p>Drag files here or click to browse</p>
            <p style="font-size: 0.75rem; color: #888;">Accepted: JPG, PNG, GIF, WebP, SVG, MP4, WebM (max 50MB)</p>
            <input type="file" name="media_files[]" id="mediaFiles" multiple accept="image/*,video/mp4,video/webm" style="display:none;">
        </div>
        <button type="submit" class="btn-admin btn-save" style="margin-top: 1rem;"><i class="fas fa-upload"></i> Upload</button>
    </form>
</div>

<!-- Media Grid -->
<div class="admin-section">
    <h2>All Media (<?php echo count($allMedia); ?>)</h2>
    <?php if (empty($allMedia)): ?>
        <p class="empty-state">No media uploaded yet.</p>
    <?php else: ?>
        <div class="media-grid">
            <?php foreach ($allMedia as $m): ?>
                <div class="media-item">
                    <?php
                    $filepath = UPLOADS_URL . '/' . $m['filename'];
                    $isVideo = str_starts_with($m['mime_type'] ?? '', 'video/');
                    ?>
                    <div class="media-preview">
                        <?php if ($isVideo): ?>
                            <video src="<?php echo e($filepath); ?>" muted></video>
                            <div class="media-type-badge"><i class="fas fa-video"></i></div>
                        <?php else: ?>
                            <img src="<?php echo e($filepath); ?>" alt="<?php echo e($m['alt_text'] ?? $m['original_name']); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="media-info">
                        <div class="media-name" title="<?php echo e($m['original_name']); ?>"><?php echo e($m['original_name']); ?></div>
                        <div class="media-meta"><?php echo round(($m['file_size'] ?? 0) / 1024); ?> KB</div>
                    </div>
                    <div class="media-actions">
                        <button class="btn-icon" onclick="copyToClipboard('<?php echo e($filepath); ?>')" title="Copy URL"><i class="fas fa-copy"></i></button>
                        <a href="/admin/media.php?delete=<?php echo $m['id']; ?>&csrf=<?php echo e($csrfToken); ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete this file?')"><i class="fas fa-trash"></i></a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// Upload area click/drag
var uploadArea = document.getElementById('uploadArea');
var mediaFiles = document.getElementById('mediaFiles');

if (uploadArea && mediaFiles) {
    uploadArea.addEventListener('click', function() { mediaFiles.click(); });
    uploadArea.addEventListener('dragover', function(e) { e.preventDefault(); this.classList.add('drag-over'); });
    uploadArea.addEventListener('dragleave', function() { this.classList.remove('drag-over'); });
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        this.classList.remove('drag-over');
        mediaFiles.files = e.dataTransfer.files;
    });
}

function copyToClipboard(text) {
    navigator.clipboard.writeText(window.location.origin + text).then(function() {
        alert('URL copied to clipboard!');
    });
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
