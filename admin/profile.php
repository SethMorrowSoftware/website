<?php
/**
 * Admin — Profile, Change Password, 2FA Setup, Active Sessions
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/two-factor.php';

requireLogin();

$userId = getCurrentUserId();
$error = '';
$success = '';
$recoveryCodes = [];
$setupSecret = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? 'change_password';

    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (strlen($new) < 8) {
            $error = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $error = 'New passwords do not match.';
        } elseif (changePassword($userId, $current, $new)) {
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Password changed successfully!'];
            redirect('admin/profile.php');
        } else {
            $error = 'Current password is incorrect.';
        }
    }

    if ($action === 'setup_2fa') {
        $setupSecret = $_POST['totp_secret'] ?? '';
        $code = trim($_POST['totp_code'] ?? '');

        if ($setupSecret && $code && verifyTotpCode($setupSecret, $code)) {
            $recoveryCodes = enableTwoFactor($userId, $setupSecret);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Two-factor authentication enabled!'];
        } else {
            $error = 'Invalid verification code. Please try again.';
            // Keep the secret so user can retry
        }
    }

    if ($action === 'disable_2fa') {
        $password = $_POST['confirm_password'] ?? '';
        $db = getDB();
        $stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            disableTwoFactor($userId);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Two-factor authentication disabled.'];
            redirect('admin/profile.php');
        } else {
            $error = 'Incorrect password. Cannot disable 2FA.';
        }
    }

    if ($action === 'revoke_session') {
        $sessionToRevoke = $_POST['session_id'] ?? '';
        if ($sessionToRevoke && $sessionToRevoke !== session_id()) {
            revokeAdminSession($userId, $sessionToRevoke);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Session revoked.'];
            redirect('admin/profile.php');
        }
    }
}

$has2FA = isTwoFactorEnabled($userId);
$activeSessions = getActiveSessions($userId);
$currentSessionId = session_id();

// Generate new secret for setup if not already in progress
if (!$has2FA && !$setupSecret && empty($recoveryCodes)) {
    $setupSecret = generateTotpSecret();
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-user-shield"></i> My Profile</h1>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-lg); max-width: 900px;">

<!-- Left Column: Account + Password -->
<div>
    <div class="admin-card" style="padding: var(--space-lg); margin-bottom: var(--space-lg);">
        <h3 style="margin-bottom: var(--space-md);"><i class="fas fa-user"></i> Account Information</h3>
        <div style="padding: 0.75rem 0; border-bottom: 1px solid #eee;">
            <strong>Username:</strong> <?php echo e(getCurrentUsername()); ?>
        </div>
        <div style="padding: 0.75rem 0;">
            <strong>2FA Status:</strong>
            <?php if ($has2FA): ?>
                <span class="badge-status badge-active">Enabled</span>
            <?php else: ?>
                <span class="badge-status badge-inactive">Disabled</span>
            <?php endif; ?>
        </div>
    </div>

    <div class="admin-card" style="padding: var(--space-lg);">
        <h3 style="margin-bottom: var(--space-md);"><i class="fas fa-key"></i> Change Password</h3>

        <?php if ($error): ?>
            <div class="admin-alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?></div>
        <?php endif; ?>

        <form method="POST" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
            <input type="hidden" name="action" value="change_password">

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

<!-- Right Column: 2FA + Sessions -->
<div>
    <div class="admin-card" style="padding: var(--space-lg); margin-bottom: var(--space-lg);">
        <h3 style="margin-bottom: var(--space-md);"><i class="fas fa-shield-alt"></i> Two-Factor Authentication</h3>

        <?php if (!empty($recoveryCodes)): ?>
            <!-- Recovery Codes Display -->
            <div class="admin-alert alert-success" style="margin-bottom: var(--space-md);">
                <i class="fas fa-check-circle"></i> 2FA is now enabled! Save these recovery codes in a safe place.
            </div>
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; font-family: monospace; margin-bottom: var(--space-md);">
                <?php foreach ($recoveryCodes as $code): ?>
                    <div style="padding: 0.25rem 0;"><?php echo e($code); ?></div>
                <?php endforeach; ?>
            </div>
            <p style="color: var(--color-gray-500); font-size: 0.875rem;">
                Each recovery code can only be used once. Store them securely.
            </p>

        <?php elseif ($has2FA): ?>
            <!-- 2FA is enabled — show disable option -->
            <p style="color: var(--color-gray-500); margin-bottom: var(--space-md);">
                Two-factor authentication is active. Enter your password to disable it.
            </p>
            <form method="POST" class="admin-form" onsubmit="return confirm('Are you sure you want to disable 2FA?');">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="disable_2fa">
                <div class="form-group">
                    <label>Your Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn-admin btn-delete"><i class="fas fa-times"></i> Disable 2FA</button>
            </form>

        <?php else: ?>
            <!-- 2FA Setup -->
            <p style="color: var(--color-gray-500); margin-bottom: var(--space-md);">
                Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.):
            </p>
            <?php
            $otpUri = getTotpUri($setupSecret, getCurrentUsername());
            $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpUri);
            ?>
            <div style="text-align: center; margin-bottom: var(--space-md);">
                <img src="<?php echo e($qrUrl); ?>" alt="2FA QR Code" style="border: 4px solid #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 8px;" width="200" height="200">
            </div>
            <div style="text-align: center; margin-bottom: var(--space-md);">
                <small style="color: var(--color-gray-400);">Manual entry key:</small><br>
                <code style="font-size: 0.9rem; letter-spacing: 2px;"><?php echo e($setupSecret); ?></code>
            </div>

            <form method="POST" class="admin-form">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="action" value="setup_2fa">
                <input type="hidden" name="totp_secret" value="<?php echo e($setupSecret); ?>">
                <div class="form-group">
                    <label>Verification Code</label>
                    <input type="text" name="totp_code" class="form-control" required pattern="\d{6}" maxlength="6" placeholder="Enter 6-digit code" autocomplete="off">
                </div>
                <button type="submit" class="btn-admin btn-save"><i class="fas fa-check"></i> Enable 2FA</button>
            </form>
        <?php endif; ?>
    </div>

    <!-- Active Sessions -->
    <div class="admin-card" style="padding: var(--space-lg);">
        <h3 style="margin-bottom: var(--space-md);"><i class="fas fa-desktop"></i> Active Sessions</h3>
        <?php if (empty($activeSessions)): ?>
            <p style="color: var(--color-gray-400);">No session data available.</p>
        <?php else: ?>
            <?php foreach ($activeSessions as $sess): ?>
                <div style="padding: 0.75rem 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 0.875rem;">
                            <i class="fas fa-<?php echo $sess['session_id'] === $currentSessionId ? 'circle' : 'circle'; ?>"
                               style="color: <?php echo $sess['session_id'] === $currentSessionId ? '#22c55e' : '#94a3b8'; ?>; font-size: 0.5rem; vertical-align: middle;"></i>
                            <?php echo e(substr($sess['user_agent'], 0, 60)); ?>
                            <?php if ($sess['session_id'] === $currentSessionId): ?>
                                <strong>(current)</strong>
                            <?php endif; ?>
                        </div>
                        <small style="color: var(--color-gray-400);">
                            IP: <?php echo e($sess['ip_address']); ?> &middot;
                            Last active: <?php echo formatDate($sess['last_active']); ?>
                        </small>
                    </div>
                    <?php if ($sess['session_id'] !== $currentSessionId): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                            <input type="hidden" name="action" value="revoke_session">
                            <input type="hidden" name="session_id" value="<?php echo e($sess['session_id']); ?>">
                            <button type="submit" class="btn-admin btn-sm btn-delete" title="Revoke"><i class="fas fa-times"></i></button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
