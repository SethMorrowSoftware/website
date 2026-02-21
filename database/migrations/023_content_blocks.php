<?php
/**
 * Migration: Add block-based content support to pages.
 *
 * Adds a `blocks` JSON column to the pages table for storing structured
 * block content. Existing pages with HTML content are migrated to a single
 * "classic" block so they render identically without any manual conversion.
 */
return function (PDO $db) {
    // Add blocks column if not present
    if (!columnExists($db, 'pages', 'blocks')) {
        $db->exec('ALTER TABLE pages ADD COLUMN blocks JSON DEFAULT NULL AFTER content');

        // Migrate existing content into a single "classic" block
        $pages = $db->query("SELECT id, content FROM pages WHERE content IS NOT NULL AND content != ''")->fetchAll();
        $stmt = $db->prepare('UPDATE pages SET blocks = ? WHERE id = ?');
        foreach ($pages as $page) {
            $blocks = json_encode([
                [
                    'type' => 'classic',
                    'data' => ['content' => $page['content']],
                ],
            ]);
            $stmt->execute([$blocks, $page['id']]);
        }
    }
};
