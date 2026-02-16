<?php
/**
 * Admin — Blog Posts Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $id = (int)$_POST['delete'];
    deleteBlogPost($id);
    logAudit('delete', 'blog_post', $id);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Blog post deleted.'];
    redirect('admin/blog-posts.php');
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['bulk_action']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $ids = $_POST['post_ids'] ?? [];
    $action = $_POST['bulk_action'];
    foreach ($ids as $id) {
        $id = (int)$id;
        if ($action === 'publish') {
            $db->prepare("UPDATE blog_posts SET status = 'published', published_at = COALESCE(published_at, datetime('now')), updated_at = datetime('now') WHERE id = ?")->execute([$id]);
        } elseif ($action === 'draft') {
            $db->prepare("UPDATE blog_posts SET status = 'draft', updated_at = datetime('now') WHERE id = ?")->execute([$id]);
        } elseif ($action === 'delete') {
            deleteBlogPost($id);
        }
    }
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Bulk action completed.'];
    redirect('admin/blog-posts.php');
}

// Filters
$filters = [];
if (!empty($_GET['status'])) $filters['status'] = $_GET['status'];
if (!empty($_GET['category_id'])) $filters['category_id'] = $_GET['category_id'];
if (!empty($_GET['search'])) $filters['search'] = $_GET['search'];

$currentPage = max(1, (int)($_GET['p'] ?? 1));
$result = getAdminBlogPosts($filters, $currentPage);
$posts = $result['posts'];
$totalPages = $result['pages'];
$totalPosts = $result['total'];
$categories = getBlogCategories(true);
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-blog"></i> Blog Posts</h1>
    <a href="<?php echo url('admin/blog-post-edit.php'); ?>" class="btn-admin btn-add"><i class="fas fa-plus"></i> New Post</a>
</div>

<!-- Filters -->
<form method="GET" class="blog-filter-bar">
    <select name="status" onchange="this.form.submit()" class="admin-select">
        <option value="">All Statuses</option>
        <option value="published" <?php echo ($_GET['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
        <option value="draft" <?php echo ($_GET['status'] ?? '') === 'draft' ? 'selected' : ''; ?>>Draft</option>
        <option value="scheduled" <?php echo ($_GET['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
    </select>
    <select name="category_id" onchange="this.form.submit()" class="admin-select">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?php echo $cat['id']; ?>" <?php echo ((int)($_GET['category_id'] ?? 0)) === $cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
        <?php endforeach; ?>
    </select>
    <input type="text" name="search" placeholder="Search posts..." value="<?php echo e($_GET['search'] ?? ''); ?>" class="admin-input">
    <button type="submit" class="btn-admin btn-small btn-save"><i class="fas fa-search"></i></button>
    <?php if (!empty($filters)): ?>
        <a href="<?php echo url('admin/blog-posts.php'); ?>" class="btn-admin btn-small btn-outline">Clear</a>
    <?php endif; ?>
    <span style="margin-left:auto; font-size:12px; color:var(--color-gray-500);"><?php echo $totalPosts; ?> post<?php echo $totalPosts !== 1 ? 's' : ''; ?></span>
</form>

<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
    <div class="blog-bulk-bar">
        <select name="bulk_action" class="admin-select">
            <option value="">Bulk Actions</option>
            <option value="publish">Publish</option>
            <option value="draft">Move to Draft</option>
            <option value="delete">Delete</option>
        </select>
        <button type="submit" class="btn-admin btn-small btn-outline" onclick="return confirm('Apply this action to selected posts?')">Apply</button>
    </div>

    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:30px;"><input type="checkbox" id="selectAll"></th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Views</th>
                    <th>Comments</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($posts)): ?>
                    <tr><td colspan="8" class="empty-state"><p>No blog posts found. <a href="<?php echo url('admin/blog-post-edit.php'); ?>">Create your first post</a></p></td></tr>
                <?php endif; ?>
                <?php foreach ($posts as $p): ?>
                    <tr>
                        <td><input type="checkbox" name="post_ids[]" value="<?php echo $p['id']; ?>"></td>
                        <td>
                            <div class="post-title-cell">
                                <?php if ($p['featured_image']): ?>
                                    <img src="<?php echo e($p['featured_image']); ?>" class="post-thumb" alt="">
                                <?php else: ?>
                                    <div class="post-thumb-placeholder"><i class="fas fa-file-alt"></i></div>
                                <?php endif; ?>
                                <div class="post-title-text">
                                    <a href="<?php echo url('admin/blog-post-edit.php?id=' . $p['id']); ?>"><?php echo e($p['title']); ?></a>
                                    <?php if ($p['is_featured']): ?>
                                        <span class="badge-featured"><i class="fas fa-star"></i> Featured</span>
                                    <?php endif; ?>
                                    <?php if ($p['author_name']): ?>
                                        <div class="post-author"><?php echo e($p['author_name']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo e($p['category_name'] ?? '—'); ?></td>
                        <td>
                            <?php if ($p['status'] === 'published'): ?>
                                <span class="badge-status badge-active">Published</span>
                            <?php elseif ($p['status'] === 'scheduled'): ?>
                                <span class="badge-status badge-scheduled">Scheduled</span>
                            <?php else: ?>
                                <span class="badge-status badge-inactive">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <?php if ($p['published_at']): ?>
                                <?php echo date('M j, Y', strtotime($p['published_at'])); ?>
                            <?php else: ?>
                                <span style="color:var(--color-gray-400);">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo number_format($p['view_count']); ?></td>
                        <td><?php echo (int)$p['comment_count']; ?></td>
                        <td class="actions">
                            <a href="<?php echo url('admin/blog-post-edit.php?id=' . $p['id']); ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                            <?php if ($p['status'] === 'published'): ?>
                                <a href="<?php echo url('index.php?page=blog-post&slug=' . e($p['slug'])); ?>" target="_blank" class="btn-icon" title="View"><i class="fas fa-eye"></i></a>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this post?')">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="delete" value="<?php echo $p['id']; ?>">
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
    $baseHref = 'admin/blog-posts.php' . ($baseQuery ? '?' . $baseQuery . '&' : '?');
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
    document.querySelectorAll('input[name="post_ids[]"]').forEach(cb => cb.checked = this.checked);
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
