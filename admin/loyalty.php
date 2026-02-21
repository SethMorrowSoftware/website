<?php
/**
 * Admin — Loyalty Program & Marketing Tools
 *
 * Manage loyalty points settings, flash sales, and referral program.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/loyalty.php';

requireLogin();
requirePermission('manage_settings');

$db = getDB();

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $fields = ['loyalty_enabled', 'loyalty_points_per_dollar', 'loyalty_redemption_rate', 'loyalty_welcome_bonus', 'referral_enabled', 'referral_reward_points'];
        foreach ($fields as $f) {
            $val = $_POST[$f] ?? '0';
            if ($f === 'loyalty_enabled' || $f === 'referral_enabled') {
                $val = isset($_POST[$f]) ? '1' : '0';
            }
            setSetting($f, $val);
        }
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Loyalty settings saved!'];
        redirect('admin/loyalty.php');
    }

    if ($action === 'adjust_points') {
        $customerId = (int)$_POST['customer_id'];
        $points = (int)$_POST['points'];
        $reason = trim($_POST['reason'] ?? 'Admin adjustment');
        if ($customerId && $points !== 0) {
            adjustPoints($customerId, $points, $reason);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "Points adjusted: " . ($points > 0 ? '+' : '') . "$points"];
        }
        redirect('admin/loyalty.php');
    }

    if ($action === 'create_flash_sale') {
        $productId = (int)$_POST['product_id'];
        $salePrice = (float)$_POST['sale_price'];
        $startsAt = $_POST['starts_at'] ?? '';
        $endsAt = $_POST['ends_at'] ?? '';
        if ($productId && $salePrice > 0 && $startsAt && $endsAt) {
            createFlashSale($productId, $salePrice, $startsAt, $endsAt);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Flash sale created!'];
        }
        redirect('admin/loyalty.php');
    }

    if ($action === 'delete_flash_sale') {
        deleteFlashSale((int)$_POST['sale_id']);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Flash sale deleted.'];
        redirect('admin/loyalty.php');
    }
}

$flashSales = getAllFlashSales();

// Get top loyalty customers
$topLoyalty = [];
try {
    $topLoyalty = $db->query("
        SELECT lp.*, c.first_name, c.last_name, c.email
        FROM loyalty_points lp
        JOIN customers c ON lp.customer_id = c.id
        WHERE lp.lifetime_points > 0
        ORDER BY lp.lifetime_points DESC
        LIMIT 15
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Get products for flash sale form
$products = $db->query("SELECT id, name, price FROM products WHERE deleted_at IS NULL ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-gift"></i> Loyalty & Marketing</h1>
</div>

<!-- Loyalty Settings -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-cog"></i> Loyalty Program Settings</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="loyalty_enabled" value="1" <?php echo getSetting('loyalty_enabled', '0') === '1' ? 'checked' : ''; ?>>
                        <strong>Enable Loyalty Points Program</strong>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="referral_enabled" value="1" <?php echo getSetting('referral_enabled', '0') === '1' ? 'checked' : ''; ?>>
                        <strong>Enable Referral Program</strong>
                    </label>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Points Earned Per $1 Spent</label>
                    <input type="number" name="loyalty_points_per_dollar" class="form-control" value="<?php echo e(getSetting('loyalty_points_per_dollar', '1')); ?>" min="0">
                </div>
                <div class="form-group">
                    <label>Points Per $1 Redemption</label>
                    <input type="number" name="loyalty_redemption_rate" class="form-control" value="<?php echo e(getSetting('loyalty_redemption_rate', '100')); ?>" min="1">
                    <small class="form-help">How many points equal $1 discount. E.g., 100 means 100 points = $1.</small>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Welcome Bonus Points</label>
                    <input type="number" name="loyalty_welcome_bonus" class="form-control" value="<?php echo e(getSetting('loyalty_welcome_bonus', '0')); ?>" min="0">
                    <small class="form-help">Points awarded when a customer creates an account. Set to 0 to disable.</small>
                </div>
                <div class="form-group">
                    <label>Referral Reward Points</label>
                    <input type="number" name="referral_reward_points" class="form-control" value="<?php echo e(getSetting('referral_reward_points', '500')); ?>" min="0">
                    <small class="form-help">Points awarded to referrer. Referred customer gets half.</small>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>

<!-- Points Adjustment -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-balance-scale"></i> Adjust Customer Points</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <form method="POST" class="admin-form" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="adjust_points">
            <div class="form-group" style="flex: 1; min-width: 150px;">
                <label>Customer ID</label>
                <input type="number" name="customer_id" class="form-control" required min="1" placeholder="Customer ID">
            </div>
            <div class="form-group" style="flex: 1; min-width: 120px;">
                <label>Points (+/-)</label>
                <input type="number" name="points" class="form-control" required placeholder="+500 or -200">
            </div>
            <div class="form-group" style="flex: 2; min-width: 200px;">
                <label>Reason</label>
                <input type="text" name="reason" class="form-control" value="Admin adjustment" placeholder="Reason">
            </div>
            <button type="submit" class="btn-admin btn-save"><i class="fas fa-plus-minus"></i> Adjust</button>
        </form>
    </div>
</div>

<!-- Top Loyalty Members -->
<?php if (!empty($topLoyalty)): ?>
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-crown"></i> Top Loyalty Members</h2>
    <div class="admin-card" style="margin-top: var(--space-md);">
        <table class="admin-table">
            <thead><tr><th>Customer</th><th>Balance</th><th>Lifetime</th></tr></thead>
            <tbody>
                <?php foreach ($topLoyalty as $lm): ?>
                <tr>
                    <td><strong><?php echo e($lm['first_name'] . ' ' . $lm['last_name']); ?></strong><br><small><?php echo e($lm['email']); ?></small></td>
                    <td><?php echo number_format($lm['points_balance']); ?> pts</td>
                    <td><?php echo number_format($lm['lifetime_points']); ?> pts</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Flash Sales -->
<div class="admin-section" style="margin-bottom: var(--space-xl);">
    <h2><i class="fas fa-bolt"></i> Flash Sales</h2>

    <!-- Create form -->
    <div class="admin-card" style="margin-top: var(--space-md); margin-bottom: var(--space-md);">
        <h4 style="margin-bottom: 0.75rem;">Create Flash Sale</h4>
        <form method="POST" class="admin-form" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="create_flash_sale">
            <div class="form-group" style="flex: 2; min-width: 200px;">
                <label>Product</label>
                <select name="product_id" class="form-control" required>
                    <option value="">Select product...</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo e($p['name']); ?> (<?php echo e($p['price']); ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" style="flex: 1; min-width: 100px;">
                <label>Sale Price</label>
                <input type="number" name="sale_price" class="form-control" step="0.01" min="0.01" required placeholder="19.99">
            </div>
            <div class="form-group" style="flex: 1; min-width: 180px;">
                <label>Starts At</label>
                <input type="datetime-local" name="starts_at" class="form-control" required>
            </div>
            <div class="form-group" style="flex: 1; min-width: 180px;">
                <label>Ends At</label>
                <input type="datetime-local" name="ends_at" class="form-control" required>
            </div>
            <button type="submit" class="btn-admin btn-save"><i class="fas fa-plus"></i> Create</button>
        </form>
    </div>

    <!-- Flash sales list -->
    <div class="admin-card">
        <table class="admin-table">
            <thead><tr><th>Product</th><th>Original</th><th>Sale Price</th><th>Period</th><th>Status</th><th></th></tr></thead>
            <tbody>
                <?php foreach ($flashSales as $fs):
                    $now = time();
                    $start = strtotime($fs['starts_at']);
                    $end = strtotime($fs['ends_at']);
                    $status = $now < $start ? 'Upcoming' : ($now > $end ? 'Ended' : 'Active');
                    $statusClass = $status === 'Active' ? 'badge-active' : ($status === 'Ended' ? 'badge-inactive' : 'badge-unread');
                ?>
                <tr>
                    <td><strong><?php echo e($fs['product_name']); ?></strong></td>
                    <td><?php echo e($fs['original_price']); ?></td>
                    <td style="color: var(--color-error); font-weight: 600;"><?php echo formatCurrency($fs['sale_price']); ?></td>
                    <td><small><?php echo formatDate($fs['starts_at']); ?> — <?php echo formatDate($fs['ends_at']); ?></small></td>
                    <td><span class="badge-status <?php echo $statusClass; ?>"><?php echo $status; ?></span></td>
                    <td>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this flash sale?');">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="action" value="delete_flash_sale">
                            <input type="hidden" name="sale_id" value="<?php echo $fs['id']; ?>">
                            <button type="submit" class="btn-admin btn-sm btn-delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($flashSales)): ?>
                <tr><td colspan="6" style="text-align:center; color:var(--color-gray-400);">No flash sales created yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
