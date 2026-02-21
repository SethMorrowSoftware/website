<?php
/**
 * Migration: Analytics infrastructure.
 *
 * Creates page_views table for lightweight traffic tracking
 * and report_cache for caching expensive aggregation queries.
 */
return function (PDO $db) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS page_views (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            page_path VARCHAR(255) NOT NULL,
            referrer VARCHAR(500) DEFAULT NULL,
            user_agent VARCHAR(500) DEFAULT NULL,
            session_id VARCHAR(64) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_page_views_path (page_path),
            INDEX idx_page_views_created (created_at),
            INDEX idx_page_views_session (session_id, created_at)
        ) ENGINE=InnoDB
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS report_cache (
            report_key VARCHAR(100) PRIMARY KEY,
            data_json LONGTEXT NOT NULL,
            generated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB
    ");
};
