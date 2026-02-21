<?php
/**
 * Multi-Admin Roles & Permissions System
 *
 * Provides role-based access control (RBAC) for the admin panel.
 *
 * Permission slugs:
 *   manage_products     - CRUD products and categories
 *   manage_orders       - View/update orders
 *   manage_pages        - CRUD pages, hero sections
 *   manage_blog         - CRUD blog posts, categories, comments
 *   manage_settings     - Site settings, payment config, feature flags
 *   manage_users        - Create/edit admin users and roles
 *   manage_plugins      - Activate/deactivate plugins
 *   manage_themes       - Change themes, customize theme settings
 *   manage_coupons      - CRUD discount coupons
 *   manage_shipping     - Shipping zones and methods
 *   manage_customers    - View customer accounts
 *   manage_testimonials - CRUD testimonials
 *   manage_hero         - CRUD hero sections
 *   manage_navigation   - Edit navigation menu
 *   manage_media        - Upload/delete media files
 *   manage_reviews      - Moderate product reviews
 *   view_analytics      - View dashboard stats and reports
 *   export_data         - Export orders/data
 *   view_audit_log      - View audit log
 *   * (wildcard)        - Super admin bypass (all permissions)
 */

// ============================================================
// Role Functions
// ============================================================

/**
 * Get all roles.
 *
 * @return array
 */
function getAllRoles(): array {
    try {
        $db = getDB();
        return $db->query('SELECT * FROM roles ORDER BY id ASC')->fetchAll();
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Get a role by ID.
 */
function getRole(int $id): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM roles WHERE id = ?');
        $stmt->execute([$id]);
        $role = $stmt->fetch();
        return $role ?: null;
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Get a role by name.
 */
function getRoleByName(string $name): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT * FROM roles WHERE name = ?');
        $stmt->execute([$name]);
        $role = $stmt->fetch();
        return $role ?: null;
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Get permissions for a role (decoded from JSON).
 *
 * @param array $role Role row from database
 * @return string[] Array of permission slugs
 */
function getRolePermissions(array $role): array {
    $perms = json_decode($role['permissions'] ?? '[]', true);
    return is_array($perms) ? $perms : [];
}

// ============================================================
// Permission Checking
// ============================================================

/**
 * Get the current admin user's role.
 *
 * @return array|null Role data or null
 */
function getCurrentUserRole(): ?array {
    static $cached = null;
    if ($cached !== null) return $cached ?: null;

    $userId = getCurrentUserId();
    if (!$userId) {
        $cached = false;
        return null;
    }

    try {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT r.* FROM roles r '
            . 'JOIN users u ON u.role_id = r.id '
            . 'WHERE u.id = ?'
        );
        $stmt->execute([$userId]);
        $role = $stmt->fetch();
        $cached = $role ?: false;
        return $role ?: null;
    } catch (\Throwable $e) {
        // roles table may not exist yet (pre-migration)
        $cached = false;
        return null;
    }
}

/**
 * Check if the current admin user has a specific permission.
 *
 * Super admins (permission '*') always return true.
 * If the roles system hasn't been migrated yet, returns true for backward compat.
 *
 * @param string $permission Permission slug to check
 * @return bool
 */
function hasPermission(string $permission): bool {
    $role = getCurrentUserRole();

    // If no role system (pre-migration), allow everything for backward compat
    if ($role === null) return true;

    $perms = getRolePermissions($role);

    // Wildcard = super admin
    if (in_array('*', $perms, true)) return true;

    return in_array($permission, $perms, true);
}

/**
 * Require a specific permission — redirect with error if not authorized.
 *
 * Usage in admin pages:
 *   requirePermission('manage_products');
 *
 * @param string $permission Permission slug
 */
function requirePermission(string $permission): void {
    if (!hasPermission($permission)) {
        $_SESSION['admin_flash'] = [
            'type' => 'error',
            'message' => 'You do not have permission to access this section.',
        ];
        redirect('admin/');
    }
}

/**
 * Check if the current user is a super admin.
 */
function isSuperAdmin(): bool {
    $role = getCurrentUserRole();
    if ($role === null) return true; // backward compat
    $perms = getRolePermissions($role);
    return in_array('*', $perms, true);
}

// ============================================================
// Admin User Management
// ============================================================

/**
 * Get all admin users with their roles.
 *
 * @return array
 */
function getAllAdminUsers(): array {
    try {
        $db = getDB();
        return $db->query(
            'SELECT u.*, r.display_name as role_name, r.name as role_slug '
            . 'FROM users u '
            . 'LEFT JOIN roles r ON u.role_id = r.id '
            . 'ORDER BY u.id ASC'
        )->fetchAll();
    } catch (\Throwable $e) {
        return [];
    }
}

/**
 * Get a single admin user by ID.
 */
function getAdminUser(int $id): ?array {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            'SELECT u.*, r.display_name as role_name, r.name as role_slug '
            . 'FROM users u '
            . 'LEFT JOIN roles r ON u.role_id = r.id '
            . 'WHERE u.id = ?'
        );
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    } catch (\Throwable $e) {
        return null;
    }
}

/**
 * Create a new admin user.
 *
 * @return int|false New user ID or false on failure
 */
function createAdminUser(string $username, string $password, int $roleId, ?string $displayName = null, ?string $email = null): int|false {
    try {
        $db = getDB();
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare(
            'INSERT INTO users (username, password_hash, role_id, display_name, email) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$username, $hash, $roleId, $displayName, $email]);
        return (int)$db->lastInsertId();
    } catch (\Throwable $e) {
        error_log('[USER] Failed to create admin user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update an admin user.
 */
function updateAdminUser(int $id, array $data): bool {
    try {
        $db = getDB();
        $fields = [];
        $values = [];

        if (isset($data['username'])) {
            $fields[] = 'username = ?';
            $values[] = $data['username'];
        }
        if (isset($data['role_id'])) {
            $fields[] = 'role_id = ?';
            $values[] = $data['role_id'];
        }
        if (isset($data['display_name'])) {
            $fields[] = 'display_name = ?';
            $values[] = $data['display_name'];
        }
        if (isset($data['email'])) {
            $fields[] = 'email = ?';
            $values[] = $data['email'];
        }
        if (isset($data['is_active'])) {
            $fields[] = 'is_active = ?';
            $values[] = $data['is_active'];
        }
        if (isset($data['password'])) {
            $fields[] = 'password_hash = ?';
            $values[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) return false;

        $values[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        return $db->prepare($sql)->execute($values);
    } catch (\Throwable $e) {
        error_log('[USER] Failed to update admin user: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete an admin user (cannot delete yourself or the last super admin).
 */
function deleteAdminUser(int $id): bool {
    try {
        $db = getDB();

        // Cannot delete yourself
        if ($id === getCurrentUserId()) return false;

        // Cannot delete the last super admin
        $user = getAdminUser($id);
        if ($user && $user['role_slug'] === 'super_admin') {
            $count = $db->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'super_admin'")->fetchColumn();
            if ((int)$count <= 1) return false;
        }

        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        return $stmt->execute([$id]);
    } catch (\Throwable $e) {
        return false;
    }
}

/**
 * Get all available permission slugs with labels.
 *
 * @return array<string, string> slug => label
 */
function getAllPermissions(): array {
    return [
        'manage_products'     => 'Manage Products & Categories',
        'manage_orders'       => 'Manage Orders',
        'manage_pages'        => 'Manage Pages',
        'manage_blog'         => 'Manage Blog',
        'manage_settings'     => 'Manage Settings',
        'manage_users'        => 'Manage Users & Roles',
        'manage_plugins'      => 'Manage Plugins',
        'manage_themes'       => 'Manage Themes',
        'manage_coupons'      => 'Manage Coupons',
        'manage_shipping'     => 'Manage Shipping',
        'manage_customers'    => 'View Customers',
        'manage_testimonials' => 'Manage Testimonials',
        'manage_hero'         => 'Manage Hero Sections',
        'manage_navigation'   => 'Manage Navigation',
        'manage_media'        => 'Manage Media Library',
        'manage_reviews'      => 'Moderate Reviews',
        'view_analytics'      => 'View Dashboard & Analytics',
        'export_data'         => 'Export Data',
        'view_audit_log'      => 'View Audit Log',
    ];
}
