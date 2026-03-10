#!/usr/bin/env php
<?php
/**
 * CLI Migration Runner
 *
 * Run database schema migrations as an explicit deploy step.
 * Usage: php cli/migrate.php
 *
 * This script replaces the previous behaviour of running migrations
 * inside the web request path (getDB → ensureMigrations).  It should
 * be executed during deployment, before routing traffic to the new code.
 *
 * Exit codes:
 *   0 = success (or already up-to-date)
 *   1 = migration error
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo 'This script must be run from the command line.';
    exit(1);
}

// Bootstrap the application config (defines constants, getDB(), etc.)
require_once __DIR__ . '/../config.php';

echo "Starting database migrations...\n";

try {
    $db = getDB();
} catch (Exception $e) {
    fwrite(STDERR, "ERROR: Could not connect to database: " . $e->getMessage() . "\n");
    exit(1);
}

// Acquire advisory lock (same mechanism as the old ensureMigrations)
$lockName = 'cms_schema_migration_' . DB_NAME;
$lockStmt = $db->prepare("SELECT GET_LOCK(?, 30)");
$lockStmt->execute([$lockName]);
$acquired = (int)$lockStmt->fetchColumn();

if (!$acquired) {
    fwrite(STDERR, "ERROR: Could not acquire migration lock — another migration may be running.\n");
    exit(1);
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
        echo "Initializing database schema...\n";
        initializeDatabase($db);
        echo "Schema initialized.\n";
    } else {
        echo "Running inline migrations...\n";
        migrateDatabase($db);
        echo "Inline migrations complete.\n";
    }

    // Run file-based migrations
    require_once BASE_PATH . '/includes/migrations.php';
    echo "Running file-based migrations...\n";
    runMigrations($db);
    echo "File-based migrations complete.\n";

    echo "All migrations completed successfully.\n";
} catch (Exception $e) {
    fwrite(STDERR, "MIGRATION ERROR: " . $e->getMessage() . "\n");
    exit(1);
} finally {
    $db->prepare("SELECT RELEASE_LOCK(?)")->execute([$lockName]);
}

exit(0);
