<?php
/**
 * Admin — Blog Comments Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($commentId) {
        if ($action === 'approve') {
            approveBlogComment($commentId);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Comment approved.'];
        } elseif ($action === 'reject') {
            rejectBlogComment($commentId);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Comment rejected.'];
        } elseif ($action === 'delete') {
            deleteBlogComment($commentId);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Comment deleted.'];
        }
    }

    // Bulk actions
    if (!empty($_POST['bulk_action']) && !empty($_POST['comment_ids'])) {
        foreach ($_POST['comment_ids'] as $cId) {
            $cId = (int)$cId;
            if ($_POST['bulk_action'] === 'approve') approveBlogComment($cId);
            elseif ($_POST['bulk_action'] === 'reject') rejectBlogComment($cId);
            elseif ($_POST['bulk_action'] === 'delete') deleteBlogComment($cId);
        }
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Bulk action completed.'];
    }

    redirect('admin/blog-comments.php' . (!empty($_GET['status']) ? '?status=' . $_GET['status'] : ''));
}

// Filters
$filters = [];
if (isset($_GET['status'])) {
    $filters['status'] = $_GET['status'] === 'pending' ? 0 : 1;
}
if (!empty($_GET['post_id'])) {
    $filters['post_id'] = (int)$_GET['post_id'];
}

$comments = getAdminBlogComments($filters);
$pendingCount = getPendingCommentCount();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-comments"></i> Blog Comments</h1>
    <div>
        <?php if ($pendingCount > 0): ?>
            <span class="badge" style="background:#f59e0b; color:#fff; padding:4px 12px; border-radius:12px; font-size:0.9rem;">
                <?php echo $pendingCount; ?> Pending
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Status Tabs -->
<div style="display:flex; gap:10px; margin-bottom:20px;">
    <a href="<?php echo url('admin/blog-comments.php'); ?>" class="btn-admin btn-small <?php echo !isset($_GET['status']) ? 'btn-primary' : 'btn-outline'; ?>">All</a>
    <a href="<?php echo url('admin/blog-comments.php?status=pending'); ?>" class="btn-admin btn-small <?php echo ($_GET['status'] ?? '') === 'pending' ? 'btn-primary' : 'btn-outline'; ?>">
        Pending <?php if ($pendingCount > 0) echo "($pendingCount)"; ?>
    </a>
    <a href="<?php echo url('admin/blog-comments.php?status=approved'); ?>" class="btn-admin btn-small <?php echo ($_GET['status'] ?? '') === 'approved' ? 'btn-primary' : 'btn-outline'; ?>">Approved</a>
</div>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
    <div style="display:flex; gap:10px; margin-bottom:15px;">
        <select name="bulk_action" class="admin-select" style="max-width:180px;">
            <option value="">Bulk Actions</option>
            <option value="approve">Approve</option>
            <option value="reject">Reject</option>
            <option value="delete">Delete</option>
        </select>
        <button type="submit" class="btn-admin btn-small" onclick="return confirm('Apply action to selected comments?')">Apply</button>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:30px;"><input type="checkbox" id="selectAll"></th>
                    <th>Author</th>
                    <th>Comment</th>
                    <th>Post</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($comments)): ?>
                    <tr><td colspan="7" style="text-align:center; padding:40px; color:#666;">No comments found.</td></tr>
                <?php endif; ?>
                <?php foreach ($comments as $c): ?>
                    <tr style="<?php echo !$c['is_approved'] ? 'background:#fffbeb;' : ''; ?>">
                        <td><input type="checkbox" name="comment_ids[]" value="<?php echo $c['id']; ?>"></td>
                        <td>
                            <strong><?php echo e($c['author_name']); ?></strong>
                            <br><small style="color:#666;"><?php echo e($c['author_email']); ?></small>
                        </td>
                        <td style="max-width:300px;">
                            <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                <?php echo e(mb_substr($c['content'], 0, 120)); ?>
                            </div>
                        </td>
                        <td>
                            <a href="<?php echo url('admin/blog-post-edit.php?id=' . $c['post_id']); ?>" style="font-size:0.85rem;">
                                <?php echo e(mb_substr($c['post_title'], 0, 40)); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($c['is_approved']): ?>
                                <span class="badge-status badge-active">Approved</span>
                            <?php else: ?>
                                <span class="badge-status badge-inactive">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;"><?php echo date('M j, Y', strtotime($c['created_at'])); ?></td>
                        <td class="actions" style="white-space:nowrap;">
                            <?php if (!$c['is_approved']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn-icon" title="Approve" style="color:#22c55e;"><i class="fas fa-check"></i></button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn-icon" title="Reject" style="color:#f59e0b;"><i class="fas fa-times"></i></button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this comment?')">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                <input type="hidden" name="action" value="delete">
                                <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</form>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="comment_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
