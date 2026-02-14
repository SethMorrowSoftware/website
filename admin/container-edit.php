<?php
/**
 * Admin — Container Editor
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$container = null;

if ($id) {
    $stmt = $db->prepare('SELECT * FROM containers WHERE id = ?');
    $stmt->execute([$id]);
    $container = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $name = trim($_POST['name'] ?? '');
    $size = trim($_POST['size'] ?? '');
    $unit = trim($_POST['unit'] ?? 'yard');
    $dimensions = trim($_POST['dimensions'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $use_cases = trim($_POST['use_cases'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $price_note = trim($_POST['price_note'] ?? '');
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $image = $container['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $uploaded = handleUpload($_FILES['image']);
        if ($uploaded) $image = $uploaded;
    }

    if ($name && $size) {
        if ($id && $container) {
            $stmt = $db->prepare('UPDATE containers SET name=?, size=?, unit=?, dimensions=?, description=?, use_cases=?, image=?, price=?, price_note=?, is_visible=?, sort_order=? WHERE id=?');
            $stmt->execute([$name, $size, $unit, $dimensions, $description, $use_cases, $image, $price, $price_note, $is_visible, $sort_order, $id]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Container updated!'];
        } else {
            $stmt = $db->prepare('INSERT INTO containers (name, size, unit, dimensions, description, use_cases, image, price, price_note, is_visible, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$name, $size, $unit, $dimensions, $description, $use_cases, $image, $price, $price_note, $is_visible, $sort_order]);
            $id = $db->lastInsertId();
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Container created!'];
        }
        redirect('/admin/container-edit.php?id=' . $id);
    }
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-dumpster"></i> <?php echo $container ? 'Edit Container' : 'Add Container'; ?></h1>
    <a href="/admin/containers.php" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Containers</a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form" style="max-width: 800px;">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

    <div class="form-section">
        <div class="form-row">
            <div class="form-group">
                <label>Container Name <span class="required">*</span></label>
                <input type="text" name="name" class="form-control" value="<?php echo e($container['name'] ?? ''); ?>" required placeholder="e.g., 20 Yard Container">
            </div>
            <div class="form-group" style="max-width: 150px;">
                <label>Size <span class="required">*</span></label>
                <input type="text" name="size" class="form-control" value="<?php echo e($container['size'] ?? ''); ?>" required placeholder="e.g., 20">
            </div>
            <div class="form-group" style="max-width: 150px;">
                <label>Unit</label>
                <input type="text" name="unit" class="form-control" value="<?php echo e($container['unit'] ?? 'yard'); ?>" placeholder="yard">
            </div>
        </div>
        <div class="form-group">
            <label>Dimensions</label>
            <input type="text" name="dimensions" class="form-control" value="<?php echo e($container['dimensions'] ?? ''); ?>" placeholder="e.g., 22' x 8' x 4.5'">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3"><?php echo e($container['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label>Use Cases (comma-separated)</label>
            <input type="text" name="use_cases" class="form-control" value="<?php echo e($container['use_cases'] ?? ''); ?>" placeholder="e.g., Large renovations, construction projects, whole-house cleanouts">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Price</label>
                <input type="text" name="price" class="form-control" value="<?php echo e($container['price'] ?? ''); ?>" placeholder="e.g., $350 or Call for Pricing">
            </div>
            <div class="form-group">
                <label>Price Note</label>
                <input type="text" name="price_note" class="form-control" value="<?php echo e($container['price_note'] ?? ''); ?>" placeholder="e.g., Pricing varies by material">
            </div>
        </div>
        <div class="form-group">
            <label>Container Image</label>
            <?php if (!empty($container['image'])): ?>
                <div class="current-image"><img src="<?php echo e($container['image']); ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_visible" value="1" <?php echo (!$container || $container['is_visible']) ? 'checked' : ''; ?>>
                    Visible
                </label>
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?php echo e($container['sort_order'] ?? '0'); ?>">
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Container</button>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
