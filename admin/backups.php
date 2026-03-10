<?php
/**
 * Admin — Backup & GDPR Management
 *
 * Create/download/delete backups and manage GDPR data requests.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_settings');

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'backup_database') {
        $result = createDatabaseBackup();
        $_SESSION['admin_flash'] = $result
            ? ['type' => 'success', 'message' => 'Database backup created successfully!']
            : ['type' => 'error', 'message' => 'Backup failed. Check error logs.'];
        redirect('admin/backups.php');
    }

    if ($action === 'backup_files') {
        $result = createFileBackup();
        $_SESSION['admin_flash'] = $result
            ? ['type' => 'success', 'message' => 'File backup created successfully!']
            : ['type' => 'error', 'message' => 'File backup failed. ZipArchive extension may be required.'];
        redirect('admin/backups.php');
    }

    if ($action === 'delete_backup') {
        deleteBackup((int)$_POST['backup_id']);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Backup deleted.'];
        redirect('admin/backups.php');
    }

    if ($action === 'save_settings') {
        setSetting('backup_retention_count', (int)$_POST['backup_retention_count']);
        setSetting('cookie_consent_enabled', isset($_POST['cookie_consent_enabled']) ? '1' : '0');
        setSetting('privacy_policy_page', trim($_POST['privacy_policy_page'] ?? ''));
        setSetting('gdpr_self_service', isset($_POST['gdpr_self_service']) ? '1' : '0');
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Settings saved!'];
        redirect('admin/backups.php');
    }
}

// Download backup
if (isset($_GET['download'])) {
    $backupId = (int)$_GET['download'];
    $stmt = $db->prepare("SELECT filename FROM backups WHERE id = ?");
    $stmt->execute([$backupId]);
    $filename = $stmt->fetchColumn();
    if ($filename) {
        $filename = basename($filename); // Prevent path traversal
        $filepath = BACKUPS_PATH . '/' . $filename;
        if (file_exists($filepath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        }
    }
}

$backups = getAllBackups();
$gdprRequests = getGdprRequests();

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-shield-alt"></i> Backup & Data Privacy</h1>
</div>

<!-- Backup Actions -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-database"></i> Create Backup</h2>
    <div style="display: flex; gap: 1rem; margin-top: var(--space-md); flex-wrap: wrap;">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="backup_database">
            <button type="submit" class="btn-admin btn-save"><i class="fas fa-database"></i> Backup Database</button>
        </form>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="backup_files">
            <button type="submit" class="btn-admin btn-outline"><i class="fas fa-folder"></i> Backup Files (Uploads)</button>
        </form>
    </div>
</div>

<!-- Backup List -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-history"></i> Backup History</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <table class="admin-table">
            <thead><tr><th>Filename</th><th>Type</th><th>Size</th><th>Date</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($backups as $b): ?>
                <tr>
                    <td><code><?php echo e($b['filename']); ?></code></td>
                    <td><span class="badge-status badge-active"><?php echo e(ucfirst($b['type'])); ?></span></td>
                    <td><?php echo $b['file_size'] ? round($b['file_size'] / 1024 / 1024, 2) . ' MB' : '—'; ?></td>
                    <td><?php echo formatDate($b['created_at']); ?></td>
                    <td>
                        <a href="<?php echo url('admin/backups.php?download=' . $b['id']); ?>" class="btn-admin btn-sm btn-outline"><i class="fas fa-download"></i></a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this backup?');">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete_backup">
                            <input type="hidden" name="backup_id" value="<?php echo $b['id']; ?>">
                            <button type="submit" class="btn-admin btn-sm btn-delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($backups)): ?>
                <tr><td colspan="5" style="text-align:center; color:var(--color-gray-400);">No backups yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Privacy Settings -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-user-shield"></i> Privacy & GDPR Settings</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="form-row">
                <div class="form-group">
                    <label>Backup Retention (max backups to keep)</label>
                    <input type="number" name="backup_retention_count" class="form-control" value="<?php echo e(getSetting('backup_retention_count', '10')); ?>" min="1">
                </div>
                <div class="form-group">
                    <label>Privacy Policy Page Slug</label>
                    <input type="text" name="privacy_policy_page" class="form-control" value="<?php echo e(getSetting('privacy_policy_page', '')); ?>" placeholder="e.g. privacy-policy">
                </div>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="cookie_consent_enabled" value="1" <?php echo getSetting('cookie_consent_enabled', '0') === '1' ? 'checked' : ''; ?>>
                    <strong>Show Cookie Consent Banner</strong>
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="gdpr_self_service" value="1" <?php echo getSetting('gdpr_self_service', '1') === '1' ? 'checked' : ''; ?>>
                    <strong>Allow customers to export/delete their data</strong> (from account page)
                </label>
            </div>

            <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>

<!-- GDPR Requests -->
<?php if (!empty($gdprRequests)): ?>
<div class="admin-section">
    <h2><i class="fas fa-clipboard-list"></i> Data Requests</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <table class="admin-table">
            <thead><tr><th>Customer</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
                <?php foreach ($gdprRequests as $r): ?>
                <tr>
                    <td><?php echo e(($r['first_name'] ?? 'Deleted') . ' ' . ($r['last_name'] ?? '')); ?><br><small><?php echo e($r['email'] ?? 'Removed'); ?></small></td>
                    <td><span class="badge-status <?php echo $r['request_type'] === 'delete' ? 'badge-inactive' : 'badge-active'; ?>"><?php echo ucfirst($r['request_type']); ?></span></td>
                    <td><?php echo ucfirst($r['status']); ?></td>
                    <td><?php echo formatDate($r['created_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
