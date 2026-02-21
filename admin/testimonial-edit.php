<?php
/**
 * Admin — Testimonial Editor
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_testimonials');

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$testimonial = null;

if ($id) {
    $stmt = $db->prepare('SELECT * FROM testimonials WHERE id = ?');
    $stmt->execute([$id]);
    $testimonial = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $quote = trim($_POST['quote'] ?? '');
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    $customer_photo = $testimonial['customer_photo'] ?? '';
    if (!empty($_FILES['customer_photo']['name'])) {
        $uploaded = handleUpload($_FILES['customer_photo']);
        if ($uploaded) $customer_photo = $uploaded;
    }

    if ($customer_name && $quote) {
        if ($id && $testimonial) {
            $stmt = $db->prepare('UPDATE testimonials SET customer_name=?, quote=?, customer_photo=?, is_visible=?, sort_order=? WHERE id=?');
            $stmt->execute([$customer_name, $quote, $customer_photo, $is_visible, $sort_order, $id]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Testimonial updated!'];
        } else {
            $stmt = $db->prepare('INSERT INTO testimonials (customer_name, quote, customer_photo, is_visible, sort_order) VALUES (?,?,?,?,?)');
            $stmt->execute([$customer_name, $quote, $customer_photo, $is_visible, $sort_order]);
            $id = $db->lastInsertId();
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Testimonial created!'];
        }
        redirect('admin/testimonial-edit.php?id=' . $id);
    }
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-quote-right"></i> <?php echo $testimonial ? 'Edit Testimonial' : 'Add Testimonial'; ?></h1>
    <a href="<?php echo url('admin/testimonials.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Testimonials</a>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form" style="max-width: 700px;">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

    <div class="form-section">
        <div class="form-group">
            <label>Customer Name <span class="required">*</span></label>
            <input type="text" name="customer_name" class="form-control" value="<?php echo e($testimonial['customer_name'] ?? ''); ?>" required placeholder="e.g., John D.">
        </div>
        <div class="form-group">
            <label>Quote <span class="required">*</span></label>
            <textarea name="quote" class="form-control" rows="4" required><?php echo e($testimonial['quote'] ?? ''); ?></textarea>
        </div>
        <div class="form-group">
            <label>Customer Photo (optional)</label>
            <?php if (!empty($testimonial['customer_photo'])): ?>
                <div class="current-image"><img src="<?php echo e($testimonial['customer_photo']); ?>" alt="" style="max-height: 80px; border-radius: 50%;"></div>
            <?php endif; ?>
            <input type="file" name="customer_photo" class="form-control" accept="image/*">
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_visible" value="1" <?php echo (!$testimonial || $testimonial['is_visible']) ? 'checked' : ''; ?>>
                    Visible
                </label>
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" name="sort_order" class="form-control" value="<?php echo e($testimonial['sort_order'] ?? '0'); ?>">
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Testimonial</button>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
