<?php
/**
 * Plugin / Extension System
 *
 * Provides a lightweight event/hook dispatcher (similar to WordPress actions/filters).
 * Plugins live in plugins/<plugin-name>/ with a plugin.json manifest and init.php entry point.
 *
 * Usage:
 *   do_action('hook_name', $arg1, $arg2, ...);       // Fire an action hook
 *   $value = apply_filters('filter_name', $value);    // Apply filters to a value
 *   add_action('hook_name', callable, priority);      // Register action callback
 *   add_filter('filter_name', callable, priority);    // Register filter callback
 */

// ============================================================
// Hook Registry
// ============================================================

/** @var array<string, array<int, callable[]>> Action hooks indexed by name, then priority */
$_plugin_actions = [];

/** @var array<string, array<int, callable[]>> Filter hooks indexed by name, then priority */
$_plugin_filters = [];

/** @var array<string, array> Loaded plugin manifests indexed by plugin slug */
$_loaded_plugins = [];

/**
 * Register a callback for an action hook.
 *
 * @param string   $hook     Hook name (e.g. 'before_checkout', 'after_order_created')
 * @param callable $callback Function to call when the hook fires
 * @param int      $priority Lower numbers run first (default 10)
 */
function add_action(string $hook, callable $callback, int $priority = 10): void {
    global $_plugin_actions;
    $_plugin_actions[$hook][$priority][] = $callback;
}

/**
 * Register a callback for a filter hook.
 *
 * @param string   $hook     Filter name (e.g. 'product_price', 'cart_totals')
 * @param callable $callback Function that receives and returns the filtered value
 * @param int      $priority Lower numbers run first (default 10)
 */
function add_filter(string $hook, callable $callback, int $priority = 10): void {
    global $_plugin_filters;
    $_plugin_filters[$hook][$priority][] = $callback;
}

/**
 * Fire an action hook — calls all registered callbacks in priority order.
 *
 * @param string $hook Hook name
 * @param mixed  ...$args Arguments passed to each callback
 */
function do_action(string $hook, mixed ...$args): void {
    global $_plugin_actions;
    if (empty($_plugin_actions[$hook])) return;

    $hooks = $_plugin_actions[$hook];
    ksort($hooks);
    foreach ($hooks as $callbacks) {
        foreach ($callbacks as $callback) {
            try {
                call_user_func_array($callback, $args);
            } catch (\Throwable $e) {
                error_log("[PLUGIN] Action hook '$hook' error: " . $e->getMessage());
            }
        }
    }
}

/**
 * Apply filters — passes the value through all registered filter callbacks.
 *
 * @param string $hook  Filter name
 * @param mixed  $value The value to filter
 * @param mixed  ...$args Additional arguments passed to each callback
 * @return mixed The filtered value
 */
function apply_filters(string $hook, mixed $value, mixed ...$args): mixed {
    global $_plugin_filters;
    if (empty($_plugin_filters[$hook])) return $value;

    $hooks = $_plugin_filters[$hook];
    ksort($hooks);
    foreach ($hooks as $callbacks) {
        foreach ($callbacks as $callback) {
            try {
                $value = call_user_func_array($callback, array_merge([$value], $args));
            } catch (\Throwable $e) {
                error_log("[PLUGIN] Filter hook '$hook' error: " . $e->getMessage());
            }
        }
    }
    return $value;
}

/**
 * Check if a hook has any registered callbacks.
 */
function has_action(string $hook): bool {
    global $_plugin_actions;
    return !empty($_plugin_actions[$hook]);
}

/**
 * Check if a filter has any registered callbacks.
 */
function has_filter(string $hook): bool {
    global $_plugin_filters;
    return !empty($_plugin_filters[$hook]);
}

/**
 * Remove all callbacks for a specific action hook.
 */
function remove_all_actions(string $hook): void {
    global $_plugin_actions;
    unset($_plugin_actions[$hook]);
}

/**
 * Remove all callbacks for a specific filter hook.
 */
function remove_all_filters(string $hook): void {
    global $_plugin_filters;
    unset($_plugin_filters[$hook]);
}

// ============================================================
// Plugin Loader
// ============================================================

/**
 * Get the plugins directory path.
 */
function getPluginsDir(): string {
    return BASE_PATH . '/plugins';
}

/**
 * Scan the plugins directory and return all discovered plugin manifests.
 *
 * @return array<string, array> Plugins indexed by slug, each containing manifest data
 */
function discoverPlugins(): array {
    $pluginsDir = getPluginsDir();
    if (!is_dir($pluginsDir)) return [];

    $plugins = [];
    $dirs = glob($pluginsDir . '/*/plugin.json');
    if (!$dirs) return [];

    foreach ($dirs as $manifestPath) {
        $pluginDir = dirname($manifestPath);
        $slug = basename($pluginDir);

        $json = file_get_contents($manifestPath);
        $manifest = json_decode($json, true);
        if (!$manifest || !is_array($manifest)) {
            error_log("[PLUGIN] Invalid manifest: $manifestPath");
            continue;
        }

        $manifest['slug'] = $slug;
        $manifest['path'] = $pluginDir;
        $manifest['entry'] = $pluginDir . '/' . ($manifest['entry_point'] ?? 'init.php');
        $plugins[$slug] = $manifest;
    }

    return $plugins;
}

/**
 * Get the list of active plugin slugs from the database.
 *
 * @return string[]
 */
function getActivePlugins(): array {
    try {
        $value = getSetting('active_plugins', '[]');
        $list = json_decode($value, true);
        return is_array($list) ? $list : [];
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Save the list of active plugin slugs to the database.
 *
 * @param string[] $slugs
 */
function setActivePlugins(array $slugs): void {
    updateSetting('active_plugins', json_encode(array_values($slugs)));
}

/**
 * Activate a plugin by slug.
 */
function activatePlugin(string $slug): bool {
    $plugins = discoverPlugins();
    if (!isset($plugins[$slug])) return false;

    $active = getActivePlugins();
    if (!in_array($slug, $active)) {
        $active[] = $slug;
        setActivePlugins($active);
    }

    // Run activation hook if plugin defines one
    $plugin = $plugins[$slug];
    $activateFile = $plugin['path'] . '/activate.php';
    if (file_exists($activateFile)) {
        try {
            require_once $activateFile;
        } catch (\Throwable $e) {
            error_log("[PLUGIN] Activation error for '$slug': " . $e->getMessage());
        }
    }

    return true;
}

/**
 * Deactivate a plugin by slug.
 */
function deactivatePlugin(string $slug): bool {
    $active = getActivePlugins();
    $active = array_filter($active, fn($s) => $s !== $slug);
    setActivePlugins($active);

    // Run deactivation hook if plugin defines one
    $plugins = discoverPlugins();
    if (isset($plugins[$slug])) {
        $deactivateFile = $plugins[$slug]['path'] . '/deactivate.php';
        if (file_exists($deactivateFile)) {
            try {
                require_once $deactivateFile;
            } catch (\Throwable $e) {
                error_log("[PLUGIN] Deactivation error for '$slug': " . $e->getMessage());
            }
        }
    }

    return true;
}

/**
 * Load and initialize all active plugins.
 * Called once during bootstrap (from functions.php).
 */
function loadActivePlugins(): void {
    global $_loaded_plugins;

    $activePlugins = getActivePlugins();
    if (empty($activePlugins)) return;

    $allPlugins = discoverPlugins();

    foreach ($activePlugins as $slug) {
        if (!isset($allPlugins[$slug])) {
            error_log("[PLUGIN] Active plugin '$slug' not found in plugins directory — skipping");
            continue;
        }

        $plugin = $allPlugins[$slug];
        $entryFile = $plugin['entry'];

        if (!file_exists($entryFile)) {
            error_log("[PLUGIN] Entry point not found for '$slug': $entryFile");
            continue;
        }

        try {
            require_once $entryFile;
            $_loaded_plugins[$slug] = $plugin;
        } catch (\Throwable $e) {
            error_log("[PLUGIN] Failed to load '$slug': " . $e->getMessage());
        }
    }
}

/**
 * Get all loaded (active and initialized) plugins.
 *
 * @return array<string, array>
 */
function getLoadedPlugins(): array {
    global $_loaded_plugins;
    return $_loaded_plugins;
}

// ============================================================
// Plugin Settings API
// ============================================================

/**
 * Get a plugin-specific setting.
 *
 * @param string $pluginSlug Plugin identifier
 * @param string $key        Setting key
 * @param mixed  $default    Default value if not found
 * @return string
 */
function getPluginSetting(string $pluginSlug, string $key, string $default = ''): string {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT `value` FROM plugin_settings WHERE plugin_name = ? AND `key` = ?');
        $stmt->execute([$pluginSlug, $key]);
        $result = $stmt->fetchColumn();
        return $result !== false ? $result : $default;
    } catch (\Throwable $e) {
        return $default;
    }
}

/**
 * Set a plugin-specific setting.
 *
 * @param string $pluginSlug Plugin identifier
 * @param string $key        Setting key
 * @param string $value      Setting value
 */
function setPluginSetting(string $pluginSlug, string $key, string $value): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO plugin_settings (plugin_name, `key`, `value`) VALUES (?, ?, ?) '
            . 'ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)'
        );
        return $stmt->execute([$pluginSlug, $key, $value]);
    } catch (\Throwable $e) {
        error_log("[PLUGIN] Failed to set setting '$key' for '$pluginSlug': " . $e->getMessage());
        return false;
    }
}

/**
 * Get all settings for a plugin.
 *
 * @param string $pluginSlug Plugin identifier
 * @return array<string, string> Key-value pairs
 */
function getAllPluginSettings(string $pluginSlug): array {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT `key`, `value` FROM plugin_settings WHERE plugin_name = ?');
        $stmt->execute([$pluginSlug]);
        $settings = [];
        while ($row = $stmt->fetch()) {
            $settings[$row['key']] = $row['value'];
        }
        return $settings;
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Delete all settings for a plugin (used during uninstall).
 */
function deletePluginSettings(string $pluginSlug): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare('DELETE FROM plugin_settings WHERE plugin_name = ?');
        return $stmt->execute([$pluginSlug]);
    } catch (\Throwable $e) {
        return false;
    }
}
