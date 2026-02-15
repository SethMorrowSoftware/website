<?php
/**
 * Reset Password Page
 */

if (isCustomerLoggedIn()) {
    redirect('index.php?page=account');
}

$token = $_GET['token'] ?? '';
$csrfToken = generateCSRFToken();

// Validate token exists (actual consumption happens on form submit)
$validToken = false;
if ($token) {
    $db = getDB();
    $stmt = $db->prepare('SELECT id FROM password_resets WHERE token = ? AND used = 0 AND expires_at > datetime("now")');
    $stmt->execute([$token]);
    $validToken = (bool)$stmt->fetch();
}
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <a href="<?php echo url('index.php?page=login'); ?>">Sign In</a>
        <span>/</span>
        <span class="current">Reset Password</span>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <?php if (!$token || !$validToken): ?>
                    <h1><i class="fas fa-exclamation-circle"></i> Invalid Link</h1>
                    <p class="auth-subtitle">This password reset link is invalid or has expired.</p>
                    <div class="auth-links" style="margin-top: var(--space-xl);">
                        <p><a href="<?php echo url('index.php?page=forgot-password'); ?>">Request a new reset link</a></p>
                    </div>
                <?php else: ?>
                    <h1><i class="fas fa-lock"></i> Reset Password</h1>
                    <p class="auth-subtitle">Enter your new password below.</p>

                    <form method="POST" action="<?php echo url('index.php'); ?>" class="auth-form">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                        <input type="hidden" name="token" value="<?php echo e($token); ?>">

                        <div class="form-group">
                            <label for="password">New Password <span class="required">*</span></label>
                            <input type="password" id="password" name="password" class="form-control" required minlength="8" placeholder="At least 8 characters">
                        </div>

                        <div class="form-group">
                            <label for="password_confirm">Confirm New Password <span class="required">*</span></label>
                            <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="8" placeholder="Repeat password">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-lock"></i> Reset Password
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
