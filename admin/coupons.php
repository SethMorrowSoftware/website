<?php
/**
 * Admin — Coupon Management
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $postAction = $_POST['post_action'] ?? '';

    if ($postAction === 'delete') {
        $deleteId = (int)($_POST['delete_id'] ?? 0);
        if ($deleteId) {
            $db->prepare('DELETE FROM coupons WHERE id = ?')->execute([$deleteId]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Coupon deleted.'];
        }
        redirect('admin/coupons.php');
    }

    if ($postAction === 'toggle') {
        $toggleId = (int)($_POST['toggle_id'] ?? 0);
        if ($toggleId) {
            $db->prepare('UPDATE coupons SET is_active = 1 - is_active WHERE id = ?')->execute([$toggleId]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Coupon status updated.'];
        }
        redirect('admin/coupons.php');
    }

    if ($postAction === 'create' || $postAction === 'update') {
        $couponId = (int)($_POST['coupon_id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $type = in_array($_POST['type'] ?? '', ['percentage', 'fixed', 'free_shipping']) ? $_POST['type'] : 'percentage';
        $value = (float)($_POST['value'] ?? 0);
        $minimumOrder = (float)($_POST['minimum_order'] ?? 0);
        $maximumDiscount = (float)($_POST['maximum_discount'] ?? 0);
        $usageLimit = (int)($_POST['usage_limit'] ?? 0);
        $validFrom = $_POST['valid_from'] ?? null;
        $validUntil = $_POST['valid_until'] ?? null;
        $appliesTo = in_array($_POST['applies_to'] ?? '', ['all', 'specific_products', 'specific_categories']) ? $_POST['applies_to'] : 'all';
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        if ($code && $value >= 0) {
            if ($postAction === 'update' && $couponId) {
                $stmt = $db->prepare('UPDATE coupons SET code=?, type=?, value=?, minimum_order=?, maximum_discount=?, usage_limit=?, valid_from=?, valid_until=?, applies_to=?, is_active=? WHERE id=?');
                $stmt->execute([$code, $type, $value, $minimumOrder, $maximumDiscount, $usageLimit, $validFrom ?: null, $validUntil ?: null, $appliesTo, $isActive, $couponId]);
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Coupon updated!'];
            } else {
                $stmt = $db->prepare('INSERT INTO coupons (code, type, value, minimum_order, maximum_discount, usage_limit, valid_from, valid_until, applies_to, is_active) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $stmt->execute([$code, $type, $value, $minimumOrder, $maximumDiscount, $usageLimit, $validFrom ?: null, $validUntil ?: null, $appliesTo, $isActive]);
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Coupon created!'];
            }
        } else {
            $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Code and value are required.'];
        }
        redirect('admin/coupons.php');
    }
}

$coupons = getAllCoupons();
$editCoupon = null;
if (isset($_GET['edit'])) {
    $editCoupon = getCoupon((int)$_GET['edit']);
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-ticket-alt"></i> Coupons & Discounts</h1>
</div>

<!-- Create / Edit Form -->
<div class="admin-card" style="margin-bottom: var(--space-xl);">
    <h3><?php echo $editCoupon ? 'Edit Coupon' : 'Create New Coupon'; ?></h3>
    <form method="POST" class="admin-form" style="margin-top: var(--space-md);">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
        <input type="hidden" name="post_action" value="<?php echo $editCoupon ? 'update' : 'create'; ?>">
        <?php if ($editCoupon): ?>
            <input type="hidden" name="coupon_id" value="<?php echo $editCoupon['id']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Coupon Code <span class="required">*</span></label>
                <input type="text" name="code" class="form-control" value="<?php echo e($editCoupon['code'] ?? ''); ?>" required placeholder="e.g. SAVE20" style="text-transform: uppercase;">
            </div>
            <div class="form-group">
                <label>Discount Type</label>
                <select name="type" class="form-control">
                    <option value="percentage" <?php echo ($editCoupon['type'] ?? '') === 'percentage' ? 'selected' : ''; ?>>Percentage (%)</option>
                    <option value="fixed" <?php echo ($editCoupon['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Fixed Amount ($)</option>
                    <option value="free_shipping" <?php echo ($editCoupon['type'] ?? '') === 'free_shipping' ? 'selected' : ''; ?>>Free Shipping</option>
                </select>
            </div>
            <div class="form-group">
                <label>Value</label>
                <input type="number" name="value" class="form-control" value="<?php echo e($editCoupon['value'] ?? '0'); ?>" min="0" step="0.01" placeholder="e.g. 20">
                <small class="form-help">For percentage: enter 20 for 20%. For fixed: enter dollar amount.</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Minimum Order</label>
                <input type="number" name="minimum_order" class="form-control" value="<?php echo e($editCoupon['minimum_order'] ?? '0'); ?>" min="0" step="0.01">
                <small class="form-help">0 = no minimum</small>
            </div>
            <div class="form-group">
                <label>Max Discount</label>
                <input type="number" name="maximum_discount" class="form-control" value="<?php echo e($editCoupon['maximum_discount'] ?? '0'); ?>" min="0" step="0.01">
                <small class="form-help">0 = no cap</small>
            </div>
            <div class="form-group">
                <label>Usage Limit</label>
                <input type="number" name="usage_limit" class="form-control" value="<?php echo e($editCoupon['usage_limit'] ?? '0'); ?>" min="0">
                <small class="form-help">0 = unlimited</small>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Valid From</label>
                <input type="datetime-local" name="valid_from" class="form-control" value="<?php echo $editCoupon && $editCoupon['valid_from'] ? date('Y-m-d\TH:i', strtotime($editCoupon['valid_from'])) : ''; ?>">
            </div>
            <div class="form-group">
                <label>Valid Until</label>
                <input type="datetime-local" name="valid_until" class="form-control" value="<?php echo $editCoupon && $editCoupon['valid_until'] ? date('Y-m-d\TH:i', strtotime($editCoupon['valid_until'])) : ''; ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Applies To</label>
                <select name="applies_to" class="form-control">
                    <option value="all" <?php echo ($editCoupon['applies_to'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Products</option>
                    <option value="specific_categories" <?php echo ($editCoupon['applies_to'] ?? '') === 'specific_categories' ? 'selected' : ''; ?>>Specific Categories</option>
                    <option value="specific_products" <?php echo ($editCoupon['applies_to'] ?? '') === 'specific_products' ? 'selected' : ''; ?>>Specific Products</option>
                </select>
            </div>
            <div class="form-group" style="display:flex; align-items:end; padding-bottom: 4px;">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?php echo (!$editCoupon || $editCoupon['is_active']) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>
        </div>

        <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> <?php echo $editCoupon ? 'Update Coupon' : 'Create Coupon'; ?></button>
        <?php if ($editCoupon): ?>
            <a href="<?php echo url('admin/coupons.php'); ?>" class="btn-admin btn-back" style="margin-left: var(--space-sm);">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<!-- Coupons List -->
<?php if (!empty($coupons)): ?>
<div class="admin-card">
    <h3>All Coupons</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Min Order</th>
                    <th>Usage</th>
                    <th>Valid Until</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $c): ?>
                    <tr>
                        <td><strong><?php echo e($c['code']); ?></strong></td>
                        <td><?php echo e(ucfirst($c['type'])); ?></td>
                        <td>
                            <?php if ($c['type'] === 'percentage'): ?>
                                <?php echo e($c['value']); ?>%
                            <?php elseif ($c['type'] === 'fixed'): ?>
                                <?php echo formatCurrency($c['value']); ?>
                            <?php else: ?>
                                Free Shipping
                            <?php endif; ?>
                        </td>
                        <td><?php echo $c['minimum_order'] > 0 ? formatCurrency($c['minimum_order']) : '—'; ?></td>
                        <td><?php echo $c['used_count']; ?><?php echo $c['usage_limit'] > 0 ? ' / ' . $c['usage_limit'] : ''; ?></td>
                        <td><?php echo $c['valid_until'] ? date('M j, Y', strtotime($c['valid_until'])) : 'No expiry'; ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $c['is_active'] ? 'completed' : 'cancelled'; ?>">
                                <?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="action-buttons">
                            <a href="<?php echo url('admin/coupons.php?edit=' . $c['id']); ?>" class="btn-admin btn-sm btn-edit"><i class="fas fa-edit"></i></a>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Toggle this coupon?');">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="post_action" value="toggle">
                                <input type="hidden" name="toggle_id" value="<?php echo $c['id']; ?>">
                                <button type="submit" class="btn-admin btn-sm btn-<?php echo $c['is_active'] ? 'warning' : 'edit'; ?>">
                                    <i class="fas fa-<?php echo $c['is_active'] ? 'pause' : 'play'; ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this coupon?');">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="post_action" value="delete">
                                <input type="hidden" name="delete_id" value="<?php echo $c['id']; ?>">
                                <button type="submit" class="btn-admin btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php else: ?>
    <div class="empty-state" style="text-align:center; padding: var(--space-xxl);">
        <i class="fas fa-ticket-alt" style="font-size: 3rem; color: var(--color-gray-400);"></i>
        <h3 style="margin-top: var(--space-md);">No coupons yet</h3>
        <p>Create your first discount coupon using the form above.</p>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
