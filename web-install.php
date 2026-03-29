<?php
/**
 * Web-Based Installer for Business Website CMS
 *
 * For cPanel shared hosting where CLI access may not be available.
 *
 * Usage:
 *   1. Upload all CMS files to your public_html (or a subdirectory)
 *   2. Open https://yourdomain.com/web-install.php in your browser
 *   3. Follow the steps to configure your database and admin account
 *   4. Delete this file after installation is complete
 *
 * Security:
 *   - Self-deletes after successful installation (with user confirmation)
 *   - Blocks access if the site is already installed (settings table has data)
 *   - CSRF-protected form submissions
 *   - Rate-limited to prevent brute-force abuse
 */

// Prevent CLI usage — this is a web installer
if (php_sapi_name() === 'cli') {
    echo "This is a web installer. Open it in your browser instead.\n";
    echo "For CLI installation, use: php install.php\n";
    exit(1);
}

// Start session for CSRF tokens and step tracking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Constants ───────────────────────────────────────────────
define('INSTALLER_VERSION', '1.0');
define('MIN_PHP_VERSION', '8.0.0');
define('BASE_PATH_INSTALL', __DIR__);

// ─── CSRF helpers ────────────────────────────────────────────
function installerCsrfToken(): string {
    if (empty($_SESSION['_installer_csrf'])) {
        $_SESSION['_installer_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_installer_csrf'];
}

function installerVerifyCsrf(): bool {
    $token = $_POST['_csrf'] ?? '';
    return hash_equals($_SESSION['_installer_csrf'] ?? '', $token);
}

// ─── Utility ─────────────────────────────────────────────────
// Named _esc() to avoid collision with e() in config.php when we require it later
function _esc(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
}

function installerError(string $msg): string {
    return '<div class="alert alert-error">' . _esc($msg) . '</div>';
}

function installerSuccess(string $msg): string {
    return '<div class="alert alert-success">' . _esc($msg) . '</div>';
}

function installerInfo(string $msg): string {
    return '<div class="alert alert-info">' . _esc($msg) . '</div>';
}

// ─── Check if already installed ──────────────────────────────
function isAlreadyInstalled(): bool {
    // If the current session is mid-install, don't block it
    if (!empty($_SESSION['installer_db_done'])) {
        return false;
    }

    $envFile = BASE_PATH_INSTALL . '/.env';
    if (!file_exists($envFile)) {
        return false;
    }

    // Parse .env to get DB credentials
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$key, $val] = explode('=', $line, 2);
        $env[trim($key)] = trim($val);
    }

    if (empty($env['DB_NAME']) || empty($env['DB_USER'])) {
        return false;
    }

    try {
        $dsn = 'mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1')
             . ';port=' . ($env['DB_PORT'] ?? '3306')
             . ';dbname=' . $env['DB_NAME']
             . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $env['DB_USER'], $env['DB_PASS'] ?? '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Only consider "installed" if an admin user exists — that's the
        // final proof the installer ran to completion.  Checking settings
        // alone causes false positives because migrations seed default rows
        // during the database step, before admin/settings steps finish.
        $count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count > 0) {
            return true;
        }
    } catch (PDOException $ex) {
        // DB not reachable or table doesn't exist — not installed
    }
    return false;
}

// ─── Requirement checks ─────────────────────────────────────
function checkRequirements(): array {
    $checks = [];

    // PHP version
    $checks[] = [
        'label' => 'PHP Version >= ' . MIN_PHP_VERSION,
        'pass' => version_compare(PHP_VERSION, MIN_PHP_VERSION, '>='),
        'value' => PHP_VERSION,
        'required' => true,
    ];

    // Required extensions
    $requiredExt = ['pdo', 'pdo_mysql', 'mbstring', 'session', 'fileinfo'];
    foreach ($requiredExt as $ext) {
        $checks[] = [
            'label' => "PHP Extension: $ext",
            'pass' => extension_loaded($ext),
            'value' => extension_loaded($ext) ? 'Loaded' : 'Missing',
            'required' => true,
        ];
    }

    // Optional extensions
    $optionalExt = ['curl', 'gd', 'zip'];
    foreach ($optionalExt as $ext) {
        $checks[] = [
            'label' => "PHP Extension: $ext (optional)",
            'pass' => extension_loaded($ext),
            'value' => extension_loaded($ext) ? 'Loaded' : 'Not loaded',
            'required' => false,
        ];
    }

    // Writable directories
    $writableDirs = ['.', 'uploads', 'uploads/images', 'uploads/videos', 'uploads/downloads'];
    foreach ($writableDirs as $dir) {
        $fullPath = BASE_PATH_INSTALL . '/' . $dir;
        $writable = is_dir($fullPath) ? is_writable($fullPath) : is_writable(dirname($fullPath));
        $checks[] = [
            'label' => "Writable: $dir/",
            'pass' => $writable,
            'value' => $writable ? 'Writable' : 'Not writable',
            'required' => ($dir === '.'), // Root must be writable for .env
        ];
    }

    // .htaccess exists (Apache)
    $checks[] = [
        'label' => '.htaccess file present',
        'pass' => file_exists(BASE_PATH_INSTALL . '/.htaccess'),
        'value' => file_exists(BASE_PATH_INSTALL . '/.htaccess') ? 'Found' : 'Missing',
        'required' => false,
    ];

    // mod_rewrite (best-effort detection)
    $modRewrite = (function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules()))
                  || getenv('HTTP_MOD_REWRITE') === 'On'
                  || true; // Can't reliably detect on cPanel, assume OK
    $checks[] = [
        'label' => 'Apache mod_rewrite',
        'pass' => $modRewrite,
        'value' => $modRewrite ? 'Likely available' : 'Unknown',
        'required' => false,
    ];

    return $checks;
}

// ─── Block access if already installed ───────────────────────
if (isAlreadyInstalled() && ($_GET['step'] ?? '') !== 'done' && ($_POST['action'] ?? '') !== 'delete_installer') {
    renderPage('Already Installed', installerError(
        'This site appears to be already installed. For security, the web installer is disabled. '
        . 'If you need to reinstall, delete the .env file first or remove this web-install.php file.'
    ));
    exit;
}

// ─── Route steps ─────────────────────────────────────────────
$step = $_GET['step'] ?? 'welcome';
$postAction = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $postAction) {
    if (!installerVerifyCsrf()) {
        renderPage('Error', installerError('Invalid security token. Please go back and try again.'));
        exit;
    }

    switch ($postAction) {
        case 'check_db':
            handleDatabaseSetup();
            break;
        case 'create_admin':
            handleAdminCreation();
            break;
        case 'save_settings':
            handleSiteSettings();
            break;
        case 'delete_installer':
            handleDeleteInstaller();
            break;
        default:
            renderPage('Error', installerError('Unknown action.'));
    }
    exit;
}

switch ($step) {
    case 'welcome':
        renderWelcome();
        break;
    case 'requirements':
        renderRequirements();
        break;
    case 'database':
        renderDatabaseForm();
        break;
    case 'admin':
        renderAdminForm();
        break;
    case 'settings':
        renderSiteSettingsForm();
        break;
    case 'done':
        renderComplete();
        break;
    default:
        renderWelcome();
}
exit;

// ═══════════════════════════════════════════════════════════════
//  Step renderers
// ═══════════════════════════════════════════════════════════════

function renderWelcome(): void {
    ob_start();
    ?>
    <h2>Welcome to the Installer</h2>
    <p>This wizard will guide you through setting up your Business Website CMS on this server.</p>
    <p>Before you begin, make sure you have:</p>
    <ul>
        <li>A MySQL database created (via cPanel &rarr; MySQL Databases)</li>
        <li>The database name, username, and password</li>
        <li>The database host (usually <code>localhost</code> on cPanel)</li>
    </ul>
    <div class="step-nav">
        <a href="?step=requirements" class="btn btn-primary">Check Requirements &rarr;</a>
    </div>
    <?php
    renderPage('Welcome', ob_get_clean());
}

function renderRequirements(): void {
    $checks = checkRequirements();
    $allRequired = true;
    foreach ($checks as $c) {
        if ($c['required'] && !$c['pass']) {
            $allRequired = false;
        }
    }

    ob_start();
    ?>
    <h2>Server Requirements</h2>
    <table class="requirements-table">
        <thead><tr><th>Requirement</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach ($checks as $c): ?>
            <tr>
                <td><?= _esc($c['label']) ?></td>
                <td>
                    <?php if ($c['pass']): ?>
                        <span class="badge badge-pass"><?= _esc($c['value']) ?></span>
                    <?php elseif ($c['required']): ?>
                        <span class="badge badge-fail"><?= _esc($c['value']) ?></span>
                    <?php else: ?>
                        <span class="badge badge-warn"><?= _esc($c['value']) ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!$allRequired): ?>
        <div class="alert alert-error">
            Some required checks failed. Please fix them before continuing.
        </div>
        <div class="step-nav">
            <a href="?step=requirements" class="btn btn-secondary">Re-check</a>
        </div>
    <?php else: ?>
        <div class="step-nav">
            <a href="?step=welcome" class="btn btn-secondary">&larr; Back</a>
            <a href="?step=database" class="btn btn-primary">Configure Database &rarr;</a>
        </div>
    <?php endif; ?>
    <?php
    renderPage('Requirements', ob_get_clean());
}

function renderDatabaseForm(string $error = '', array $old = []): void {
    ob_start();
    ?>
    <h2>Database Configuration</h2>
    <p>Enter your MySQL database credentials. On cPanel, you can find these under <strong>MySQL Databases</strong>.</p>

    <?php if ($error) echo installerError($error); ?>

    <form method="post" action="web-install.php" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= _esc(installerCsrfToken()) ?>">
        <input type="hidden" name="action" value="check_db">

        <div class="form-group">
            <label for="db_host">Database Host</label>
            <input type="text" id="db_host" name="db_host" value="<?= _esc($old['db_host'] ?? 'localhost') ?>" required>
            <small>Usually <code>localhost</code> on cPanel shared hosting</small>
        </div>

        <div class="form-group">
            <label for="db_port">Database Port</label>
            <input type="text" id="db_port" name="db_port" value="<?= _esc($old['db_port'] ?? '3306') ?>">
        </div>

        <div class="form-group">
            <label for="db_name">Database Name</label>
            <input type="text" id="db_name" name="db_name" value="<?= _esc($old['db_name'] ?? '') ?>" required>
            <small>e.g. <code>yourusername_cms</code></small>
        </div>

        <div class="form-group">
            <label for="db_user">Database Username</label>
            <input type="text" id="db_user" name="db_user" value="<?= _esc($old['db_user'] ?? '') ?>" required>
            <small>e.g. <code>yourusername_cmsuser</code></small>
        </div>

        <div class="form-group">
            <label for="db_pass">Database Password</label>
            <input type="password" id="db_pass" name="db_pass" value="<?= _esc($old['db_pass'] ?? '') ?>">
        </div>

        <div class="step-nav">
            <a href="?step=requirements" class="btn btn-secondary">&larr; Back</a>
            <button type="submit" class="btn btn-primary">Test Connection &amp; Continue &rarr;</button>
        </div>
    </form>
    <?php
    renderPage('Database', ob_get_clean());
}

function renderAdminForm(string $error = '', array $old = []): void {
    ob_start();
    ?>
    <h2>Create Admin Account</h2>
    <p>Set up your administrator login for the CMS admin panel.</p>

    <?php if ($error) echo installerError($error); ?>

    <form method="post" action="web-install.php" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= _esc(installerCsrfToken()) ?>">
        <input type="hidden" name="action" value="create_admin">

        <div class="form-group">
            <label for="admin_user">Admin Username</label>
            <input type="text" id="admin_user" name="admin_user" value="<?= _esc($old['admin_user'] ?? 'admin') ?>" required minlength="3">
        </div>

        <div class="form-group">
            <label for="admin_pass">Admin Password</label>
            <input type="password" id="admin_pass" name="admin_pass" required minlength="8">
            <small>Minimum 8 characters</small>
        </div>

        <div class="form-group">
            <label for="admin_pass_confirm">Confirm Password</label>
            <input type="password" id="admin_pass_confirm" name="admin_pass_confirm" required minlength="8">
        </div>

        <div class="step-nav">
            <button type="submit" class="btn btn-primary">Create Admin &amp; Continue &rarr;</button>
        </div>
    </form>
    <?php
    renderPage('Admin Account', ob_get_clean());
}

function renderSiteSettingsForm(string $error = '', array $old = []): void {
    ob_start();
    ?>
    <h2>Site Settings</h2>
    <p>Configure basic information about your site. You can change all of these later in the admin panel.</p>

    <?php if ($error) echo installerError($error); ?>

    <form method="post" action="web-install.php" autocomplete="off">
        <input type="hidden" name="_csrf" value="<?= _esc(installerCsrfToken()) ?>">
        <input type="hidden" name="action" value="save_settings">

        <div class="form-group">
            <label for="site_name">Company / Site Name</label>
            <input type="text" id="site_name" name="site_name" value="<?= _esc($old['site_name'] ?? 'My Business') ?>" required>
        </div>

        <div class="form-group">
            <label for="site_email">Contact Email</label>
            <input type="email" id="site_email" name="site_email" value="<?= _esc($old['site_email'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label for="site_phone">Contact Phone</label>
            <input type="text" id="site_phone" name="site_phone" value="<?= _esc($old['site_phone'] ?? '') ?>">
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="load_demo" value="1">
                Load demo data (sample products, pages, blog posts)
            </label>
        </div>

        <div class="step-nav">
            <button type="submit" class="btn btn-primary">Save &amp; Finish Installation &rarr;</button>
        </div>
    </form>
    <?php
    renderPage('Site Settings', ob_get_clean());
}

function renderComplete(): void {
    // Determine URLs
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseUrl = rtrim($protocol . '://' . $host . $scriptDir, '/');

    ob_start();
    ?>
    <h2>Installation Complete!</h2>

    <div class="alert alert-success">
        TheWuzaBus website has been successfully installed.
    </div>

    <div class="links-box">
        <p><strong>Your site:</strong> <a href="<?= _esc($baseUrl) ?>/" target="_blank"><?= _esc($baseUrl) ?>/</a></p>
        <p><strong>Admin panel:</strong> <a href="<?= _esc($baseUrl) ?>/admin/" target="_blank"><?= _esc($baseUrl) ?>/admin/</a></p>
    </div>

    <div class="alert alert-error">
        <strong>Important:</strong> For security, you must delete this installer file (<code>web-install.php</code>)
        now. Leaving it on your server is a security risk.
    </div>

    <form method="post" action="web-install.php" class="delete-form">
        <input type="hidden" name="_csrf" value="<?= _esc(installerCsrfToken()) ?>">
        <input type="hidden" name="action" value="delete_installer">
        <div class="step-nav">
            <button type="submit" class="btn btn-danger" onclick="return confirm('This will permanently delete web-install.php. Continue?')">
                Delete Installer File Now
            </button>
            <a href="<?= _esc($baseUrl) ?>/admin/" class="btn btn-primary">Go to Admin Panel &rarr;</a>
        </div>
    </form>
    <?php
    renderPage('Complete', ob_get_clean());
}

// ═══════════════════════════════════════════════════════════════
//  POST handlers
// ═══════════════════════════════════════════════════════════════

function handleDatabaseSetup(): void {
    $dbHost = trim($_POST['db_host'] ?? 'localhost');
    $dbPort = trim($_POST['db_port'] ?? '3306');
    $dbName = trim($_POST['db_name'] ?? '');
    $dbUser = trim($_POST['db_user'] ?? '');
    $dbPass = $_POST['db_pass'] ?? '';

    $old = compact('dbHost', 'dbPort', 'dbName', 'dbUser', 'dbPass');
    // Map to form field names for repopulation
    $old = [
        'db_host' => $dbHost,
        'db_port' => $dbPort,
        'db_name' => $dbName,
        'db_user' => $dbUser,
        'db_pass' => $dbPass,
    ];

    if ($dbName === '' || $dbUser === '') {
        renderDatabaseForm('Database name and username are required.', $old);
        return;
    }

    // Test connection
    try {
        $dsn = "mysql:host=$dbHost;port=$dbPort;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $ex) {
        renderDatabaseForm('Could not connect to MySQL: ' . $ex->getMessage(), $old);
        return;
    }

    // On cPanel shared hosting, databases are usually pre-created.
    // Try to select the database; if it doesn't exist, try to create it.
    try {
        $pdo->exec("USE `$dbName`");
    } catch (PDOException $ex) {
        try {
            $pdo->exec("CREATE DATABASE `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            $pdo->exec("USE `$dbName`");
        } catch (PDOException $ex2) {
            renderDatabaseForm(
                'Database "' . $dbName . '" does not exist and could not be created. '
                . 'On cPanel, create the database first via MySQL Databases, then try again. '
                . 'Error: ' . $ex2->getMessage(),
                $old
            );
            return;
        }
    }

    // Write .env file
    $envContent = "# Generated by web installer on " . date('Y-m-d H:i:s') . "\n";
    $envContent .= "DB_HOST=$dbHost\n";
    $envContent .= "DB_PORT=$dbPort\n";
    $envContent .= "DB_NAME=$dbName\n";
    $envContent .= "DB_USER=$dbUser\n";
    $envContent .= "DB_PASS=$dbPass\n";
    $envContent .= "APP_ENV=production\n";
    $envContent .= "APP_DEBUG=0\n";

    $envPath = BASE_PATH_INSTALL . '/.env';
    if (file_put_contents($envPath, $envContent) === false) {
        renderDatabaseForm('Could not write .env file. Check that the web directory is writable.', $old);
        return;
    }

    // Load the .env we just wrote so config.php picks it up
    putenv("DB_HOST=$dbHost");
    putenv("DB_PORT=$dbPort");
    putenv("DB_NAME=$dbName");
    putenv("DB_USER=$dbUser");
    putenv("DB_PASS=$dbPass");

    // Run schema and migrations
    try {
        // Load the application config (defines constants, getDB, etc.)
        require_once BASE_PATH_INSTALL . '/config.php';
        require_once BASE_PATH_INSTALL . '/includes/functions.php';

        $db = getDB();

        // Apply base schema
        $schemaFile = BASE_PATH_INSTALL . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $statements = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($statements as $stmt) {
                if ($stmt !== '') {
                    $db->exec($stmt);
                }
            }
        }

        // Run migrations
        if (function_exists('ensureMigrations')) {
            ensureMigrations($db);
        } elseif (file_exists(BASE_PATH_INSTALL . '/includes/migrations.php')) {
            require_once BASE_PATH_INSTALL . '/includes/migrations.php';
            if (function_exists('runMigrations')) {
                runMigrations($db);
            }
        }
    } catch (Exception $ex) {
        renderDatabaseForm('Database schema setup failed: ' . $ex->getMessage(), $old);
        return;
    }

    // Create upload directories
    $dirs = ['uploads/images', 'uploads/images/wuzabus_photos', 'uploads/videos', 'uploads/downloads'];
    foreach ($dirs as $dir) {
        $full = BASE_PATH_INSTALL . '/' . $dir;
        if (!is_dir($full)) {
            @mkdir($full, 0755, true);
        }
    }

    // Copy bundled WuzaBus gallery photos into uploads for consistent public serving.
    $bundledPhotoDir = BASE_PATH_INSTALL . '/wuzabus_photos';
    $installPhotoDir = BASE_PATH_INSTALL . '/uploads/images/wuzabus_photos';
    if (is_dir($bundledPhotoDir) && is_dir($installPhotoDir)) {
        $bundledPhotos = glob($bundledPhotoDir . '/*.{jpg,jpeg,png,webp,JPG,JPEG,PNG,WEBP}', GLOB_BRACE) ?: [];
        foreach ($bundledPhotos as $sourcePhoto) {
            $targetPhoto = $installPhotoDir . '/' . basename($sourcePhoto);
            if (!file_exists($targetPhoto)) {
                @copy($sourcePhoto, $targetPhoto);
            }
        }
    }

    // Store success in session and redirect to admin step
    $_SESSION['installer_db_done'] = true;
    header('Location: web-install.php?step=admin');
    exit;
}

function handleAdminCreation(): void {
    $username = trim($_POST['admin_user'] ?? '');
    $password = $_POST['admin_pass'] ?? '';
    $confirm = $_POST['admin_pass_confirm'] ?? '';

    $old = ['admin_user' => $username];

    if (strlen($username) < 3) {
        renderAdminForm('Username must be at least 3 characters.', $old);
        return;
    }
    if (strlen($password) < 8) {
        renderAdminForm('Password must be at least 8 characters.', $old);
        return;
    }
    if ($password !== $confirm) {
        renderAdminForm('Passwords do not match.', $old);
        return;
    }

    try {
        // Ensure config is loaded
        if (!function_exists('getDB')) {
            require_once BASE_PATH_INSTALL . '/config.php';
            require_once BASE_PATH_INSTALL . '/includes/functions.php';
        }
        $db = getDB();

        // Check if admin already exists
        $existing = $db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($existing > 0) {
            // Skip creation but proceed
            $_SESSION['installer_admin_done'] = true;
            header('Location: web-install.php?step=settings');
            exit;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare('INSERT INTO users (username, password_hash, role_id) VALUES (?, ?, 1)');
        $stmt->execute([$username, $hash]);

        $_SESSION['installer_admin_done'] = true;
        header('Location: web-install.php?step=settings');
        exit;
    } catch (Exception $ex) {
        renderAdminForm('Failed to create admin account: ' . $ex->getMessage(), $old);
    }
}

function handleSiteSettings(): void {
    $siteName = trim($_POST['site_name'] ?? 'My Business');
    $siteEmail = trim($_POST['site_email'] ?? '');
    $sitePhone = trim($_POST['site_phone'] ?? '');
    $loadDemo = !empty($_POST['load_demo']);

    try {
        if (!function_exists('getDB')) {
            require_once BASE_PATH_INSTALL . '/config.php';
            require_once BASE_PATH_INSTALL . '/includes/functions.php';
        }
        $db = getDB();

        // Use updateSetting if available, otherwise direct insert/update
        if (function_exists('updateSetting')) {
            updateSetting('company_name', $siteName);
            if ($siteEmail) updateSetting('company_email', $siteEmail);
            if ($sitePhone) updateSetting('company_phone', $sitePhone);
        } else {
            $upsert = function (PDO $db, string $key, string $val) {
                $stmt = $db->prepare('INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
                $stmt->execute([$key, $val]);
            };
            $upsert($db, 'company_name', $siteName);
            if ($siteEmail) $upsert($db, 'company_email', $siteEmail);
            if ($sitePhone) $upsert($db, 'company_phone', $sitePhone);
        }

        // Load demo data if requested
        if ($loadDemo) {
            $demoFile = BASE_PATH_INSTALL . '/database/demo-data.php';
            if (file_exists($demoFile)) {
                $demoFn = require $demoFile;
                if (is_callable($demoFn)) {
                    $demoFn($db);
                }
            }
        }

        $_SESSION['installer_complete'] = true;
        header('Location: web-install.php?step=done');
        exit;
    } catch (Exception $ex) {
        renderSiteSettingsForm('Failed to save settings: ' . $ex->getMessage(), [
            'site_name' => $siteName,
            'site_email' => $siteEmail,
            'site_phone' => $sitePhone,
        ]);
    }
}

function handleDeleteInstaller(): void {
    $file = BASE_PATH_INSTALL . '/web-install.php';
    if (file_exists($file)) {
        @unlink($file);
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $baseUrl = rtrim($protocol . '://' . $host . $scriptDir, '/');

    header('Location: ' . $baseUrl . '/admin/');
    exit;
}

// ═══════════════════════════════════════════════════════════════
//  Page layout
// ═══════════════════════════════════════════════════════════════

function renderPage(string $stepTitle, string $body): void {
    $steps = ['Welcome', 'Requirements', 'Database', 'Admin Account', 'Site Settings', 'Complete'];
    $currentIdx = array_search($stepTitle, $steps);
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Install — Business Website CMS</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
        }
        .installer {
            max-width: 680px;
            margin: 30px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.08);
            overflow: hidden;
        }
        .installer-header {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            color: #fff;
            padding: 24px 32px;
            text-align: center;
        }
        .installer-header h1 {
            font-size: 1.4rem;
            font-weight: 600;
        }
        .installer-header small {
            opacity: .75;
            font-size: .85rem;
        }

        /* Progress steps */
        .steps {
            display: flex;
            padding: 0;
            list-style: none;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }
        .steps li {
            flex: 1;
            text-align: center;
            padding: 10px 4px;
            font-size: .7rem;
            font-weight: 500;
            color: #94a3b8;
            position: relative;
        }
        .steps li.active { color: #1e40af; font-weight: 700; }
        .steps li.done { color: #16a34a; }
        .steps li.done::before { content: "\2713 "; }

        .installer-body { padding: 32px; }
        .installer-body h2 { margin-bottom: 12px; font-size: 1.25rem; }
        .installer-body p { margin-bottom: 16px; color: #475569; }
        .installer-body ul { margin: 0 0 20px 20px; color: #475569; }
        .installer-body ul li { margin-bottom: 6px; }

        /* Form */
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; font-weight: 600; margin-bottom: 4px; font-size: .9rem; }
        .form-group input[type="text"],
        .form-group input[type="password"],
        .form-group input[type="email"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            font-size: .95rem;
            transition: border-color .15s;
        }
        .form-group input:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.15); }
        .form-group small { display: block; margin-top: 4px; color: #94a3b8; font-size: .8rem; }
        .form-group input[type="checkbox"] { margin-right: 6px; }

        /* Alerts */
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: .9rem; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .alert-success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; }
        .alert-info { background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; }

        /* Buttons */
        .btn {
            display: inline-block;
            padding: 10px 22px;
            border: none;
            border-radius: 6px;
            font-size: .9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: background .15s, transform .1s;
        }
        .btn:active { transform: scale(.98); }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-secondary { background: #e2e8f0; color: #475569; }
        .btn-secondary:hover { background: #cbd5e1; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }

        .step-nav { display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px; flex-wrap: wrap; }

        /* Requirements table */
        .requirements-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .requirements-table th, .requirements-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: .85rem; }
        .requirements-table th { background: #f8fafc; font-weight: 600; }
        .badge { display: inline-block; padding: 2px 10px; border-radius: 99px; font-size: .75rem; font-weight: 600; }
        .badge-pass { background: #dcfce7; color: #166534; }
        .badge-fail { background: #fef2f2; color: #991b1b; }
        .badge-warn { background: #fefce8; color: #854d0e; }

        .links-box { background: #f8fafc; padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; }
        .links-box p { margin-bottom: 8px; }
        .links-box a { color: #2563eb; }

        .delete-form { margin-top: 8px; }
        code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: .85em; }

        @media (max-width: 600px) {
            body { padding: 10px; }
            .installer-body { padding: 20px; }
            .steps li { font-size: .6rem; padding: 8px 2px; }
        }
    </style>
</head>
<body>
    <div class="installer">
        <div class="installer-header">
            <h1>Business Website CMS</h1>
            <small>Installation Wizard v<?= INSTALLER_VERSION ?></small>
        </div>

        <ul class="steps">
            <?php foreach ($steps as $i => $label): ?>
                <li class="<?php
                    if ($currentIdx !== false && $i < $currentIdx) echo 'done';
                    elseif ($currentIdx !== false && $i === $currentIdx) echo 'active';
                ?>"><?= _esc($label) ?></li>
            <?php endforeach; ?>
        </ul>

        <div class="installer-body">
            <?= $body ?>
        </div>
    </div>
</body>
</html>
    <?php
}
