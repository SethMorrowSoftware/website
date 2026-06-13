<?php
/**
 * Admin — Blog Comments Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_blog');

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

    // Preserve the status filter across the redirect, but only for known
    // values so an arbitrary query string can't be reflected back.
    $statusFilter = in_array($_GET['status'] ?? '', ['pending', 'approved'], true) ? $_GET['status'] : '';
    redirect('admin/blog-comments.php' . ($statusFilter ? '?status=' . $statusFilter : ''));
}

// Filters
$filters = [];
if (isset($_GET['status'])) {
    $filters['status'] = $_GET['status'] === 'pending' ? 0 : 1;
}
if (!empty($_GET['post_id'])) {
    $filters['post_id'] = (int)$_GET['post_id'];
}

$currentPage = max(1, (int)($_GET['p'] ?? 1));
$result = getAdminBlogComments($filters, $currentPage);
$comments = $result['comments'];
$totalPages = $result['pages'];
$pendingCount = getPendingCommentCount();
$csrfToken = generateCSRFToken();
$activeStatus = $_GET['status'] ?? '';

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-comments"></i> Blog Comments</h1>
</div>

<!-- Status Tabs -->
<div class="blog-admin-tabs">
    <a href="<?php echo url('admin/blog-comments.php'); ?>" class="<?php echo !$activeStatus ? 'active' : ''; ?>">All</a>
    <a href="<?php echo url('admin/blog-comments.php?status=pending'); ?>" class="<?php echo $activeStatus === 'pending' ? 'active' : ''; ?>">
        Pending
        <?php if ($pendingCount > 0): ?>
            <span class="tab-badge"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
    </a>
    <a href="<?php echo url('admin/blog-comments.php?status=approved'); ?>" class="<?php echo $activeStatus === 'approved' ? 'active' : ''; ?>">Approved</a>
</div>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
    <div class="blog-bulk-bar">
        <select name="bulk_action" class="admin-select">
            <option value="">Bulk Actions</option>
            <option value="approve">Approve</option>
            <option value="reject">Reject</option>
            <option value="delete">Delete</option>
        </select>
        <button type="submit" class="btn-admin btn-small btn-outline" onclick="return confirm('Apply action to selected comments?')">Apply</button>
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
                    <tr><td colspan="7" class="empty-state"><p>No comments found.</p></td></tr>
                <?php endif; ?>
                <?php foreach ($comments as $c): ?>
                    <tr<?php echo !$c['is_approved'] ? ' class="row-unread"' : ''; ?>>
                        <td><input type="checkbox" name="comment_ids[]" value="<?php echo $c['id']; ?>"></td>
                        <td class="comment-author-cell">
                            <strong><?php echo e($c['author_name']); ?></strong>
                            <span class="comment-email"><?php echo e($c['author_email']); ?></span>
                        </td>
                        <td>
                            <div class="comment-content-preview"><?php echo e(mb_substr($c['content'], 0, 120)); ?></div>
                        </td>
                        <td>
                            <a href="<?php echo url('admin/blog-post-edit.php?id=' . $c['post_id']); ?>" style="font-size:0.85rem; color:var(--color-primary); text-decoration:none; font-weight:500;">
                                <?php echo e(mb_substr($c['post_title'], 0, 40)); ?>
                            </a>
                        </td>
                        <td>
                            <?php if ($c['is_approved']): ?>
                                <span class="badge-status badge-active">Approved</span>
                            <?php else: ?>
                                <span class="badge-status badge-unread">Pending</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;"><?php echo date('M j, Y', strtotime($c['created_at'])); ?></td>
                        <td class="actions">
                            <?php if (!$c['is_approved']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <button type="submit" class="btn-icon" title="Approve" style="color:var(--color-success);"><i class="fas fa-check"></i></button>
                                </form>
                            <?php else: ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="comment_id" value="<?php echo $c['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <button type="submit" class="btn-icon" title="Reject" style="color:var(--color-secondary);"><i class="fas fa-times"></i></button>
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

<?php if ($totalPages > 1): ?>
<nav class="blog-pagination">
    <?php
    $baseParams = $_GET;
    unset($baseParams['p']);
    $baseQuery = http_build_query($baseParams);
    $baseHref = 'admin/blog-comments.php' . ($baseQuery ? '?' . $baseQuery . '&' : '?');
    ?>
    <?php if ($currentPage > 1): ?>
        <a href="<?php echo url($baseHref . 'p=' . ($currentPage - 1)); ?>">&laquo; Prev</a>
    <?php endif; ?>
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <?php if ($i === $currentPage): ?>
            <span class="page-current"><?php echo $i; ?></span>
        <?php elseif ($i <= 2 || $i > $totalPages - 2 || abs($i - $currentPage) <= 1): ?>
            <a href="<?php echo url($baseHref . 'p=' . $i); ?>"><?php echo $i; ?></a>
        <?php elseif ($i === 3 || $i === $totalPages - 2): ?>
            <span class="page-dots">&hellip;</span>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($currentPage < $totalPages): ?>
        <a href="<?php echo url($baseHref . 'p=' . ($currentPage + 1)); ?>">Next &raquo;</a>
    <?php endif; ?>
</nav>
<?php endif; ?>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    document.querySelectorAll('input[name="comment_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
