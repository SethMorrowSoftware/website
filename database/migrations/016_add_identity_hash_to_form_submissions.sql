-- Add identity_hash column to form_submissions for composite rate limiting
ALTER TABLE form_submissions ADD COLUMN identity_hash VARCHAR(64) DEFAULT '' AFTER form_type;
CREATE INDEX idx_form_submissions_identity ON form_submissions (identity_hash, form_type, created_at);
