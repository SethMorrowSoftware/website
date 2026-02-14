<?php
/**
 * Admin — Containers Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

if (isset($_GET['delete']) && isset($_GET['csrf']) && verifyCSRFToken($_GET['csrf'])) {
    $db->prepare('DELETE FROM containers WHERE id = ?')->execute([(int)$_GET['delete']]);
    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Container deleted.'];
    redirect('/admin/containers.php');
}

$containers = $db->query('SELECT * FROM containers ORDER BY sort_order')->fetchAll();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-dumpster"></i> Roll Off Containers</h1>
    <a href="/admin/container-edit.php" class="btn-admin btn-add"><i class="fas fa-plus"></i> Add Container</a>
</div>

<div class="admin-table-wrap">
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Size</th>
                <th>Dimensions</th>
                <th>Price</th>
                <th>Visible</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($containers as $c): ?>
                <tr>
                    <td>
                        <?php if ($c['image']): ?>
                            <img src="<?php echo e($c['image']); ?>" alt="" class="table-thumb">
                        <?php else: ?>
                            <div class="table-thumb-placeholder"><i class="fas fa-dumpster"></i></div>
                        <?php endif; ?>
                    </td>
                    <td><strong><?php echo e($c['name']); ?></strong></td>
                    <td><?php echo e($c['size']); ?> <?php echo e($c['unit']); ?></td>
                    <td><?php echo e($c['dimensions']); ?></td>
                    <td><?php echo e($c['price'] ?: 'Call'); ?></td>
                    <td><?php echo $c['is_visible'] ? '<span class="badge-status badge-active">Yes</span>' : '<span class="badge-status badge-inactive">No</span>'; ?></td>
                    <td class="actions">
                        <a href="/admin/container-edit.php?id=<?php echo $c['id']; ?>" class="btn-icon" title="Edit"><i class="fas fa-edit"></i></a>
                        <a href="/admin/containers.php?delete=<?php echo $c['id']; ?>&csrf=<?php echo e($csrfToken); ?>" class="btn-icon btn-danger" title="Delete" onclick="return confirm('Delete this container?')"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
