<?php
/**
 * Migration: Seed i18n settings.
 *
 * Sets the default locale to 'en' (English).
 * The i18n system reads this from the settings table at runtime.
 */
return function (PDO $db) {
    // Seed locale setting if not already set
    $stmt = $db->prepare("SELECT COUNT(*) FROM settings WHERE setting_key = 'locale'");
    $stmt->execute();
    if ((int)$stmt->fetchColumn() === 0) {
        $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('locale', 'en')")->execute();
    }
};
