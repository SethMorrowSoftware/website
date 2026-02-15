<?php
/**
 * Customer Registration Page
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
        <span class="current">Create Account</span>
    </div>
</div>

<section class="section">
    <div class="container">
        <div class="auth-container">
            <div class="auth-card">
                <h1><i class="fas fa-user-plus"></i> Create Account</h1>
                <p class="auth-subtitle">Sign up to track orders, save your info, and speed up checkout.</p>

                <form method="POST" action="<?php echo url('index.php'); ?>" class="auth-form">
                    <input type="hidden" name="action" value="customer_register">
                    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="John">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="Doe">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address <span class="required">*</span></label>
                        <input type="email" id="email" name="email" class="form-control" required placeholder="you@example.com">
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="(555) 000-0000">
                    </div>

                    <div class="form-group">
                        <label for="password">Password <span class="required">*</span></label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="8" placeholder="At least 8 characters">
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirm Password <span class="required">*</span></label>
                        <input type="password" id="password_confirm" name="password_confirm" class="form-control" required minlength="8" placeholder="Repeat password">
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                        <i class="fas fa-user-plus"></i> Create Account
                    </button>
                </form>

                <div class="auth-links">
                    <p>Already have an account? <a href="<?php echo url('index.php?page=login'); ?>">Sign in</a></p>
                </div>
            </div>
        </div>
    </div>
</section>
