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

// Plugin hook: early init (after session, before routing)
do_action('init');

// Maintenance mode check
if (getSetting('maintenance_mode') === '1' || getSetting('enable_maintenance') === '1') {
    // Allow admin access
    $isAdminPath = str_contains($_SERVER['REQUEST_URI'] ?? '', '/admin');
    if (!$isAdminPath) {
        $maintenanceMsg = getSetting('maintenance_message', 'We are currently performing scheduled maintenance. We will be back online shortly.');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Maintenance</title>';
        echo '<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f8fafc;display:flex;align-items:center;justify-content:center;min-height:100vh;padding:20px;color:#334155}';
        echo '.maintenance{text-align:center;max-width:500px}.maintenance i{font-size:4rem;color:#f59e0b;margin-bottom:1.5rem}.maintenance h1{font-size:1.75rem;margin-bottom:1rem}.maintenance p{color:#64748b;line-height:1.6}</style>';
        echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"></head>';
        echo '<body><div class="maintenance"><i class="fas fa-tools"></i><h1>Under Maintenance</h1><p>' . htmlspecialchars($maintenanceMsg, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
        exit;
    }
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page.']);
        exit;
    }

    if ($action === 'add_to_cart' && isFeatureEnabled('cart')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if ($productId && !isInStock($productId, $quantity)) {
            $stock = getStockQuantity($productId);
            echo json_encode(['success' => false, 'message' => $stock !== null ? "Sorry, only $stock available in stock." : 'This item is currently out of stock.']);
            exit;
        }

        if ($productId && addToCart($productId, $quantity)) {
            echo json_encode(['success' => true, 'message' => 'Item added to your cart!', 'cartCount' => getCartCount()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Could not add item to cart.']);
        }
        exit;
    }

    if ($action === 'add_wishlist' && isFeatureEnabled('wishlists')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId) {
            addToWishlist($productId);
            echo json_encode(['success' => true, 'message' => 'Added to your wishlist!', 'wishlistCount' => getWishlistCount()]);
        }
        exit;
    }

    if ($action === 'remove_wishlist' && isFeatureEnabled('wishlists')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId) {
            removeFromWishlist($productId);
            echo json_encode(['success' => true, 'message' => 'Removed from wishlist.', 'wishlistCount' => getWishlistCount()]);
        }
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
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
        redirectToReferrer('index.php?page=catalog');
    }

    if ($action === 'update_cart' && isFeatureEnabled('cart') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        $quantity = (int)($_POST['quantity'] ?? 0);
        if ($productId) {
            // Validate against available stock before updating
            if ($quantity > 0 && !isInStock($productId, $quantity)) {
                $available = getStockQuantity($productId);
                if ($available !== null) {
                    $_SESSION['flash_message'] = 'Only ' . $available . ' available in stock.';
                    $_SESSION['flash_type'] = 'error';
                    $quantity = $available; // Cap to available stock
                }
            }
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

        // Calculate shipping — validate that submitted method is actually available.
        // Only methods returned by getAvailableShippingMethods() are accepted.
        // No fallback: if the method is not in the available list, reject checkout.
        $shippingMethodId = (int)($_POST['shipping_method_id'] ?? 0);
        $shippingCost = 0;
        $shippingMethodName = '';
        if ($shippingMethodId > 0) {
            $availableMethods = cartHasPhysicalItems() ? getAvailableShippingMethods() : [];
            $validMethod = null;
            foreach ($availableMethods as $method) {
                if ((int)$method['id'] === $shippingMethodId) {
                    $validMethod = $method;
                    break;
                }
            }
            if ($validMethod) {
                $shippingCost = $validMethod['cost'];
                $shippingMethodName = $validMethod['name'];
            } else {
                $_SESSION['flash_message'] = 'The selected shipping method is not available. Please choose a valid option.';
                $_SESSION['flash_type'] = 'error';
                redirect('index.php?page=checkout');
            }
        }

        // Build adjustments to apply atomically within the order transaction.
        // This ensures the persisted order always has consistent pricing,
        // shipping, and ownership — no partial-state window.
        $adjustments = [];

        // Coupon discount — increment usage atomically BEFORE creating order.
        // incrementCouponUsage() is atomic — if the usage limit was reached
        // between validation and now (concurrent checkout race), the coupon
        // is silently skipped rather than over-redeemed.
        $coupon = getAppliedCoupon();
        if ($coupon) {
            if (incrementCouponUsage($coupon['id'])) {
                $adjustments['coupon'] = [
                    'id' => $coupon['id'],
                    'code' => $coupon['code'],
                    'discount' => $coupon['discount'],
                ];
            } else {
                error_log('[COUPON] Usage limit reached for coupon #' . $coupon['id'] . ' during checkout');
            }
            removeCouponFromCart();
        }

        // Shipping cost
        if ($shippingCost > 0 || $shippingMethodName) {
            $adjustments['shipping'] = [
                'cost' => $shippingCost,
                'method' => $shippingMethodName,
            ];
        }

        // Customer account linkage
        if (isCustomerLoggedIn()) {
            $adjustments['customer_id'] = getCustomerId();
        }

        // Create order with all adjustments in a single transaction
        $orderId = createOrder($customerData, $paymentMethod, $adjustments);
        if (!$orderId) {
            // If coupon usage was incremented but order creation failed,
            // the coupon usage is already consumed. This is acceptable:
            // the alternative (decrementing on failure) risks races.
            $_SESSION['flash_message'] = 'There was a problem creating your order. Please try again.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=checkout');
        }

        $db = getDB();

        // Re-fetch the order with all adjustments already applied
        $order = getOrder($orderId);

        // Store order number in session for order-complete page verification
        $_SESSION['recent_order_number'] = $order['order_number'];

        // Route to payment provider
        // Note: inventory is decremented immediately for manual orders,
        // and on payment confirmation for gateway orders (to avoid
        // permanently decrementing stock on abandoned payments).
        switch ($paymentMethod) {
            case 'stripe':
                $stripeUrl = createStripeCheckoutSession($orderId);
                if ($stripeUrl) {
                    $_SESSION['pending_checkout_order'] = $orderId;
                    header('Location: ' . $stripeUrl);
                    exit;
                } else {
                    // Mark order as failed — gateway session could not be created.
                    // Prevents orphaned pending orders from cluttering the system.
                    updateOrderPayment($orderId, 'failed', '', 'stripe');
                    $_SESSION['flash_message'] = 'Could not connect to Stripe. Please try another payment method.';
                    $_SESSION['flash_type'] = 'error';
                    redirect('index.php?page=checkout');
                }
                break;

            case 'paypal':
                $_SESSION['pending_paypal_order'] = $orderId;
                $_SESSION['pending_checkout_order'] = $orderId;
                redirect('index.php?page=paypal-checkout&order=' . $order['order_number']);
                break;

            case 'square':
                $squareUrl = createSquareCheckout($orderId);
                if ($squareUrl) {
                    $_SESSION['pending_checkout_order'] = $orderId;
                    header('Location: ' . $squareUrl);
                    exit;
                } else {
                    updateOrderPayment($orderId, 'failed', '', 'square');
                    $_SESSION['flash_message'] = 'Could not connect to Square. Please try another payment method.';
                    $_SESSION['flash_type'] = 'error';
                    redirect('index.php?page=checkout');
                }
                break;

            case 'btcpay':
                $btcpayUrl = createBTCPayInvoice($orderId);
                if ($btcpayUrl) {
                    $_SESSION['pending_checkout_order'] = $orderId;
                    header('Location: ' . $btcpayUrl);
                    exit;
                } else {
                    updateOrderPayment($orderId, 'failed', '', 'btcpay');
                    $_SESSION['flash_message'] = 'Could not connect to BTCPay Server. Please try another payment method.';
                    $_SESSION['flash_type'] = 'error';
                    redirect('index.php?page=checkout');
                }
                break;

            case 'manual':
            default:
                $inventoryOk = processOrderInventory($orderId);
                if (!$inventoryOk) {
                    // Stock decrement failed — place order under manual review
                    // instead of confirming with unreserved inventory.
                    $db->prepare("UPDATE orders SET order_status = 'needs_review', payment_status = 'on_hold', updated_at = CURRENT_TIMESTAMP WHERE id = ?")
                       ->execute([$orderId]);
                    clearCart();
                    $_SESSION['flash_message'] = 'Your order has been placed but some items could not be reserved. Our team will review it and contact you shortly.';
                    $_SESSION['flash_type'] = 'warning';
                    redirect('index.php?page=order-complete&order=' . $order['order_number'] . '&payment=manual&review=1');
                }
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
        redirectToReferrer('index.php?page=catalog');
    }

    if ($action === 'remove_wishlist' && isFeatureEnabled('wishlists') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $productId = (int)($_POST['product_id'] ?? 0);
        if ($productId) {
            removeFromWishlist($productId);
            $_SESSION['flash_message'] = 'Removed from wishlist.';
            $_SESSION['flash_type'] = 'info';
        }
        redirectToReferrer('index.php?page=wishlist');
    }

    // ---- Review Submission ----
    if ($action === 'submit_review' && isFeatureEnabled('reviews') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if (isFormRateLimited('review', 3, 3600)) {
            $_SESSION['flash_message'] = 'Too many reviews submitted. Please try again later.';
            $_SESSION['flash_type'] = 'error';
        } else {
            $productId = (int)($_POST['product_id'] ?? 0);
            $data = [
                'name' => trim($_POST['reviewer_name'] ?? ''),
                'email' => trim($_POST['reviewer_email'] ?? ''),
                'rating' => (int)($_POST['rating'] ?? 0),
                'title' => trim($_POST['review_title'] ?? ''),
                'body' => trim($_POST['review_body'] ?? ''),
            ];

            $errors = [];
            if (!$data['name']) $errors[] = 'Name is required.';
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
            if ($data['rating'] < 1 || $data['rating'] > 5) $errors[] = 'Rating must be between 1 and 5.';
            if (!$data['body'] || strlen($data['body']) < 10) $errors[] = 'Review must be at least 10 characters.';

            if (empty($errors) && $productId) {
                recordFormSubmission('review');
                submitReview($productId, $data);
                $_SESSION['flash_message'] = 'Thank you for your review! It will be visible after approval.';
                $_SESSION['flash_type'] = 'success';
            } else {
                $_SESSION['flash_message'] = !empty($errors) ? implode(' ', $errors) : 'Could not submit review.';
                $_SESSION['flash_type'] = 'error';
            }
        }
        $slug = $_POST['product_slug'] ?? '';
        redirect('index.php?page=product&slug=' . urlencode($slug) . '#reviews');
    }

    // ---- Blog Comment Submission ----
    if ($action === 'submit_blog_comment' && isFeatureEnabled('blog') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if (isFormRateLimited('blog_comment', 5, 300)) {
            $_SESSION['flash_message'] = 'Too many comments. Please try again later.';
            $_SESSION['flash_type'] = 'error';
        } else {
            $postId = (int)($_POST['post_id'] ?? 0);
            $commentData = [
                'parent_id' => (int)($_POST['parent_id'] ?? 0) ?: null,
                'customer_id' => isCustomerLoggedIn() ? getCustomerId() : null,
                'author_name' => trim($_POST['commenter_name'] ?? ''),
                'author_email' => trim($_POST['commenter_email'] ?? ''),
                'content' => trim($_POST['comment_content'] ?? ''),
            ];

            $errors = [];
            if (!$commentData['author_name']) $errors[] = 'Name is required.';
            if (!filter_var($commentData['author_email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
            if (!$commentData['content'] || strlen($commentData['content']) < 3) $errors[] = 'Comment must be at least 3 characters.';

            if (empty($errors) && $postId && getSetting('blog_allow_comments', '1') === '1') {
                recordFormSubmission('blog_comment');
                $commentId = submitBlogComment($postId, $commentData);
                if ($commentId > 0) {
                    $moderation = getSetting('blog_comment_moderation', '1') === '1';
                    $_SESSION['flash_message'] = $moderation
                        ? 'Thank you! Your comment will appear after approval.'
                        : 'Comment posted successfully!';
                    $_SESSION['flash_type'] = 'success';
                } else {
                    $_SESSION['flash_message'] = 'Comments are closed for this post.';
                    $_SESSION['flash_type'] = 'error';
                }
            } else {
                $_SESSION['flash_message'] = !empty($errors) ? implode(' ', $errors) : 'Could not submit comment.';
                $_SESSION['flash_type'] = 'error';
            }
        }
        $commentPostSlug = $_POST['post_slug'] ?? '';
        redirect('index.php?page=blog-post&slug=' . urlencode($commentPostSlug) . '#comments');
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

    // ---- Forgot Password ----
    if ($action === 'forgot_password' && isFeatureEnabled('customer_accounts') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if (isFormRateLimited('forgot_password', 5, 300)) {
            $_SESSION['flash_message'] = 'Too many attempts. Please try again later.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=forgot-password');
        }

        $email = trim($_POST['email'] ?? '');
        // Always show success message to prevent email enumeration
        $_SESSION['flash_message'] = 'If an account exists with that email, a password reset link has been sent.';
        $_SESSION['flash_type'] = 'success';

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            recordFormSubmission('forgot_password');
            $customer = getCustomerByEmail($email);
            if ($customer) {
                $token = createPasswordResetToken($customer['id']);
                sendPasswordResetEmail($customer['email'], $customer['first_name'], $token);
            }
        }
        redirect('index.php?page=forgot-password');
    }

    // ---- Reset Password ----
    if ($action === 'reset_password' && isFeatureEnabled('customer_accounts') && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        if (strlen($password) < 8) {
            $_SESSION['flash_message'] = 'Password must be at least 8 characters.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=reset-password&token=' . urlencode($token));
        }

        if ($password !== $passwordConfirm) {
            $_SESSION['flash_message'] = 'Passwords do not match.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=reset-password&token=' . urlencode($token));
        }

        $customerId = validatePasswordResetToken($token);
        if ($customerId) {
            resetCustomerPassword($customerId, $password);
            $_SESSION['flash_message'] = 'Your password has been reset. You can now sign in.';
            $_SESSION['flash_type'] = 'success';
            redirect('index.php?page=login');
        } else {
            $_SESSION['flash_message'] = 'This reset link is invalid or has expired.';
            $_SESSION['flash_type'] = 'error';
            redirect('index.php?page=forgot-password');
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

// Plugin hook: before routing
do_action('before_route');

// Route to correct page
$page = $_GET['page'] ?? 'home';
$allowedPages = [
    'home', 'about', 'catalog', 'contact', 'order', 'payment',
    'cart', 'checkout', 'order-complete', 'download', 'paypal-checkout',
    // New pages
    'product', 'search', 'login', 'register', 'account', 'wishlist',
    'forgot-password', 'reset-password', 'order-status',
    // Blog pages
    'blog', 'blog-post',
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
    'forgot-password' => 'customer_accounts',
    'reset-password'  => 'customer_accounts',
    'wishlist'        => 'wishlists',
    'blog'            => 'blog',
    'blog-post'       => 'blog',
];
if (isset($featurePageMap[$page]) && !isFeatureEnabled($featurePageMap[$page])) {
    if (!in_array($page, ['order-complete', 'download'])) {
        redirect('/');
    }
}

// Check if it's a system page or a custom page
if (in_array($page, $allowedPages)) {
    // Theme template override: check active theme first, fall back to default
    $template = resolveTemplate('pages/' . $page . '.php');
    if (!file_exists($template)) {
        http_response_code(404);
        $template = resolveTemplate('pages/home.php');
        $page = 'home';
    }
} else {
    // Check for custom page in database
    $customPage = getPage($page);
    if ($customPage) {
        $template = resolveTemplate('pages/custom.php');
    } else {
        http_response_code(404);
        $template = resolveTemplate('pages/home.php');
        $page = 'home';
    }
}

// Flash message handling
$flashMessage = $_SESSION['flash_message'] ?? null;
$flashType = $_SESSION['flash_type'] ?? 'info';
unset($_SESSION['flash_message'], $_SESSION['flash_type']);

// Plugin hook: allow plugins to modify the template path
$template = apply_filters('page_template', $template, $page);

// Plugin hook: before page render
do_action('before_render', $page);

// Load the page with output buffering so template-level redirects work
ob_start();
require_once resolveTemplate('includes/header.php');
require_once $template;
require_once resolveTemplate('includes/footer.php');
do_action('after_render', $page);
ob_end_flush();
