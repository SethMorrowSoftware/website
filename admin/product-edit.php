<?php
/**
 * Admin — Product Editor
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;

if ($id) {
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
}

$categories = $db->query('SELECT * FROM product_categories ORDER BY sort_order')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $name = trim($_POST['name'] ?? '');
    $slug = createSlug($_POST['slug'] ?? $name);
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $price = trim($_POST['price'] ?? '');
    $unit = trim($_POST['unit'] ?? '');
    $specifications = trim($_POST['specifications'] ?? '');
    $features = trim($_POST['features'] ?? '');
    $price_note = trim($_POST['price_note'] ?? '');
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $image = $product['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $uploaded = handleUpload($_FILES['image']);
        if ($uploaded) $image = $uploaded;
    }

    if ($name && $category_id) {
        if ($id && $product) {
            $stmt = $db->prepare('UPDATE products SET name=?, slug=?, category_id=?, description=?, image=?, price=?, unit=?, specifications=?, features=?, price_note=?, is_visible=?, is_available=?, sort_order=? WHERE id=?');
            $stmt->execute([$name, $slug, $category_id, $description, $image, $price, $unit, $specifications, $features, $price_note, $is_visible, $is_available, $sort_order, $id]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Product updated!'];
        } else {
            $stmt = $db->prepare('INSERT INTO products (name, slug, category_id, description, image, price, unit, specifications, features, price_note, is_visible, is_available, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$name, $slug, $category_id, $description, $image, $price, $unit, $specifications, $features, $price_note, $is_visible, $is_available, $sort_order]);
            $id = $db->lastInsertId();
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Product created!'];
        }
        redirect('admin/product-edit.php?id=' . $id);
    }
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-box"></i> <?php echo $product ? 'Edit Product' : 'Add New Product'; ?></h1>
    <a href="<?php echo url('admin/products.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Products</a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

    <div class="form-layout-sidebar">
        <div class="form-main">
            <div class="form-section">
                <div class="form-group">
                    <label>Product Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" value="<?php echo e($product['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>URL Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo e($product['slug'] ?? ''); ?>" placeholder="auto-generated">
                </div>
                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <select name="category_id" class="form-control" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($product['category_id'] ?? 0) == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo e($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="4"><?php echo e($product['description'] ?? ''); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Price</label>
                        <input type="text" name="price" class="form-control" value="<?php echo e($product['price'] ?? ''); ?>" placeholder="e.g., $45 or Call for Pricing">
                    </div>
                    <div class="form-group">
                        <label>Unit</label>
                        <input type="text" name="unit" class="form-control" value="<?php echo e($product['unit'] ?? ''); ?>" placeholder="e.g., per unit, per hour, each">
                    </div>
                </div>
                <div class="form-group">
                    <label>Price Note</label>
                    <input type="text" name="price_note" class="form-control" value="<?php echo e($product['price_note'] ?? ''); ?>" placeholder="e.g., Pricing varies by location">
                    <small class="form-help">Optional note shown below the price (e.g., disclaimers, conditions).</small>
                </div>
            </div>

            <div class="form-section">
                <h3><i class="fas fa-info-circle"></i> Additional Details</h3>
                <div class="form-group">
                    <label>Specifications</label>
                    <input type="text" name="specifications" class="form-control" value="<?php echo e($product['specifications'] ?? ''); ?>" placeholder="e.g., 22' x 8' x 4.5', 500ml, 10lbs">
                    <small class="form-help">Dimensions, weight, size, or other technical specs.</small>
                </div>
                <div class="form-group">
                    <label>Features / Use Cases</label>
                    <textarea name="features" class="form-control" rows="3" placeholder="Comma-separated features, e.g.: Fast delivery, Premium quality, Satisfaction guaranteed"><?php echo e($product['features'] ?? ''); ?></textarea>
                    <small class="form-help">Comma-separated list of features or use cases displayed as bullet points.</small>
                </div>
            </div>

            <div class="form-section">
                <div class="form-group">
                    <label>Product Image</label>
                    <?php if (!empty($product['image'])): ?>
                        <div class="current-image"><img src="<?php echo e($product['image']); ?>" alt=""></div>
                    <?php endif; ?>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
            </div>
        </div>

        <div class="form-sidebar">
            <div class="form-section">
                <h4>Settings</h4>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_visible" value="1" <?php echo (!$product || $product['is_visible']) ? 'checked' : ''; ?>>
                        Visible on site
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_available" value="1" <?php echo (!$product || $product['is_available']) ? 'checked' : ''; ?>>
                        Available for order
                    </label>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?php echo e($product['sort_order'] ?? '0'); ?>">
                </div>
            </div>
            <button type="submit" class="btn-admin btn-save" style="width:100%;"><i class="fas fa-save"></i> Save Product</button>
        </div>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
