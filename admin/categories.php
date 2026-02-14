<?php
/**
 * Admin — Categories Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

if (isset($_GET['delete']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $db->prepare('DELETE FROM products WHERE category_id = ?')->execute([(int)$_GET['delete']]);
    $db->prepare('DELETE FROM product_categories WHERE id = ?')->execute([(int)$_GET['delete']]);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Category and its products deleted.'];
    redirect('admin/categories.php');
}

$categories = $db->query('SELECT pc.*, (SELECT COUNT(*) FROM products WHERE category_id = pc.id) as product_count FROM product_categories pc ORDER BY pc.sort_order')->fetchAll();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-tags"></i> Product Categories</h1>
    <a href="<?php echo url('admin/category-edit.php'); ?>" class="btn-admin btn-add"><i class="fas fa-plus"></i> Add Category</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Slug</th>
                <th>Products</th>
                <th>Visible</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td>
                        <?php if ($c['image']): ?>
                            <img src="<?php echo e($c['image']); ?>" alt="" class="table-thumb">
                        <?php else: ?>
                            <div class="table-thumb-placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo e($c['name']); ?></strong></td>
                    <td><code><?php echo e($c['slug']); ?></code></td>
                    <td><?php echo $c['product_count']; ?></td>
                    <td><?php echo $c['is_visible'] ? '<span class="badge-status badge-active">Yes</span>' : '<span class="badge-status badge-inactive">No</span>'; ?></td>
                    <td class="actions">
                        <a href="<?php echo url('admin/category-edit.php?id=' . $c['id']); ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="<?php echo url('admin/categories.php?delete=' . $c['id'] . '&csrf=' . e($csrfToken)); ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete this category and ALL its products?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
