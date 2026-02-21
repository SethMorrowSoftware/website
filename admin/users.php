<?php
/**
 * Admin — User & Role Management
 *
 * Manage admin users, assign roles, create/edit accounts.
 * Only accessible to users with 'manage_users' permission (super admins by default).
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_users');

$db = getDB();
$roles = getAllRoles();
$users = getAllAdminUsers();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['user_action'] ?? '';

    // Create new user
    if ($action === 'create') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 0);
        $displayName = trim($_POST['display_name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        $errors = [];
        if (strlen($username) < 3) $errors[] = 'Username must be at least 3 characters.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if (!$roleId) $errors[] = 'Please select a role.';
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email.';

        if (empty($errors)) {
            $newId = createAdminUser($username, $password, $roleId, $displayName ?: null, $email ?: null);
            if ($newId) {
                logAudit('user_created', 'users', "Created user '$username' (ID: $newId)");
                $_SESSION['admin_flash'] = ['type' => 'success', 'message' => "User '$username' created successfully."];
            } else {
                $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Could not create user. Username may already exist.'];
            }
        } else {
            $_SESSION['admin_flash'] = ['type' => 'error', 'message' => implode(' ', $errors)];
        }
        header('Location: ' . url('admin/users.php'));
        exit;
    }

    // Update user
    if ($action === 'update') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $data = [];

        if (!empty($_POST['display_name'])) $data['display_name'] = trim($_POST['display_name']);
        if (!empty($_POST['email'])) $data['email'] = trim($_POST['email']);
        if (!empty($_POST['role_id'])) $data['role_id'] = (int)$_POST['role_id'];
        if (!empty($_POST['new_password'])) $data['password'] = $_POST['new_password'];
        $data['is_active'] = isset($_POST['is_active']) ? 1 : 0;

        // Prevent demoting yourself from super_admin
        if ($userId === getCurrentUserId() && isset($data['role_id'])) {
            $currentRole = getCurrentUserRole();
            if ($currentRole && $currentRole['name'] === 'super_admin') {
                $newRole = getRole($data['role_id']);
                if ($newRole && $newRole['name'] !== 'super_admin') {
                    $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'You cannot remove your own super admin role.'];
                    header('Location: ' . url('admin/users.php'));
                    exit;
                }
            }
        }

        if ($userId && updateAdminUser($userId, $data)) {
            logAudit('user_updated', 'users', "Updated user ID: $userId");
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'User updated successfully.'];
        } else {
            $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Could not update user.'];
        }
        header('Location: ' . url('admin/users.php'));
        exit;
    }

    // Delete user
    if ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId && deleteAdminUser($userId)) {
            logAudit('user_deleted', 'users', "Deleted user ID: $userId");
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'User deleted.'];
        } else {
            $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Cannot delete this user. You cannot delete yourself or the last super admin.'];
        }
        header('Location: ' . url('admin/users.php'));
        exit;
    }
}

// Check if editing a specific user
$editUserId = (int)($_GET['edit'] ?? 0);
$editUser = $editUserId ? getAdminUser($editUserId) : null;

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-users-cog"></i> Team Management</h1>
    <p>Manage admin users and their roles.</p>
</div>

<?php if ($editUser): ?>
<!-- Edit User Form -->
<div class="admin-card">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.5rem;">
        <h3>Edit User: <?php echo e($editUser['username']); ?></h3>
        <a href="<?php echo url('admin/users.php'); ?>" class="btn btn-sm btn-outline">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
        <input type="hidden" name="user_action" value="update">
        <input type="hidden" name="user_id" value="<?php echo $editUser['id']; ?>">

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
            <div class="form-group">
                <label>Username</label>
                <input type="text" class="form-control" value="<?php echo e($editUser['username']); ?>" disabled>
                <small style="color: var(--color-gray-400);">Username cannot be changed.</small>
            </div>
            <div class="form-group">
                <label for="display_name">Display Name</label>
                <input type="text" id="display_name" name="display_name" class="form-control" value="<?php echo e($editUser['display_name'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" value="<?php echo e($editUser['email'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="role_id">Role</label>
                <select id="role_id" name="role_id" class="form-control">
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>" <?php echo ($editUser['role_id'] ?? 0) == $role['id'] ? 'selected' : ''; ?>>
                            <?php echo e($role['display_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Leave blank to keep current">
            </div>
            <div class="form-group" style="display: flex; align-items: center; gap: 0.5rem; padding-top: 1.5rem;">
                <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo ($editUser['is_active'] ?? 1) ? 'checked' : ''; ?>>
                <label for="is_active" style="margin: 0;">Account Active</label>
            </div>
        </div>

        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Changes
            </button>
        </div>
    </form>
</div>

<?php else: ?>
<!-- Create New User -->
<div class="admin-card">
    <h3>Add New User</h3>
    <form method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
        <input type="hidden" name="user_action" value="create">

        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <div class="form-group">
                <label for="username">Username *</label>
                <input type="text" id="username" name="username" class="form-control" required minlength="3">
            </div>
            <div class="form-group">
                <label for="password">Password *</label>
                <input type="password" id="password" name="password" class="form-control" required minlength="8">
            </div>
            <div class="form-group">
                <label for="role_id">Role *</label>
                <select id="role_id" name="role_id" class="form-control" required>
                    <option value="">Select role...</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo e($role['display_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="display_name">Display Name</label>
                <input type="text" id="display_name" name="display_name" class="form-control">
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control">
            </div>
            <div class="form-group" style="display: flex; align-items: end;">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Users List -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <h3>Admin Users</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Display Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <strong><?php echo e($user['username']); ?></strong>
                        <?php if ($user['id'] === getCurrentUserId()): ?>
                            <span style="color: var(--color-primary); font-size: 0.8rem;">(you)</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($user['display_name'] ?? '—'); ?></td>
                    <td><?php echo e($user['email'] ?? '—'); ?></td>
                    <td>
                        <span style="background: var(--color-gray-100); padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.85rem;">
                            <?php echo e($user['role_name'] ?? 'No Role'); ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['is_active'] ?? 1): ?>
                            <span style="color: var(--color-success);">Active</span>
                        <?php else: ?>
                            <span style="color: var(--color-error);">Disabled</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $user['last_login'] ? formatDate($user['last_login']) : '—'; ?></td>
                    <td>
                        <a href="<?php echo url('admin/users.php'); ?>?edit=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <?php if ($user['id'] !== getCurrentUserId()): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
                            <input type="hidden" name="user_action" value="delete">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline" style="color: var(--color-error);"
                                    onclick="return confirm('Delete this user? This action cannot be undone.');">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Roles Reference -->
<div class="admin-card" style="margin-top: 1.5rem;">
    <h3>Roles & Permissions</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Permissions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($roles as $role):
                    $perms = json_decode($role['permissions'] ?? '[]', true);
                ?>
                <tr>
                    <td><strong><?php echo e($role['display_name']); ?></strong></td>
                    <td style="color: var(--color-gray-400);"><?php echo e($role['description'] ?? ''); ?></td>
                    <td>
                        <?php if (in_array('*', $perms)): ?>
                            <span style="background: var(--color-primary); color: #fff; padding: 0.2rem 0.6rem; border-radius: 4px; font-size: 0.8rem;">All Permissions</span>
                        <?php else: ?>
                            <?php
                            $allPerms = getAllPermissions();
                            foreach ($perms as $p):
                                $label = $allPerms[$p] ?? $p;
                            ?>
                                <span style="background: var(--color-gray-100); padding: 0.15rem 0.5rem; border-radius: 3px; font-size: 0.8rem; display: inline-block; margin: 0.1rem;"><?php echo e($label); ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
