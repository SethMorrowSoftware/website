<?php
/**
 * Business Website CMS
 * Configuration file
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', 1);
}

// Base path configuration
define('BASE_PATH', __DIR__);
define('DB_PATH', BASE_PATH . '/database/database.sqlite');
define('UPLOADS_PATH', BASE_PATH . '/uploads');

// Base URL — auto-detect the subdirectory this site lives in.
// Override manually if auto-detection doesn't work for your setup:
//   define('BASE_URL', '/my-subdirectory');
if (!defined('BASE_URL')) {
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    // If we're in /admin or /admin/api, walk up to the project root
    $scriptDir = preg_replace('#/admin(/api)?$#', '', $scriptDir);
    // If we're loading index.php at the root level, dirname gives us the subdir
    define('BASE_URL', rtrim($scriptDir, '/'));
}

define('UPLOADS_URL', BASE_URL . '/uploads');

// Site defaults
define('SITE_NAME', 'Your Business Name');
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hour

/**
 * Get database connection (singleton)
 */
function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        $dbExists = file_exists(DB_PATH);
        $db = new PDO('sqlite:' . DB_PATH);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->exec('PRAGMA journal_mode=WAL');
        $db->exec('PRAGMA foreign_keys=ON');

        if (!$dbExists) {
            initializeDatabase($db);
        } else {
            migrateDatabase($db);
        }

        // Run file-based migrations (for both new and existing DBs)
        require_once BASE_PATH . '/includes/migrations.php';
        runMigrations($db);
    }
    return $db;
}

/**
 * Initialize database schema
 */
function initializeDatabase(PDO $db): void {
    $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
    $db->exec($schema);

    // Run seeder
    require_once BASE_PATH . '/database/seed.php';
    seedDatabase($db);
}

/**
 * Run database migrations for existing installations.
 * Called on every connection to bring older schemas up to date.
 */
function migrateDatabase(PDO $db): void {
    // Check if products table has the new columns
    $cols = $db->query("PRAGMA table_info(products)")->fetchAll();
    $colNames = array_column($cols, 'name');

    if (!in_array('specifications', $colNames)) {
        $db->exec('ALTER TABLE products ADD COLUMN specifications TEXT');
    }
    if (!in_array('features', $colNames)) {
        $db->exec('ALTER TABLE products ADD COLUMN features TEXT');
    }
    if (!in_array('price_note', $colNames)) {
        $db->exec('ALTER TABLE products ADD COLUMN price_note TEXT');
    }

    // Check if product_categories has icon column
    $catCols = $db->query("PRAGMA table_info(product_categories)")->fetchAll();
    $catColNames = array_column($catCols, 'name');

    if (!in_array('icon', $catColNames)) {
        $db->exec("ALTER TABLE product_categories ADD COLUMN icon TEXT DEFAULT 'fa-tag'");
    }

    // Add e-commerce columns to products
    if (!in_array('product_type', $colNames)) {
        $db->exec("ALTER TABLE products ADD COLUMN product_type TEXT DEFAULT 'physical'");
    }
    if (!in_array('download_file', $colNames)) {
        $db->exec('ALTER TABLE products ADD COLUMN download_file TEXT');
    }
    if (!in_array('download_limit', $colNames)) {
        $db->exec('ALTER TABLE products ADD COLUMN download_limit INTEGER DEFAULT 0');
    }
    if (!in_array('download_expiry_hours', $colNames)) {
        $db->exec('ALTER TABLE products ADD COLUMN download_expiry_hours INTEGER DEFAULT 72');
    }

    // Create orders table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_number TEXT UNIQUE NOT NULL,
        customer_name TEXT NOT NULL,
        customer_email TEXT NOT NULL,
        customer_phone TEXT,
        shipping_address TEXT,
        subtotal REAL DEFAULT 0,
        tax REAL DEFAULT 0,
        total REAL DEFAULT 0,
        payment_method TEXT,
        payment_id TEXT,
        payment_status TEXT DEFAULT 'pending',
        order_status TEXT DEFAULT 'pending',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Create order_items table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        product_id INTEGER,
        product_name TEXT NOT NULL,
        product_type TEXT DEFAULT 'physical',
        quantity INTEGER DEFAULT 1,
        unit_price REAL DEFAULT 0,
        total_price REAL DEFAULT 0,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    )");

    // Create download_tokens table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS download_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        order_id INTEGER NOT NULL,
        order_item_id INTEGER NOT NULL,
        product_id INTEGER NOT NULL,
        token TEXT UNIQUE NOT NULL,
        download_count INTEGER DEFAULT 0,
        max_downloads INTEGER DEFAULT 0,
        expires_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
    )");

    // ---- Store Configuration settings ----
    // These are stored in the settings table; add defaults for existing installs.
    $storeConfigDefaults = [
        ['store_type',           'products_and_services'],
        ['business_type',        'local'],
        ['enable_catalog',       '1'],
        ['enable_cart',          '1'],
        ['enable_order_inquiry', '1'],
        ['enable_contact_form',  '1'],
        ['enable_testimonials',  '1'],
        ['enable_about_page',    '1'],
        ['show_phone_header',    '1'],
        ['show_email_header',    '1'],
        ['show_address',         '1'],
        ['show_business_hours',  '1'],
        ['show_map',             '1'],
        ['catalog_page_title',   'Our Catalog'],
        ['catalog_section_title','Browse Our Offerings'],
        ['order_inquiry_title',  'Order Inquiry'],
        ['cta_heading',          'Ready to Get Started?'],
        ['cta_subtext',          ''],
        ['homepage_offerings_heading', 'What We Offer'],
        ['homepage_offerings_subtext', 'Explore our products and services'],
        ['homepage_featured_heading',  'Featured Products & Services'],
        ['homepage_featured_subtext',  'A selection of what we have to offer'],
        ['enable_customer_accounts', '1'],
        ['enable_search',       '1'],
        ['enable_wishlists',    '1'],
        ['enable_reviews',      '0'],
    ];
    $checkStmt = $db->prepare('SELECT COUNT(*) FROM settings WHERE key = ?');
    $insertStmt = $db->prepare('INSERT INTO settings (key, value, type) VALUES (?, ?, ?)');
    foreach ($storeConfigDefaults as [$sKey, $sVal]) {
        $checkStmt->execute([$sKey]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            $insertStmt->execute([$sKey, $sVal, 'text']);
        }
    }

    // Migrate containers into products if containers table still exists
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='containers'")->fetchColumn();
    if ($tables) {
        // Ensure a "Containers" category exists
        $catStmt = $db->prepare('SELECT id FROM product_categories WHERE slug = ?');
        $catStmt->execute(['containers']);
        $catId = $catStmt->fetchColumn();

        if (!$catId) {
            $db->exec("INSERT INTO product_categories (name, slug, description, icon, sort_order) VALUES ('Containers', 'containers', 'Container rentals and equipment', 'fa-boxes-stacked', 0)");
            $catId = $db->lastInsertId();
        }

        // Migrate each container as a product
        $containers = $db->query('SELECT * FROM containers')->fetchAll();
        $insertStmt = $db->prepare('INSERT OR IGNORE INTO products (category_id, name, slug, description, image, price, unit, specifications, features, price_note, is_visible, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($containers as $c) {
            $slug = strtolower(trim(preg_replace('/[^a-z0-9-]/', '-', preg_replace('/-+/', '-', strtolower($c['name'])))));
            $specs = $c['dimensions'] ?? '';
            $features = $c['use_cases'] ?? '';
            $insertStmt->execute([
                $catId,
                $c['name'],
                $slug,
                $c['description'] ?? '',
                $c['image'] ?? '',
                $c['price'] ?? '',
                $c['unit'] ?? '',
                $specs,
                $features,
                $c['price_note'] ?? '',
                $c['is_visible'] ?? 1,
                $c['sort_order'] ?? 0,
            ]);
        }

        // Drop old containers table
        $db->exec('DROP TABLE IF EXISTS containers');
    }
}

/**
 * Get a site setting
 */
function getSetting(string $key, string $default = ''): string {
    static $cache = [];
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT value FROM settings WHERE key = ?');
        $stmt->execute([$key]);
        $result = $stmt->fetchColumn();
        $cache[$key] = $result !== false ? $result : $default;
        return $cache[$key];
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Update a site setting
 */
function updateSetting(string $key, string $value, string $type = 'text'): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT OR REPLACE INTO settings (key, value, type) VALUES (?, ?, ?)');
        return $stmt->execute([$key, $value, $type]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken(string $token): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Sanitize output
 */
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize stored HTML — allows safe formatting tags, strips everything else.
 * Used for admin-authored content (custom pages, footer text, embeds).
 */
function sanitizeHtml(string $html): string {
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><hr><span><div><table><thead><tbody><tr><th><td><figure><figcaption><pre><code>';
    $clean = strip_tags($html, $allowed);
    // Strip event handlers — match on + any whitespace/control chars + word chars + =
    // Handles bypass attempts with tabs/newlines between "on" and the event name
    $clean = preg_replace('/\s+on[\s\x00-\x1f]*\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    // Strip javascript:, data:, and vbscript: URIs from href/src/action attributes
    // Handles whitespace padding and entity-encoded variations
    $clean = preg_replace_callback('/(href|src|action)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', function($m) {
        $val = trim($m[2], '"\'');
        $decoded = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
        $stripped = preg_replace('/[\s\x00-\x1f]+/', '', $decoded);
        if (preg_match('/^(javascript|data|vbscript):/i', $stripped)) {
            return $m[1] . '=""';
        }
        return $m[0];
    }, $clean);
    return $clean;
}

/**
 * Generate a URL relative to the site's base path.
 * e.g. url('/admin/settings.php') => '/subdir/admin/settings.php'
 */
function url(string $path = '/'): string {
    if ($path === '/') {
        return BASE_URL . '/';
    }
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Generate an asset URL (shorthand for css/js/images)
 */
function asset(string $path): string {
    return url('assets/' . ltrim($path, '/'));
}

/**
 * Redirect helper — automatically prepends BASE_URL
 */
function redirect(string $path): void {
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: ' . url($path));
    exit;
}
