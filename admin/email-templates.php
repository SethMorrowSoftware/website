<?php
/**
 * Admin — Email Templates Manager
 *
 * Edit email templates, view queue stats, process queue, and
 * manage notification preferences.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notifications.php';

requireLogin();
requirePermission('manage_settings');

$db = getDB();

// Handle template update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_template') {
        $id = (int)$_POST['template_id'];
        $subject = trim($_POST['subject'] ?? '');
        $bodyHtml = $_POST['body_html'] ?? '';
        $isActive = isset($_POST['is_active']);
        if ($subject) {
            updateEmailTemplate($id, $subject, $bodyHtml, $isActive);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Template updated!'];
        }
        redirect('admin/email-templates.php');
    }

    if ($action === 'process_queue') {
        $sent = processEmailQueue(50);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "Processed queue: $sent email(s) sent."];
        redirect('admin/email-templates.php');
    }

    if ($action === 'update_preferences') {
        $events = getNotificationEvents();
        foreach ($events as $eventType => $def) {
            if (!$def['admin']) continue;
            $enabled = isset($_POST['pref_' . $eventType]);
            setNotificationPreference(null, $eventType, 'email', $enabled);
        }
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Notification preferences saved!'];
        redirect('admin/email-templates.php');
    }
}

$templates = getAllEmailTemplates();
$queueStats = getEmailQueueStats();
$editTemplate = null;

if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($templates as $t) {
        if ($t['id'] == $editId) { $editTemplate = $t; break; }
    }
}

$csrfToken = generateCSRFToken();
$notificationEvents = getNotificationEvents();
$adminPrefs = getNotificationPreferences(null);

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-envelope"></i> Email & Notifications</h1>
</div>

<!-- Queue Stats -->
<div class="stats-cards" style="margin-bottom: var(--space-xl);">
    <div class="stat-card">
        <div class="stat-icon bg-warning"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $queueStats['pending']; ?></div>
            <div class="stat-label">Pending in Queue</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $queueStats['sent']; ?></div>
            <div class="stat-label">Sent</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:var(--color-error);color:#fff;"><i class="fas fa-exclamation-circle"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $queueStats['failed']; ?></div>
            <div class="stat-label">Failed</div>
        </div>
    </div>
    <div class="stat-card">
        <form method="POST" style="display:inline;">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="process_queue">
            <button type="submit" class="btn-admin btn-save" style="margin-top:0.5rem;">
                <i class="fas fa-play"></i> Process Queue Now
            </button>
        </form>
    </div>
</div>

<?php if ($editTemplate): ?>
<!-- Template Editor -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <div class="section-head">
        <h2>Edit: <?php echo e($editTemplate['name']); ?></h2>
        <a href="<?php echo url('admin/email-templates.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="update_template">
            <input type="hidden" name="template_id" value="<?php echo $editTemplate['id']; ?>">

            <div class="form-group">
                <label>Template Slug</label>
                <input type="text" class="form-control" value="<?php echo e($editTemplate['slug']); ?>" disabled>
            </div>

            <div class="form-group">
                <label>Subject Line</label>
                <input type="text" name="subject" class="form-control" value="<?php echo e($editTemplate['subject']); ?>" required>
            </div>

            <div class="form-group">
                <label>Email Body (HTML)</label>
                <textarea name="body_html" class="form-control" rows="12" style="font-family: monospace; font-size: 0.85rem;"><?php echo e($editTemplate['body_html']); ?></textarea>
            </div>

            <?php if ($editTemplate['variables']): ?>
            <div class="form-group">
                <label>Available Variables</label>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                    <?php foreach (explode(', ', $editTemplate['variables']) as $var): ?>
                        <code style="background: var(--color-gray-100); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;">{{<?php echo e(trim($var)); ?>}}</code>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?php echo $editTemplate['is_active'] ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Template</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Templates List -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2>Email Templates</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Template</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Last Updated</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $t): ?>
                <tr>
                    <td>
                        <strong><?php echo e($t['name']); ?></strong>
                        <br><small class="text-muted"><code><?php echo e($t['slug']); ?></code></small>
                    </td>
                    <td><?php echo e($t['subject']); ?></td>
                    <td>
                        <span class="badge-status <?php echo $t['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                            <?php echo $t['is_active'] ? 'Active' : 'Disabled'; ?>
                        </span>
                    </td>
                    <td><?php echo formatDate($t['updated_at']); ?></td>
                    <td>
                        <a href="<?php echo url('admin/email-templates.php?edit=' . $t['id']); ?>" class="btn-admin btn-sm btn-edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($templates)): ?>
                <tr><td colspan="5" style="text-align:center; color:var(--color-gray-400);">No templates found. Run database migrations to seed defaults.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Admin Notification Preferences -->
<div class="admin-section">
    <h2>Admin Notification Preferences</h2>
    <p style="color: var(--color-gray-500); margin-bottom: var(--space-md);">Choose which events send email notifications to the admin email address.</p>
    <div class="admin-card">
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="update_preferences">

            <?php foreach ($notificationEvents as $eventType => $def):
                if (!$def['admin']) continue;
                $checked = isset($adminPrefs[$eventType]['email']) ? $adminPrefs[$eventType]['email'] : true;
            ?>
            <div class="form-group" style="margin-bottom: 0.5rem;">
                <label class="checkbox-label">
                    <input type="checkbox" name="pref_<?php echo e($eventType); ?>" value="1" <?php echo $checked ? 'checked' : ''; ?>>
                    <strong><?php echo e($def['label']); ?></strong> — <?php echo e($def['description']); ?>
                </label>
            </div>
            <?php endforeach; ?>

            <button type="submit" class="btn-admin btn-save" style="margin-top: var(--space-md);"><i class="fas fa-save"></i> Save Preferences</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
