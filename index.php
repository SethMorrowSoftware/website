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

    if ($action === 'contact' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
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

    if ($action === 'order_inquiry' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $validServiceTypes = ['containers', 'materials', 'trucking'];
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
        if (!in_array($data['service_type'], $validServiceTypes)) {
            $errors[] = 'Invalid service type.';
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
}

// Route to correct page
$page = $_GET['page'] ?? 'home';
$allowedPages = ['home', 'about', 'containers', 'materials', 'trucking', 'contact', 'order', 'payment'];

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
