<?php
/**
 * Enhanced Email & Notification System
 *
 * Provides:
 * - Database-driven email templates with variable substitution
 * - Email queue for async sending
 * - Admin & customer notification preferences
 * - Template rendering with company branding
 */

// ============================================================
// Email Templates
// ============================================================

/**
 * Get an email template by slug.
 */
function getEmailTemplate(string $slug): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM email_templates WHERE slug = ? AND is_active = 1');
        $stmt->execute([$slug]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Get all email templates.
 */
function getAllEmailTemplates(): array {
    try {
        $db = getDB();
        return $db->query('SELECT * FROM email_templates ORDER BY name')->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Update an email template.
 */
function updateEmailTemplate(int $id, string $subject, string $bodyHtml, bool $isActive = true): bool {
    $db = getDB();
    $stmt = $db->prepare('UPDATE email_templates SET subject = ?, body_html = ?, is_active = ?, updated_at = NOW() WHERE id = ?');
    return $stmt->execute([$subject, $bodyHtml, $isActive ? 1 : 0, $id]);
}

/**
 * Render an email template by replacing {{variables}}.
 */
function renderEmailTemplate(string $slug, array $vars = []): ?array {
    $template = getEmailTemplate($slug);
    if (!$template) return null;

    // Add global variables
    $vars['company_name'] = $vars['company_name'] ?? getSetting('company_name', 'Our Store');
    $vars['primary_color'] = $vars['primary_color'] ?? getSetting('primary_color', '#2563EB');

    $subject = $template['subject'];
    $body = $template['body_html'];

    // Replace {{variable}} placeholders
    foreach ($vars as $key => $value) {
        $subject = str_replace('{{' . $key . '}}', $value, $subject);
        $body = str_replace('{{' . $key . '}}', $value, $body);
    }

    return [
        'subject' => $subject,
        'body_html' => buildEmailHtml($subject, $body),
        'body_raw' => $body,
    ];
}

/**
 * Send a templated email.
 *
 * @param string $slug     Template slug
 * @param string $to       Recipient email
 * @param array  $vars     Template variables
 * @param bool   $queue    If true, queue instead of sending immediately
 * @return bool
 */
function sendTemplatedEmail(string $slug, string $to, array $vars = [], bool $queue = false): bool {
    $rendered = renderEmailTemplate($slug, $vars);
    if (!$rendered) {
        // Template not found — can't send
        return false;
    }

    if ($queue) {
        return queueEmail($to, $rendered['subject'], $rendered['body_html']);
    }

    return sendEmail($to, $rendered['subject'], $rendered['body_html']);
}

// ============================================================
// Email Queue
// ============================================================

/**
 * Add an email to the queue.
 */
function queueEmail(string $to, string $subject, string $bodyHtml, ?string $scheduledAt = null, ?string $fromName = null, ?string $fromEmail = null): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO email_queue (to_email, subject, body_html, scheduled_at, from_name, from_email) VALUES (?, ?, ?, ?, ?, ?)');
        return $stmt->execute([$to, $subject, $bodyHtml, $scheduledAt ?? date('Y-m-d H:i:s'), $fromName, $fromEmail]);
    } catch (Exception $e) {
        // Queue table might not exist yet — send immediately as fallback
        return sendEmail($to, $subject, $bodyHtml);
    }
}

/**
 * Process the email queue (called by cron or admin action).
 * Returns number of emails sent.
 */
function processEmailQueue(int $batchSize = 20): int {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM email_queue
            WHERE status = 'pending'
              AND scheduled_at <= NOW()
              AND attempts < 3
            ORDER BY scheduled_at ASC
            LIMIT ?
        ");
        $stmt->execute([$batchSize]);
        $emails = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return 0;
    }

    $sent = 0;
    foreach ($emails as $email) {
        $success = sendEmail(
            $email['to_email'],
            $email['subject'],
            $email['body_html'],
            $email['from_name'] ?? '',
            $email['from_email'] ?? ''
        );

        if ($success) {
            $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW(), attempts = attempts + 1 WHERE id = ?")->execute([$email['id']]);
            $sent++;
        } else {
            $db->prepare("UPDATE email_queue SET attempts = attempts + 1, last_error = 'Send failed' WHERE id = ?")->execute([$email['id']]);
            // Mark as failed after 3 attempts
            if ($email['attempts'] >= 2) {
                $db->prepare("UPDATE email_queue SET status = 'failed' WHERE id = ?")->execute([$email['id']]);
            }
        }
    }

    return $sent;
}

/**
 * Get queue stats.
 */
function getEmailQueueStats(): array {
    try {
        $db = getDB();
        $pending = $db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'pending'")->fetchColumn();
        $sent = $db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'sent'")->fetchColumn();
        $failed = $db->query("SELECT COUNT(*) FROM email_queue WHERE status = 'failed'")->fetchColumn();
        return ['pending' => $pending, 'sent' => $sent, 'failed' => $failed];
    } catch (Exception $e) {
        return ['pending' => 0, 'sent' => 0, 'failed' => 0];
    }
}

// ============================================================
// Notification Preferences
// ============================================================

/**
 * Check if a notification event is enabled for a user.
 */
function isNotificationEnabled(?int $userId, string $eventType, string $channel = 'email'): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT is_enabled FROM notification_preferences WHERE user_id <=> ? AND event_type = ? AND channel = ?');
        $stmt->execute([$userId, $eventType, $channel]);
        $result = $stmt->fetchColumn();
        // Default to enabled if no preference set
        return $result === false ? true : (bool)$result;
    } catch (Exception $e) {
        return true;
    }
}

/**
 * Set a notification preference.
 */
function setNotificationPreference(?int $userId, string $eventType, string $channel, bool $enabled): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO notification_preferences (user_id, event_type, channel, is_enabled)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE is_enabled = VALUES(is_enabled)
        ");
        return $stmt->execute([$userId, $eventType, $channel, $enabled ? 1 : 0]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all notification preferences for a user.
 */
function getNotificationPreferences(?int $userId): array {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT event_type, channel, is_enabled FROM notification_preferences WHERE user_id <=> ?');
        $stmt->execute([$userId]);
        $prefs = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $prefs[$row['event_type']][$row['channel']] = (bool)$row['is_enabled'];
        }
        return $prefs;
    } catch (Exception $e) {
        return [];
    }
}

// ============================================================
// Notification Event Definitions
// ============================================================

/**
 * Get all available notification events.
 */
function getNotificationEvents(): array {
    return [
        'new_order' => ['label' => 'New Order', 'description' => 'When a new order is placed', 'admin' => true],
        'order_shipped' => ['label' => 'Order Shipped', 'description' => 'When an order is marked as shipped', 'admin' => false],
        'order_delivered' => ['label' => 'Order Delivered', 'description' => 'When an order is delivered', 'admin' => false],
        'low_stock' => ['label' => 'Low Stock Alert', 'description' => 'When a product is running low', 'admin' => true],
        'new_customer' => ['label' => 'New Customer Registration', 'description' => 'When a new customer registers', 'admin' => true],
        'new_contact' => ['label' => 'New Contact Submission', 'description' => 'When someone submits the contact form', 'admin' => true],
        'new_review' => ['label' => 'New Review', 'description' => 'When a customer submits a review', 'admin' => true],
        'review_request' => ['label' => 'Review Request', 'description' => 'Prompt customers to review their purchase', 'admin' => false],
    ];
}

/**
 * Send a notification by event type, respecting preferences.
 */
function sendNotification(string $eventType, string $to, array $vars = [], ?int $userId = null): bool {
    if (!isNotificationEnabled($userId, $eventType)) {
        return false; // User opted out
    }

    // Map event types to template slugs
    $templateMap = [
        'new_order' => 'new_order_admin',
        'order_shipped' => 'order_shipped',
        'order_delivered' => 'order_delivered',
        'low_stock' => 'low_stock_alert',
        'new_customer' => 'welcome_email',
        'new_contact' => 'new_contact',
        'new_review' => 'new_review',
        'review_request' => 'review_request',
    ];

    $templateSlug = $templateMap[$eventType] ?? null;
    if (!$templateSlug) return false;

    return sendTemplatedEmail($templateSlug, $to, $vars);
}
