<?php
/**
 * Admin — Subscription Management
 *
 * Manage subscription plans, view active subscriptions,
 * and handle billing operations.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/subscriptions.php';

requireLogin();
requirePermission('manage_orders');

$db = getDB();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'create_plan') {
        createSubscriptionPlan([
            'product_id' => $_POST['product_id'] ?? null,
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'price' => (float)$_POST['price'],
            'billing_interval' => $_POST['billing_interval'] ?? 'monthly',
            'interval_count' => (int)($_POST['interval_count'] ?? 1),
            'trial_days' => (int)($_POST['trial_days'] ?? 0),
            'setup_fee' => (float)($_POST['setup_fee'] ?? 0),
            'is_active' => isset($_POST['is_active']),
        ]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Plan created!'];
        redirect('admin/subscriptions.php');
    }

    if ($action === 'update_plan') {
        updateSubscriptionPlan((int)$_POST['plan_id'], [
            'name' => trim($_POST['name']),
            'description' => trim($_POST['description'] ?? ''),
            'price' => (float)$_POST['price'],
            'billing_interval' => $_POST['billing_interval'] ?? 'monthly',
            'interval_count' => (int)($_POST['interval_count'] ?? 1),
            'trial_days' => (int)($_POST['trial_days'] ?? 0),
            'setup_fee' => (float)($_POST['setup_fee'] ?? 0),
            'is_active' => isset($_POST['is_active']),
        ]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Plan updated!'];
        redirect('admin/subscriptions.php');
    }

    if ($action === 'pause_subscription') {
        pauseSubscription((int)$_POST['sub_id']);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Subscription paused.'];
        redirect('admin/subscriptions.php');
    }

    if ($action === 'resume_subscription') {
        resumeSubscription((int)$_POST['sub_id']);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Subscription resumed.'];
        redirect('admin/subscriptions.php');
    }

    if ($action === 'cancel_subscription') {
        cancelSubscription((int)$_POST['sub_id'], trim($_POST['cancel_reason'] ?? ''));
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Subscription cancelled.'];
        redirect('admin/subscriptions.php');
    }

    if ($action === 'process_renewals') {
        $results = processSubscriptionRenewals();
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "Processed {$results['processed']} subscriptions: {$results['renewed']} renewed, {$results['failed']} failed."];
        redirect('admin/subscriptions.php');
    }
}

// Data
$stats = getSubscriptionStats();
$plans = getSubscriptionPlans(false);
$statusFilter = $_GET['status'] ?? null;
$subscriptions = getAllSubscriptions($statusFilter);
$products = $db->query("SELECT id, name FROM products WHERE deleted_at IS NULL ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$editPlan = null;
if (isset($_GET['edit_plan'])) {
    $editPlan = getSubscriptionPlan((int)$_GET['edit_plan']);
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-sync-alt"></i> Subscriptions</h1>
    <form method="POST" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
        <input type="hidden" name="action" value="process_renewals">
        <button type="submit" class="btn-admin btn-save"><i class="fas fa-play"></i> Process Renewals</button>
    </form>
</div>

<!-- Stats -->
<div class="stats-cards" style="margin-bottom: var(--space-xl);">
    <div class="stat-card">
        <div class="stat-icon bg-success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $stats['active']; ?></div>
            <div class="stat-label">Active</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-info"><i class="fas fa-hourglass-half"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $stats['trialing']; ?></div>
            <div class="stat-label">Trialing</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo $stats['past_due']; ?></div>
            <div class="stat-label">Past Due</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon bg-primary"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <div class="stat-number"><?php echo formatCurrency($stats['mrr']); ?></div>
            <div class="stat-label">Monthly Recurring Revenue</div>
        </div>
    </div>
</div>

<!-- Plans Management -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-list"></i> Subscription Plans</h2>

    <div class="admin-card" style="margin-top: var(--space-md); margin-bottom: var(--space-md);">
        <h4 style="margin-bottom: 0.75rem;"><?php echo $editPlan ? 'Edit Plan' : 'Create Plan'; ?></h4>
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="<?php echo $editPlan ? 'update_plan' : 'create_plan'; ?>">
            <?php if ($editPlan): ?>
                <input type="hidden" name="plan_id" value="<?php echo $editPlan['id']; ?>">
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group">
                    <label>Plan Name *</label>
                    <input type="text" name="name" class="form-control" value="<?php echo e($editPlan['name'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label>Price *</label>
                    <input type="number" name="price" class="form-control" step="0.01" min="0.01" value="<?php echo e($editPlan['price'] ?? ''); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Billing Interval</label>
                    <select name="billing_interval" class="form-control">
                        <?php foreach (['weekly', 'monthly', 'quarterly', 'yearly'] as $int): ?>
                            <option value="<?php echo $int; ?>" <?php echo ($editPlan['billing_interval'] ?? 'monthly') === $int ? 'selected' : ''; ?>>
                                <?php echo ucfirst($int); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Linked Product (Optional)</label>
                    <select name="product_id" class="form-control">
                        <option value="">None</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($editPlan['product_id'] ?? '') == $p['id'] ? 'selected' : ''; ?>><?php echo e($p['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Trial Days</label>
                    <input type="number" name="trial_days" class="form-control" min="0" value="<?php echo e($editPlan['trial_days'] ?? '0'); ?>">
                </div>
                <div class="form-group">
                    <label>Setup Fee</label>
                    <input type="number" name="setup_fee" class="form-control" step="0.01" min="0" value="<?php echo e($editPlan['setup_fee'] ?? '0'); ?>">
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" class="form-control" rows="2"><?php echo e($editPlan['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" value="1" <?php echo (!$editPlan || $editPlan['is_active']) ? 'checked' : ''; ?>>
                    Active
                </label>
            </div>

            <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> <?php echo $editPlan ? 'Update Plan' : 'Create Plan'; ?></button>
            <?php if ($editPlan): ?>
                <a href="<?php echo url('admin/subscriptions.php'); ?>" class="btn-admin btn-outline">Cancel</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Plans list -->
    <div class="admin-card">
        <table class="admin-table">
            <thead><tr><th>Plan</th><th>Price</th><th>Interval</th><th>Trial</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($plans as $plan): ?>
                <tr>
                    <td>
                        <strong><?php echo e($plan['name']); ?></strong>
                        <?php if ($plan['product_name']): ?><br><small>Product: <?php echo e($plan['product_name']); ?></small><?php endif; ?>
                    </td>
                    <td><?php echo formatCurrency($plan['price']); ?><?php echo getBillingLabel($plan['billing_interval'], $plan['interval_count']); ?></td>
                    <td><?php echo ucfirst($plan['billing_interval']); ?></td>
                    <td><?php echo $plan['trial_days'] ? $plan['trial_days'] . ' days' : 'None'; ?></td>
                    <td><span class="badge-status <?php echo $plan['is_active'] ? 'badge-active' : 'badge-inactive'; ?>"><?php echo $plan['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                    <td><a href="<?php echo url('admin/subscriptions.php?edit_plan=' . $plan['id']); ?>" class="btn-admin btn-sm btn-edit"><i class="fas fa-edit"></i></a></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($plans)): ?>
                <tr><td colspan="6" style="text-align:center; color:var(--color-gray-400);">No plans created yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Active Subscriptions -->
<div class="admin-section">
    <h2><i class="fas fa-users"></i> Subscriptions</h2>
    <div style="margin: var(--space-md) 0; display: flex; gap: 0.35rem; flex-wrap: wrap;">
        <a href="<?php echo url('admin/subscriptions.php'); ?>" class="btn-admin btn-sm <?php echo !$statusFilter ? 'btn-save' : 'btn-outline'; ?>">All</a>
        <?php foreach (['active', 'trialing', 'paused', 'past_due', 'cancelled', 'expired'] as $st): ?>
            <a href="<?php echo url('admin/subscriptions.php?status=' . $st); ?>" class="btn-admin btn-sm <?php echo $statusFilter === $st ? 'btn-save' : 'btn-outline'; ?>"><?php echo ucfirst(str_replace('_', ' ', $st)); ?></a>
        <?php endforeach; ?>
    </div>

    <div class="admin-card">
        <table class="admin-table">
            <thead><tr><th>Customer</th><th>Plan</th><th>Status</th><th>Next Billing</th><th>Created</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($subscriptions as $sub):
                    $statusColors = ['active' => 'badge-active', 'trialing' => 'badge-unread', 'paused' => 'badge-read', 'past_due' => 'badge-unread', 'cancelled' => 'badge-inactive', 'expired' => 'badge-inactive'];
                ?>
                <tr>
                    <td><strong><?php echo e($sub['first_name'] . ' ' . $sub['last_name']); ?></strong><br><small><?php echo e($sub['customer_email']); ?></small></td>
                    <td><?php echo e($sub['plan_name']); ?> — <?php echo formatCurrency($sub['price']); ?><?php echo getBillingLabel($sub['billing_interval']); ?></td>
                    <td><span class="badge-status <?php echo $statusColors[$sub['status']] ?? ''; ?>"><?php echo ucfirst(str_replace('_', ' ', $sub['status'])); ?></span></td>
                    <td><?php echo $sub['next_billing_date'] ? formatDate($sub['next_billing_date']) : '—'; ?></td>
                    <td><?php echo formatDate($sub['created_at']); ?></td>
                    <td>
                        <?php if ($sub['status'] === 'active' || $sub['status'] === 'trialing'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="pause_subscription">
                                <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                                <button type="submit" class="btn-admin btn-sm btn-outline" title="Pause"><i class="fas fa-pause"></i></button>
                            </form>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this subscription?');">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="cancel_subscription">
                                <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                                <button type="submit" class="btn-admin btn-sm btn-delete" title="Cancel"><i class="fas fa-times"></i></button>
                            </form>
                        <?php elseif ($sub['status'] === 'paused'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                                <input type="hidden" name="action" value="resume_subscription">
                                <input type="hidden" name="sub_id" value="<?php echo $sub['id']; ?>">
                                <button type="submit" class="btn-admin btn-sm btn-save" title="Resume"><i class="fas fa-play"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($subscriptions)): ?>
                <tr><td colspan="6" style="text-align:center; color:var(--color-gray-400);">No subscriptions found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
