<?php
/**
 * Admin — Theme Management
 *
 * Browse themes, activate, and customize theme settings.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_themes');

$allThemes = discoverThemes();
$activeTheme = getActiveTheme();

// Handle theme activation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['theme_action'] ?? '';

    if ($action === 'activate') {
        $slug = $_POST['theme_slug'] ?? '';
        if ($slug && (isset($allThemes[$slug]) || $slug === 'default')) {
            setActiveTheme($slug);
            logAudit('theme_activated', 'themes', $slug);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "Theme '$slug' activated."];
        }
        header('Location: ' . url('admin/themes.php'));
        exit;
    }

    if ($action === 'save_settings') {
        $slug = $_POST['theme_slug'] ?? '';
        if ($slug && isset($allThemes[$slug])) {
            $manifest = $allThemes[$slug];
            $schema = getThemeSettingsSchema($manifest);
            foreach ($schema as $setting) {
                $key = $setting['key'] ?? '';
                if (!$key) continue;
                if (isset($_POST['theme_setting_' . $key])) {
                    setThemeSetting($slug, $key, $_POST['theme_setting_' . $key]);
                }
            }
            logAudit('theme_settings_updated', 'themes', $slug);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Theme settings saved.'];
        }
        header('Location: ' . url('admin/themes.php') . '?customize=' . urlencode($slug));
        exit;
    }
}

// Check if we're in customize mode
$customizeSlug = $_GET['customize'] ?? '';
$customizeTheme = null;
if ($customizeSlug && isset($allThemes[$customizeSlug])) {
    $customizeTheme = $allThemes[$customizeSlug];
}

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-palette"></i> Themes</h1>
    <p>Manage the look and feel of your site.</p>
</div>

<?php if ($customizeTheme): ?>
<!-- Theme Customizer -->
<div class="admin-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h3>Customize: <?php echo e($customizeTheme['name'] ?? $customizeSlug); ?></h3>
        <a href="<?php echo url('admin/themes.php'); ?>" class="btn btn-sm btn-outline">
            <i class="fas fa-arrow-left"></i> Back to Themes
        </a>
    </div>

    <?php
    $schema = getThemeSettingsSchema($customizeTheme);
    $values = getThemeSettingValues($customizeSlug, $customizeTheme);
    ?>

    <?php if (empty($schema)): ?>
        <p style="color: var(--color-gray-400);">This theme has no customizable settings.</p>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
            <input type="hidden" name="theme_action" value="save_settings">
            <input type="hidden" name="theme_slug" value="<?php echo e($customizeSlug); ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                <?php foreach ($schema as $setting):
                    $key = $setting['key'] ?? '';
                    $label = $setting['label'] ?? $key;
                    $type = $setting['type'] ?? 'text';
                    $value = $values[$key] ?? ($setting['default'] ?? '');
                    $options = $setting['options'] ?? [];
                ?>
                <div class="form-group">
                    <label for="theme_setting_<?php echo e($key); ?>">
                        <?php echo e($label); ?>
                    </label>

                    <?php if ($type === 'color'): ?>
                        <div style="display: flex; gap: 0.5rem; align-items: center;">
                            <input type="color"
                                   id="theme_setting_<?php echo e($key); ?>"
                                   name="theme_setting_<?php echo e($key); ?>"
                                   value="<?php echo e($value); ?>"
                                   style="width: 50px; height: 38px; padding: 2px; border: 1px solid var(--color-gray-200); border-radius: 4px; cursor: pointer;">
                            <input type="text"
                                   value="<?php echo e($value); ?>"
                                   class="form-control"
                                   style="flex: 1;"
                                   oninput="document.getElementById('theme_setting_<?php echo e($key); ?>').value = this.value"
                                   onchange="document.getElementById('theme_setting_<?php echo e($key); ?>').value = this.value">
                        </div>

                    <?php elseif ($type === 'select' && !empty($options)): ?>
                        <select id="theme_setting_<?php echo e($key); ?>"
                                name="theme_setting_<?php echo e($key); ?>"
                                class="form-control">
                            <?php foreach ($options as $opt): ?>
                                <option value="<?php echo e($opt); ?>" <?php echo $value === $opt ? 'selected' : ''; ?>>
                                    <?php echo e($opt); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                    <?php elseif ($type === 'number'): ?>
                        <input type="number"
                               id="theme_setting_<?php echo e($key); ?>"
                               name="theme_setting_<?php echo e($key); ?>"
                               value="<?php echo e($value); ?>"
                               class="form-control">

                    <?php else: ?>
                        <input type="text"
                               id="theme_setting_<?php echo e($key); ?>"
                               name="theme_setting_<?php echo e($key); ?>"
                               value="<?php echo e($value); ?>"
                               class="form-control">
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top: 1.5rem;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Theme Settings
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php else: ?>
<!-- Theme Browser -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
    <!-- Default (built-in) theme -->
    <div class="admin-card" style="<?php echo $activeTheme === 'default' ? 'border: 2px solid var(--color-primary);' : ''; ?>">
        <div style="background: linear-gradient(135deg, #2563EB, #3b82f6); height: 140px; border-radius: 6px; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-paint-brush" style="font-size: 2.5rem; color: rgba(255,255,255,0.8);"></i>
        </div>
        <h3>Default Theme</h3>
        <p style="color: var(--color-gray-400); font-size: 0.9rem; margin-bottom: 1rem;">
            The built-in theme. Clean, professional, mobile-first design.
        </p>
        <?php if ($activeTheme === 'default'): ?>
            <span style="color: var(--color-primary); font-weight: 600;">
                <i class="fas fa-check-circle"></i> Active
            </span>
        <?php else: ?>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
                <input type="hidden" name="theme_action" value="activate">
                <input type="hidden" name="theme_slug" value="default">
                <button type="submit" class="btn btn-sm btn-primary">Activate</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Discovered themes -->
    <?php foreach ($allThemes as $slug => $theme):
        $isActive = ($slug === $activeTheme);
    ?>
    <div class="admin-card" style="<?php echo $isActive ? 'border: 2px solid var(--color-primary);' : ''; ?>">
        <div style="background: linear-gradient(135deg, #6366f1, #8b5cf6); height: 140px; border-radius: 6px; margin-bottom: 1rem; display: flex; align-items: center; justify-content: center;">
            <i class="fas fa-palette" style="font-size: 2.5rem; color: rgba(255,255,255,0.8);"></i>
        </div>
        <h3><?php echo e($theme['name'] ?? $slug); ?></h3>
        <p style="color: var(--color-gray-400); font-size: 0.9rem; margin-bottom: 0.5rem;">
            <?php echo e($theme['description'] ?? ''); ?>
        </p>
        <?php if (!empty($theme['author'])): ?>
            <p style="color: var(--color-gray-400); font-size: 0.8rem; margin-bottom: 1rem;">
                By <?php echo e($theme['author']); ?>
                <?php if (!empty($theme['version'])): ?> &middot; v<?php echo e($theme['version']); ?><?php endif; ?>
            </p>
        <?php endif; ?>

        <div style="display: flex; gap: 0.5rem;">
            <?php if ($isActive): ?>
                <span style="color: var(--color-primary); font-weight: 600;">
                    <i class="fas fa-check-circle"></i> Active
                </span>
                <?php if (!empty(getThemeSettingsSchema($theme))): ?>
                    <a href="<?php echo url('admin/themes.php'); ?>?customize=<?php echo e($slug); ?>" class="btn btn-sm btn-outline">
                        <i class="fas fa-sliders-h"></i> Customize
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
                    <input type="hidden" name="theme_action" value="activate">
                    <input type="hidden" name="theme_slug" value="<?php echo e($slug); ?>">
                    <button type="submit" class="btn btn-sm btn-primary">Activate</button>
                </form>
                <?php if (!empty(getThemeSettingsSchema($theme))): ?>
                    <a href="<?php echo url('admin/themes.php'); ?>?customize=<?php echo e($slug); ?>" class="btn btn-sm btn-outline">
                        <i class="fas fa-sliders-h"></i> Customize
                    </a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($allThemes)): ?>
<div class="admin-card" style="margin-top: 1.5rem;">
    <div style="text-align: center; padding: 2rem 1rem; color: var(--color-gray-400);">
        <p>
            <i class="fas fa-info-circle"></i>
            Place custom themes in the <code>themes/</code> directory. Each theme needs a
            <code>theme.json</code> manifest and optionally a <code>style.css</code> file.
            Themes can override any template in <code>pages/</code> or <code>includes/</code>.
        </p>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
