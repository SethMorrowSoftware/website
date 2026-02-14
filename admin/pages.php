<?php
/**
 * Admin — Pages Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

// Handle delete (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $id = (int)$_POST['delete'];
    $page = $db->prepare('SELECT * FROM pages WHERE id = ?');
    $page->execute([$id]);
    $p = $page->fetch();
    if ($p && !$p['is_system']) {
        $db->prepare('DELETE FROM pages WHERE id = ?')->execute([$id]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Page deleted.'];
    } else {
        $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'System pages cannot be deleted.'];
    }
    redirect('admin/pages.php');
}

$pages = $db->query('SELECT * FROM pages ORDER BY sort_order ASC')->fetchAll();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-file-alt"></i> Pages</h1>
    <a href="<?php echo url('admin/page-edit.php'); ?>" class="btn-admin btn-add"><i class="fas fa-plus"></i> Add New Page</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Slug</th>
                <th>Template</th>
                <th>Status</th>
                <th>Nav</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="sortablePages">
            <?php foreach ($pages as $p): ?>
                <tr data-id="<?php echo $p['id']; ?>">
                    <td>
                        <strong><?php echo e($p['title']); ?></strong>
                        <?php if ($p['is_system']): ?>
                            <span class="badge-small">System</span>
                        <?php endif; ?>
                    </td>
                    <td><code>/<?php echo e($p['slug']); ?></code></td>
                    <td><?php echo e($p['template']); ?></td>
                    <td>
                        <?php if ($p['is_published']): ?>
                            <span class="badge-status badge-active">Published</span>
                        <?php else: ?>
                            <span class="badge-status badge-inactive">Draft</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $p['show_in_nav'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
                    <td class="actions">
                        <a href="<?php echo url('admin/page-edit.php?id=' . $p['id']); ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="<?php echo url('index.php?page=' . e($p['slug'])); ?>" target="_blank" class="btn-icon" title="View"><i class="fas fa-eye"></i></a>
                        <?php if (!$p['is_system']): ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this page?')">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="delete" value="<?php echo $p['id']; ?>">
                                <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
