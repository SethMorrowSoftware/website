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

    if ($action === 'contact' && isFeatureEnabled('contact_form') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');

        if ($name && filter_var($email, FILTER_VALIDATE_EMAIL) && $message) {
            submitContact($name, $email, $phone, $message);
            $_SESSION['flash_message'] = 'Thank you for your message! We will get back to you shortly.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Please fill in all required fields with valid information.';
            $_SESSION['flash_type'] = 'error';
        }
        redirect('index.php?page=contact');
    }

    if ($action === 'order_inquiry' && isFeatureEnabled('order_inquiry') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Validate service_type against actual category slugs
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

        // Validate phone format (digits, spaces, dashes, parens, plus — at least 7 digits)
        $phoneDigits = preg_replace('/[^0-9]/', '', $data['phone']);
        // Validate date if provided (must be today or future)
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
            $data['phone'] = $phoneDigits; // Store normalized
            submitOrderInquiry($data);
            $_SESSION['flash_message'] = 'Your order inquiry has been submitted! We will contact you within 24 hours.';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = implode(' ', $errors);
            $_SESSION['flash_type'] = 'error';
        }
        redirect('index.php?page=order');
    }

    // ---- Cart Actions (only when cart is enabled) ----

    if ($action === 'add_to_cart' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));
        if ($productId && addToCart($productId, $quantity)) {
            $_SESSION['flash_message'] = 'Item added to your cart!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Could not add item to cart.';
            $_SESSION['flash_type'] = 'error';
        }
        // Redirect back to referring page or catalog
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

    // ---- Checkout Action ----

    if ($action === 'checkout' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $cart = getCart();
        if (empty($cart)) {
            $_SESSION['flash_message'] = 'Your cart is empty.';
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

        // Validate
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

        // Create order
        $orderId = createOrder($customerData, $paymentMethod);
        if (!$orderId) {
            $_SESSION['flash_message'] = 'There was a problem creating your order. Please try again.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=checkout');
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
                // PayPal uses client-side JS SDK — redirect to a PayPal checkout page
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
                // No payment gateway — just mark as pending and complete
                clearCart();
                generateDownloadTokens($orderId);
                sendOrderConfirmation($orderId);
                redirect('index.php?page=order-complete&order=' . $order['order_number'] . '&payment=manual');
                break;
        }
    }
}

// Route to correct page
$page = $_GET['page'] ?? 'home';
$allowedPages = ['home', 'about', 'catalog', 'contact', 'order', 'payment', 'cart', 'checkout', 'order-complete', 'download', 'paypal-checkout'];

// Redirect away from disabled feature pages
$featurePageMap = [
    'catalog'         => 'catalog',
    'cart'            => 'cart',
    'checkout'        => 'cart',
    'paypal-checkout' => 'cart',
    'order'           => 'order_inquiry',
    'contact'         => 'contact_form',
    'about'           => 'about_page',
];
if (isset($featurePageMap[$page]) && !isFeatureEnabled($featurePageMap[$page])) {
    // order-complete and download still need to work even if cart is disabled
    // (for existing orders), so don't block those
    if (!in_array($page, ['order-complete', 'download'])) {
        redirect('/');
    }
}

// Check if it's a system page or a custom page
if (in_array($page, $allowedPages)) {
    $template = __DIR__ . '/pages/' . $page . '.php';
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
