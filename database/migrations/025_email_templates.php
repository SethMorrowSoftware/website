<?php
/**
 * Migration: Email templates, notification preferences, and email queue.
 */
return function (PDO $db) {
    // Email templates with variable placeholders
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_templates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(100) UNIQUE NOT NULL,
            name VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            body_html TEXT NOT NULL,
            variables TEXT DEFAULT NULL,
            is_active TINYINT(1) DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Admin notification preferences
    $db->exec("
        CREATE TABLE IF NOT EXISTS notification_preferences (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT DEFAULT NULL,
            event_type VARCHAR(100) NOT NULL,
            channel VARCHAR(50) DEFAULT 'email',
            is_enabled TINYINT(1) DEFAULT 1,
            UNIQUE KEY uk_user_event_channel (user_id, event_type, channel)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Email queue for async sending
    $db->exec("
        CREATE TABLE IF NOT EXISTS email_queue (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            to_email VARCHAR(255) NOT NULL,
            subject VARCHAR(500) NOT NULL,
            body_html TEXT NOT NULL,
            from_name VARCHAR(255) DEFAULT NULL,
            from_email VARCHAR(255) DEFAULT NULL,
            status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            last_error TEXT DEFAULT NULL,
            scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            sent_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_email_queue_status (status, scheduled_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Seed default email templates
    $templates = [
        [
            'slug' => 'order_confirmation',
            'name' => 'Order Confirmation',
            'subject' => 'Order Confirmation - {{order_number}}',
            'body_html' => '<p>Thank you for your order, {{customer_name}}!</p><p><strong>Order Number:</strong> {{order_number}}</p>{{order_items_table}}<p style="font-size:18px;font-weight:700;text-align:right;">Total: {{order_total}}</p>{{shipping_address}}',
            'variables' => 'customer_name, customer_email, order_number, order_total, order_items_table, shipping_address, order_status',
        ],
        [
            'slug' => 'order_shipped',
            'name' => 'Order Shipped',
            'subject' => 'Your Order Has Shipped - {{order_number}}',
            'body_html' => '<p>Hi {{customer_name}},</p><p>Great news! Your order <strong>{{order_number}}</strong> has been shipped.</p><p><strong>Tracking Number:</strong> {{tracking_number}}<br><strong>Carrier:</strong> {{tracking_carrier}}</p><p>You can track your delivery status at any time.</p>',
            'variables' => 'customer_name, order_number, tracking_number, tracking_carrier',
        ],
        [
            'slug' => 'order_delivered',
            'name' => 'Order Delivered',
            'subject' => 'Your Order Has Been Delivered - {{order_number}}',
            'body_html' => '<p>Hi {{customer_name}},</p><p>Your order <strong>{{order_number}}</strong> has been delivered!</p><p>We hope you love your purchase. If you have a moment, we\'d appreciate a review.</p>',
            'variables' => 'customer_name, order_number',
        ],
        [
            'slug' => 'welcome_email',
            'name' => 'Welcome Email',
            'subject' => 'Welcome to {{company_name}}!',
            'body_html' => '<p>Hi {{customer_name}},</p><p>Welcome to {{company_name}}! We\'re excited to have you.</p><p>Start browsing our latest products and enjoy your shopping experience.</p>',
            'variables' => 'customer_name, customer_email, company_name',
        ],
        [
            'slug' => 'password_reset',
            'name' => 'Password Reset',
            'subject' => 'Reset Your Password - {{company_name}}',
            'body_html' => '<p>Hi {{customer_name}},</p><p>We received a request to reset your password. Click the button below to set a new password:</p><p style="text-align:center;margin:24px 0;"><a href="{{reset_url}}" style="display:inline-block;padding:12px 32px;background:{{primary_color}};color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Reset Password</a></p><p>If you did not request this, you can safely ignore this email. The link will expire in 1 hour.</p>',
            'variables' => 'customer_name, reset_url, company_name, primary_color',
        ],
        [
            'slug' => 'low_stock_alert',
            'name' => 'Low Stock Alert (Admin)',
            'subject' => 'Low Stock Alert: {{product_name}}',
            'body_html' => '<p>The following product is running low on stock:</p><p><strong>{{product_name}}</strong><br>Current Stock: {{stock_quantity}}<br>Threshold: {{low_stock_threshold}}</p><p>Please restock soon to avoid missed sales.</p>',
            'variables' => 'product_name, stock_quantity, low_stock_threshold',
        ],
        [
            'slug' => 'new_order_admin',
            'name' => 'New Order (Admin)',
            'subject' => 'New Order Received - {{order_number}}',
            'body_html' => '<p>A new order has been placed!</p><p><strong>Order #:</strong> {{order_number}}<br><strong>Customer:</strong> {{customer_name}} ({{customer_email}})<br><strong>Total:</strong> {{order_total}}<br><strong>Payment:</strong> {{payment_method}}</p>{{order_items_table}}',
            'variables' => 'order_number, customer_name, customer_email, order_total, payment_method, order_items_table',
        ],
        [
            'slug' => 'review_request',
            'name' => 'Review Request',
            'subject' => 'How was your purchase? Leave a review!',
            'body_html' => '<p>Hi {{customer_name}},</p><p>We hope you\'re enjoying your recent purchase from {{company_name}}.</p><p>Would you mind taking a moment to leave a review? Your feedback helps other customers and helps us improve.</p><p style="text-align:center;margin:24px 0;"><a href="{{review_url}}" style="display:inline-block;padding:12px 32px;background:{{primary_color}};color:#fff;text-decoration:none;border-radius:6px;font-weight:600;">Write a Review</a></p>',
            'variables' => 'customer_name, company_name, review_url, primary_color',
        ],
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO email_templates (slug, name, subject, body_html, variables) VALUES (?, ?, ?, ?, ?)");
    foreach ($templates as $t) {
        $stmt->execute([$t['slug'], $t['name'], $t['subject'], $t['body_html'], $t['variables']]);
    }
};
