<?php
/**
 * Admin — Navigation Manager
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$csrfToken = generateCSRFToken();

// Handle add nav item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['nav_action'] ?? '';

    if ($action === 'add') {
        $label = trim($_POST['label'] ?? '');
        $url = trim($_POST['url'] ?? '');
        $sort_order = (int)($_POST['sort_order'] ?? 99);
        if ($label && $url) {
            $db->prepare('INSERT INTO navigation (label, url, sort_order, is_visible) VALUES (?,?,?,1)')->execute([$label, $url, $sort_order]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Nav item added!'];
        }
    } elseif ($action === 'update') {
        $items = $_POST['items'] ?? [];
        foreach ($items as $id => $data) {
            $db->prepare('UPDATE navigation SET label=?, url=?, sort_order=?, is_visible=? WHERE id=?')->execute([
                trim($data['label']),
                trim($data['url']),
                (int)$data['sort_order'],
                isset($data['is_visible']) ? 1 : 0,
                (int)$id
            ]);
        }
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Navigation updated!'];
    } elseif ($action === 'delete' && isset($_POST['delete'])) {
        $db->prepare('DELETE FROM navigation WHERE id = ?')->execute([(int)$_POST['delete']]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Nav item deleted.'];
    }
    redirect('admin/navigation.php');
}

$navItems = $db->query('SELECT * FROM navigation ORDER BY sort_order ASC')->fetchAll();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-bars"></i> Navigation Manager</h1>
    <p>Manage your site's main navigation menu</p>
</div>

<!-- Edit Existing -->
<form method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
    <input type="hidden" name="nav_action" value="update">

    <div class="admin-section">
        <h2>Navigation Items</h2>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Label</th>
                        <th>URL</th>
                        <th>Visible</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($navItems as $item): ?>
                        <tr>
                            <td style="width: 80px;">
                                <input type="number" name="items[<?php echo $item['id']; ?>][sort_order]" class="form-control" value="<?php echo $item['sort_order']; ?>" style="width: 70px;">
                            </td>
                            <td>
                                <input type="text" name="items[<?php echo $item['id']; ?>][label]" class="form-control" value="<?php echo e($item['label']); ?>">
                            </td>
                            <td>
                                <input type="text" name="items[<?php echo $item['id']; ?>][url]" class="form-control" value="<?php echo e($item['url']); ?>">
                            </td>
                            <td style="width: 80px; text-align: center;">
                                <input type="checkbox" name="items[<?php echo $item['id']; ?>][is_visible]" value="1" <?php echo $item['is_visible'] ? 'checked' : ''; ?>>
                            </td>
                            <td style="width: 60px;">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Remove this nav item?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                    <input type="hidden" name="nav_action" value="delete">
                                    <input type="hidden" name="delete" value="<?php echo $item['id']; ?>">
                                    <button type="submit" class="btn-icon btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Navigation</button>
        </div>
    </div>
</form>

<!-- Add New -->
<form method="POST" class="admin-form" style="max-width: 700px;">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
    <input type="hidden" name="nav_action" value="add">

    <div class="form-section">
        <h3>Add Navigation Item</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Label</label>
                <input type="text" name="label" class="form-control" required placeholder="e.g., Services">
            </div>
            <div class="form-group">
                <label>URL</label>
                <input type="text" name="url" class="form-control" required placeholder="/index.php?page=services">
            </div>
            <div class="form-group" style="max-width: 100px;">
                <label>Order</label>
                <input type="number" name="sort_order" class="form-control" value="99">
            </div>
        </div>
        <button type="submit" class="btn-admin btn-add"><i class="fas fa-plus"></i> Add Item</button>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
