<?php
/**
 * Migration: Create plugin_settings table and add active_plugins setting.
 *
 * Part of the Plugin/Extension System (Phase 1).
 */
return function (PDO $db) {
    // Plugin settings storage — each plugin can persist key-value config
    if (!tableExists($db, 'plugin_settings')) {
        $db->exec("CREATE TABLE plugin_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plugin_name VARCHAR(255) NOT NULL,
            `key` VARCHAR(255) NOT NULL,
            `value` TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_plugin_setting (plugin_name, `key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Seed the active_plugins setting if not present
    $stmt = $db->prepare('SELECT COUNT(*) FROM settings WHERE `key` = ?');
    $stmt->execute(['active_plugins']);
    if ((int)$stmt->fetchColumn() === 0) {
        $db->prepare('INSERT INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?)')->execute(['active_plugins', '[]', 'text']);
    }
};
