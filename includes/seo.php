<?php
/**
 * Advanced SEO Toolkit
 *
 * Provides:
 * - JSON-LD structured data (Product, BreadcrumbList, BlogPosting, WebSite)
 * - Breadcrumb navigation with schema markup
 * - Auto-generated meta descriptions
 * - URL redirect management
 */

// ============================================================
// Structured Data (JSON-LD)
// ============================================================

/**
 * Generate Product JSON-LD schema.
 */
function getProductSchema(array $product, array $rating = []): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $product['name'],
        'description' => $product['meta_description'] ?? ($product['description'] ?? ''),
    ];

    if (!empty($product['image'])) {
        $schema['image'] = $product['image'];
    }

    if (!empty($product['price']) && is_numeric($product['price'])) {
        $schema['offers'] = [
            '@type' => 'Offer',
            'price' => number_format((float)$product['price'], 2, '.', ''),
            'priceCurrency' => getSetting('currency_code', 'USD'),
            'availability' => 'https://schema.org/' . ($product['is_available'] ? 'InStock' : 'OutOfStock'),
        ];
    }

    if (!empty($rating['count']) && $rating['count'] > 0) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round($rating['average'], 1),
            'reviewCount' => $rating['count'],
        ];
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * Generate BlogPosting JSON-LD schema.
 */
function getBlogPostSchema(array $post): string {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => $post['title'],
        'datePublished' => $post['published_at'] ?? $post['created_at'],
        'dateModified' => $post['updated_at'] ?? $post['published_at'],
        'author' => [
            '@type' => 'Person',
            'name' => $post['author_name'] ?? getSetting('company_name', 'Admin'),
        ],
    ];

    if (!empty($post['featured_image'])) {
        $schema['image'] = $post['featured_image'];
    }
    if (!empty($post['meta_description'] ?? $post['excerpt'])) {
        $schema['description'] = $post['meta_description'] ?? $post['excerpt'];
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * Generate BreadcrumbList JSON-LD schema.
 */
function getBreadcrumbSchema(array $items): string {
    $listItems = [];
    foreach ($items as $i => $item) {
        $listItem = [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $item['name'],
        ];
        if (!empty($item['url'])) {
            $listItem['item'] = $item['url'];
        }
        $listItems[] = $listItem;
    }

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => $listItems,
    ];

    return '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
}

/**
 * Generate WebSite schema with SearchAction.
 */
function getWebSiteSchema(): string {
    $baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => getSetting('company_name', 'Our Store'),
        'url' => $baseUrl,
    ];

    if (isFeatureEnabled('search')) {
        $schema['potentialAction'] = [
            '@type' => 'SearchAction',
            'target' => $baseUrl . url('index.php?page=search&q={search_term_string}'),
            'query-input' => 'required name=search_term_string',
        ];
    }

    return '<script type="application/ld+json">' . json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</script>';
}

// ============================================================
// Breadcrumb Navigation
// ============================================================

/**
 * Render breadcrumb HTML with schema markup.
 */
function renderBreadcrumbs(array $items): string {
    if (empty($items)) return '';

    $html = '<nav aria-label="Breadcrumb" class="breadcrumb-nav"><ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">';
    foreach ($items as $i => $item) {
        $isLast = $i === count($items) - 1;
        $html .= '<li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        if (!$isLast && !empty($item['url'])) {
            $html .= '<a itemprop="item" href="' . e($item['url']) . '"><span itemprop="name">' . e($item['name']) . '</span></a>';
        } else {
            $html .= '<span itemprop="name">' . e($item['name']) . '</span>';
        }
        $html .= '<meta itemprop="position" content="' . ($i + 1) . '">';
        if (!$isLast) $html .= '<span class="breadcrumb-sep">/</span>';
        $html .= '</li>';
    }
    $html .= '</ol></nav>';

    return $html;
}

// ============================================================
// Meta Description Generation
// ============================================================

/**
 * Auto-generate meta description from content if not provided.
 */
function autoMetaDescription(?string $customMeta, string $content, int $maxLen = 160): string {
    if ($customMeta && trim($customMeta) !== '') {
        return substr(trim($customMeta), 0, $maxLen);
    }
    // Strip HTML, normalize whitespace, truncate
    $text = trim(preg_replace('/\s+/', ' ', strip_tags($content)));
    if (strlen($text) <= $maxLen) return $text;
    return substr($text, 0, $maxLen - 3) . '...';
}

// ============================================================
// URL Redirects
// ============================================================

/**
 * Check for a redirect and perform it if found.
 */
function handleUrlRedirects(string $path): void {
    try {
        $db = getDB();
        $stmt = $db->prepare('SELECT new_path, status_code FROM url_redirects WHERE old_path = ?');
        $stmt->execute([$path]);
        $redirect = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($redirect) {
            http_response_code($redirect['status_code']);
            header('Location: ' . $redirect['new_path']);
            exit;
        }
    } catch (Exception $e) {
        // Table might not exist yet
    }
}

/**
 * Create a URL redirect.
 */
function createUrlRedirect(string $oldPath, string $newPath, int $statusCode = 301): bool {
    try {
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO url_redirects (old_path, new_path, status_code) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE new_path = VALUES(new_path), status_code = VALUES(status_code)");
        return $stmt->execute([$oldPath, $newPath, $statusCode]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get all URL redirects.
 */
function getAllUrlRedirects(): array {
    try {
        $db = getDB();
        return $db->query("SELECT * FROM url_redirects ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Delete a URL redirect.
 */
function deleteUrlRedirect(int $id): bool {
    try {
        $db = getDB();
        return $db->prepare("DELETE FROM url_redirects WHERE id = ?")->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}
