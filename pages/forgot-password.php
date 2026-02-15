<?php
/**
 * Forgot Password Page
 */

if (isCustomerLoggedIn()) {
    redirect('index.php?page=account');
}

$csrfToken = generateCSRFToken();
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <a href="<?php echo url('index.php?page=login'); ?>">Sign In</a>
        <span>/</span>
        <span class="current">Forgot Password</span>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h1><i class="fas fa-key"></i> Forgot Password</h1>
                <p class="auth-subtitle">Enter your email address and we'll send you a link to reset your password.</p>

                <form method="POST" action="<?php echo url('index.php'); ?>" class="auth-form">
                    <input type="hidden" name="action" value="forgot_password">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required autofocus placeholder="you@example.com">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-paper-plane"></i> Send Reset Link
                    </button>
                </form>

                <div class="auth-links">
                    <p>Remember your password? <a href="<?php echo url('index.php?page=login'); ?>">Sign in</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
