<?php
/**
 * Migration: Add form submission rate limiting table
 */
return function(PDO $db) {
    $db->exec('CREATE TABLE IF NOT EXISTS form_submissions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL,
        form_type TEXT NOT NULL,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_form_submissions_ip_type ON form_submissions(ip_address, form_type, submitted_at)');
};
