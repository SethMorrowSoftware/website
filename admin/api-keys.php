<?php
/**
 * Admin — API Key Management
 *
 * Create, view, and revoke API keys for REST API access.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api.php';

requireLogin();
requirePermission('manage_settings');

$newKeyData = null; // Set when a key is just created (to show the secret once)

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['key_action'] ?? '';

    if ($action === 'create') {
        $label = trim($_POST['label'] ?? '');
        $rateLimit = max(1, (int)($_POST['rate_limit'] ?? 60));
        $perms = $_POST['permissions'] ?? [];
        if (!is_array($perms) || empty($perms)) {
            $perms = ['*'];
        }

        if (!$label) {
            $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Label is required.'];
        } else {
            $newKeyData = createApiKey($label, $perms, $rateLimit, getCurrentUserId());
            logAudit('api_key_created', 'api_keys', "Created API key '$label'");
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "API key created. Copy the secret now — it won't be shown again."];
        }
    }

    if ($action === 'revoke') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if ($keyId) {
            revokeApiKey($keyId);
            logAudit('api_key_revoked', 'api_keys', "Revoked API key ID: $keyId");
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'API key revoked.'];
        }
        header('Location: ' . url('admin/api-keys.php'));
        exit;
    }

    if ($action === 'delete') {
        $keyId = (int)($_POST['key_id'] ?? 0);
        if ($keyId) {
            deleteApiKey($keyId);
            logAudit('api_key_deleted', 'api_keys', "Deleted API key ID: $keyId");
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'API key deleted.'];
        }
        header('Location: ' . url('admin/api-keys.php'));
        exit;
    }
}

$apiKeys = getAllApiKeys();

$availablePerms = [
    '*' => 'Full Access (all permissions)',
    'read_products' => 'Read Products',
    'write_products' => 'Write Products',
    'read_orders' => 'Read Orders',
    'write_orders' => 'Write Orders',
    'read_customers' => 'Read Customers',
    'read_analytics' => 'Read Analytics',
];

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-key"></i> API Keys</h1>
    <p>Manage API keys for third-party integrations.</p>
</div>

<?php if ($newKeyData): ?>
<div class="admin-alert alert-success" style="background: #f0fdf4; border: 2px solid #22c55e; padding: 1.5rem;">
    <h3 style="margin-top: 0;"><i class="fas fa-exclamation-triangle"></i> Save These Credentials Now</h3>
    <p>The API secret is shown only once. Store it securely.</p>
    <div style="background: #fff; padding: 1rem; border-radius: 6px; font-family: monospace; margin: 1rem 0; word-break: break-all;">
        <strong>API Key:</strong> <?php echo e($newKeyData['key']); ?><br>
        <strong>API Secret:</strong> <?php echo e($newKeyData['secret']); ?>
    </div>
    <p style="margin-bottom: 0; font-size: 0.9rem;">
        <strong>Usage:</strong> <code>Authorization: Bearer <?php echo e($newKeyData['key']); ?>:<?php echo e($newKeyData['secret']); ?></code>
    </p>
</div>
<?php endif; ?>

<!-- Create New Key -->
<div class="admin-card">
    <h3>Create New API Key</h3>
    <form method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
        <input type="hidden" name="key_action" value="create">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label for="label">Label *</label>
                <input type="text" id="label" name="label" class="form-control" required placeholder="e.g. Mobile App, POS System">
            </div>
            <div class="form-group">
                <label for="rate_limit">Rate Limit (req/min)</label>
                <input type="number" id="rate_limit" name="rate_limit" class="form-control" value="60" min="1" max="1000">
            </div>
        </div>

        <div class="form-group">
            <label>Permissions</label>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 0.5rem; margin-top: 0.5rem;">
                <?php foreach ($availablePerms as $perm => $label): ?>
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" name="permissions[]" value="<?php echo e($perm); ?>" <?php echo $perm === '*' ? 'checked' : ''; ?>>
                    <?php echo e($label); ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create API Key
        </button>
    </form>
</div>

<!-- Existing Keys -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <h3>API Keys</h3>
    <?php if (empty($apiKeys)): ?>
        <p style="color: var(--color-gray-400); margin-top: 1rem;">No API keys created yet.</p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Label</th>
                    <th>Key Prefix</th>
                    <th>Rate Limit</th>
                    <th>Status</th>
                    <th>Last Used</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($apiKeys as $key): ?>
                <tr>
                    <td><strong><?php echo e($key['label']); ?></strong></td>
                    <td><code><?php echo e(substr($key['api_key'], 0, 12)); ?>...</code></td>
                    <td><?php echo e($key['rate_limit']); ?>/min</td>
                    <td>
                        <?php if ($key['is_active']): ?>
                            <span style="color: var(--color-success);">Active</span>
                        <?php else: ?>
                            <span style="color: var(--color-error);">Revoked</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $key['last_used_at'] ? formatDate($key['last_used_at']) : '—'; ?></td>
                    <td><?php echo formatDate($key['created_at']); ?></td>
                    <td>
                        <?php if ($key['is_active']): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
                            <input type="hidden" name="key_action" value="revoke">
                            <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('Revoke this key? Integrations using it will stop working.');">
                                <i class="fas fa-ban"></i> Revoke
                            </button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
                            <input type="hidden" name="key_action" value="delete">
                            <input type="hidden" name="key_id" value="<?php echo $key['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline" style="color: var(--color-error);" onclick="return confirm('Delete permanently?');">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- API Documentation Quick Reference -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <h3>API Quick Reference</h3>
    <p style="color: var(--color-gray-400); margin-bottom: 1rem;">Base URL: <code><?php echo e(getCanonicalBaseUrl()); ?>/api/v1</code></p>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr><th>Method</th><th>Endpoint</th><th>Permission</th><th>Description</th></tr>
            </thead>
            <tbody>
                <tr><td>GET</td><td><code>/products</code></td><td>read_products</td><td>List products (paginated)</td></tr>
                <tr><td>GET</td><td><code>/products/:id</code></td><td>read_products</td><td>Get product details</td></tr>
                <tr><td>POST</td><td><code>/products</code></td><td>write_products</td><td>Create a product</td></tr>
                <tr><td>PUT</td><td><code>/products/:id</code></td><td>write_products</td><td>Update a product</td></tr>
                <tr><td>DELETE</td><td><code>/products/:id</code></td><td>write_products</td><td>Delete a product</td></tr>
                <tr><td>GET</td><td><code>/categories</code></td><td>read_products</td><td>List categories</td></tr>
                <tr><td>GET</td><td><code>/orders</code></td><td>read_orders</td><td>List orders (paginated)</td></tr>
                <tr><td>GET</td><td><code>/orders/:id</code></td><td>read_orders</td><td>Get order with line items</td></tr>
                <tr><td>PUT</td><td><code>/orders/:id</code></td><td>write_orders</td><td>Update order status</td></tr>
                <tr><td>GET</td><td><code>/customers</code></td><td>read_customers</td><td>List customers (paginated)</td></tr>
                <tr><td>GET</td><td><code>/customers/:id</code></td><td>read_customers</td><td>Get customer details</td></tr>
                <tr><td>GET</td><td><code>/inventory</code></td><td>read_products</td><td>List inventory levels</td></tr>
                <tr><td>PUT</td><td><code>/inventory/:id</code></td><td>write_products</td><td>Update stock quantity</td></tr>
                <tr><td>GET</td><td><code>/coupons</code></td><td>read_orders</td><td>List coupons</td></tr>
                <tr><td>POST</td><td><code>/coupons</code></td><td>write_orders</td><td>Create a coupon</td></tr>
                <tr><td>GET</td><td><code>/analytics/sales</code></td><td>read_analytics</td><td>Sales analytics</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
