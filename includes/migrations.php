<?php
/**
 * Database Migration System
 * Runs numbered SQL migration files automatically on each request.
 */

/**
 * Check if a column exists in a table (schema introspection).
 * Preferred over try/catch string matching for DDL idempotency.
 */
function columnExists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Check if a table exists in the current database.
 */
function tableExists(PDO $db, string $table): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Check if an index exists on a table.
 */
function indexExists(PDO $db, string $table, string $indexName): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS '
        . 'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $indexName]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Determine whether a PDOException represents a DDL-idempotent error
 * (table/column/index already exists, duplicate entry).
 *
 * Checks MySQL error codes first (locale-independent), then falls
 * back to message string matching for edge cases.
 */
function isIdempotentDdlError(PDOException $e): bool {
    // MySQL error codes for idempotent DDL failures:
    //   1050 = Table already exists
    //   1060 = Duplicate column name
    //   1061 = Duplicate key name (index)
    //   1062 = Duplicate entry (unique constraint)
    $code = (int)($e->errorInfo[1] ?? 0);
    if (in_array($code, [1050, 1060, 1061, 1062], true)) {
        return true;
    }

    // Fallback: string matching for non-standard drivers / error reporting
    $msg = $e->getMessage();
    return str_contains($msg, 'already exists')
        || str_contains($msg, 'Duplicate column')
        || str_contains($msg, 'Duplicate entry')
        || str_contains($msg, 'Duplicate key name');
}

function runMigrations(PDO $db): void {
    // Create migrations tracking table
    $db->exec('CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) UNIQUE NOT NULL,
        executed_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

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
                    // Strip SQL line comments before splitting on semicolons
                    $sql = preg_replace('/--.*$/m', '', $sql);
                    $statements = array_filter(array_map('trim', explode(';', $sql)));
                    foreach ($statements as $statement) {
                        if ($statement) {
                            $db->exec($statement);
                        }
                    }
                }
            }
            $stmt = $db->prepare('INSERT INTO migrations (filename) VALUES (?)');
            $stmt->execute([$filename]);
        } catch (PDOException $e) {
            // Check by error code first (locale-independent), then by message
            if (isIdempotentDdlError($e)) {
                try {
                    $db->prepare('INSERT IGNORE INTO migrations (filename) VALUES (?)')->execute([$filename]);
                } catch (Exception $ex) {}
            } else {
                // Log genuine errors; do NOT mark as executed so they can be retried
                error_log('[MIGRATION ERROR] ' . $filename . ': ' . $e->getMessage());
            }
        } catch (Exception $e) {
            error_log('[MIGRATION ERROR] ' . $filename . ': ' . $e->getMessage());
        }
    }
}
