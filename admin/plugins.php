<?php
/**
 * Admin — Plugin Management
 *
 * Lists installed plugins, allows activation/deactivation.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_plugins');

// Handle plugin activation/deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $pluginSlug = $_POST['plugin_slug'] ?? '';
    $pluginAction = $_POST['plugin_action'] ?? '';

    if ($pluginSlug && in_array($pluginAction, ['activate', 'deactivate'])) {
        if ($pluginAction === 'activate') {
            if (activatePlugin($pluginSlug)) {
                logAudit('plugin_activated', 'plugins', $pluginSlug);
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "Plugin '$pluginSlug' activated successfully."];
            } else {
                $_SESSION['admin_flash'] = ['type' => 'error', 'message' => "Could not activate plugin '$pluginSlug'. Check that it exists and has a valid manifest."];
            }
        } else {
            deactivatePlugin($pluginSlug);
            logAudit('plugin_deactivated', 'plugins', $pluginSlug);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "Plugin '$pluginSlug' deactivated."];
        }
    }

    header('Location: ' . url('admin/plugins.php'));
    exit;
}

// Discover all plugins and check which are active
$allPlugins = discoverPlugins();
$activePlugins = getActivePlugins();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-puzzle-piece"></i> Plugins</h1>
    <p>Manage installed plugins to extend your site's functionality.</p>
</div>

<?php if (empty($allPlugins)): ?>
<div class="admin-card">
    <div style="text-align: center; padding: 3rem 1rem; color: var(--color-gray-400);">
        <i class="fas fa-puzzle-piece" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
        <h3>No Plugins Installed</h3>
        <p style="margin-top: 0.5rem;">
            Place plugins in the <code>plugins/</code> directory. Each plugin needs a
            <code>plugin.json</code> manifest and an <code>init.php</code> entry point.
        </p>
    </div>
</div>
<?php else: ?>
<div class="admin-card">
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Plugin</th>
                    <th>Version</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($allPlugins as $slug => $plugin):
                    $isActive = in_array($slug, $activePlugins);
                ?>
                <tr>
                    <td>
                        <strong><?php echo e($plugin['name'] ?? $slug); ?></strong>
                        <?php if (!empty($plugin['description'])): ?>
                            <br><small style="color: var(--color-gray-400);"><?php echo e($plugin['description']); ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($plugin['version'] ?? '—'); ?></td>
                    <td><?php echo e($plugin['author'] ?? '—'); ?></td>
                    <td>
                        <?php if ($isActive): ?>
                            <span class="badge" style="background: var(--color-success, #22c55e); color: #fff; padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.8rem;">Active</span>
                        <?php else: ?>
                            <span class="badge" style="background: var(--color-gray-200, #e2e8f0); color: var(--color-gray-600, #475569); padding: 0.25rem 0.75rem; border-radius: 4px; font-size: 0.8rem;">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
                            <input type="hidden" name="plugin_slug" value="<?php echo e($slug); ?>">
                            <?php if ($isActive): ?>
                                <input type="hidden" name="plugin_action" value="deactivate">
                                <button type="submit" class="btn btn-sm btn-outline" onclick="return confirm('Deactivate this plugin?');">
                                    <i class="fas fa-power-off"></i> Deactivate
                                </button>
                            <?php else: ?>
                                <input type="hidden" name="plugin_action" value="activate">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-check"></i> Activate
                                </button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="admin-card" style="margin-top: 1.5rem;">
    <h3>Available Hooks</h3>
    <p style="color: var(--color-gray-400); margin-bottom: 1rem;">Plugins can register callbacks for these hooks to extend site behavior:</p>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Hook Name</th>
                    <th>Type</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr><td><code>init</code></td><td>Action</td><td>Fires after session start, before routing</td></tr>
                <tr><td><code>before_route</code></td><td>Action</td><td>Fires before page routing begins</td></tr>
                <tr><td><code>before_render</code></td><td>Action</td><td>Fires before page template is rendered</td></tr>
                <tr><td><code>after_render</code></td><td>Action</td><td>Fires after page template is rendered</td></tr>
                <tr><td><code>wp_head</code></td><td>Action</td><td>Inject content into &lt;head&gt; (styles, meta tags)</td></tr>
                <tr><td><code>wp_footer</code></td><td>Action</td><td>Inject content before &lt;/body&gt; (scripts, widgets)</td></tr>
                <tr><td><code>cart_item_added</code></td><td>Action</td><td>Fires after an item is added to cart</td></tr>
                <tr><td><code>after_order_created</code></td><td>Action</td><td>Fires after a new order is created</td></tr>
                <tr><td><code>page_template</code></td><td>Filter</td><td>Filter the template file path before rendering</td></tr>
                <tr><td><code>admin_menu</code></td><td>Action</td><td>Add items to the admin sidebar menu</td></tr>
                <tr><td><code>product_display</code></td><td>Action</td><td>Add content to product detail pages</td></tr>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
