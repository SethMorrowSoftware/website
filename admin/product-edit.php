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
    $product_type = in_array($_POST['product_type'] ?? '', ['physical', 'digital', 'service']) ? $_POST['product_type'] : 'physical';
    $download_limit = (int)($_POST['download_limit'] ?? 0);
    $download_expiry_hours = (int)($_POST['download_expiry_hours'] ?? 72);
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);
    $track_inventory = isset($_POST['track_inventory']) ? 1 : 0;
    $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
    $low_stock_threshold = (int)($_POST['low_stock_threshold'] ?? 5);
    $allow_backorder = isset($_POST['allow_backorder']) ? 1 : 0;

    $image = $product['image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $uploaded = handleUpload($_FILES['image']);
        if ($uploaded) $image = $uploaded;
    }

    // Handle digital download file upload
    $download_file = $product['download_file'] ?? '';
    if ($product_type === 'digital' && !empty($_FILES['download_file']['name'])) {
        $uploadedFile = handleDownloadUpload($_FILES['download_file']);
        if ($uploadedFile) $download_file = $uploadedFile;
    }

    if ($name && $category_id) {
        if ($id && $product) {
            $stmt = $db->prepare('UPDATE products SET name=?, slug=?, category_id=?, description=?, image=?, price=?, unit=?, specifications=?, features=?, price_note=?, product_type=?, download_file=?, download_limit=?, download_expiry_hours=?, is_visible=?, is_available=?, sort_order=?, track_inventory=?, stock_quantity=?, low_stock_threshold=?, allow_backorder=? WHERE id=?');
            $stmt->execute([$name, $slug, $category_id, $description, $image, $price, $unit, $specifications, $features, $price_note, $product_type, $download_file, $download_limit, $download_expiry_hours, $is_visible, $is_available, $sort_order, $track_inventory, $stock_quantity, $low_stock_threshold, $allow_backorder, $id]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Product updated!'];
        } else {
            $stmt = $db->prepare('INSERT INTO products (name, slug, category_id, description, image, price, unit, specifications, features, price_note, product_type, download_file, download_limit, download_expiry_hours, is_visible, is_available, sort_order, track_inventory, stock_quantity, low_stock_threshold, allow_backorder) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$name, $slug, $category_id, $description, $image, $price, $unit, $specifications, $features, $price_note, $product_type, $download_file, $download_limit, $download_expiry_hours, $is_visible, $is_available, $sort_order, $track_inventory, $stock_quantity, $low_stock_threshold, $allow_backorder]);
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
                <div class="form-row">
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
                        <label>Product Type <span class="required">*</span></label>
                        <select name="product_type" class="form-control" id="productType">
                            <option value="physical" <?php echo ($product['product_type'] ?? 'physical') === 'physical' ? 'selected' : ''; ?>>Physical Product</option>
                            <option value="digital" <?php echo ($product['product_type'] ?? '') === 'digital' ? 'selected' : ''; ?>>Digital Download</option>
                            <option value="service" <?php echo ($product['product_type'] ?? '') === 'service' ? 'selected' : ''; ?>>Service</option>
                        </select>
                        <small class="form-help">Physical: shipped/delivered. Digital: downloadable file. Service: no physical item.</small>
                    </div>
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

            <!-- Digital Download Section -->
            <div class="form-section" id="digitalSection" style="<?php echo ($product['product_type'] ?? 'physical') !== 'digital' ? 'display:none;' : ''; ?>">
                <h3><i class="fas fa-file-download"></i> Digital Download Settings</h3>
                <div class="form-group">
                    <label>Download File</label>
                    <?php if (!empty($product['download_file'])): ?>
                        <div class="current-file">
                            <i class="fas fa-file"></i>
                            <span><?php echo e(basename($product['download_file'])); ?></span>
                        </div>
                    <?php endif; ?>
                    <input type="file" name="download_file" class="form-control">
                    <small class="form-help">Upload the file customers will download after purchase. Supports PDF, ZIP, MP3, MP4, and many more formats (max 500MB).</small>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Download Limit</label>
                        <input type="number" name="download_limit" class="form-control" value="<?php echo e($product['download_limit'] ?? '0'); ?>" min="0">
                        <small class="form-help">Max downloads per purchase. 0 = unlimited.</small>
                    </div>
                    <div class="form-group">
                        <label>Link Expiry (hours)</label>
                        <input type="number" name="download_expiry_hours" class="form-control" value="<?php echo e($product['download_expiry_hours'] ?? '72'); ?>" min="1">
                        <small class="form-help">Hours until download link expires after purchase.</small>
                    </div>
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

            <div class="form-section">
                <h4><i class="fas fa-warehouse"></i> Inventory</h4>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="track_inventory" value="1" id="trackInventory" <?php echo ($product['track_inventory'] ?? 0) ? 'checked' : ''; ?>>
                        Track inventory
                    </label>
                    <small class="form-help">Enable stock quantity tracking for this product.</small>
                </div>
                <div id="inventoryFields" style="<?php echo ($product['track_inventory'] ?? 0) ? '' : 'display:none;'; ?>">
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock_quantity" class="form-control" value="<?php echo e($product['stock_quantity'] ?? '0'); ?>" min="0">
                    </div>
                    <div class="form-group">
                        <label>Low Stock Alert</label>
                        <input type="number" name="low_stock_threshold" class="form-control" value="<?php echo e($product['low_stock_threshold'] ?? '5'); ?>" min="0">
                        <small class="form-help">Alert when stock falls below this.</small>
                    </div>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="allow_backorder" value="1" <?php echo ($product['allow_backorder'] ?? 0) ? 'checked' : ''; ?>>
                            Allow backorders
                        </label>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-save" style="width:100%;"><i class="fas fa-save"></i> Save Product</button>
        </div>
    </div>
</form>

<script>
document.getElementById('productType').addEventListener('change', function() {
    document.getElementById('digitalSection').style.display = this.value === 'digital' ? '' : 'none';
});
document.getElementById('trackInventory').addEventListener('change', function() {
    document.getElementById('inventoryFields').style.display = this.checked ? '' : 'none';
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
