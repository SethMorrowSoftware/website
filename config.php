<?php
/**
 * Business Website CMS
 * Configuration file
 */

// Load .env file if present (before any getenv() calls)
// Supports:
//   - Single/double quoted values: KEY="value with spaces"
//   - Inline comments: KEY=value  # comment
//   - Escaped quotes within quoted values
//   - Empty values: KEY= or KEY=""
$_envFile = __DIR__ . '/.env';
if (file_exists($_envFile)) {
    $_envLines = file($_envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($_envLines as $_envLine) {
        $_envLine = trim($_envLine);
        if ($_envLine === '' || $_envLine[0] === '#') continue;
        if (strpos($_envLine, '=') === false) continue;
        [$_envKey, $_envVal] = explode('=', $_envLine, 2);
        $_envKey = trim($_envKey);
        $_envVal = trim($_envVal);

        // Handle quoted values
        if (strlen($_envVal) >= 2) {
            $firstChar = $_envVal[0];
            if ($firstChar === '"' && str_ends_with($_envVal, '"')) {
                // Double-quoted: strip quotes, process escape sequences
                $_envVal = substr($_envVal, 1, -1);
                $_envVal = str_replace(['\\n', '\\t', '\\"', '\\\\'], ["\n", "\t", '"', '\\'], $_envVal);
            } elseif ($firstChar === "'" && str_ends_with($_envVal, "'")) {
                // Single-quoted: strip quotes, no escape processing
                $_envVal = substr($_envVal, 1, -1);
            } else {
                // Unquoted: strip inline comments (# preceded by whitespace)
                $_envVal = preg_replace('/\s+#.*$/', '', $_envVal);
            }
        } else {
            // Single char or empty — strip inline comments
            $_envVal = preg_replace('/\s+#.*$/', '', $_envVal);
        }

        if (!getenv($_envKey)) {
            putenv("$_envKey=$_envVal");
        }
    }
    unset($_envLines, $_envLine, $_envKey, $_envVal);
}
unset($_envFile);

// Error reporting (disable in production)
$_appDebug = getenv('APP_DEBUG') ?: '0';
error_reporting(E_ALL);
ini_set('display_errors', $_appDebug === '1' ? 1 : 0);
ini_set('log_errors', 1);

/**
 * Check if the immediate connecting peer is a trusted proxy.
 *
 * Only trusts X-Forwarded-* headers when:
 *   1. The `trusted_proxy_enabled` setting is '1', AND
 *   2. REMOTE_ADDR is in the `trusted_proxy_ips` allowlist.
 *
 * This prevents attackers from spoofing forwarded headers when the
 * app is directly internet-exposed or behind an untrusted hop.
 *
 * Called early (before DB may be available), so it catches and
 * returns false on any DB exception.
 */
function isTrustedProxy(): bool {
    static $result = null;
    static $dbWasAvailable = false;

    // Return cached result only if it was computed with DB access.
    // During early init (before DB constants are defined), the function
    // returns false without caching, so it re-evaluates once the DB is ready.
    if ($result !== null && $dbWasAvailable) return $result;

    try {
        $enabled = getSetting('trusted_proxy_enabled') === '1';
        $dbWasAvailable = true;
    } catch (\Throwable $e) {
        // DB not available yet (early init) or other error — fail closed.
        // Do NOT cache this result so we re-evaluate after DB init.
        return false;
    }

    if (!$enabled) {
        $result = false;
        return false;
    }

    // Validate that the immediate peer (REMOTE_ADDR) is in the allowlist
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    try {
        $allowlistRaw = getSetting('trusted_proxy_ips', '');
    } catch (\Throwable $e) {
        return false;
    }

    if ($allowlistRaw === '') {
        // No allowlist configured — refuse to trust any proxy.
        // This is fail-closed: enabling trusted_proxy without an
        // allowlist does NOT trust all peers.
        $result = false;
        return false;
    }

    $allowedEntries = array_map('trim', explode(',', $allowlistRaw));
    $result = ipMatchesAllowlist($remoteAddr, $allowedEntries);
    return $result;
}

/**
 * Check if an IP address matches any entry in an allowlist.
 * Supports both exact IPs and CIDR notation (e.g. '10.0.0.0/8', '2001:db8::/32').
 */
function ipMatchesAllowlist(string $ip, array $entries): bool {
    foreach ($entries as $entry) {
        if ($entry === '') continue;

        if (str_contains($entry, '/')) {
            // CIDR notation
            if (ipInCidr($ip, $entry)) return true;
        } else {
            // Exact match
            if ($ip === $entry) return true;
        }
    }
    return false;
}

/**
 * Check if an IP address falls within a CIDR range.
 * Works for both IPv4 and IPv6.
 */
function ipInCidr(string $ip, string $cidr): bool {
    $parts = explode('/', $cidr, 2);
    if (count($parts) !== 2) return false;

    $subnet = $parts[0];
    $bits   = (int)$parts[1];

    $ipBin    = @inet_pton($ip);
    $subnetBin = @inet_pton($subnet);

    if ($ipBin === false || $subnetBin === false) return false;
    if (strlen($ipBin) !== strlen($subnetBin)) return false; // IPv4 vs IPv6 mismatch

    $totalBits = strlen($ipBin) * 8;
    if ($bits < 0 || $bits > $totalBits) return false;

    // Build a bitmask of $bits leading 1s
    $mask = str_repeat("\xff", intdiv($bits, 8));
    $remainder = $bits % 8;
    if ($remainder > 0) {
        $mask .= chr(0xff << (8 - $remainder) & 0xff);
    }
    $mask = str_pad($mask, strlen($ipBin), "\x00");

    return ($ipBin & $mask) === ($subnetBin & $mask);
}

/**
 * Determine whether the current request was made over HTTPS.
 *
 * Checks the direct $_SERVER['HTTPS'] variable first.  When the app
 * sits behind a TLS-terminating reverse proxy (Cloudflare, ALB, Nginx),
 * also checks X-Forwarded-Proto — but ONLY when isTrustedProxy() is true,
 * so the header cannot be spoofed by arbitrary clients.
 */
function isRequestSecure(): bool {
    // Direct HTTPS termination on this server
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        return true;
    }

    // Behind a trusted TLS-terminating proxy
    if (isTrustedProxy()) {
        $proto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
        if (strtolower($proto) === 'https') {
            return true;
        }
    }

    return false;
}

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
if (isRequestSecure()) {
    ini_set('session.cookie_secure', 1);
}

// Base path configuration
define('BASE_PATH', __DIR__);
define('UPLOADS_PATH', BASE_PATH . '/uploads');
// Backups are stored outside the document root by default to prevent
// accidental exposure via web server misconfiguration.
// Override via BACKUPS_PATH env var if needed.
define('BACKUPS_PATH', getenv('BACKUPS_PATH') ?: dirname(BASE_PATH) . '/backups');

// MySQL Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'business_cms');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

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
 * Resolve the real client IP, respecting trusted proxy configuration.
 *
 * Only trusts X-Forwarded-For when isTrustedProxy() confirms that
 * REMOTE_ADDR is in the configured allowlist.  This prevents IP
 * spoofing when the app is directly internet-exposed.
 */
function getClientIp(): string {
    static $ip = null;
    if ($ip !== null) return $ip;

    if (isTrustedProxy() && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Take the left-most IP (original client) from the chain
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $clientIp = trim($parts[0]);
        if (filter_var($clientIp, FILTER_VALIDATE_IP)) {
            $ip = $clientIp;
            return $ip;
        }
    }
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return $ip;
}

/**
 * Get database connection (singleton)
 *
 * Connection-only — no schema DDL is executed here.
 * Migrations must be run explicitly via `php cli/migrate.php`
 * as a deploy step before routing traffic to the new code.
 */
function getDB(): PDO {
    static $db = null;
    if ($db === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $db = new PDO($dsn, DB_USER, DB_PASS);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }
    return $db;
}

/**
 * Run schema initialisation / migrations exactly once per process,
 * protected by a MySQL advisory lock so concurrent workers never
 * execute DDL simultaneously.
 */
function ensureMigrations(PDO $db): void {
    static $done = false;
    if ($done) return;
    $done = true;

    // Acquire an advisory lock (non-blocking attempt first, then blocking with timeout).
    // Lock name is scoped to this database so different apps on the same server don't collide.
    $lockName = 'cms_schema_migration_' . DB_NAME;
    $lockStmt = $db->prepare("SELECT GET_LOCK(?, 10)");
    $lockStmt->execute([$lockName]);
    $acquired = (int)$lockStmt->fetchColumn();
    if (!$acquired) {
        // Another process is running migrations — do not serve traffic against
        // a potentially inconsistent schema. Return 503 so load balancers and
        // clients retry after the migration completes.
        error_log('[MIGRATION] Could not acquire advisory lock — returning 503');
        http_response_code(503);
        header('Retry-After: 10');
        echo 'Service temporarily unavailable — schema migration in progress.';
        exit;
    }

    try {
        // Check if database has been initialized (settings table exists)
        $initialized = false;
        try {
            $db->query('SELECT 1 FROM settings LIMIT 1');
            $initialized = true;
        } catch (PDOException $e) {
            // Table doesn't exist — needs initialization
        }

        if (!$initialized) {
            initializeDatabase($db);
        } else {
            migrateDatabase($db);
        }

        // Run file-based migrations (for both new and existing DBs)
        require_once BASE_PATH . '/includes/migrations.php';
        runMigrations($db);
    } finally {
        $db->prepare("SELECT RELEASE_LOCK(?)")->execute([$lockName]);
    }
}

/**
 * Initialize database schema
 */
function initializeDatabase(PDO $db): void {
    $schema = file_get_contents(BASE_PATH . '/database/schema.sql');
    // Execute each statement separately for MySQL
    $statements = array_filter(array_map('trim', explode(';', $schema)));
    foreach ($statements as $statement) {
        if ($statement) {
            $db->exec($statement);
        }
    }

    // Run seeder
    require_once BASE_PATH . '/database/seed.php';
    seedDatabase($db);
}

/**
 * Run database migrations for existing installations.
 * Called under advisory lock via ensureMigrations().
 *
 * Uses schema introspection (INFORMATION_SCHEMA) to check column/table
 * existence before executing DDL. This is locale-independent and avoids
 * fragile error-message string matching.
 */
function migrateDatabase(PDO $db): void {
    // Load introspection helpers from migrations module
    require_once BASE_PATH . '/includes/migrations.php';

    // Check if products table has the new columns
    if (!columnExists($db, 'products', 'specifications')) {
        $db->exec('ALTER TABLE products ADD COLUMN specifications TEXT');
    }
    if (!columnExists($db, 'products', 'features')) {
        $db->exec('ALTER TABLE products ADD COLUMN features TEXT');
    }
    if (!columnExists($db, 'products', 'price_note')) {
        $db->exec('ALTER TABLE products ADD COLUMN price_note TEXT');
    }

    // Check if product_categories has icon column
    if (!columnExists($db, 'product_categories', 'icon')) {
        $db->exec("ALTER TABLE product_categories ADD COLUMN icon VARCHAR(100) DEFAULT 'fa-tag'");
    }

    // Add e-commerce columns to products
    if (!columnExists($db, 'products', 'product_type')) {
        $db->exec("ALTER TABLE products ADD COLUMN product_type VARCHAR(50) DEFAULT 'physical'");
    }
    if (!columnExists($db, 'products', 'download_file')) {
        $db->exec('ALTER TABLE products ADD COLUMN download_file TEXT');
    }
    if (!columnExists($db, 'products', 'download_limit')) {
        $db->exec('ALTER TABLE products ADD COLUMN download_limit INT DEFAULT 0');
    }
    if (!columnExists($db, 'products', 'download_expiry_hours')) {
        $db->exec('ALTER TABLE products ADD COLUMN download_expiry_hours INT DEFAULT 72');
    }

    // Create orders table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_number VARCHAR(100) UNIQUE NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50),
        shipping_address TEXT,
        subtotal DECIMAL(10,2) DEFAULT 0,
        tax DECIMAL(10,2) DEFAULT 0,
        total DECIMAL(10,2) DEFAULT 0,
        payment_method VARCHAR(100),
        payment_id VARCHAR(255),
        payment_status VARCHAR(50) DEFAULT 'pending',
        order_status VARCHAR(50) DEFAULT 'pending',
        notes TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create order_items table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        product_id INT,
        product_name VARCHAR(255) NOT NULL,
        product_type VARCHAR(50) DEFAULT 'physical',
        quantity INT DEFAULT 1,
        unit_price DECIMAL(10,2) DEFAULT 0,
        total_price DECIMAL(10,2) DEFAULT 0,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Create download_tokens table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS download_tokens (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        order_item_id INT NOT NULL,
        product_id INT NOT NULL,
        token VARCHAR(255) UNIQUE NOT NULL,
        download_count INT DEFAULT 0,
        max_downloads INT DEFAULT 0,
        expires_at DATETIME,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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
        // Beta-readiness settings (proxy, mail, webhooks)
        ['trusted_proxy_enabled', '0'],
        ['trusted_proxy_ips',   ''],
        ['site_url',            ''],
        ['mail_from_address',   ''],
        ['mail_from_name',      ''],
        ['company_domain',      ''],
        ['square_webhook_url',  ''],
    ];
    $checkStmt = $db->prepare('SELECT COUNT(*) FROM settings WHERE `key` = ?');
    $insertStmt = $db->prepare('INSERT INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?)');
    foreach ($storeConfigDefaults as [$sKey, $sVal]) {
        $checkStmt->execute([$sKey]);
        if ((int)$checkStmt->fetchColumn() === 0) {
            $insertStmt->execute([$sKey, $sVal, 'text']);
        }
    }

    // Migrate containers into products if containers table still exists
    $tables = $db->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'containers'")->fetchColumn();
    if ($tables > 0) {
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
        $insertStmt = $db->prepare('INSERT IGNORE INTO products (category_id, name, slug, description, image, price, unit, specifications, features, price_note, is_visible, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
        foreach ($containers as $c) {
            $slug = trim(preg_replace('/-+/', '-', preg_replace('/[^a-z0-9-]/', '-', strtolower($c['name']))), '-');
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
function &_getSettingCacheRef(): array {
    static $cache = [];
    return $cache;
}

function getSetting(string $key, string $default = ''): string {
    $cache = &_getSettingCacheRef();
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT `value` FROM settings WHERE `key` = ?');
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
        $stmt = $db->prepare('INSERT INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `type` = VALUES(`type`)');
        $result = $stmt->execute([$key, $value, $type]);
        if ($result) {
            // Invalidate the in-process cache so subsequent reads in the same
            // request see the updated value (important for security toggles).
            invalidateSettingCache($key, $value);
        }
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Invalidate the getSetting() static cache for a specific key.
 */
function invalidateSettingCache(string $key, ?string $newValue = null): void {
    $cache = &_getSettingCacheRef();
    if ($newValue !== null) {
        $cache[$key] = $newValue;
    } else {
        unset($cache[$key]);
    }
}

/**
 * Alias for updateSetting() — used throughout admin panel
 */
function setSetting(string $key, $value, string $type = 'text'): bool {
    return updateSetting($key, (string)$value, $type);
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
 *
 * Uses DOM-based parsing instead of regex to avoid bypass-prone pattern matching.
 * The DOM parser handles malformed markup, entity encoding, and browser parser
 * edge cases that regex sanitizers historically miss.
 */
function sanitizeHtml(?string $html): string {
    if ($html === null || trim($html) === '') return '';

    // Allowed tags and their permitted attributes
    $allowedTags = [
        'p' => ['class', 'style'],
        'br' => [],
        'strong' => [],
        'b' => [],
        'em' => [],
        'i' => ['class'],  // class for icon fonts like Font Awesome
        'u' => [],
        'ul' => ['class'],
        'ol' => ['class', 'start', 'type'],
        'li' => ['class'],
        'h1' => ['class', 'id'],
        'h2' => ['class', 'id'],
        'h3' => ['class', 'id'],
        'h4' => ['class', 'id'],
        'h5' => ['class', 'id'],
        'h6' => ['class', 'id'],
        'a' => ['href', 'title', 'target', 'rel', 'class'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'class', 'loading'],
        'blockquote' => ['class'],
        'hr' => [],
        'span' => ['class', 'style'],
        'div' => ['class', 'style', 'id'],
        'table' => ['class'],
        'thead' => [],
        'tbody' => [],
        'tr' => ['class'],
        'th' => ['class', 'colspan', 'rowspan'],
        'td' => ['class', 'colspan', 'rowspan'],
        'figure' => ['class'],
        'figcaption' => [],
        'pre' => ['class'],
        'code' => ['class'],
    ];

    // URI schemes allowed in href/src attributes
    $allowedSchemes = ['http', 'https', 'mailto', 'tel', ''];

    // Parse the HTML fragment via DOMDocument
    $dom = new DOMDocument('1.0', 'UTF-8');
    // Suppress warnings for malformed HTML; wrap in a root element
    $wrapped = '<div>' . $html . '</div>';
    @$dom->loadHTML(
        '<?xml encoding="UTF-8"><body>' . $wrapped . '</body>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
    );

    // Walk the DOM and sanitize
    _sanitizeDomNode($dom->documentElement, $allowedTags, $allowedSchemes, $dom);

    // Extract the inner HTML of our wrapper div
    $body = $dom->getElementsByTagName('body')->item(0);
    if (!$body) return '';

    // Find our wrapper div
    $wrapper = null;
    foreach ($body->childNodes as $child) {
        if ($child->nodeType === XML_ELEMENT_NODE && $child->nodeName === 'div') {
            $wrapper = $child;
            break;
        }
    }
    if (!$wrapper) return '';

    $output = '';
    foreach ($wrapper->childNodes as $child) {
        $output .= $dom->saveHTML($child);
    }
    return $output;
}

/**
 * Recursively sanitize a DOM node: remove disallowed elements/attributes,
 * neutralize dangerous URI schemes.
 */
function _sanitizeDomNode(DOMNode $node, array $allowedTags, array $allowedSchemes, DOMDocument $dom): void {
    if ($node->nodeType === XML_TEXT_NODE || $node->nodeType === XML_CDATA_SECTION_NODE) {
        return;
    }

    // Process children first (collect into array to avoid mutation issues)
    $children = [];
    if ($node->hasChildNodes()) {
        foreach ($node->childNodes as $child) {
            $children[] = $child;
        }
    }
    foreach ($children as $child) {
        _sanitizeDomNode($child, $allowedTags, $allowedSchemes, $dom);
    }

    // Only filter element nodes; skip the root/body/wrapper structural nodes
    if ($node->nodeType !== XML_ELEMENT_NODE) return;
    $tagName = strtolower($node->nodeName);

    // Allow structural nodes used by the parser
    if (in_array($tagName, ['html', 'body', 'div'], true) && $node->parentNode && $node->parentNode->nodeName === 'body') {
        return;
    }
    if (in_array($tagName, ['html', 'body'], true)) {
        return;
    }

    if (!isset($allowedTags[$tagName])) {
        // Replace disallowed element with its children (unwrap)
        $parent = $node->parentNode;
        if ($parent) {
            while ($node->firstChild) {
                $parent->insertBefore($node->firstChild, $node);
            }
            $parent->removeChild($node);
        }
        return;
    }

    // Filter attributes
    $allowedAttrs = $allowedTags[$tagName];
    $attrsToRemove = [];
    if ($node->hasAttributes()) {
        foreach ($node->attributes as $attr) {
            $attrName = strtolower($attr->name);
            // Remove any event handler attributes (on*)
            if (str_starts_with($attrName, 'on')) {
                $attrsToRemove[] = $attr->name;
                continue;
            }
            if (!in_array($attrName, $allowedAttrs, true)) {
                $attrsToRemove[] = $attr->name;
                continue;
            }
            // Validate URI attributes
            if (in_array($attrName, ['href', 'src', 'action'], true)) {
                $val = $attr->value;
                $decoded = html_entity_decode($val, ENT_QUOTES, 'UTF-8');
                // Strip whitespace/control chars for scheme check
                $stripped = preg_replace('/[\s\x00-\x1f]+/', '', $decoded);
                // Extract scheme
                if (preg_match('/^([a-zA-Z][a-zA-Z0-9+\-.]*):/', $stripped, $m)) {
                    $scheme = strtolower($m[1]);
                    if (!in_array($scheme, $allowedSchemes, true)) {
                        $attrsToRemove[] = $attr->name;
                        continue;
                    }
                }
            }
            // Sanitize style attribute — only allow safe CSS properties
            if ($attrName === 'style') {
                $attr->value = _sanitizeCssStyle($attr->value);
            }
        }
    }
    foreach ($attrsToRemove as $attrName) {
        $node->removeAttribute($attrName);
    }
}

/**
 * Sanitize inline CSS style values — allow only safe, visual properties.
 * Strips expression(), url(), and other potentially dangerous CSS.
 */
function _sanitizeCssStyle(string $style): string {
    $safeProperties = [
        'color', 'background-color', 'background', 'font-size', 'font-weight',
        'font-style', 'text-align', 'text-decoration', 'line-height',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'border', 'border-radius', 'width', 'max-width', 'height', 'max-height',
        'display', 'float', 'clear', 'vertical-align', 'opacity',
        'list-style', 'list-style-type', 'white-space', 'overflow',
    ];

    $declarations = explode(';', $style);
    $safe = [];
    foreach ($declarations as $decl) {
        $decl = trim($decl);
        if ($decl === '') continue;
        $parts = explode(':', $decl, 2);
        if (count($parts) !== 2) continue;
        $prop = strtolower(trim($parts[0]));
        $val = trim($parts[1]);
        if (!in_array($prop, $safeProperties, true)) continue;
        // Block expression(), url(), and similar dangerous CSS values
        $valLower = strtolower($val);
        if (preg_match('/expression\s*\(|url\s*\(|javascript:|import/i', $valLower)) continue;
        $safe[] = $prop . ': ' . $val;
    }
    return implode('; ', $safe);
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
 * Build the canonical absolute base URL for external-facing links
 * (payment callbacks, webhook URLs, email links, etc.).
 *
 * Priority:
 *   1. Explicit `site_url` setting (most reliable for production).
 *   2. Auto-detect from request using proxy-aware scheme/host.
 *
 * Always returns a URL without a trailing slash.
 */
function getCanonicalBaseUrl(): string {
    static $cached = null;
    if ($cached !== null) return $cached;

    // 1. Prefer explicit setting — avoids all proxy-detection issues
    try {
        $siteUrl = getSetting('site_url', '');
    } catch (\Throwable $e) {
        $siteUrl = '';
    }

    if ($siteUrl !== '') {
        $cached = rtrim($siteUrl, '/');
        return $cached;
    }

    // 2. Auto-detect from request with host validation
    $scheme = isRequestSecure() ? 'https' : 'http';

    // Use X-Forwarded-Host when behind a trusted proxy, otherwise HTTP_HOST
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    if (isTrustedProxy() && !empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $host = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_HOST'])[0]);
    }

    // Validate host against allowed_hosts setting to prevent host-header poisoning.
    // If allowed_hosts is configured, reject unrecognized hosts.
    try {
        $allowedHosts = getSetting('allowed_hosts', '');
    } catch (\Throwable $e) {
        $allowedHosts = '';
    }
    if ($allowedHosts !== '') {
        $hostList = array_map('trim', explode(',', $allowedHosts));
        // Compare host without port for matching
        $hostWithoutPort = strtolower(explode(':', $host)[0]);
        if (!in_array($hostWithoutPort, array_map('strtolower', $hostList), true)) {
            error_log('[SECURITY] Rejected unrecognized Host header: ' . $host);
            $cached = $scheme . '://' . $hostList[0] . BASE_URL;
            return $cached;
        }
    }

    $cached = $scheme . '://' . $host . BASE_URL;
    return $cached;
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

/**
 * Safely redirect to the HTTP referrer if it belongs to this site.
 * Uses strict parse_url() host comparison to prevent open redirects.
 * Falls back to $fallback if referrer is missing or from a different host.
 */
function redirectToReferrer(string $fallback): void {
    $referrer = $_SERVER['HTTP_REFERER'] ?? '';
    if ($referrer) {
        $parsed = parse_url($referrer);
        if (isset($parsed['host']) && $parsed['host'] === ($_SERVER['HTTP_HOST'] ?? '')) {
            while (ob_get_level()) ob_end_clean();
            header('Location: ' . $referrer);
            exit;
        }
    }
    redirect($fallback);
}
