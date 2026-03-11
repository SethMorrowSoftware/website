<?php
/**
 * Performance Module
 *
 * Provides:
 * - File-based settings cache (avoids DB hits on every page load)
 * - Simple CSS/JS minification with caching
 * - HTTP cache headers for pages
 */

// ============================================================
// Settings Cache (file-based)
// ============================================================

/**
 * Warm the settings cache from the database.
 * Stores all settings in a file so subsequent reads avoid DB queries.
 */
function warmSettingsCache(): void {
    $cacheFile = getSettingsCacheFile();
    if ($cacheFile && file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
        return; // Cache is fresh (5 min TTL)
    }

    try {
        $db = getDB();
        $rows = $db->query('SELECT `key`, `value` FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
        $cacheDir = dirname($cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cacheFile, '<?php return ' . var_export($rows, true) . ';', LOCK_EX);
    } catch (Exception $e) {
        // Silently fail — DB may not be ready
    }
}

/**
 * Load settings from cache file into the static cache used by getSetting().
 */
function loadSettingsFromCache(): ?array {
    $cacheFile = getSettingsCacheFile();
    if ($cacheFile && file_exists($cacheFile)) {
        $data = @include $cacheFile;
        if (is_array($data)) {
            return $data;
        }
    }
    return null;
}

/**
 * Invalidate the settings cache (call after any settings save).
 */
function invalidateSettingsCache(): void {
    $cacheFile = getSettingsCacheFile();
    if ($cacheFile && file_exists($cacheFile)) {
        @unlink($cacheFile);
    }
}

/**
 * Get the settings cache file path.
 */
function getSettingsCacheFile(): string {
    return BACKUPS_PATH . '/.settings_cache.php';
}

// ============================================================
// CSS/JS Minification
// ============================================================

/**
 * Minify CSS by removing comments, whitespace, and unnecessary characters.
 */
function minifyCss(string $css): string {
    // Remove comments
    $css = preg_replace('/\/\*[\s\S]*?\*\//', '', $css);
    // Remove whitespace around selectors and properties
    $css = preg_replace('/\s+/', ' ', $css);
    $css = preg_replace('/\s*([{}:;,>+~])\s*/', '$1', $css);
    // Remove trailing semicolons before closing braces
    $css = str_replace(';}', '}', $css);
    return trim($css);
}

/**
 * Minify JS by removing single-line comments, collapsing whitespace.
 * This is a lightweight minifier — for production use a build tool.
 */
function minifyJs(string $js): string {
    // Remove single-line comments (but not URLs with //)
    $js = preg_replace('#(?<!:)//[^\n]*#', '', $js);
    // Remove multi-line comments
    $js = preg_replace('/\/\*[\s\S]*?\*\//', '', $js);
    // Collapse whitespace
    $js = preg_replace('/\s+/', ' ', $js);
    return trim($js);
}

/**
 * Get a minified version of a CSS/JS asset. Caches to disk.
 * Returns the URL to the minified file.
 */
function minifiedAsset(string $path): string {
    $fullPath = BASE_PATH . '/assets/' . ltrim($path, '/');
    if (!file_exists($fullPath)) {
        return asset($path);
    }

    $ext = pathinfo($path, PATHINFO_EXTENSION);
    $minDir = BASE_PATH . '/assets/min';
    if (!is_dir($minDir)) {
        mkdir($minDir, 0755, true);
    }

    $hash = substr(md5_file($fullPath), 0, 8);
    $minName = pathinfo($path, PATHINFO_FILENAME) . '.' . $hash . '.min.' . $ext;
    $minPath = $minDir . '/' . $minName;

    if (!file_exists($minPath)) {
        $content = file_get_contents($fullPath);
        $minified = ($ext === 'css') ? minifyCss($content) : minifyJs($content);
        file_put_contents($minPath, $minified, LOCK_EX);
    }

    return asset('min/' . $minName);
}

// ============================================================
// Database Index Optimization
// ============================================================

/**
 * Add performance indexes to frequently-queried columns.
 * Called from migration system.
 */
function addPerformanceIndexes(PDO $db): void {
    $indexes = [
        ['orders', 'idx_orders_status', 'order_status'],
        ['orders', 'idx_orders_payment', 'payment_status'],
        ['orders', 'idx_orders_customer', 'customer_id'],
        ['orders', 'idx_orders_created', 'created_at'],
        ['order_items', 'idx_oitems_product', 'product_id'],
        ['products', 'idx_products_category', 'category_id'],
        ['products', 'idx_products_visible', 'is_visible'],
        ['products', 'idx_products_slug', 'slug'],
    ];

    foreach ($indexes as [$table, $indexName, $column]) {
        try {
            // Check if index exists
            $stmt = $db->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?");
            $stmt->execute([$table, $indexName]);
            if ((int)$stmt->fetchColumn() === 0) {
                $db->exec("CREATE INDEX $indexName ON $table ($column)");
            }
        } catch (Exception $e) {
            // Table/column may not exist yet
        }
    }
}
