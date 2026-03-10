<?php
/**
 * Admin Login Page
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

ensureSession();

// Handle logout (POST only, CSRF protected)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    logout();
    redirect('admin/login.php');
}

// Already logged in?
if (isLoggedIn()) {
    redirect('admin/');
}

$error = '';
$show2FA = false;

// Handle 2FA verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['totp_code'])) {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $code = trim($_POST['totp_code'] ?? '');
        $result = completeTwoFactorLogin($code);
        if ($result === true) {
            redirect('admin/');
        } elseif ($result === 'lockout') {
            $error = 'Too many verification attempts. Please log in again.';
        } else {
            $error = 'Invalid verification code or session expired.';
            // Check if still pending
            if (!empty($_SESSION['2fa_pending_user_id'])) {
                $show2FA = true;
            }
        }
    }
}

// Handle normal login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf = $_POST['csrf_token'] ?? '';
    $ip = getClientIp();

    // Check rate limit before processing (composite: IP + username)
    $lockoutSeconds = checkLoginThrottle($ip, $username);
    if ($lockoutSeconds > 0) {
        $minutes = (int)ceil($lockoutSeconds / 60);
        $error = "Too many login attempts. Please try again in $minutes minute(s).";
    } elseif (!verifyCSRFToken($csrf)) {
        $error = 'Invalid session. Please try again.';
    } else {
        $result = attemptLogin($username, $password);
        if ($result === true) {
            redirect('admin/');
        } elseif ($result === '2fa') {
            $show2FA = true;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}

// Check if we already have a pending 2FA challenge
if (!empty($_SESSION['2fa_pending_user_id']) && !$show2FA && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $pendingTime = $_SESSION['2fa_pending_time'] ?? 0;
    if ((time() - $pendingTime) < 300) {
        $show2FA = true;
    }
}

$csrfToken = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login | <?php echo e(getSetting('company_name', SITE_NAME)); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Open Sans', sans-serif;
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 420px;
            padding: 40px;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-logo i {
            font-size: 3rem;
            color: #2563EB;
        }
        .login-logo h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.25rem;
            color: #2563EB;
            margin-top: 10px;
        }
        .login-logo p {
            font-size: 0.875rem;
            color: #888;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.875rem;
            margin-bottom: 6px;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 12px 16px;
            font-size: 1rem;
            font-family: 'Open Sans', sans-serif;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            transition: border-color 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }
        .btn-login {
            width: 100%;
            padding: 14px;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            background: #2563EB;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-login:hover {
            background: #3B82F6;
        }
        .error-msg {
            background: #f8d7da;
            color: #721c24;
            padding: 10px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
            border: 1px solid #f5c6cb;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #888;
            font-size: 0.875rem;
            text-decoration: none;
        }
        .back-link:hover { color: #2563EB; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="login-logo">
            <i class="fas fa-cog"></i>
            <h1>Admin Panel</h1>
            <p><?php echo e(getSetting('company_name', SITE_NAME)); ?></p>
        </div>

        <?php if ($error): ?>
            <div class="error-msg"><i class="fas fa-exclamation-circle"></i> <?php echo e($error); ?></div>
        <?php endif; ?>

        <?php if ($show2FA): ?>
        <!-- 2FA Verification Form -->
        <p style="text-align:center; color:#666; margin-bottom:20px; font-size:0.875rem;">
            <i class="fas fa-shield-alt" style="color:#2563EB;"></i>
            Enter the 6-digit code from your authenticator app.
        </p>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-group">
                <label for="totp_code">Verification Code</label>
                <input type="text" id="totp_code" name="totp_code" class="form-control" required autofocus
                       placeholder="Enter 6-digit code or recovery code"
                       autocomplete="one-time-code" inputmode="numeric" maxlength="20"
                       style="text-align:center; letter-spacing:4px; font-size:1.25rem;">
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-check"></i> Verify
            </button>
        </form>
        <?php else: ?>
        <!-- Normal Login Form -->
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" required autofocus placeholder="Enter username">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required placeholder="Enter password">
            </div>

            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
        </form>
        <?php endif; ?>

        <a href="<?php echo url('/'); ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to Website</a>
    </div>
</body>
</html>
