<?php
/**
 * Admin — Profile / Change Password
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (strlen($new) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $error = 'New passwords do not match.';
    } elseif (changePassword(getCurrentUserId(), $current, $new)) {
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Password changed successfully!'];
        redirect('/admin/profile.php');
    } else {
        $error = 'Current password is incorrect.';
    }
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-user-shield"></i> My Profile</h1>
</div>

<div class="admin-form" style="max-width: 500px;">
    <div class="form-section">
        <h3>Account Information</h3>
        <div class="detail-row" style="padding: 1rem 0; border-bottom: 1px solid #eee;">
            <strong>Username:</strong> <?php echo e(getCurrentUsername()); ?>
        </div>
    </div>

    <div class="form-section">
        <h3>Change Password</h3>

        <?php if ($error): ?>
            <div class="admin-alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" class="form-control" required minlength="8">
                <small class="form-help">Minimum 8 characters</small>
            </div>
            <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" required minlength="8">
            </div>

            <button type="submit" class="btn-admin btn-save"><i class="fas fa-key"></i> Change Password</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
