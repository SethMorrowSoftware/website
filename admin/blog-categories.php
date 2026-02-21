<?php
/**
 * Admin — Blog Categories Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_blog');

$db = getDB();

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $data = [
        'id' => (int)($_POST['id'] ?? 0),
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'image' => trim($_POST['image'] ?? ''),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'is_visible' => isset($_POST['is_visible']) ? 1 : 0,
    ];

    if (!$data['name']) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Category name is required.'];
    } else {
        $catId = saveBlogCategory($data);
        logAudit($data['id'] ? 'update' : 'create', 'blog_category', $catId, ['name' => $data['name']]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Category saved.'];
    }
    redirect('admin/blog-categories.php');
}

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $id = (int)$_POST['delete'];
    deleteBlogCategory($id);
    logAudit('delete', 'blog_category', $id);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Category deleted.'];
    redirect('admin/blog-categories.php');
}

$categories = $db->query(
    "SELECT bc.*, COUNT(bp.id) AS post_count
     FROM blog_categories bc
     LEFT JOIN blog_posts bp ON bp.category_id = bc.id
     GROUP BY bc.id
     ORDER BY bc.sort_order ASC, bc.name ASC"
)->fetchAll();

$editCat = null;
if (!empty($_GET['edit'])) {
    $editCat = getBlogCategoryById((int)$_GET['edit']);
}

$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-folder-open"></i> Blog Categories</h1>
    <a href="<?php echo url('admin/blog-posts.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Posts</a>
</div>

<div class="blog-categories-grid">
    <!-- Category Form -->
    <div class="admin-section">
        <div class="section-head" style="padding:18px 24px;">
            <h2><?php echo $editCat ? 'Edit Category' : 'Add Category'; ?></h2>
        </div>
        <form method="POST">
            <div class="form-section">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="save" value="1">
                <input type="hidden" name="id" value="<?php echo $editCat['id'] ?? 0; ?>">

                <div class="form-group">
                    <label>Name *</label>
                    <input type="text" name="name" value="<?php echo e($editCat['name'] ?? ''); ?>" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3" class="form-control"><?php echo e($editCat['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Image URL</label>
                    <input type="text" name="image" value="<?php echo e($editCat['image'] ?? ''); ?>" class="form-control" placeholder="/uploads/images/...">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" value="<?php echo $editCat['sort_order'] ?? 0; ?>" class="form-control">
                    </div>
                    <div class="form-group" style="display:flex; align-items:flex-end; padding-bottom:2px;">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_visible" value="1" <?php echo ($editCat['is_visible'] ?? 1) ? 'checked' : ''; ?>>
                            <span>Visible on site</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Category</button>
                <?php if ($editCat): ?>
                    <a href="<?php echo url('admin/blog-categories.php'); ?>" class="btn-admin btn-outline">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Categories List -->
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th>Posts</th>
                    <th>Visible</th>
                    <th>Order</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr><td colspan="6" class="empty-state"><p>No categories yet. Add one using the form.</p></td></tr>
                <?php endif; ?>
                <?php foreach ($categories as $cat): ?>
                    <tr<?php echo ($editCat && $editCat['id'] === $cat['id']) ? ' style="background:rgba(37,99,235,0.04);"' : ''; ?>>
                        <td><strong><?php echo e($cat['name']); ?></strong></td>
                        <td><code style="font-size:12px; background:var(--color-gray-100); padding:2px 6px; border-radius:4px;"><?php echo e($cat['slug']); ?></code></td>
                        <td><?php echo (int)$cat['post_count']; ?></td>
                        <td>
                            <?php if ($cat['is_visible']): ?>
                                <span class="badge-status badge-active">Visible</span>
                            <?php else: ?>
                                <span class="badge-status badge-inactive">Hidden</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo (int)$cat['sort_order']; ?></td>
                        <td class="actions">
                            <a href="<?php echo url('admin/blog-categories.php?edit=' . $cat['id']); ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this category? Posts will be uncategorized.')">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="delete" value="<?php echo $cat['id']; ?>">
                                <button type="submit" class="btn-icon btn-danger" title="Delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
