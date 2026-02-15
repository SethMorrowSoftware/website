<?php
/**
 * Helper functions
 */

require_once __DIR__ . '/../config.php';

// ============================================================
// Store Configuration Helpers
// ============================================================

/**
 * Check if a feature is enabled via settings.
 * Accepts: 'catalog', 'cart', 'order_inquiry', 'contact_form',
 *          'testimonials', 'about_page', 'phone_header', 'email_header',
 *          'address', 'business_hours', 'map'
 */
function isFeatureEnabled(string $feature): bool {
    // Map short names to setting keys
    $map = [
        'catalog'        => 'enable_catalog',
        'cart'           => 'enable_cart',
        'order_inquiry'  => 'enable_order_inquiry',
        'contact_form'   => 'enable_contact_form',
        'testimonials'   => 'enable_testimonials',
        'about_page'     => 'enable_about_page',
        'phone_header'   => 'show_phone_header',
        'email_header'   => 'show_email_header',
        'address'        => 'show_address',
        'business_hours' => 'show_business_hours',
        'map'            => 'show_map',
    ];

    $key = $map[$feature] ?? $feature;
    return getSetting($key, '1') === '1';
}

/**
 * Get store type: products_and_services, products_only, services_only,
 *                 digital_only, informational
 */
function getStoreType(): string {
    return getSetting('store_type', 'products_and_services');
}

/**
 * Get business type: local, online, hybrid
 */
function getBusinessType(): string {
    return getSetting('business_type', 'local');
}

/**
 * Check if the store sells physical products
 */
function storeHasProducts(): bool {
    $type = getStoreType();
    return in_array($type, ['products_and_services', 'products_only', 'digital_only']);
}

/**
 * Check if the store offers services
 */
function storeHasServices(): bool {
    $type = getStoreType();
    return in_array($type, ['products_and_services', 'services_only']);
}

/**
 * Check if the store is purely informational (no catalog, no cart)
 */
function storeIsInformational(): bool {
    return getStoreType() === 'informational';
}

/**
 * Check whether a hero CTA link targets a feature page that is currently
 * disabled.  Returns true when the link is safe to render, false when the
 * destination would redirect the user to the homepage.
 */
function isHeroCtaLinkEnabled(?string $link): bool {
    if (!$link) return false;

    // Normalise: strip BASE_URL prefix, query-string form, and clean-URL form
    $path = $link;
    // Remove leading base-url if present
    if (BASE_URL && str_starts_with($path, ltrim(BASE_URL, '/'))) {
        $path = substr($path, strlen(ltrim(BASE_URL, '/')));
    }

    // Extract the page slug from either "index.php?page=X" or "/X"
    $slug = null;
    if (preg_match('/[?&]page=([a-z_-]+)/i', $path, $m)) {
        $slug = $m[1];
    } else {
        $slug = trim(parse_url($path, PHP_URL_PATH) ?? '', '/');
    }

    if (!$slug) return true; // homepage or external — always OK

    $featureMap = [
        'catalog'         => 'catalog',
        'cart'            => 'cart',
        'checkout'        => 'cart',
        'paypal-checkout' => 'cart',
        'order'           => 'order_inquiry',
        'contact'         => 'contact_form',
        'about'           => 'about_page',
    ];

    if (isset($featureMap[$slug])) {
        return isFeatureEnabled($featureMap[$slug]);
    }

    return true; // custom page or unknown — allow
}

/**
 * Get a customizable label with fallback default
 */
function getLabel(string $key, string $default = ''): string {
    return getSetting($key, $default);
}

/**
 * Get all navigation items (ordered)
 */
function getNavigation(): array {
    $db = getDB();
    $stmt = $db->query('SELECT n.*, p.slug as page_slug, p.is_published
                        FROM navigation n
                        LEFT JOIN pages p ON n.page_id = p.id
                        WHERE n.is_visible = 1
                        ORDER BY n.sort_order ASC');
    $items = $stmt->fetchAll();

    // Build tree structure
    $tree = [];
    $children = [];
    foreach ($items as $item) {
        if ($item['parent_id']) {
            $children[$item['parent_id']][] = $item;
        } else {
            $tree[] = $item;
        }
    }
    foreach ($tree as &$item) {
        $item['children'] = $children[$item['id']] ?? [];
    }
    return $tree;
}

/**
 * Get page by slug
 */
function getPage(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM pages WHERE slug = ? AND is_published = 1');
    $stmt->execute([$slug]);
    $page = $stmt->fetch();
    return $page ?: null;
}

/**
 * Get hero section for a page
 */
function getHero(string $pageSlug): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM hero_sections WHERE page_slug = ? AND is_active = 1');
    $stmt->execute([$pageSlug]);
    $hero = $stmt->fetch();
    return $hero ?: null;
}

/**
 * Get all visible product categories
 */
function getCategories(): array {
    $db = getDB();
    $stmt = $db->query('SELECT * FROM product_categories WHERE is_visible = 1 ORDER BY sort_order ASC');
    return $stmt->fetchAll();
}

/**
 * Get products by category
 */
function getProductsByCategory(int $categoryId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM products WHERE category_id = ? AND is_visible = 1 ORDER BY sort_order ASC');
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll();
}

/**
 * Get all visible testimonials
 */
function getTestimonials(): array {
    $db = getDB();
    $stmt = $db->query('SELECT * FROM testimonials WHERE is_visible = 1 ORDER BY sort_order ASC');
    return $stmt->fetchAll();
}

/**
 * Get all products (for order form and catalog)
 */
function getAllProducts(): array {
    $db = getDB();
    $stmt = $db->query('SELECT p.*, pc.name as category_name, pc.slug as category_slug
                        FROM products p
                        JOIN product_categories pc ON p.category_id = pc.id
                        WHERE p.is_visible = 1 AND p.is_available = 1
                        ORDER BY pc.sort_order, p.sort_order');
    return $stmt->fetchAll();
}

/**
 * Get featured products (limited set for homepage)
 */
function getFeaturedProducts(int $limit = 6): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT p.*, pc.name as category_name, pc.icon as category_icon
                          FROM products p
                          JOIN product_categories pc ON p.category_id = pc.id
                          WHERE p.is_visible = 1
                          ORDER BY pc.sort_order, p.sort_order
                          LIMIT ?');
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Submit contact form
 */
function submitContact(string $name, string $email, string $phone, string $message): bool {
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO contact_submissions (name, email, phone, message) VALUES (?, ?, ?, ?)');
    $result = $stmt->execute([$name, $email, $phone, $message]);

    // Send email notification
    $notifyEmail = getSetting('contact_email');
    if ($notifyEmail && $result) {
        $subject = 'New Contact Form Submission - ' . SITE_NAME;
        $body = "New contact form submission:\n\n";
        $body .= "Name: $name\n";
        $body .= "Email: $email\n";
        $body .= "Phone: $phone\n";
        $body .= "Message:\n$message\n";
        sendNotificationEmail($notifyEmail, $subject, $body);
    }

    return $result;
}

/**
 * Submit order inquiry
 */
function submitOrderInquiry(array $data): bool {
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO order_inquiries (service_type, product_details, delivery_address, preferred_date, name, email, phone, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $result = $stmt->execute([
        $data['service_type'],
        json_encode($data['product_details'] ?? []),
        $data['delivery_address'] ?? '',
        $data['preferred_date'] ?? '',
        $data['name'],
        $data['email'],
        $data['phone'],
        $data['notes'] ?? ''
    ]);

    // Send email notification
    $notifyEmail = getSetting('contact_email');
    if ($notifyEmail && $result) {
        $subject = 'New Order Inquiry - ' . SITE_NAME;
        $body = "New order inquiry received:\n\n";
        $body .= "Category: {$data['service_type']}\n";
        $body .= "Name: {$data['name']}\n";
        $body .= "Email: {$data['email']}\n";
        $body .= "Phone: {$data['phone']}\n";
        $body .= "Delivery Address: " . ($data['delivery_address'] ?? 'N/A') . "\n";
        $body .= "Preferred Date: " . ($data['preferred_date'] ?? 'N/A') . "\n";
        $body .= "Notes: " . ($data['notes'] ?? 'N/A') . "\n";
        sendNotificationEmail($notifyEmail, $subject, $body);
    }

    return $result;
}

/**
 * Send email with logging (replaces raw @mail)
 */
function sendNotificationEmail(string $to, string $subject, string $body): bool {
    $headers = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $sent = mail($to, $subject, $body, $headers);
    if (!$sent) {
        error_log("[MAIL FAILURE] To: $to | Subject: $subject | " . date('Y-m-d H:i:s'));
    }
    return $sent;
}

/**
 * Create URL-safe slug
 */
function createSlug(string $text): string {
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9-]/', '-', $slug);
    $slug = preg_replace('/-+/', '-', $slug);
    return trim($slug, '-');
}

/**
 * Handle file upload
 */
function handleUpload(array $file, string $subdir = 'images'): ?string {
    // MIME-to-extension map (SVG removed — can carry active content)
    $mimeExtMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
        'video/mp4'  => 'mp4',
        'video/webm' => 'webm',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!isset($mimeExtMap[$mimeType])) {
        return null;
    }

    // Max 50MB
    if ($file['size'] > 50 * 1024 * 1024) {
        return null;
    }

    if (str_starts_with($mimeType, 'video/')) {
        $subdir = 'videos';
    }

    $uploadDir = UPLOADS_PATH . '/' . $subdir;
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Derive extension from MIME type, not user filename
    $ext = $mimeExtMap[$mimeType];
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Add to media library
        $db = getDB();
        $stmt = $db->prepare('INSERT INTO media (filename, original_name, mime_type, file_size) VALUES (?, ?, ?, ?)');
        $stmt->execute([$subdir . '/' . $filename, $file['name'], $mimeType, $file['size']]);

        return UPLOADS_URL . '/' . $subdir . '/' . $filename;
    }

    return null;
}

/**
 * Format date for display
 */
function formatDate(string $date): string {
    return date('M j, Y g:i A', strtotime($date));
}

/**
 * Get unread inquiry count
 */
function getUnreadCount(): array {
    $db = getDB();
    $contacts = $db->query('SELECT COUNT(*) FROM contact_submissions WHERE is_read = 0')->fetchColumn();
    $orders = $db->query('SELECT COUNT(*) FROM order_inquiries WHERE is_read = 0')->fetchColumn();
    return ['contacts' => $contacts, 'orders' => $orders];
}

/**
 * Check if current page matches slug
 */
function isCurrentPage(string $slug): bool {
    $currentPage = $_GET['page'] ?? 'home';
    return $currentPage === $slug;
}

/**
 * Get image URL with fallback placeholder
 */
function getImageUrl(?string $path, ?string $placeholder = null): string {
    if ($placeholder === null) {
        $placeholder = asset('images/placeholders/default.jpg');
    }
    if ($path && file_exists(BASE_PATH . $path)) {
        return $path;
    }
    if ($path && (str_contains($path, '/uploads/') || str_starts_with($path, 'uploads/'))) {
        return $path;
    }
    return $placeholder;
}

// ============================================================
// Shopping Cart Functions
// ============================================================

/**
 * Get current cart from session
 */
function getCart(): array {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return $_SESSION['cart'] ?? [];
}

/**
 * Add item to cart
 */
function addToCart(int $productId, int $quantity = 1): bool {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $db = getDB();
    $stmt = $db->prepare('SELECT p.*, pc.name as category_name FROM products p JOIN product_categories pc ON p.category_id = pc.id WHERE p.id = ? AND p.is_visible = 1 AND p.is_available = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) return false;

    // Parse price — extract numeric value
    $numericPrice = parsePrice($product['price']);

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    $key = (string)$productId;
    if (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart'][$key] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'price' => $numericPrice,
            'price_display' => $product['price'],
            'unit' => $product['unit'],
            'image' => $product['image'],
            'product_type' => $product['product_type'] ?? 'physical',
            'category' => $product['category_name'],
            'quantity' => $quantity,
        ];
    }
    return true;
}

/**
 * Update cart item quantity
 */
function updateCartItem(int $productId, int $quantity): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $key = (string)$productId;
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$key]);
    } elseif (isset($_SESSION['cart'][$key])) {
        $_SESSION['cart'][$key]['quantity'] = $quantity;
    }
}

/**
 * Remove item from cart
 */
function removeFromCart(int $productId): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['cart'][(string)$productId]);
}

/**
 * Get cart totals
 */
function getCartTotals(): array {
    $cart = getCart();
    $subtotal = 0;
    $itemCount = 0;
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
        $itemCount += $item['quantity'];
    }
    $taxRate = (float)getSetting('tax_rate', '0');
    $tax = $subtotal * ($taxRate / 100);
    return [
        'subtotal' => $subtotal,
        'tax' => $tax,
        'tax_rate' => $taxRate,
        'total' => $subtotal + $tax,
        'item_count' => $itemCount,
    ];
}

/**
 * Get cart item count
 */
function getCartCount(): int {
    $cart = getCart();
    $count = 0;
    foreach ($cart as $item) {
        $count += $item['quantity'];
    }
    return $count;
}

/**
 * Clear the entire cart
 */
function clearCart(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['cart'] = [];
}

/**
 * Parse a price string to a numeric value
 */
function parsePrice(string $price): float {
    // Remove everything except digits, dots, and commas
    $cleaned = preg_replace('/[^0-9.,]/', '', $price);
    // Handle comma as thousands separator
    $cleaned = str_replace(',', '', $cleaned);
    return (float)$cleaned;
}

/**
 * Check if cart has any items requiring shipping
 */
function cartHasPhysicalItems(): bool {
    foreach (getCart() as $item) {
        if (($item['product_type'] ?? 'physical') === 'physical') {
            return true;
        }
    }
    return false;
}

// ============================================================
// Order Functions
// ============================================================

/**
 * Generate a unique order number
 */
function generateOrderNumber(): string {
    return 'ORD-' . strtoupper(substr(date('Ymd'), 2)) . '-' . strtoupper(bin2hex(random_bytes(3)));
}

/**
 * Create a new order from the current cart
 */
function createOrder(array $customerData, string $paymentMethod = ''): ?int {
    $cart = getCart();
    if (empty($cart)) return null;

    $totals = getCartTotals();
    $orderNumber = generateOrderNumber();
    $db = getDB();

    try {
        $db->beginTransaction();

        $stmt = $db->prepare('INSERT INTO orders (order_number, customer_name, customer_email, customer_phone, shipping_address, subtotal, tax, total, payment_method, notes) VALUES (?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute([
            $orderNumber,
            $customerData['name'],
            $customerData['email'],
            $customerData['phone'] ?? '',
            $customerData['shipping_address'] ?? '',
            $totals['subtotal'],
            $totals['tax'],
            $totals['total'],
            $paymentMethod,
            $customerData['notes'] ?? '',
        ]);
        $orderId = (int)$db->lastInsertId();

        $itemStmt = $db->prepare('INSERT INTO order_items (order_id, product_id, product_name, product_type, quantity, unit_price, total_price) VALUES (?,?,?,?,?,?,?)');
        foreach ($cart as $item) {
            $itemStmt->execute([
                $orderId,
                $item['product_id'],
                $item['name'],
                $item['product_type'] ?? 'physical',
                $item['quantity'],
                $item['price'],
                $item['price'] * $item['quantity'],
            ]);
        }

        $db->commit();
        return $orderId;
    } catch (Exception $e) {
        $db->rollBack();
        error_log('[ORDER ERROR] ' . $e->getMessage());
        return null;
    }
}

/**
 * Get order by ID
 */
function getOrder(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    return $order ?: null;
}

/**
 * Get order by order number
 */
function getOrderByNumber(string $orderNumber): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM orders WHERE order_number = ?');
    $stmt->execute([$orderNumber]);
    $order = $stmt->fetch();
    return $order ?: null;
}

/**
 * Get order items
 */
function getOrderItems(int $orderId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

/**
 * Update order payment status
 */
function updateOrderPayment(int $orderId, string $paymentStatus, string $paymentId = '', string $paymentMethod = ''): bool {
    $db = getDB();
    $fields = ['payment_status = ?', 'updated_at = CURRENT_TIMESTAMP'];
    $params = [$paymentStatus];

    if ($paymentId) {
        $fields[] = 'payment_id = ?';
        $params[] = $paymentId;
    }
    if ($paymentMethod) {
        $fields[] = 'payment_method = ?';
        $params[] = $paymentMethod;
    }
    if ($paymentStatus === 'completed') {
        $fields[] = "order_status = 'processing'";
    }

    $params[] = $orderId;
    $stmt = $db->prepare('UPDATE orders SET ' . implode(', ', $fields) . ' WHERE id = ?');
    return $stmt->execute($params);
}

/**
 * Generate download tokens for digital items in an order
 */
function generateDownloadTokens(int $orderId): array {
    $db = getDB();
    $items = getOrderItems($orderId);
    $tokens = [];

    foreach ($items as $item) {
        if ($item['product_type'] !== 'digital' || !$item['product_id']) continue;

        // Get product download settings
        $stmt = $db->prepare('SELECT download_file, download_limit, download_expiry_hours FROM products WHERE id = ?');
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch();
        if (!$product || !$product['download_file']) continue;

        $token = bin2hex(random_bytes(32));
        $maxDownloads = $product['download_limit'] ?: 0; // 0 = unlimited
        $expiryHours = $product['download_expiry_hours'] ?: 72;
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));

        $stmt = $db->prepare('INSERT INTO download_tokens (order_id, order_item_id, product_id, token, max_downloads, expires_at) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$orderId, $item['id'], $item['product_id'], $token, $maxDownloads, $expiresAt]);

        $tokens[] = [
            'token' => $token,
            'product_name' => $item['product_name'],
            'expires_at' => $expiresAt,
        ];
    }

    return $tokens;
}

/**
 * Validate and serve a download
 */
function validateDownloadToken(string $token): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT dt.*, p.download_file, p.name as product_name FROM download_tokens dt JOIN products p ON dt.product_id = p.id WHERE dt.token = ?');
    $stmt->execute([$token]);
    $download = $stmt->fetch();

    if (!$download) return null;

    // Check expiry
    if ($download['expires_at'] && strtotime($download['expires_at']) < time()) {
        return ['error' => 'This download link has expired.'];
    }

    // Check download count
    if ($download['max_downloads'] > 0 && $download['download_count'] >= $download['max_downloads']) {
        return ['error' => 'Maximum download limit reached.'];
    }

    return $download;
}

/**
 * Record a download and serve the file
 */
function processDownload(string $token): void {
    $download = validateDownloadToken($token);
    if (!$download || isset($download['error'])) {
        return;
    }

    $filePath = BASE_PATH . '/' . $download['download_file'];
    if (!file_exists($filePath)) {
        return;
    }

    // Increment download count
    $db = getDB();
    $db->prepare('UPDATE download_tokens SET download_count = download_count + 1 WHERE token = ?')->execute([$token]);

    // Serve file
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . basename($download['download_file']) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-store, no-cache, must-revalidate');
    readfile($filePath);
    exit;
}

/**
 * Get download tokens for an order
 */
function getDownloadTokens(int $orderId): array {
    $db = getDB();
    $stmt = $db->prepare('SELECT dt.*, p.name as product_name FROM download_tokens dt JOIN products p ON dt.product_id = p.id WHERE dt.order_id = ?');
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

/**
 * Handle digital product file upload (supports more types than media upload)
 */
function handleDownloadUpload(array $file): ?string {
    $allowedMimes = [
        'application/pdf' => 'pdf',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
        'application/x-rar-compressed' => 'rar',
        'application/x-7z-compressed' => '7z',
        'application/gzip' => 'gz',
        'application/x-tar' => 'tar',
        'application/epub+zip' => 'epub',
        'audio/mpeg' => 'mp3',
        'audio/wav' => 'wav',
        'audio/x-wav' => 'wav',
        'audio/flac' => 'flac',
        'audio/ogg' => 'ogg',
        'audio/aac' => 'aac',
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg',
        'application/octet-stream' => 'bin',
        'text/plain' => 'txt',
        'text/csv' => 'csv',
        'application/json' => 'json',
        'application/xml' => 'xml',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/msword' => 'doc',
        'application/vnd.ms-excel' => 'xls',
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    // Use detected extension or fall back to original extension
    $ext = $allowedMimes[$mimeType] ?? pathinfo($file['name'], PATHINFO_EXTENSION);
    if (!$ext) return null;

    // Max 500MB for download files
    if ($file['size'] > 500 * 1024 * 1024) return null;

    $uploadDir = UPLOADS_PATH . '/downloads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = uniqid('dl_') . '_' . time() . '.' . strtolower($ext);
    $filepath = $uploadDir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/downloads/' . $filename;
    }

    return null;
}

/**
 * Send order confirmation email
 */
function sendOrderConfirmation(int $orderId): void {
    $order = getOrder($orderId);
    if (!$order) return;

    $items = getOrderItems($orderId);
    $downloads = getDownloadTokens($orderId);

    // Email to customer
    $subject = 'Order Confirmation - ' . $order['order_number'] . ' - ' . getSetting('company_name', SITE_NAME);
    $body = "Thank you for your order!\n\n";
    $body .= "Order Number: {$order['order_number']}\n";
    $body .= "Date: " . formatDate($order['created_at']) . "\n\n";
    $body .= "Items:\n";
    foreach ($items as $item) {
        $body .= "- {$item['product_name']} x{$item['quantity']} — $" . number_format($item['total_price'], 2) . "\n";
    }
    $body .= "\nSubtotal: $" . number_format($order['subtotal'], 2) . "\n";
    if ($order['tax'] > 0) {
        $body .= "Tax: $" . number_format($order['tax'], 2) . "\n";
    }
    $body .= "Total: $" . number_format($order['total'], 2) . "\n";

    if (!empty($downloads)) {
        $body .= "\nYour Downloads:\n";
        foreach ($downloads as $dl) {
            $downloadUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url('index.php?page=download&token=' . $dl['token']);
            $body .= "- {$dl['product_name']}: {$downloadUrl}\n";
        }
    }

    $headers = "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    @mail($order['customer_email'], $subject, $body, $headers);

    // Email to admin
    $notifyEmail = getSetting('contact_email');
    if ($notifyEmail) {
        $adminSubject = 'New Order Received - ' . $order['order_number'] . ' - ' . SITE_NAME;
        $adminBody = "A new order has been placed.\n\n";
        $adminBody .= "Order Number: {$order['order_number']}\n";
        $adminBody .= "Customer: {$order['customer_name']} ({$order['customer_email']})\n";
        $adminBody .= "Total: $" . number_format($order['total'], 2) . "\n";
        $adminBody .= "Payment: {$order['payment_method']} ({$order['payment_status']})\n";
        sendNotificationEmail($notifyEmail, $adminSubject, $adminBody);
    }
}

// ============================================================
// Payment Gateway Functions
// ============================================================

/**
 * Check which payment providers are enabled
 */
function getEnabledPaymentProviders(): array {
    $providers = [];

    if (getSetting('stripe_enabled') === '1' && getSetting('stripe_publishable_key')) {
        $providers[] = 'stripe';
    }
    if (getSetting('paypal_enabled') === '1' && getSetting('paypal_client_id')) {
        $providers[] = 'paypal';
    }
    if (getSetting('square_enabled') === '1' && getSetting('square_application_id')) {
        $providers[] = 'square';
    }
    if (getSetting('btcpay_enabled') === '1' && getSetting('btcpay_url') && getSetting('btcpay_api_key') && getSetting('btcpay_store_id')) {
        $providers[] = 'btcpay';
    }
    if (getSetting('swipesimple_link') || getSetting('swipesimple_embed')) {
        $providers[] = 'swipesimple';
    }

    return $providers;
}

/**
 * Create a Stripe Checkout session via cURL
 */
function createStripeCheckoutSession(int $orderId): ?string {
    $order = getOrder($orderId);
    if (!$order) return null;

    $secretKey = getSetting('stripe_secret_key');
    if (!$secretKey) return null;

    $items = getOrderItems($orderId);
    $lineItems = [];
    foreach ($items as $item) {
        $lineItems[] = [
            'price_data' => [
                'currency' => strtolower(getSetting('currency_code', 'usd')),
                'product_data' => ['name' => $item['product_name']],
                'unit_amount' => (int)round($item['unit_price'] * 100),
            ],
            'quantity' => $item['quantity'],
        ];
    }

    // Add tax as a line item if present
    if ($order['tax'] > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => strtolower(getSetting('currency_code', 'usd')),
                'product_data' => ['name' => 'Tax'],
                'unit_amount' => (int)round($order['tax'] * 100),
            ],
            'quantity' => 1,
        ];
    }

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;

    $postData = [
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => $baseUrl . '/index.php?page=order-complete&order=' . $order['order_number'] . '&payment=stripe&session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => $baseUrl . '/index.php?page=checkout&cancelled=1',
        'customer_email' => $order['customer_email'],
        'metadata' => ['order_id' => $orderId, 'order_number' => $order['order_number']],
    ];

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_USERPWD => $secretKey . ':',
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('[STRIPE ERROR] HTTP ' . $httpCode . ': ' . $response);
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['url'])) {
        error_log('[STRIPE ERROR] Invalid response: ' . $response);
        return null;
    }

    // Store Stripe session ID
    updateOrderPayment($orderId, 'pending', $data['id'], 'stripe');

    return $data['url'];
}

/**
 * Verify a Stripe Checkout session
 */
function verifyStripePayment(string $sessionId): ?array {
    $secretKey = getSetting('stripe_secret_key');
    if (!$secretKey) return null;

    $ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => $secretKey . ':',
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;

    $data = json_decode($response, true);
    return $data ?: null;
}

/**
 * Create a PayPal order via REST API
 */
function createPayPalOrder(int $orderId): ?array {
    $order = getOrder($orderId);
    if (!$order) return null;

    $clientId = getSetting('paypal_client_id');
    $clientSecret = getSetting('paypal_secret');
    if (!$clientId || !$clientSecret) return null;

    $sandbox = getSetting('paypal_sandbox', '1') === '1';
    $apiBase = $sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

    // Get access token
    $ch = curl_init($apiBase . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
        CURLOPT_TIMEOUT => 30,
    ]);
    $tokenResponse = curl_exec($ch);
    curl_close($ch);

    $tokenData = json_decode($tokenResponse, true);
    if (!$tokenData || !isset($tokenData['access_token'])) {
        error_log('[PAYPAL ERROR] Token: ' . $tokenResponse);
        return null;
    }

    $currency = strtoupper(getSetting('currency_code', 'USD'));
    $items = getOrderItems($orderId);
    $paypalItems = [];
    foreach ($items as $item) {
        $paypalItems[] = [
            'name' => $item['product_name'],
            'quantity' => (string)$item['quantity'],
            'unit_amount' => [
                'currency_code' => $currency,
                'value' => number_format($item['unit_price'], 2, '.', ''),
            ],
        ];
    }

    $payload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $order['order_number'],
            'amount' => [
                'currency_code' => $currency,
                'value' => number_format($order['total'], 2, '.', ''),
                'breakdown' => [
                    'item_total' => [
                        'currency_code' => $currency,
                        'value' => number_format($order['subtotal'], 2, '.', ''),
                    ],
                    'tax_total' => [
                        'currency_code' => $currency,
                        'value' => number_format($order['tax'], 2, '.', ''),
                    ],
                ],
            ],
            'items' => $paypalItems,
        ]],
    ];

    $ch = curl_init($apiBase . '/v2/checkout/orders');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $tokenData['access_token'],
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('[PAYPAL ERROR] Create order: HTTP ' . $httpCode . ': ' . $response);
        return null;
    }

    $data = json_decode($response, true);
    if ($data && isset($data['id'])) {
        updateOrderPayment($orderId, 'pending', $data['id'], 'paypal');
    }

    return $data;
}

/**
 * Capture a PayPal order
 */
function capturePayPalOrder(string $paypalOrderId): ?array {
    $clientId = getSetting('paypal_client_id');
    $clientSecret = getSetting('paypal_secret');
    if (!$clientId || !$clientSecret) return null;

    $sandbox = getSetting('paypal_sandbox', '1') === '1';
    $apiBase = $sandbox ? 'https://api-m.sandbox.paypal.com' : 'https://api-m.paypal.com';

    // Get access token
    $ch = curl_init($apiBase . '/v1/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_USERPWD => $clientId . ':' . $clientSecret,
        CURLOPT_TIMEOUT => 30,
    ]);
    $tokenResponse = curl_exec($ch);
    curl_close($ch);
    $tokenData = json_decode($tokenResponse, true);
    if (!$tokenData || !isset($tokenData['access_token'])) return null;

    // Capture order
    $ch = curl_init($apiBase . '/v2/checkout/orders/' . urlencode($paypalOrderId) . '/capture');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $tokenData['access_token'],
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

/**
 * Create Square Checkout via API
 */
function createSquareCheckout(int $orderId): ?string {
    $order = getOrder($orderId);
    if (!$order) return null;

    $accessToken = getSetting('square_access_token');
    $locationId = getSetting('square_location_id');
    if (!$accessToken || !$locationId) return null;

    $sandbox = getSetting('square_sandbox', '1') === '1';
    $apiBase = $sandbox ? 'https://connect.squareupsandbox.com' : 'https://connect.squareup.com';

    $items = getOrderItems($orderId);
    $lineItems = [];
    foreach ($items as $item) {
        $lineItems[] = [
            'name' => $item['product_name'],
            'quantity' => (string)$item['quantity'],
            'base_price_money' => [
                'amount' => (int)round($item['unit_price'] * 100),
                'currency' => strtoupper(getSetting('currency_code', 'USD')),
            ],
        ];
    }

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;

    $payload = [
        'idempotency_key' => bin2hex(random_bytes(16)),
        'order' => [
            'order' => [
                'location_id' => $locationId,
                'line_items' => $lineItems,
            ],
        ],
        'checkout_options' => [
            'redirect_url' => $baseUrl . '/index.php?page=order-complete&order=' . $order['order_number'] . '&payment=square',
        ],
        'pre_populated_data' => [
            'buyer_email' => $order['customer_email'],
        ],
    ];

    $ch = curl_init($apiBase . '/v2/online-checkout/payment-links');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Square-Version: 2024-01-18',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('[SQUARE ERROR] HTTP ' . $httpCode . ': ' . $response);
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['payment_link']['url'])) {
        error_log('[SQUARE ERROR] Invalid response: ' . $response);
        return null;
    }

    updateOrderPayment($orderId, 'pending', $data['payment_link']['id'] ?? '', 'square');

    return $data['payment_link']['url'];
}

/**
 * Create a BTCPay Server invoice via the Greenfield API
 */
function createBTCPayInvoice(int $orderId): ?string {
    $order = getOrder($orderId);
    if (!$order) return null;

    $btcpayUrl = rtrim(getSetting('btcpay_url'), '/');
    $apiKey = getSetting('btcpay_api_key');
    $storeId = getSetting('btcpay_store_id');
    if (!$btcpayUrl || !$apiKey || !$storeId) return null;

    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
        . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . BASE_URL;

    $currencyCode = strtoupper(getSetting('currency_code', 'USD'));

    $payload = [
        'amount' => number_format($order['total'], 2, '.', ''),
        'currency' => $currencyCode,
        'metadata' => [
            'orderId' => (string)$orderId,
            'orderNumber' => $order['order_number'],
            'buyerName' => $order['customer_name'],
            'buyerEmail' => $order['customer_email'],
        ],
        'checkout' => [
            'redirectURL' => $baseUrl . '/index.php?page=order-complete&order=' . $order['order_number'] . '&payment=btcpay',
            'redirectAutomatically' => true,
            'defaultLanguage' => 'en',
        ],
        'receipt' => [
            'enabled' => true,
        ],
    ];

    // Include buyer email for receipt
    if ($order['customer_email']) {
        $payload['metadata']['buyerEmail'] = $order['customer_email'];
    }

    $endpoint = $btcpayUrl . '/api/v1/stores/' . urlencode($storeId) . '/invoices';

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: token ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log('[BTCPAY ERROR] cURL error: ' . $curlError);
        return null;
    }

    if ($httpCode < 200 || $httpCode >= 300) {
        error_log('[BTCPAY ERROR] HTTP ' . $httpCode . ': ' . $response);
        return null;
    }

    $data = json_decode($response, true);
    if (!$data || !isset($data['id'])) {
        error_log('[BTCPAY ERROR] Invalid response: ' . $response);
        return null;
    }

    // Store invoice ID and mark as pending
    updateOrderPayment($orderId, 'pending', $data['id'], 'btcpay');

    // BTCPay checkout URL is the invoice page
    return $btcpayUrl . '/i/' . $data['id'];
}

/**
 * Verify a BTCPay Server invoice status via the Greenfield API
 */
function verifyBTCPayInvoice(string $invoiceId): ?array {
    $btcpayUrl = rtrim(getSetting('btcpay_url'), '/');
    $apiKey = getSetting('btcpay_api_key');
    $storeId = getSetting('btcpay_store_id');
    if (!$btcpayUrl || !$apiKey || !$storeId) return null;

    $endpoint = $btcpayUrl . '/api/v1/stores/' . urlencode($storeId) . '/invoices/' . urlencode($invoiceId);

    $ch = curl_init($endpoint);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: token ' . $apiKey,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) return null;

    return json_decode($response, true) ?: null;
}

/**
 * Verify BTCPay webhook signature (HMAC-SHA256)
 */
function verifyBTCPayWebhookSignature(string $payload, string $signature): bool {
    $secret = getSetting('btcpay_webhook_secret');
    if (!$secret) {
        // If no webhook secret is configured, skip verification (less secure)
        return true;
    }

    // BTCPay sends signature as "sha256=HEXDIGEST"
    $sigParts = explode('=', $signature, 2);
    if (count($sigParts) !== 2 || $sigParts[0] !== 'sha256') {
        return false;
    }

    $expected = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected, $sigParts[1]);
}

/**
 * Get a product by ID
 */
function getProduct(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    return $product ?: null;
}

/**
 * Format currency amount
 */
function formatCurrency(float $amount): string {
    $symbol = getSetting('currency_symbol', '$');
    return $symbol . number_format($amount, 2);
}
