<?php
/**
 * Customer Login Page
 */

// Already logged in?
if (isCustomerLoggedIn()) {
    redirect('index.php?page=account');
}

$csrfToken = generateCSRFToken();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'customer_login') {
    // Handled in index.php — this is a fallback
}
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Sign In</span>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h1><i class="fas fa-sign-in-alt"></i> Sign In</h1>
                <p class="auth-subtitle">Access your account, view orders, and manage your profile.</p>

                <form method="POST" action="<?php echo url('index.php'); ?>" class="auth-form">
                    <input type="hidden" name="action" value="customer_login">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required autofocus placeholder="you@example.com">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required placeholder="Your password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </form>

                <div class="auth-links">
                    <p>Don't have an account? <a href="<?php echo url('index.php?page=register'); ?>">Create one</a></p>
                    <p><a href="<?php echo url('index.php?page=forgot-password'); ?>">Forgot your password?</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
