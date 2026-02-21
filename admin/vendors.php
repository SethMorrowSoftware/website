<?php
/**
 * Admin — Vendor / Marketplace Management
 *
 * Manage vendors, commissions, payouts, and marketplace settings.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_settings');

$db = getDB();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        setSetting('marketplace_enabled', isset($_POST['marketplace_enabled']) ? '1' : '0');
        setSetting('marketplace_commission_rate', max(0, min(100, (float)$_POST['marketplace_commission_rate'])));
        setSetting('marketplace_auto_approve_vendors', isset($_POST['marketplace_auto_approve_vendors']) ? '1' : '0');
        setSetting('marketplace_vendor_registration', isset($_POST['marketplace_vendor_registration']) ? '1' : '0');
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Marketplace settings saved!'];
        redirect('admin/vendors.php');
    }

    if ($action === 'update_status') {
        $vendorId = (int)$_POST['vendor_id'];
        $status = $_POST['status'] ?? '';
        if (updateVendorStatus($vendorId, $status)) {
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Vendor status updated.'];
        }
        redirect('admin/vendors.php');
    }

    if ($action === 'update_commission') {
        $vendorId = (int)$_POST['vendor_id'];
        $rate = $_POST['commission_rate'] !== '' ? max(0, min(100, (float)$_POST['commission_rate'])) : null;
        updateVendor($vendorId, ['commission_rate' => $rate]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Commission rate updated.'];
        redirect('admin/vendors.php');
    }

    if ($action === 'create_payout') {
        $vendorId = (int)$_POST['vendor_id'];
        $amount = (float)$_POST['amount'];
        $reference = trim($_POST['reference'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($amount > 0) {
            $payoutId = createVendorPayout($vendorId, $amount, 'manual', $reference, $notes);
            if ($payoutId) {
                completeVendorPayout($payoutId);
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Payout of ' . formatCurrency($amount) . ' recorded.'];
            } else {
                $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Payout could not be recorded. Please verify vendor balance and try again.'];
            }
        } else {
            $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Payout amount must be greater than zero.'];
        }
        redirect('admin/vendors.php');
    }
}

$vendors = getAllVendors();
$stats = getMarketplaceStats();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-store"></i> Marketplace / Vendors</h1>
</div>

<!-- Marketplace Stats -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin-bottom: var(--space-xl);">
    <div class="admin-card" style="padding: 1.25rem; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: var(--color-primary);"><?php echo $stats['total_vendors']; ?></div>
        <div style="color: var(--color-gray-500); font-size: 0.875rem;">Total Vendors</div>
    </div>
    <div class="admin-card" style="padding: 1.25rem; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: #22c55e;"><?php echo $stats['active_vendors']; ?></div>
        <div style="color: var(--color-gray-500); font-size: 0.875rem;">Active</div>
    </div>
    <div class="admin-card" style="padding: 1.25rem; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: #f59e0b;"><?php echo $stats['pending_vendors']; ?></div>
        <div style="color: var(--color-gray-500); font-size: 0.875rem;">Pending Approval</div>
    </div>
    <div class="admin-card" style="padding: 1.25rem; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: var(--color-primary);"><?php echo formatCurrency($stats['total_commission']); ?></div>
        <div style="color: var(--color-gray-500); font-size: 0.875rem;">Total Commission</div>
    </div>
    <div class="admin-card" style="padding: 1.25rem; text-align: center;">
        <div style="font-size: 2rem; font-weight: 700; color: #8b5cf6;"><?php echo formatCurrency($stats['total_payouts']); ?></div>
        <div style="color: var(--color-gray-500); font-size: 0.875rem;">Total Payouts</div>
    </div>
</div>

<!-- Settings -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-cog"></i> Marketplace Settings</h2>
    <div class="admin-card" style="margin-top: var(--space-md); padding: var(--space-lg);">
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="marketplace_enabled" value="1" <?php echo isMarketplaceEnabled() ? 'checked' : ''; ?>>
                        <strong>Enable Marketplace Mode</strong>
                    </label>
                    <small class="form-help">Allow multiple vendors to sell products on your site.</small>
                </div>
                <div class="form-group">
                    <label>Default Commission Rate (%)</label>
                    <input type="number" name="marketplace_commission_rate" class="form-control" value="<?php echo e(getSetting('marketplace_commission_rate', '15')); ?>" min="0" max="100" step="0.5">
                    <small class="form-help">Percentage of each sale kept by the marketplace.</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="marketplace_vendor_registration" value="1" <?php echo getSetting('marketplace_vendor_registration', '1') === '1' ? 'checked' : ''; ?>>
                        <strong>Allow Vendor Self-Registration</strong>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="marketplace_auto_approve_vendors" value="1" <?php echo getSetting('marketplace_auto_approve_vendors', '0') === '1' ? 'checked' : ''; ?>>
                        <strong>Auto-Approve New Vendors</strong>
                    </label>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>

<!-- Vendor List -->
<div class="admin-section">
    <h2><i class="fas fa-users"></i> Vendors</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Store</th>
                    <th>Status</th>
                    <th>Products</th>
                    <th>Sales</th>
                    <th>Commission Rate</th>
                    <th>Balance</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vendors as $v): ?>
                <tr>
                    <td>
                        <strong><?php echo e($v['store_name']); ?></strong>
                        <br><small style="color: var(--color-gray-400);">/vendor/<?php echo e($v['slug']); ?></small>
                    </td>
                    <td>
                        <span class="badge-status <?php
                            echo match($v['status']) {
                                'active' => 'badge-active',
                                'pending' => 'badge-pending',
                                'suspended' => 'badge-inactive',
                                default => 'badge-inactive',
                            };
                        ?>"><?php echo ucfirst($v['status']); ?></span>
                    </td>
                    <td><?php echo (int)($v['product_count'] ?? 0); ?></td>
                    <td><?php echo formatCurrency($v['total_sales']); ?></td>
                    <td>
                        <form method="POST" style="display: flex; gap: 0.25rem; align-items: center;">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="action" value="update_commission">
                            <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                            <input type="number" name="commission_rate" class="form-control" style="width:70px; padding:4px 6px; font-size:0.85rem;"
                                   value="<?php echo $v['commission_rate'] !== null ? e($v['commission_rate']) : ''; ?>"
                                   placeholder="<?php echo e(getSetting('marketplace_commission_rate', '15')); ?>"
                                   min="0" max="100" step="0.5">
                            <button type="submit" class="btn-admin btn-sm btn-outline" title="Save">%</button>
                        </form>
                    </td>
                    <td><strong><?php echo formatCurrency(getVendorBalance($v['id'])); ?></strong></td>
                    <td style="white-space:nowrap;">
                        <!-- Status actions -->
                        <?php if ($v['status'] === 'pending'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="btn-admin btn-sm btn-save" title="Approve"><i class="fas fa-check"></i></button>
                            </form>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="status" value="rejected">
                                <button type="submit" class="btn-admin btn-sm btn-delete" title="Reject"><i class="fas fa-times"></i></button>
                            </form>
                        <?php elseif ($v['status'] === 'active'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="status" value="suspended">
                                <button type="submit" class="btn-admin btn-sm btn-outline" title="Suspend"><i class="fas fa-ban"></i></button>
                            </form>
                        <?php elseif ($v['status'] === 'suspended'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="status" value="active">
                                <button type="submit" class="btn-admin btn-sm btn-save" title="Reactivate"><i class="fas fa-redo"></i></button>
                            </form>
                        <?php endif; ?>

                        <!-- Payout button -->
                        <?php $balance = getVendorBalance($v['id']); if ($balance > 0): ?>
                            <button type="button" class="btn-admin btn-sm btn-outline" title="Record Payout"
                                    onclick="document.getElementById('payout-<?php echo $v['id']; ?>').style.display = document.getElementById('payout-<?php echo $v['id']; ?>').style.display === 'none' ? 'table-row' : 'none';">
                                <i class="fas fa-dollar-sign"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($balance > 0): ?>
                <tr id="payout-<?php echo $v['id']; ?>" style="display:none; background: #f8f9fa;">
                    <td colspan="7">
                        <form method="POST" style="display:flex; gap:0.5rem; align-items:center; padding:0.5rem 0;">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="action" value="create_payout">
                            <input type="hidden" name="vendor_id" value="<?php echo $v['id']; ?>">
                            <strong>Payout:</strong>
                            <input type="number" name="amount" class="form-control" style="width:120px; padding:6px 8px;" step="0.01" max="<?php echo $balance; ?>" value="<?php echo $balance; ?>" required>
                            <input type="text" name="reference" class="form-control" style="width:160px; padding:6px 8px;" placeholder="Reference/txn #">
                            <input type="text" name="notes" class="form-control" style="width:200px; padding:6px 8px;" placeholder="Notes">
                            <button type="submit" class="btn-admin btn-sm btn-save"><i class="fas fa-check"></i> Record</button>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>

                <?php if (empty($vendors)): ?>
                <tr>
                    <td colspan="7" style="text-align:center; color:var(--color-gray-400); padding:2rem;">
                        No vendors yet. <?php echo isMarketplaceEnabled() ? 'Vendors can register through the storefront.' : 'Enable Marketplace Mode to get started.'; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
