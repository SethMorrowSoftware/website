<?php
/**
 * Admin — Category Editor
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = null;

if ($id) {
    $stmt = $db->prepare('SELECT * FROM product_categories WHERE id = ?');
    $stmt->execute([$id]);
    $category = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $name = trim($_POST['name'] ?? '');
    $slug = createSlug($_POST['slug'] ?? $name);
    $description = trim($_POST['description'] ?? '');
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $image = $category['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $uploaded = handleUpload($_FILES['image']);
        if ($uploaded) $image = $uploaded;
    }

    if ($name) {
        if ($id && $category) {
            $stmt = $db->prepare('UPDATE product_categories SET name=?, slug=?, description=?, image=?, is_visible=?, sort_order=? WHERE id=?');
            $stmt->execute([$name, $slug, $description, $image, $is_visible, $sort_order, $id]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Category updated!'];
        } else {
            $stmt = $db->prepare('INSERT INTO product_categories (name, slug, description, image, is_visible, sort_order) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$name, $slug, $description, $image, $is_visible, $sort_order]);
            $id = $db->lastInsertId();
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Category created!'];
        }
        redirect('admin/category-edit.php?id=' . $id);
    }
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-tags"></i> <?php echo $category ? 'Edit Category' : 'Add Category'; ?></h1>
    <a href="<?php echo url('admin/categories.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Categories</a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form" style="max-width: 700px;">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

    <div class="form-section">
        <div class="form-group">
            <label>Category Name <span class="required">*</span></label>
            <input type="text" name="name" class="form-control" value="<?php echo e($category['name'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label>URL Slug</label>
            <input type="text" name="slug" class="form-control" value="<?php echo e($category['slug'] ?? ''); ?>">
        </div>
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" class="form-control" rows="3"><?php echo e($category['description'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label>Category Image</label>
            <?php if (!empty($category['image'])): ?>
                <div class="current-image"><img src="<?php echo e($category['image']); ?>" alt=""></div>
            <?php endif; ?>
            <input type="file" name="image" class="form-control" accept="image/*">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_visible" value="1" <?php echo (!$category || $category['is_visible']) ? 'checked' : ''; ?>>
                    Visible
                </label>
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?php echo e($category['sort_order'] ?? '0'); ?>">
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Category</button>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
