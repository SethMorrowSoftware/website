<?php
// Add identity_hash column to form_submissions — schema-checked migration
return function (PDO $db) {
    if (!columnExists($db, 'form_submissions', 'identity_hash')) {
        $db->exec("ALTER TABLE form_submissions ADD COLUMN identity_hash VARCHAR(64) DEFAULT '' AFTER form_type");
    }

    if (!indexExists($db, 'form_submissions', 'idx_form_submissions_identity')) {
        $db->exec('CREATE INDEX idx_form_submissions_identity ON form_submissions (identity_hash, form_type, created_at)');
    }
};
