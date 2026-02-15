<?php
/**
 * Admin — Products Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

// Handle delete (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $db->prepare('DELETE FROM products WHERE id = ?')->execute([(int)$_POST['delete']]);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Product deleted.'];
    redirect('admin/products.php');
}

// Handle visibility toggle (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $db->prepare('UPDATE products SET is_visible = NOT is_visible WHERE id = ?')->execute([(int)$_POST['toggle']]);
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
                <th>Type</th>
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
                    <td><span class="badge-status badge-<?php echo ($p['product_type'] ?? 'physical') === 'digital' ? 'active' : 'inactive'; ?>"><?php echo e(ucfirst($p['product_type'] ?? 'physical')); ?></span></td>
                    <td><?php echo e($p['category_name']); ?></td>
                    <td><?php echo e($p['price'] ?: 'Call'); ?> <?php echo $p['unit'] ? '/ ' . e($p['unit']) : ''; ?></td>
                    <td>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="toggle" value="<?php echo $p['id']; ?>">
                            <button type="submit" class="btn-link" title="Toggle visibility">
                                <?php if ($p['is_visible']): ?>
                                    <span class="badge-status badge-active">Visible</span>
                                <?php else: ?>
                                    <span class="badge-status badge-inactive">Hidden</span>
                                <?php endif; ?>
                            </button>
                        </form>
                    </td>
                    <td class="actions">
                        <a href="<?php echo url('admin/product-edit.php?id=' . $p['id']); ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?')">
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

<?php if (empty($products)): ?>
    <div class="empty-state"><p>No products yet. <a href="<?php echo url('admin/product-edit.php'); ?>">Add your first product</a>.</p></div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
