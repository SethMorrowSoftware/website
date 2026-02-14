<?php
/**
 * Admin — Products Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

// Handle delete
if (isset($_GET['delete']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $db->prepare('DELETE FROM products WHERE id = ?')->execute([(int)$_GET['delete']]);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Product deleted.'];
    redirect('admin/products.php');
}

// Handle visibility toggle
if (isset($_GET['toggle']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $db->prepare('UPDATE products SET is_visible = NOT is_visible WHERE id = ?')->execute([(int)$_GET['toggle']]);
    redirect('admin/products.php');
}

$products = $db->query('SELECT p.*, pc.name as category_name FROM products p JOIN product_categories pc ON p.category_id = pc.id ORDER BY pc.sort_order, p.sort_order')->fetchAll();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-box"></i> Products</h1>
    <a href="<?php echo url('admin/product-edit.php'); ?>" class="btn-admin btn-add"><i class="fas fa-plus"></i> Add Product</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
                <tr>
                    <td>
                        <?php if ($p['image']): ?>
                            <img src="<?php echo e($p['image']); ?>" alt="" class="table-thumb">
                        <?php else: ?>
                            <div class="table-thumb-placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo e($p['name']); ?></strong></td>
                    <td><?php echo e($p['category_name']); ?></td>
                    <td><?php echo e($p['price'] ?: 'Call'); ?> <?php echo $p['unit'] ? '/ ' . e($p['unit']) : ''; ?></td>
                    <td>
                        <a href="<?php echo url('admin/products.php?toggle=' . $p['id'] . '&csrf=' . e($csrfToken)); ?>" title="Toggle visibility">
                            <?php if ($p['is_visible']): ?>
                                <span class="badge-status badge-active">Visible</span>
                            <?php else: ?>
                                <span class="badge-status badge-inactive">Hidden</span>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td class="actions">
                        <a href="<?php echo url('admin/product-edit.php?id=' . $p['id']); ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="<?php echo url('admin/products.php?delete=' . $p['id'] . '&csrf=' . e($csrfToken)); ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete this product?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($products)): ?>
    <div class="empty-state"><p>No products yet. <a href="<?php echo url('admin/product-edit.php'); ?>">Add your first product</a>.</p></div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
