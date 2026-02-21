<?php
/**
 * Admin — Shipping Zones & Methods
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_shipping');

$db = getDB();
$csrfToken = generateCSRFToken();
$message = '';
$messageType = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $postAction = $_POST['post_action'] ?? '';

    // Save shipping zone
    if ($postAction === 'save_zone') {
        $zoneData = [
            'id' => (int)($_POST['zone_id'] ?? 0) ?: null,
            'name' => trim($_POST['zone_name'] ?? ''),
            'countries' => trim($_POST['countries'] ?? ''),
            'states' => trim($_POST['states'] ?? ''),
            'is_default' => isset($_POST['is_default']) ? 1 : 0,
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($zoneData['name']) {
            saveShippingZone($zoneData);
            logAudit('shipping_zone_saved', 'shipping_zone', $zoneData['id'] ?? 0, ['name' => $zoneData['name']]);
            $message = 'Shipping zone saved.';
            $messageType = 'success';
        }
    }

    // Save shipping method
    if ($postAction === 'save_method') {
        $methodData = [
            'id' => (int)($_POST['method_id'] ?? 0) ?: null,
            'zone_id' => (int)($_POST['zone_id'] ?? 0),
            'name' => trim($_POST['method_name'] ?? ''),
            'type' => $_POST['method_type'] ?? 'flat_rate',
            'cost' => (float)($_POST['cost'] ?? 0),
            'free_threshold' => (float)($_POST['free_threshold'] ?? 0),
            'estimated_days' => trim($_POST['estimated_days'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($methodData['name'] && $methodData['zone_id']) {
            saveShippingMethod($methodData);
            logAudit('shipping_method_saved', 'shipping_method', $methodData['id'] ?? 0, ['name' => $methodData['name']]);
            $message = 'Shipping method saved.';
            $messageType = 'success';
        }
    }

    // Delete method
    if ($postAction === 'delete_method') {
        $methodId = (int)($_POST['method_id'] ?? 0);
        if ($methodId) {
            deleteShippingMethod($methodId);
            logAudit('shipping_method_deleted', 'shipping_method', $methodId);
            $message = 'Shipping method deleted.';
            $messageType = 'success';
        }
    }

    // Delete zone
    if ($postAction === 'delete_zone') {
        $zoneId = (int)($_POST['zone_id'] ?? 0);
        if ($zoneId) {
            deleteShippingZone($zoneId);
            logAudit('shipping_zone_deleted', 'shipping_zone', $zoneId);
            $message = 'Shipping zone and its methods deleted.';
            $messageType = 'success';
        }
    }
}

$zones = getAllShippingZones();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-shipping-fast"></i> Shipping</h1>
    <p>Configure shipping zones and methods for your store.</p>
</div>

<?php if ($message): ?>
    <div class="flash-message <?php echo e($messageType); ?>" role="alert">
        <i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
        <span class="flash-text"><?php echo e($message); ?></span>
    </div>
<?php endif; ?>

<!-- Add New Zone -->
<div class="admin-section">
    <h2><i class="fas fa-plus"></i> Add Shipping Zone</h2>
    <div class="admin-card">
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="post_action" value="save_zone">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Zone Name <span class="required">*</span></label>
                    <input type="text" name="zone_name" class="form-control" required placeholder="e.g. Domestic, International">
                </div>
                <div class="form-group">
                    <label>Countries (comma-separated)</label>
                    <input type="text" name="countries" class="form-control" placeholder="US, CA, MX">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>States/Provinces (comma-separated)</label>
                    <input type="text" name="states" class="form-control" placeholder="NY, CA, TX">
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:var(--space-md);padding-top:24px;">
                    <label class="toggle-switch" style="margin:0;">
                        <input type="checkbox" name="is_default" value="1">
                        <span class="toggle-slider"></span>
                    </label>
                    <span>Default Zone</span>
                    <label class="toggle-switch" style="margin:0;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    <span>Active</span>
                </div>
            </div>
            <button type="submit" class="btn-admin btn-primary"><i class="fas fa-plus"></i> Create Zone</button>
        </form>
    </div>
</div>

<!-- Existing Zones & Methods -->
<?php foreach ($zones as $zone): ?>
<div class="admin-section">
    <div class="section-head">
        <h2>
            <i class="fas fa-globe<?php echo $zone['is_default'] ? '' : '-americas'; ?>"></i>
            <?php echo e($zone['name']); ?>
            <?php if ($zone['is_default']): ?><span class="badge-status badge-active">Default</span><?php endif; ?>
            <?php if (!$zone['is_active']): ?><span class="badge-status badge-inactive">Inactive</span><?php endif; ?>
        </h2>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this zone and all its methods?');">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="post_action" value="delete_zone">
            <input type="hidden" name="zone_id" value="<?php echo $zone['id']; ?>">
            <button type="submit" class="btn-admin btn-sm btn-delete"><i class="fas fa-trash"></i> Delete Zone</button>
        </form>
    </div>

    <?php if ($zone['countries'] || $zone['states']): ?>
        <p style="color:var(--color-gray-500);margin-bottom:var(--space-md);">
            <?php if ($zone['countries']): ?>Countries: <?php echo e($zone['countries']); ?><?php endif; ?>
            <?php if ($zone['states']): ?> &middot; States: <?php echo e($zone['states']); ?><?php endif; ?>
        </p>
    <?php endif; ?>

    <!-- Methods for this zone -->
    <?php $methods = getAllShippingMethods($zone['id']); ?>
    <?php if (!empty($methods)): ?>
        <div class="admin-card" style="margin-bottom:var(--space-md);">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Method</th>
                        <th>Type</th>
                        <th>Cost</th>
                        <th>Free Over</th>
                        <th>Est. Days</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($methods as $method): ?>
                    <tr>
                        <td><strong><?php echo e($method['name']); ?></strong></td>
                        <td><?php echo e(ucfirst(str_replace('_', ' ', $method['type']))); ?></td>
                        <td><?php echo $method['type'] === 'free' ? 'Free' : formatCurrency($method['cost']); ?></td>
                        <td><?php echo $method['free_threshold'] > 0 ? formatCurrency($method['free_threshold']) : '—'; ?></td>
                        <td><?php echo e($method['estimated_days'] ?: '—'); ?></td>
                        <td>
                            <span class="badge-status badge-<?php echo $method['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $method['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this method?');">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="post_action" value="delete_method">
                                <input type="hidden" name="method_id" value="<?php echo $method['id']; ?>">
                                <button type="submit" class="btn-admin btn-sm btn-delete"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Add Method to this Zone -->
    <div class="admin-card">
        <h4 style="margin-bottom:var(--space-md);"><i class="fas fa-plus"></i> Add Method to <?php echo e($zone['name']); ?></h4>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="post_action" value="save_method">
            <input type="hidden" name="zone_id" value="<?php echo $zone['id']; ?>">
            <div class="form-row-2">
                <div class="form-group">
                    <label>Method Name <span class="required">*</span></label>
                    <input type="text" name="method_name" class="form-control" required placeholder="e.g. Standard Shipping, Express">
                </div>
                <div class="form-group">
                    <label>Type</label>
                    <select name="method_type" class="form-control">
                        <option value="flat_rate">Flat Rate</option>
                        <option value="free">Free Shipping</option>
                    </select>
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Cost (<?php echo e(getSetting('currency_symbol', '$')); ?>)</label>
                    <input type="number" name="cost" class="form-control" step="0.01" min="0" value="0" placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Free Shipping Threshold</label>
                    <input type="number" name="free_threshold" class="form-control" step="0.01" min="0" value="0" placeholder="Orders over this amount ship free">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label>Estimated Delivery</label>
                    <input type="text" name="estimated_days" class="form-control" placeholder="e.g. 5-7 business days">
                </div>
                <div class="form-group" style="display:flex;align-items:center;gap:var(--space-md);padding-top:24px;">
                    <label class="toggle-switch" style="margin:0;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span class="toggle-slider"></span>
                    </label>
                    <span>Active</span>
                </div>
            </div>
            <button type="submit" class="btn-admin btn-primary"><i class="fas fa-plus"></i> Add Method</button>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php if (empty($zones)): ?>
<div class="admin-section">
    <p class="empty-state">No shipping zones configured. Create a zone above to get started.</p>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
