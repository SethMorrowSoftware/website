<?php
/**
 * Database Migration System
 * Runs numbered SQL migration files automatically on each request.
 */

function runMigrations(PDO $db): void {
    // Create migrations tracking table
    $db->exec('CREATE TABLE IF NOT EXISTS migrations (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        filename TEXT UNIQUE NOT NULL,
        executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');

    $migrationsDir = BASE_PATH . '/database/migrations';
    if (!is_dir($migrationsDir)) return;

    // Get already-run migrations
    $executed = [];
    $stmt = $db->query('SELECT filename FROM migrations');
    while ($row = $stmt->fetch()) {
        $executed[] = $row['filename'];
    }

    // Find and run pending migrations in order (supports .php and .sql files)
    $phpFiles = glob($migrationsDir . '/*.php') ?: [];
    $sqlFiles = glob($migrationsDir . '/*.sql') ?: [];
    $files = array_merge($phpFiles, $sqlFiles);
    sort($files); // Ensures numerical order

    foreach ($files as $file) {
        $filename = basename($file);
        if (in_array($filename, $executed)) continue;

        try {
            if (str_ends_with($filename, '.php')) {
                // PHP migration: returns a closure that receives $db
                $migration = require $file;
                if (is_callable($migration)) {
                    $migration($db);
                }
            } elseif (str_ends_with($filename, '.sql')) {
                // SQL migration: execute each statement separately
                $sql = file_get_contents($file);
                if ($sql) {
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    foreach ($statements as $statement) {
                        if ($statement && !str_starts_with($statement, '--')) {
                            $db->exec($statement);
                        }
                    }
                }
            }
            $stmt = $db->prepare('INSERT INTO migrations (filename) VALUES (?)');
            $stmt->execute([$filename]);
        } catch (Exception $e) {
            error_log('[MIGRATION ERROR] ' . $filename . ': ' . $e->getMessage());
            // Mark as executed to avoid retrying failed migrations
            try {
                $stmt = $db->prepare('INSERT OR IGNORE INTO migrations (filename) VALUES (?)');
                $stmt->execute([$filename]);
            } catch (Exception $ex) {}
        }
    }
}
