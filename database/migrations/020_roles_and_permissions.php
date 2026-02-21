<?php
/**
 * Migration: Create roles table, expand users table with role_id.
 *
 * Part of the Multi-Admin Roles & Permissions system (Phase 1).
 *
 * Default roles:
 *   - super_admin: Full access to everything
 *   - store_manager: Manage products, orders, coupons, shipping
 *   - content_editor: Manage pages, blog, testimonials, media
 *   - order_fulfillment: View/update orders only
 *   - viewer: Read-only access to admin dashboard
 */
return function (PDO $db) {
    // Create roles table
    if (!tableExists($db, 'roles')) {
        $db->exec("CREATE TABLE roles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) UNIQUE NOT NULL,
            display_name VARCHAR(255) NOT NULL,
            description TEXT,
            permissions JSON,
            is_system TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        // Seed default roles
        $roles = [
            [
                'super_admin',
                'Super Admin',
                'Full access to everything. Cannot be deleted or restricted.',
                json_encode(['*']),
                1,
            ],
            [
                'store_manager',
                'Store Manager',
                'Manage products, orders, coupons, shipping, customers, and inventory.',
                json_encode([
                    'manage_products', 'manage_orders', 'manage_coupons',
                    'manage_shipping', 'manage_customers', 'view_analytics',
                    'manage_media', 'export_data',
                ]),
                1,
            ],
            [
                'content_editor',
                'Content Editor',
                'Manage pages, blog posts, testimonials, hero sections, navigation, and media.',
                json_encode([
                    'manage_pages', 'manage_blog', 'manage_testimonials',
                    'manage_hero', 'manage_navigation', 'manage_media',
                ]),
                1,
            ],
            [
                'order_fulfillment',
                'Order Fulfillment',
                'View and update order status. Cannot modify products or settings.',
                json_encode([
                    'manage_orders', 'view_analytics',
                ]),
                1,
            ],
            [
                'viewer',
                'Viewer',
                'Read-only access to the admin dashboard.',
                json_encode([
                    'view_analytics',
                ]),
                1,
            ],
        ];

        $stmt = $db->prepare('INSERT INTO roles (name, display_name, description, permissions, is_system) VALUES (?, ?, ?, ?, ?)');
        foreach ($roles as $role) {
            $stmt->execute($role);
        }
    }

    // Add role_id to users table (nullable for backward compat)
    if (!columnExists($db, 'users', 'role_id')) {
        $db->exec('ALTER TABLE users ADD COLUMN role_id INT DEFAULT NULL');
        $db->exec('ALTER TABLE users ADD COLUMN display_name VARCHAR(255) DEFAULT NULL');
        $db->exec('ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL');
        $db->exec('ALTER TABLE users ADD COLUMN is_active TINYINT(1) DEFAULT 1');
        $db->exec('ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL');

        // Assign existing users the super_admin role
        $superAdminId = $db->query("SELECT id FROM roles WHERE name = 'super_admin' LIMIT 1")->fetchColumn();
        if ($superAdminId) {
            $db->prepare('UPDATE users SET role_id = ? WHERE role_id IS NULL')->execute([$superAdminId]);
        }
    }
};
