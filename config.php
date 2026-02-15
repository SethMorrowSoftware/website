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
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize stored HTML — allows safe formatting tags, strips everything else.
 * Used for admin-authored content (custom pages, footer text, embeds).
 */
function sanitizeHtml(string $html): string {
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><hr><span><div><table><thead><tbody><tr><th><td><iframe><figure><figcaption><pre><code>';
    $clean = strip_tags($html, $allowed);
    // Strip event handlers (onclick, onerror, onload, etc.) from remaining tags
    $clean = preg_replace('/\s+on\w+\s*=\s*(?:"[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean);
    // Strip javascript: and data: URIs from href/src/action attributes
    $clean = preg_replace('/(<[^>]+\s)(href|src|action)\s*=\s*(?:"(?:javascript|data):[^"]*"|\'(?:javascript|data):[^\']*\')/i', '$1$2=""', $clean);
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
    header('Location: ' . url($path));
    exit;
}
