<?php
/**
 * Backup, Export & GDPR Compliance Module
 *
 * Provides:
 * - Database backup via SQL dump
 * - File backup (uploads directory)
 * - Backup retention management
 * - GDPR data export (customer profile, orders, reviews)
 * - GDPR data deletion (anonymize orders, delete personal data)
 * - Cookie consent banner settings
 */

// ============================================================
// Database Backup
// ============================================================

/**
 * Create a database backup.
 *
 * Uses PHP-based SQL dump (no shell access required).
 *
 * @return string|false Backup file path, or false on failure
 */
function createDatabaseBackup(): string|false {
    $backupDir = BACKUPS_PATH;
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
        file_put_contents($backupDir . '/.htaccess', "Deny from all\n");
    }

    $filename = 'db-backup-' . date('Y-m-d-His') . '.sql';
    $filepath = $backupDir . '/' . $filename;

    try {
        $db = getDB();
        $handle = fopen($filepath, 'w');
        if (!$handle) return false;

        fwrite($handle, "-- Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n");

        // Get all tables
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

        foreach ($tables as $table) {
            // Table structure
            $create = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            fwrite($handle, "\nDROP TABLE IF EXISTS `$table`;\n");
            fwrite($handle, $create[1] . ";\n\n");

            // Table data
            $rows = $db->query("SELECT * FROM `$table`");
            while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
                $values = array_map(function ($val) use ($db) {
                    return $val === null ? 'NULL' : $db->quote($val);
                }, $row);
                fwrite($handle, "INSERT INTO `$table` VALUES (" . implode(',', $values) . ");\n");
            }
        }

        fclose($handle);

        // Record backup
        $db->prepare("INSERT INTO backups (filename, type, file_size) VALUES (?, 'database', ?)")
            ->execute([$filename, filesize($filepath)]);

        // Enforce retention
        enforceBackupRetention();

        return $filepath;
    } catch (Exception $e) {
        error_log("Backup failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Create a file backup (uploads directory).
 *
 * @return string|false Backup file path, or false on failure
 */
function createFileBackup(): string|false {
    $backupDir = BACKUPS_PATH;
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
        file_put_contents($backupDir . '/.htaccess', "Deny from all\n");
    }

    $uploadsDir = BASE_PATH . '/uploads';
    if (!is_dir($uploadsDir)) return false;

    $filename = 'files-backup-' . date('Y-m-d-His') . '.zip';
    $filepath = $backupDir . '/' . $filename;

    try {
        if (!class_exists('ZipArchive')) return false;

        $zip = new ZipArchive();
        if ($zip->open($filepath, ZipArchive::CREATE) !== true) return false;

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploadsDir));
        foreach ($files as $file) {
            if ($file->isFile()) {
                $relativePath = 'uploads/' . substr($file->getRealPath(), strlen($uploadsDir) + 1);
                $zip->addFile($file->getRealPath(), $relativePath);
            }
        }
        $zip->close();

        $db = getDB();
        $db->prepare("INSERT INTO backups (filename, type, file_size) VALUES (?, 'files', ?)")
            ->execute([$filename, filesize($filepath)]);

        enforceBackupRetention();
        return $filepath;
    } catch (Exception $e) {
        error_log("File backup failed: " . $e->getMessage());
        return false;
    }
}

/**
 * List all backups.
 */
function getAllBackups(): array {
    try {
        $db = getDB();
        return $db->query("SELECT * FROM backups ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Delete a backup.
 */
function deleteBackup(int $id): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT filename FROM backups WHERE id = ?");
        $stmt->execute([$id]);
        $filename = $stmt->fetchColumn();

        if ($filename) {
            $filepath = BACKUPS_PATH . '/' . $filename;
            if (file_exists($filepath)) unlink($filepath);
        }

        return $db->prepare("DELETE FROM backups WHERE id = ?")->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Enforce backup retention (delete old backups beyond the limit).
 */
function enforceBackupRetention(): void {
    $maxBackups = (int)getSetting('backup_retention_count', '10');
    if ($maxBackups <= 0) return;

    try {
        $db = getDB();
        $backups = $db->query("SELECT id, filename FROM backups ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

        if (count($backups) > $maxBackups) {
            $toDelete = array_slice($backups, $maxBackups);
            foreach ($toDelete as $b) {
                deleteBackup($b['id']);
            }
        }
    } catch (Exception $e) {}
}

// ============================================================
// GDPR Data Export
// ============================================================

/**
 * Export all data for a customer (GDPR Article 15/20).
 *
 * @return array Structured customer data
 */
function exportCustomerData(int $customerId): array {
    $db = getDB();

    // Profile
    $stmt = $db->prepare("SELECT id, email, first_name, last_name, phone, default_shipping_address, created_at, last_login FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $profile = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$profile) return [];

    // Orders
    $stmt = $db->prepare("SELECT order_number, subtotal, tax, total, payment_method, order_status, shipping_address, created_at FROM orders WHERE customer_id = ? AND deleted_at IS NULL ORDER BY created_at DESC");
    $stmt->execute([$customerId]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Reviews
    $stmt = $db->prepare("SELECT r.rating, r.title, r.body, r.created_at, p.name as product_name FROM reviews r LEFT JOIN products p ON r.product_id = p.id WHERE r.customer_id = ? ORDER BY r.created_at DESC");
    $stmt->execute([$customerId]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Wishlist
    $wishlist = [];
    try {
        $stmt = $db->prepare("SELECT p.name, p.price, w.created_at FROM wishlists w JOIN products p ON w.product_id = p.id WHERE w.customer_id = ?");
        $stmt->execute([$customerId]);
        $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}

    // Log the export request
    try {
        $db->prepare("INSERT INTO gdpr_requests (customer_id, request_type, status, completed_at) VALUES (?, 'export', 'completed', NOW())")->execute([$customerId]);
    } catch (Exception $e) {}

    return [
        'exported_at' => date('Y-m-d H:i:s'),
        'profile' => $profile,
        'orders' => $orders,
        'reviews' => $reviews,
        'wishlist' => $wishlist,
    ];
}

/**
 * Generate downloadable JSON of customer data.
 */
function downloadCustomerData(int $customerId): void {
    $data = exportCustomerData($customerId);
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="my-data-export.json"');
    header('Content-Length: ' . strlen($json));
    echo $json;
    exit;
}

// ============================================================
// GDPR Data Deletion
// ============================================================

/**
 * Delete/anonymize customer data (GDPR Article 17).
 *
 * Anonymizes orders (replaces personal info with "Deleted Customer")
 * but preserves order records for accounting. Deletes: account,
 * wishlist, reviews, contact submissions.
 */
function deleteCustomerData(int $customerId): bool {
    $db = null;
    try {
        $db = getDB();
        $db->beginTransaction();

        // Anonymize orders (preserve for accounting)
        $db->prepare("UPDATE orders SET customer_name = 'Deleted Customer', customer_email = 'deleted@removed.invalid', customer_phone = '', shipping_address = 'Removed', notes = '' WHERE customer_id = ?")->execute([$customerId]);
        $db->prepare("UPDATE orders SET customer_id = NULL WHERE customer_id = ?")->execute([$customerId]);

        // Delete reviews
        $db->prepare("DELETE FROM reviews WHERE customer_id = ?")->execute([$customerId]);

        // Delete wishlist
        try {
            $db->prepare("DELETE FROM wishlists WHERE customer_id = ?")->execute([$customerId]);
        } catch (Exception $e) {}

        // Delete loyalty data
        try {
            $db->prepare("DELETE FROM loyalty_transactions WHERE customer_id = ?")->execute([$customerId]);
            $db->prepare("DELETE FROM loyalty_points WHERE customer_id = ?")->execute([$customerId]);
        } catch (Exception $e) {}

        // Log deletion
        try {
            $db->prepare("INSERT INTO gdpr_requests (customer_id, request_type, status, completed_at) VALUES (?, 'delete', 'completed', NOW())")->execute([$customerId]);
        } catch (Exception $e) {}

        // Delete the customer account
        $db->prepare("DELETE FROM customers WHERE id = ?")->execute([$customerId]);

        $db->commit();
        return true;
    } catch (Exception $e) {
        if ($db && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("GDPR deletion failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Get all GDPR requests.
 */
function getGdprRequests(): array {
    try {
        $db = getDB();
        return $db->query("
            SELECT gr.*, c.first_name, c.last_name, c.email
            FROM gdpr_requests gr
            LEFT JOIN customers c ON gr.customer_id = c.id
            ORDER BY gr.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

// ============================================================
// Cookie Consent
// ============================================================

/**
 * Check if cookie consent banner should be shown.
 */
function shouldShowCookieConsent(): bool {
    if (getSetting('cookie_consent_enabled', '0') !== '1') return false;
    return !isset($_COOKIE['cookie_consent']);
}

/**
 * Render cookie consent banner HTML.
 */
function renderCookieConsentBanner(): string {
    if (!shouldShowCookieConsent()) return '';

    $privacyPage = getSetting('privacy_policy_page', '');
    $privacyLink = $privacyPage ? ' <a href="' . e(url('index.php?page=' . $privacyPage)) . '">Privacy Policy</a>' : '';

    return '
    <div id="cookieConsent" style="position:fixed;bottom:0;left:0;right:0;background:#1a1a1a;color:#fff;padding:1rem 2rem;z-index:9999;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;font-size:0.9rem;">
        <p style="margin:0;">We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies.' . $privacyLink . '</p>
        <button onclick="document.cookie=\'cookie_consent=accepted;max-age=31536000;path=/\';document.getElementById(\'cookieConsent\').remove();" style="background:#fff;color:#1a1a1a;border:none;padding:0.5rem 1.5rem;border-radius:4px;cursor:pointer;font-weight:600;white-space:nowrap;">Accept</button>
    </div>';
}
