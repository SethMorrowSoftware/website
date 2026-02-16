<?php
/**
 * Admin — Blog Categories Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

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
    <a href="<?php echo url('admin/blog-posts.php'); ?>" class="btn-admin btn-outline"><i class="fas fa-arrow-left"></i> Back to Posts</a>
</div>

<div style="display:grid; grid-template-columns: 1fr 1fr; gap:30px; align-items:start;">
    <!-- Category Form -->
    <div class="editor-panel">
        <h3><?php echo $editCat ? 'Edit Category' : 'Add Category'; ?></h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="save" value="1">
            <input type="hidden" name="id" value="<?php echo $editCat['id'] ?? 0; ?>">

            <div class="form-group">
                <label>Name *</label>
                <input type="text" name="name" value="<?php echo e($editCat['name'] ?? ''); ?>" class="admin-input" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" class="admin-input"><?php echo e($editCat['description'] ?? ''); ?></textarea>
            </div>
            <div class="form-group">
                <label>Image URL</label>
                <input type="text" name="image" value="<?php echo e($editCat['image'] ?? ''); ?>" class="admin-input" placeholder="/uploads/images/...">
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" value="<?php echo $editCat['sort_order'] ?? 0; ?>" class="admin-input">
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_visible" value="1" <?php echo ($editCat['is_visible'] ?? 1) ? 'checked' : ''; ?>>
                    <span>Visible</span>
                </label>
            </div>
            <button type="submit" class="btn-admin btn-primary"><i class="fas fa-save"></i> Save Category</button>
            <?php if ($editCat): ?>
                <a href="<?php echo url('admin/blog-categories.php'); ?>" class="btn-admin btn-outline">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Categories List -->
    <div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Posts</th>
                        <th>Visible</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr><td colspan="5" style="text-align:center; padding:20px; color:#666;">No categories yet.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><strong><?php echo e($cat['name']); ?></strong></td>
                            <td><code><?php echo e($cat['slug']); ?></code></td>
                            <td><?php echo (int)$cat['post_count']; ?></td>
                            <td><?php echo $cat['is_visible'] ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-muted"></i>'; ?></td>
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
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
