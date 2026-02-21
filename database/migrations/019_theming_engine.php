<?php
/**
 * Migration: Add active_theme setting.
 *
 * Part of the Theming Engine (Phase 1).
 */
return function (PDO $db) {
    // Seed the active_theme setting if not present
    $stmt = $db->prepare('SELECT COUNT(*) FROM settings WHERE `key` = ?');
    $stmt->execute(['active_theme']);
    if ((int)$stmt->fetchColumn() === 0) {
        $db->prepare('INSERT INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?)')->execute(['active_theme', 'default', 'text']);
    }
};
