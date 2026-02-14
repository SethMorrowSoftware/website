<?php
/**
 * Helper functions
 */

require_once __DIR__ . '/../config.php';

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
 * Get all visible containers
 */
function getContainers(): array {
    $db = getDB();
    $stmt = $db->query('SELECT * FROM containers WHERE is_visible = 1 ORDER BY sort_order ASC');
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
 * Get all products (for order form)
 */
function getAllProducts(): array {
    $db = getDB();
    $stmt = $db->query('SELECT p.*, pc.name as category_name
                        FROM products p
                        JOIN product_categories pc ON p.category_id = pc.id
                        WHERE p.is_visible = 1 AND p.is_available = 1
                        ORDER BY pc.sort_order, p.sort_order');
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
        @mail($notifyEmail, $subject, $body, "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
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
        $body .= "Service Type: {$data['service_type']}\n";
        $body .= "Name: {$data['name']}\n";
        $body .= "Email: {$data['email']}\n";
        $body .= "Phone: {$data['phone']}\n";
        $body .= "Delivery Address: " . ($data['delivery_address'] ?? 'N/A') . "\n";
        $body .= "Preferred Date: " . ($data['preferred_date'] ?? 'N/A') . "\n";
        $body .= "Notes: " . ($data['notes'] ?? 'N/A') . "\n";
        @mail($notifyEmail, $subject, $body, "From: noreply@" . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
    }

    return $result;
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
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'video/mp4', 'video/webm'
    ];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mimeType, $allowedTypes)) {
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

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
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
