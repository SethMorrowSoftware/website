<?php
/**
 * Business Website CMS
 * Front Controller / Router
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Start session for CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ---- Contact Form ----
    if ($action === 'contact' && isFeatureEnabled('contact_form') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if (isFormRateLimited('contact', 5, 300)) {
            $_SESSION['flash_message'] = 'Too many submissions. Please try again later.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=contact');
        }
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
            recordFormSubmission('contact');
            submitContact($name, $email, $phone, $message);
            $_SESSION['flash_message'] = 'Thank you for your message! We will get back to you shortly.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Please fill in all required fields with valid information.';
            $_SESSION['flash_type'] = 'error';
        }
        redirect('index.php?page=contact');
    }

    // ---- Order Inquiry ----
    if ($action === 'order_inquiry' && isFeatureEnabled('order_inquiry') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if (isFormRateLimited('order_inquiry', 5, 300)) {
            $_SESSION['flash_message'] = 'Too many submissions. Please try again later.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=order');
        }
        $validCategories = array_column(getCategories(), 'slug');
        $data = [
            'service_type' => trim($_POST['service_type'] ?? ''),
            'product_details' => $_POST['product_details'] ?? [],
            'delivery_address' => trim($_POST['delivery_address'] ?? ''),
            'preferred_date' => trim($_POST['preferred_date'] ?? ''),
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'notes' => trim($_POST['notes'] ?? '')
        ];

        $phoneDigits = preg_replace('/[^0-9]/', '', $data['phone']);
        $dateValid = empty($data['preferred_date']) || (strtotime($data['preferred_date']) >= strtotime('today'));

        $errors = [];
        if (!in_array($data['service_type'], $validCategories)) {
            $errors[] = 'Invalid category selected.';
        }
        if (!$data['name'] || strlen($data['name']) < 2) {
            $errors[] = 'Please provide your full name.';
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }
        if (strlen($phoneDigits) < 7) {
            $errors[] = 'Please provide a valid phone number (at least 7 digits).';
        }
        if (!$dateValid) {
            $errors[] = 'Preferred date must be today or in the future.';
        }

        if (empty($errors)) {
            recordFormSubmission('order_inquiry');
            $data['phone'] = $phoneDigits;
            submitOrderInquiry($data);
            $_SESSION['flash_message'] = 'Your order inquiry has been submitted! We will contact you within 24 hours.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = implode(' ', $errors);
            $_SESSION['flash_type'] = 'error';
        }
        redirect('index.php?page=order');
    }

    // ---- Cart Actions ----
    if ($action === 'add_to_cart' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        // Check inventory before adding
        if ($productId && !isInStock($productId, $quantity)) {
            $stock = getStockQuantity($productId);
            if ($stock !== null) {
                $_SESSION['flash_message'] = 'Sorry, only ' . $stock . ' available in stock.';
                $_SESSION['flash_type'] = 'error';
            } else {
                $_SESSION['flash_message'] = 'This item is currently out of stock.';
                $_SESSION['flash_type'] = 'error';
            }
        } elseif ($productId && addToCart($productId, $quantity)) {
            $_SESSION['flash_message'] = 'Item added to your cart!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Could not add item to cart.';
            $_SESSION['flash_type'] = 'error';
        }
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referrer && str_contains($referrer, $_SERVER['HTTP_HOST'])) {
            header('Location: ' . $referrer);
            exit;
        }
        redirect('index.php?page=catalog');
    }

    if ($action === 'update_cart' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        if ($productId) {
            updateCartItem($productId, $quantity);
        }
        redirect('index.php?page=cart');
    }

    if ($action === 'remove_from_cart' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId) {
            removeFromCart($productId);
            $_SESSION['flash_message'] = 'Item removed from cart.';
            $_SESSION['flash_type'] = 'info';
        }
        redirect('index.php?page=cart');
    }

    // ---- Coupon Actions ----
    if ($action === 'apply_coupon' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $code = trim($_POST['coupon_code'] ?? '');
        if ($code) {
            $result = applyCouponToCart($code);
            if ($result['valid']) {
                $_SESSION['flash_message'] = 'Coupon applied! You save ' . formatCurrency($result['discount']) . '.';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = $result['error'];
                $_SESSION['flash_type'] = 'error';
            }
        }
        redirect('index.php?page=cart');
    }

    if ($action === 'remove_coupon' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        removeCouponFromCart();
        $_SESSION['flash_message'] = 'Coupon removed.';
        $_SESSION['flash_type'] = 'info';
        redirect('index.php?page=cart');
    }

    // ---- Checkout Action ----
    if ($action === 'checkout' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $cart = getCart();
        if (empty($cart)) {
            $_SESSION['flash_message'] = 'Your cart is empty.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=cart');
        }

        // Validate stock for all cart items
        $outOfStock = validateCartStock();
        if (!empty($outOfStock)) {
            $names = array_column($outOfStock, 'name');
            $_SESSION['flash_message'] = 'Some items are out of stock: ' . implode(', ', $names) . '. Please update your cart.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=cart');
        }

        $customerData = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'shipping_address' => trim($_POST['shipping_address'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
        ];

        $paymentMethod = $_POST['payment_method'] ?? 'manual';

        $errors = [];
        if (!$customerData['name'] || strlen($customerData['name']) < 2) {
            $errors[] = 'Please provide your full name.';
        }
        if (!filter_var($customerData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please provide a valid email address.';
        }

        if (!empty($errors)) {
            $_SESSION['flash_message'] = implode(' ', $errors);
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=checkout');
        }

        // Create order (with coupon if applied)
        $orderId = createOrder($customerData, $paymentMethod);
        if (!$orderId) {
            $_SESSION['flash_message'] = 'There was a problem creating your order. Please try again.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=checkout');
        }

        // Process inventory
        processOrderInventory($orderId);

        // Handle coupon
        $coupon = getAppliedCoupon();
        if ($coupon) {
            $db = getDB();
            $db->prepare('UPDATE orders SET coupon_id = ?, coupon_code = ?, discount_amount = ? WHERE id = ?')
               ->execute([$coupon['id'], $coupon['code'], $coupon['discount'], $orderId]);
            incrementCouponUsage($coupon['id']);
            removeCouponFromCart();
        }

        // Link to customer account if logged in
        if (isCustomerLoggedIn()) {
            $db = getDB();
            $db->prepare('UPDATE orders SET customer_id = ? WHERE id = ?')
               ->execute([getCustomerId(), $orderId]);
        }

        $order = getOrder($orderId);

        // Route to payment provider
        switch ($paymentMethod) {
            case 'stripe':
                $stripeUrl = createStripeCheckoutSession($orderId);
                if ($stripeUrl) {
                    clearCart();
                    header('Location: ' . $stripeUrl);
                    exit;
                } else {
                    $_SESSION['flash_message'] = 'Could not connect to Stripe. Please try another payment method.';
                    $_SESSION['flash_type'] = 'error';
                    redirect('index.php?page=checkout');
                }
                break;

            case 'paypal':
                clearCart();
                $_SESSION['pending_paypal_order'] = $orderId;
                redirect('index.php?page=paypal-checkout&order=' . $order['order_number']);
                break;

            case 'square':
                $squareUrl = createSquareCheckout($orderId);
                if ($squareUrl) {
                    clearCart();
                    header('Location: ' . $squareUrl);
                    exit;
                } else {
                    $_SESSION['flash_message'] = 'Could not connect to Square. Please try another payment method.';
                    $_SESSION['flash_type'] = 'error';
                    redirect('index.php?page=checkout');
                }
                break;

            case 'btcpay':
                $btcpayUrl = createBTCPayInvoice($orderId);
                if ($btcpayUrl) {
                    clearCart();
                    header('Location: ' . $btcpayUrl);
                    exit;
                } else {
                    $_SESSION['flash_message'] = 'Could not connect to BTCPay Server. Please try another payment method.';
                    $_SESSION['flash_type'] = 'error';
                    redirect('index.php?page=checkout');
                }
                break;

            case 'manual':
            default:
                clearCart();
                generateDownloadTokens($orderId);
                sendOrderConfirmation($orderId);
                redirect('index.php?page=order-complete&order=' . $order['order_number'] . '&payment=manual');
                break;
        }
    }

    // ---- Wishlist Actions ----
    if ($action === 'add_wishlist' && isFeatureEnabled('wishlists') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId) {
            addToWishlist($productId);
            $_SESSION['flash_message'] = 'Added to your wishlist!';
            $_SESSION['flash_type'] = 'success';
        }
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referrer && str_contains($referrer, $_SERVER['HTTP_HOST'])) {
            header('Location: ' . $referrer);
            exit;
        }
        redirect('index.php?page=catalog');
    }

    if ($action === 'remove_wishlist' && isFeatureEnabled('wishlists') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId) {
            removeFromWishlist($productId);
            $_SESSION['flash_message'] = 'Removed from wishlist.';
            $_SESSION['flash_type'] = 'info';
        }
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referrer && str_contains($referrer, $_SERVER['HTTP_HOST'])) {
            header('Location: ' . $referrer);
            exit;
        }
        redirect('index.php?page=wishlist');
    }

    // ---- Customer Account Actions ----
    if ($action === 'customer_login' && isFeatureEnabled('customer_accounts') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (isFormRateLimited('customer_login', 10, 300)) {
            $_SESSION['flash_message'] = 'Too many login attempts. Please try again later.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=login');
        }

        $customer = authenticateCustomer($email, $password);
        if ($customer) {
            loginCustomer($customer);
            $_SESSION['flash_message'] = 'Welcome back, ' . e($customer['first_name']) . '!';
            $_SESSION['flash_type'] = 'success';
            redirect('index.php?page=account');
        } else {
            recordFormSubmission('customer_login');
            $_SESSION['flash_message'] = 'Invalid email or password.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=login');
        }
    }

    if ($action === 'customer_register' && isFeatureEnabled('customer_accounts') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if (isFormRateLimited('customer_register', 3, 600)) {
            $_SESSION['flash_message'] = 'Too many attempts. Please try again later.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=register');
        }

        $firstName = trim($_POST['first_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $errors = [];
        if (strlen($firstName) < 1) $errors[] = 'First name is required.';
        if (strlen($lastName) < 1) $errors[] = 'Last name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please provide a valid email address.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $passwordConfirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            recordFormSubmission('customer_register');
            $customerId = registerCustomer($email, $password, $firstName, $lastName, $phone);
            if ($customerId) {
                $customer = getLoggedInCustomer() ?: ['id' => $customerId, 'email' => $email, 'first_name' => $firstName, 'last_name' => $lastName];
                loginCustomer(['id' => $customerId, 'email' => $email, 'first_name' => $firstName, 'last_name' => $lastName]);
                $_SESSION['flash_message'] = 'Account created successfully! Welcome, ' . e($firstName) . '!';
                $_SESSION['flash_type'] = 'success';
                redirect('index.php?page=account');
            } else {
                $_SESSION['flash_message'] = 'An account with this email already exists. Please sign in instead.';
                $_SESSION['flash_type'] = 'error';
                redirect('index.php?page=login');
            }
        } else {
            $_SESSION['flash_message'] = implode(' ', $errors);
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=register');
        }
    }

    if ($action === 'customer_logout' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        logoutCustomer();
        $_SESSION['flash_message'] = 'You have been signed out.';
        $_SESSION['flash_type'] = 'info';
        redirect('/');
    }

    if ($action === 'update_profile' && isCustomerLoggedIn() && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'default_shipping_address' => trim($_POST['default_shipping_address'] ?? ''),
        ];

        if ($data['first_name'] && $data['last_name']) {
            updateCustomerProfile(getCustomerId(), $data);
            $_SESSION['customer_name'] = $data['first_name'] . ' ' . $data['last_name'];
            $_SESSION['flash_message'] = 'Profile updated successfully.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Name fields are required.';
            $_SESSION['flash_type'] = 'error';
        }
        redirect('index.php?page=account&tab=profile');
    }

    if ($action === 'change_customer_password' && isCustomerLoggedIn() && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['new_password_confirm'] ?? '';

        if (strlen($newPassword) < 8) {
            $_SESSION['flash_message'] = 'New password must be at least 8 characters.';
            $_SESSION['flash_type'] = 'error';
        } elseif ($newPassword !== $confirmPassword) {
            $_SESSION['flash_message'] = 'New passwords do not match.';
            $_SESSION['flash_type'] = 'error';
        } elseif (!changeCustomerPassword(getCustomerId(), $currentPassword, $newPassword)) {
            $_SESSION['flash_message'] = 'Current password is incorrect.';
            $_SESSION['flash_type'] = 'error';
        } else {
            $_SESSION['flash_message'] = 'Password updated successfully.';
            $_SESSION['flash_type'] = 'success';
        }
        redirect('index.php?page=account&tab=password');
    }
}

// Route to correct page
$page = $_GET['page'] ?? 'home';
$allowedPages = [
    'home', 'about', 'catalog', 'contact', 'order', 'payment',
    'cart', 'checkout', 'order-complete', 'download', 'paypal-checkout',
    // New pages
    'product', 'search', 'login', 'register', 'account', 'wishlist',
    'forgot-password', 'reset-password', 'order-status',
];

// Redirect away from disabled feature pages
$featurePageMap = [
    'catalog'         => 'catalog',
    'cart'            => 'cart',
    'checkout'        => 'cart',
    'paypal-checkout' => 'cart',
    'order'           => 'order_inquiry',
    'contact'         => 'contact_form',
    'about'           => 'about_page',
    'login'           => 'customer_accounts',
    'register'        => 'customer_accounts',
    'account'         => 'customer_accounts',
    'wishlist'        => 'wishlists',
];
if (isset($featurePageMap[$page]) && !isFeatureEnabled($featurePageMap[$page])) {
    if (!in_array($page, ['order-complete', 'download'])) {
        redirect('/');
    }
}

// Check if it's a system page or a custom page
if (in_array($page, $allowedPages)) {
    $template = __DIR__ . '/pages/' . $page . '.php';
    if (!file_exists($template)) {
        http_response_code(404);
        $template = __DIR__ . '/pages/home.php';
        $page = 'home';
    }
} else {
    // Check for custom page in database
    $customPage = getPage($page);
    if ($customPage) {
        $template = __DIR__ . '/pages/custom.php';
    } else {
        http_response_code(404);
        $template = __DIR__ . '/pages/home.php';
        $page = 'home';
    }
}

// Flash message handling
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Load the page
require_once __DIR__ . '/includes/header.php';
require_once $template;
require_once __DIR__ . '/includes/footer.php';
