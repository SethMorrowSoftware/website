<?php
/**
 * Blog Functions
 * Handles blog posts, categories, tags, comments, and product integration.
 */

// SQL fragment: treat 'scheduled' posts with past published_at as visible
define('BLOG_PUBLISHED_CONDITION', "(status = 'published' OR (status = 'scheduled' AND published_at <= NOW()))");
define('BLOG_PUBLISHED_CONDITION_PREFIXED', "(bp.status = 'published' OR (bp.status = 'scheduled' AND bp.published_at <= NOW()))");

// ============================================================
// Blog Post Functions
// ============================================================

/**
 * Get paginated blog posts with optional filters.
 * Only returns published posts with published_at <= now for public queries.
 */
function getBlogPosts(int $page = 1, int $perPage = 9, array $filters = []): array {
    $db = getDB();
    $page = max(1, $page);
    $perPage = max(1, min(50, $perPage));

    $where = [BLOG_PUBLISHED_CONDITION_PREFIXED];
    $params = [];
    $where[] = 'bp.published_at <= NOW()';

    if (!empty($filters['category'])) {
        $where[] = 'bc.slug = ?';
        $params[] = $filters['category'];
    }

    if (!empty($filters['tag'])) {
        $where[] = 'bp.id IN (SELECT bpt.post_id FROM blog_post_tags bpt JOIN blog_tags bt ON bt.id = bpt.tag_id WHERE bt.slug = ?)';
        $params[] = $filters['tag'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(bp.title LIKE ? OR bp.excerpt LIKE ? OR bp.content LIKE ?)';
        $term = '%' . $filters['search'] . '%';
        $params[] = $term;
        $params[] = $term;
        $params[] = $term;
    }

    if (!empty($filters['archive'])) {
        // Format: 2026-02
        $where[] = "DATE_FORMAT(bp.published_at, '%Y-%m') = ?";
        $params[] = $filters['archive'];
    }

    $whereClause = implode(' AND ', $where);
    $offset = ($page - 1) * $perPage;

    // Count total
    $countSql = "SELECT COUNT(*) FROM blog_posts bp LEFT JOIN blog_categories bc ON bc.id = bp.category_id WHERE $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = (int)$stmt->fetchColumn();

    // Fetch posts
    $sql = "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug, u.username AS author_name
            FROM blog_posts bp
            LEFT JOIN blog_categories bc ON bc.id = bp.category_id
            LEFT JOIN users u ON u.id = bp.author_id
            WHERE $whereClause
            ORDER BY bp.is_featured DESC, bp.published_at DESC
            LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $posts = $stmt->fetchAll();

    return [
        'posts' => $posts,
        'total' => $total,
        'pages' => ceil($total / $perPage),
        'current_page' => $page,
    ];
}

/**
 * Get paginated blog posts for admin (no status filter).
 */
function getAdminBlogPosts(array $filters = [], int $page = 1, int $perPage = 25): array {
    $db = getDB();
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $where = ['1=1'];
    $params = [];

    if (!empty($filters['status'])) {
        $where[] = 'bp.status = ?';
        $params[] = $filters['status'];
    }

    if (!empty($filters['category_id'])) {
        $where[] = 'bp.category_id = ?';
        $params[] = (int)$filters['category_id'];
    }

    if (!empty($filters['search'])) {
        $where[] = '(bp.title LIKE ? OR bp.excerpt LIKE ?)';
        $term = '%' . $filters['search'] . '%';
        $params[] = $term;
        $params[] = $term;
    }

    $whereClause = implode(' AND ', $where);

    // Count total
    $countSql = "SELECT COUNT(*) FROM blog_posts bp LEFT JOIN blog_categories bc ON bc.id = bp.category_id WHERE $whereClause";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $sql = "SELECT bp.*, bc.name AS category_name, u.username AS author_name,
                   (SELECT COUNT(*) FROM blog_comments WHERE post_id = bp.id) AS comment_count
            FROM blog_posts bp
            LEFT JOIN blog_categories bc ON bc.id = bp.category_id
            LEFT JOIN users u ON u.id = bp.author_id
            WHERE $whereClause
            ORDER BY bp.updated_at DESC
            LIMIT ? OFFSET ?";
    $params[] = $perPage;
    $params[] = $offset;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    return [
        'posts' => $stmt->fetchAll(),
        'total' => $total,
        'pages' => (int)ceil($total / $perPage),
        'current_page' => $page,
    ];
}

/**
 * Get a single blog post by slug (public).
 */
function getBlogPost(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug, u.username AS author_name
         FROM blog_posts bp
         LEFT JOIN blog_categories bc ON bc.id = bp.category_id
         LEFT JOIN users u ON u.id = bp.author_id
         WHERE bp.slug = ? AND " . BLOG_PUBLISHED_CONDITION_PREFIXED . " AND bp.published_at <= NOW()"
    );
    $stmt->execute([$slug]);
    $post = $stmt->fetch();
    return $post ?: null;
}

/**
 * Get a single blog post by ID (admin).
 */
function getBlogPostById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT bp.*, bc.name AS category_name, u.username AS author_name
         FROM blog_posts bp
         LEFT JOIN blog_categories bc ON bc.id = bp.category_id
         LEFT JOIN users u ON u.id = bp.author_id
         WHERE bp.id = ?"
    );
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    return $post ?: null;
}

/**
 * Increment view count for a blog post (once per session per post).
 */
function incrementBlogPostViews(int $id): void {
    $key = 'blog_viewed_' . $id;
    if (!empty($_SESSION[$key])) {
        return;
    }
    $_SESSION[$key] = true;

    $db = getDB();
    $db->prepare('UPDATE blog_posts SET view_count = view_count + 1 WHERE id = ?')->execute([$id]);
}

/**
 * Get related posts (same category, excluding current).
 */
function getRelatedPosts(int $postId, int $limit = 3): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug
         FROM blog_posts bp
         LEFT JOIN blog_categories bc ON bc.id = bp.category_id
         WHERE bp.category_id = (SELECT category_id FROM blog_posts WHERE id = ?)
           AND bp.id != ?
           AND " . BLOG_PUBLISHED_CONDITION_PREFIXED . "
           AND bp.published_at <= NOW()
         ORDER BY bp.published_at DESC
         LIMIT ?"
    );
    $stmt->execute([$postId, $postId, $limit]);
    return $stmt->fetchAll();
}

/**
 * Get featured posts.
 */
function getFeaturedPosts(int $limit = 3): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug
         FROM blog_posts bp
         LEFT JOIN blog_categories bc ON bc.id = bp.category_id
         WHERE bp.is_featured = 1 AND " . BLOG_PUBLISHED_CONDITION_PREFIXED . " AND bp.published_at <= NOW()
         ORDER BY bp.published_at DESC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get recent posts.
 */
function getRecentPosts(int $limit = 5): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug
         FROM blog_posts bp
         LEFT JOIN blog_categories bc ON bc.id = bp.category_id
         WHERE " . BLOG_PUBLISHED_CONDITION_PREFIXED . " AND bp.published_at <= NOW()
         ORDER BY bp.published_at DESC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get popular posts by view count.
 */
function getPopularPosts(int $limit = 5): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug
         FROM blog_posts bp
         LEFT JOIN blog_categories bc ON bc.id = bp.category_id
         WHERE " . BLOG_PUBLISHED_CONDITION_PREFIXED . " AND bp.published_at <= NOW()
         ORDER BY bp.view_count DESC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Save (insert or update) a blog post.
 */
function saveBlogPost(array $data): int {
    $db = getDB();
    $id = (int)($data['id'] ?? 0);

    // Validate status
    $validStatuses = ['draft', 'published', 'scheduled'];
    if (!in_array($data['status'] ?? 'draft', $validStatuses, true)) {
        $data['status'] = 'draft';
    }

    $slug = createSlug($data['title']);
    // Ensure unique slug
    if ($id) {
        $existing = $db->prepare('SELECT id FROM blog_posts WHERE slug = ? AND id != ?');
        $existing->execute([$slug, $id]);
    } else {
        $existing = $db->prepare('SELECT id FROM blog_posts WHERE slug = ?');
        $existing->execute([$slug]);
    }
    if ($existing->fetch()) {
        $slug .= '-' . time();
    }

    // Auto-generate excerpt from content if empty
    $excerpt = trim($data['excerpt'] ?? '');
    if (!$excerpt && !empty($data['content'])) {
        $excerpt = mb_substr(strip_tags($data['content']), 0, 250) . '...';
    }

    if ($id) {
        $stmt = $db->prepare(
            'UPDATE blog_posts SET title = ?, slug = ?, excerpt = ?, content = ?, featured_image = ?,
             featured_image_alt = ?, category_id = ?, status = ?, is_featured = ?, allow_comments = ?,
             meta_description = ?, og_image = ?, published_at = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $stmt->execute([
            $data['title'], $slug, $excerpt, $data['content'] ?? '',
            $data['featured_image'] ?? '', $data['featured_image_alt'] ?? '',
            $data['category_id'] ?: null, $data['status'] ?? 'draft',
            (int)($data['is_featured'] ?? 0), (int)($data['allow_comments'] ?? 1),
            $data['meta_description'] ?? '', $data['og_image'] ?? '',
            $data['published_at'] ?? null, $id
        ]);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO blog_posts (title, slug, excerpt, content, featured_image, featured_image_alt,
             category_id, author_id, status, is_featured, allow_comments, meta_description, og_image,
             published_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        );
        $stmt->execute([
            $data['title'], $slug, $excerpt, $data['content'] ?? '',
            $data['featured_image'] ?? '', $data['featured_image_alt'] ?? '',
            $data['category_id'] ?: null, $data['author_id'] ?? null,
            $data['status'] ?? 'draft', (int)($data['is_featured'] ?? 0),
            (int)($data['allow_comments'] ?? 1), $data['meta_description'] ?? '',
            $data['og_image'] ?? '', $data['published_at'] ?? null
        ]);
        $id = (int)$db->lastInsertId();
    }

    return $id;
}

/**
 * Delete a blog post.
 */
function deleteBlogPost(int $id): bool {
    $db = getDB();
    $stmt = $db->prepare('DELETE FROM blog_posts WHERE id = ?');
    return $stmt->execute([$id]);
}

/**
 * Get archive months with post counts.
 */
function getBlogArchiveMonths(): array {
    $db = getDB();
    $stmt = $db->query(
        "SELECT DATE_FORMAT(published_at, '%Y-%m') AS month,
                COUNT(*) AS post_count
         FROM blog_posts
         WHERE " . BLOG_PUBLISHED_CONDITION . " AND published_at <= NOW()
         GROUP BY month
         ORDER BY month DESC
         LIMIT 24"
    );
    return $stmt->fetchAll();
}

/**
 * Get previous and next posts for navigation.
 */
function getAdjacentPosts(int $postId, string $publishedAt): array {
    $db = getDB();

    $prev = $db->prepare(
        "SELECT slug, title FROM blog_posts
         WHERE " . BLOG_PUBLISHED_CONDITION . " AND published_at <= NOW()
           AND published_at < ? AND id != ?
         ORDER BY published_at DESC LIMIT 1"
    );
    $prev->execute([$publishedAt, $postId]);

    $next = $db->prepare(
        "SELECT slug, title FROM blog_posts
         WHERE " . BLOG_PUBLISHED_CONDITION . " AND published_at <= NOW()
           AND published_at > ? AND id != ?
         ORDER BY published_at ASC LIMIT 1"
    );
    $next->execute([$publishedAt, $postId]);

    return [
        'prev' => $prev->fetch() ?: null,
        'next' => $next->fetch() ?: null,
    ];
}

/**
 * Process product shortcodes in blog content.
 * Converts [product id=X] to product card HTML.
 * Batch-fetches all referenced products in a single query to avoid N+1.
 */
function processBlogShortcodes(string $content): string {
    // Collect all product IDs referenced in shortcodes
    if (!preg_match_all('/\[product\s+id=(\d+)\]/', $content, $allMatches)) {
        return $content;
    }

    $ids = array_unique(array_map('intval', $allMatches[1]));
    if (empty($ids)) {
        return $content;
    }

    // Batch-fetch all products in one query
    $db = getDB();
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $db->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND is_visible = 1");
    $stmt->execute($ids);
    $products = [];
    foreach ($stmt->fetchAll() as $row) {
        $products[$row['id']] = $row;
    }

    // Replace shortcodes using the pre-fetched data
    return preg_replace_callback('/\[product\s+id=(\d+)\]/', function ($matches) use ($products) {
        $productId = (int)$matches[1];
        $product = $products[$productId] ?? null;
        if (!$product) return '';

        $name = htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8');
        $price = htmlspecialchars($product['price'] ?? '', ENT_QUOTES, 'UTF-8');
        $image = htmlspecialchars($product['image'] ?? '', ENT_QUOTES, 'UTF-8');
        $slug = htmlspecialchars($product['slug'], ENT_QUOTES, 'UTF-8');
        $url = url('index.php?page=product&slug=' . $slug);

        $html = '<div class="blog-product-card">';
        if ($image) {
            $html .= '<a href="' . $url . '" class="blog-product-image"><img src="' . $image . '" alt="' . $name . '"></a>';
        }
        $html .= '<div class="blog-product-info">';
        $html .= '<h4><a href="' . $url . '">' . $name . '</a></h4>';
        if ($price) {
            $html .= '<span class="blog-product-price">' . formatCurrency((float)$price) . '</span>';
        }
        $html .= '<a href="' . $url . '" class="btn btn-sm btn-primary">View Product</a>';
        $html .= '</div></div>';
        return $html;
    }, $content);
}

// ============================================================
// Blog Category Functions
// ============================================================

/**
 * Get all visible blog categories.
 */
function getBlogCategories(bool $allVisible = false): array {
    $db = getDB();
    if ($allVisible) {
        return $db->query('SELECT * FROM blog_categories ORDER BY sort_order ASC, name ASC')->fetchAll();
    }
    return $db->query('SELECT * FROM blog_categories WHERE is_visible = 1 ORDER BY sort_order ASC, name ASC')->fetchAll();
}

/**
 * Get blog categories with post counts.
 */
function getBlogCategoriesWithCounts(): array {
    $db = getDB();
    return $db->query(
        "SELECT bc.*, COUNT(bp.id) AS post_count
         FROM blog_categories bc
         LEFT JOIN blog_posts bp ON bp.category_id = bc.id AND " . BLOG_PUBLISHED_CONDITION_PREFIXED . " AND bp.published_at <= NOW()
         WHERE bc.is_visible = 1
         GROUP BY bc.id
         ORDER BY bc.sort_order ASC, bc.name ASC"
    )->fetchAll();
}

/**
 * Get a single blog category by slug.
 */
function getBlogCategory(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM blog_categories WHERE slug = ?');
    $stmt->execute([$slug]);
    $cat = $stmt->fetch();
    return $cat ?: null;
}

/**
 * Get a single blog category by ID.
 */
function getBlogCategoryById(int $id): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM blog_categories WHERE id = ?');
    $stmt->execute([$id]);
    $cat = $stmt->fetch();
    return $cat ?: null;
}

/**
 * Save (insert or update) a blog category.
 */
function saveBlogCategory(array $data): int {
    $db = getDB();
    $id = (int)($data['id'] ?? 0);
    $slug = createSlug($data['name']);

    if ($id) {
        $existing = $db->prepare('SELECT id FROM blog_categories WHERE slug = ? AND id != ?');
        $existing->execute([$slug, $id]);
    } else {
        $existing = $db->prepare('SELECT id FROM blog_categories WHERE slug = ?');
        $existing->execute([$slug]);
    }
    if ($existing->fetch()) {
        $slug .= '-' . time();
    }

    if ($id) {
        $stmt = $db->prepare('UPDATE blog_categories SET name = ?, slug = ?, description = ?, image = ?, sort_order = ?, is_visible = ? WHERE id = ?');
        $stmt->execute([$data['name'], $slug, $data['description'] ?? '', $data['image'] ?? '', (int)($data['sort_order'] ?? 0), (int)($data['is_visible'] ?? 1), $id]);
    } else {
        $stmt = $db->prepare('INSERT INTO blog_categories (name, slug, description, image, sort_order, is_visible) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$data['name'], $slug, $data['description'] ?? '', $data['image'] ?? '', (int)($data['sort_order'] ?? 0), (int)($data['is_visible'] ?? 1)]);
        $id = (int)$db->lastInsertId();
    }

    return $id;
}

/**
 * Delete a blog category. Explicitly nullifies posts' category_id
 * before deletion for safety.
 */
function deleteBlogCategory(int $id): bool {
    $db = getDB();
    $db->prepare('UPDATE blog_posts SET category_id = NULL WHERE category_id = ?')->execute([$id]);
    return $db->prepare('DELETE FROM blog_categories WHERE id = ?')->execute([$id]);
}

// ============================================================
// Blog Tag Functions
// ============================================================

/**
 * Get all blog tags.
 */
function getBlogTags(): array {
    $db = getDB();
    return $db->query('SELECT * FROM blog_tags ORDER BY name ASC')->fetchAll();
}

/**
 * Get tags for a specific post.
 */
function getPostTags(int $postId): array {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT bt.* FROM blog_tags bt
         JOIN blog_post_tags bpt ON bpt.tag_id = bt.id
         WHERE bpt.post_id = ?
         ORDER BY bt.name ASC'
    );
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

/**
 * Get a tag by slug.
 */
function getTagBySlug(string $slug): ?array {
    $db = getDB();
    $stmt = $db->prepare('SELECT * FROM blog_tags WHERE slug = ?');
    $stmt->execute([$slug]);
    $tag = $stmt->fetch();
    return $tag ?: null;
}

/**
 * Sync tags for a post. Creates new tags as needed.
 */
function syncPostTags(int $postId, array $tagNames): void {
    $db = getDB();

    // Remove existing
    $db->prepare('DELETE FROM blog_post_tags WHERE post_id = ?')->execute([$postId]);

    foreach ($tagNames as $name) {
        $name = trim($name);
        if (!$name) continue;

        $slug = createSlug($name);
        if (!$slug) continue;

        // Find or create tag
        $stmt = $db->prepare('SELECT id FROM blog_tags WHERE slug = ?');
        $stmt->execute([$slug]);
        $tag = $stmt->fetch();

        if ($tag) {
            $tagId = $tag['id'];
        } else {
            $db->prepare('INSERT INTO blog_tags (name, slug) VALUES (?, ?)')->execute([$name, $slug]);
            $tagId = (int)$db->lastInsertId();
        }

        $db->prepare('INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)')->execute([$postId, $tagId]);
    }
}

/**
 * Get popular tags with usage counts.
 */
function getPopularTags(int $limit = 20): array {
    $db = getDB();
    $limit = max(1, min(100, $limit));
    $stmt = $db->prepare(
        "SELECT bt.*, COUNT(bpt.post_id) AS post_count
         FROM blog_tags bt
         JOIN blog_post_tags bpt ON bpt.tag_id = bt.id
         JOIN blog_posts bp ON bp.id = bpt.post_id
            AND " . BLOG_PUBLISHED_CONDITION_PREFIXED . "
            AND bp.published_at <= NOW()
         GROUP BY bt.id
         ORDER BY post_count DESC
         LIMIT ?"
    );
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

// ============================================================
// Blog Comment Functions
// ============================================================

/**
 * Get approved comments for a post (threaded).
 */
function getPostComments(int $postId): array {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT * FROM blog_comments
         WHERE post_id = ? AND is_approved = 1
         ORDER BY created_at ASC'
    );
    $stmt->execute([$postId]);
    $flat = $stmt->fetchAll();

    // Build threaded structure
    $threaded = [];
    $map = [];
    foreach ($flat as $comment) {
        $comment['replies'] = [];
        $map[$comment['id']] = $comment;
    }
    foreach ($map as $id => $comment) {
        if ($comment['parent_id'] && isset($map[$comment['parent_id']])) {
            $map[$comment['parent_id']]['replies'][] = &$map[$id];
        } else {
            $threaded[] = &$map[$id];
        }
    }
    return $threaded;
}

/**
 * Get pending comments for admin moderation.
 */
function getPendingComments(): array {
    $db = getDB();
    return $db->query(
        'SELECT bc.*, bp.title AS post_title, bp.slug AS post_slug
         FROM blog_comments bc
         JOIN blog_posts bp ON bp.id = bc.post_id
         WHERE bc.is_approved = 0
         ORDER BY bc.created_at DESC'
    )->fetchAll();
}

/**
 * Get paginated comments for admin (with filters).
 */
function getAdminBlogComments(array $filters = [], int $page = 1, int $perPage = 30): array {
    $db = getDB();
    $page = max(1, $page);
    $perPage = max(1, min(100, $perPage));
    $where = ['1=1'];
    $params = [];

    if (isset($filters['status'])) {
        $where[] = 'bc.is_approved = ?';
        $params[] = (int)$filters['status'];
    }

    if (!empty($filters['post_id'])) {
        $where[] = 'bc.post_id = ?';
        $params[] = (int)$filters['post_id'];
    }

    $whereClause = implode(' AND ', $where);

    // Count total
    $countStmt = $db->prepare("SELECT COUNT(*) FROM blog_comments bc JOIN blog_posts bp ON bp.id = bc.post_id WHERE $whereClause");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $params[] = $perPage;
    $params[] = $offset;
    $stmt = $db->prepare(
        "SELECT bc.*, bp.title AS post_title, bp.slug AS post_slug
         FROM blog_comments bc
         JOIN blog_posts bp ON bp.id = bc.post_id
         WHERE $whereClause
         ORDER BY bc.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->execute($params);

    return [
        'comments' => $stmt->fetchAll(),
        'total' => $total,
        'pages' => (int)ceil($total / $perPage),
        'current_page' => $page,
    ];
}

/**
 * Submit a comment.
 */
function submitBlogComment(int $postId, array $data): int {
    $db = getDB();

    $postStmt = $db->prepare(
        "SELECT id, allow_comments
         FROM blog_posts
         WHERE id = ?
           AND " . BLOG_PUBLISHED_CONDITION . "
           AND published_at <= NOW()"
    );
    $postStmt->execute([$postId]);
    $post = $postStmt->fetch();
    if (!$post || (int)$post['allow_comments'] !== 1) {
        return 0;
    }

    $parentId = isset($data['parent_id']) ? (int)$data['parent_id'] : 0;
    if ($parentId > 0) {
        $parentStmt = $db->prepare('SELECT id FROM blog_comments WHERE id = ? AND post_id = ? AND is_approved = 1');
        $parentStmt->execute([$parentId, $postId]);
        if (!$parentStmt->fetch()) {
            $parentId = 0;
        }
    }

    $autoApprove = getSetting('blog_comment_moderation', '1') !== '1';

    // Validate and sanitize input lengths to prevent abuse
    $authorName = mb_substr(trim($data['author_name'] ?? ''), 0, 100);
    $authorEmail = mb_substr(trim($data['author_email'] ?? ''), 0, 255);
    $content = mb_substr(trim($data['content'] ?? ''), 0, 5000);

    if (!$authorName || !$authorEmail || !$content) {
        return 0;
    }

    $stmt = $db->prepare(
        'INSERT INTO blog_comments (post_id, parent_id, customer_id, author_name, author_email, content, is_approved)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $postId,
        $parentId ?: null,
        $data['customer_id'] ?: null,
        $authorName,
        $authorEmail,
        $content,
        $autoApprove ? 1 : 0,
    ]);
    return (int)$db->lastInsertId();
}

/**
 * Approve a comment.
 */
function approveBlogComment(int $id): bool {
    $db = getDB();
    return $db->prepare('UPDATE blog_comments SET is_approved = 1 WHERE id = ?')->execute([$id]);
}

/**
 * Reject (unapprove) a comment.
 */
function rejectBlogComment(int $id): bool {
    $db = getDB();
    return $db->prepare('UPDATE blog_comments SET is_approved = 0 WHERE id = ?')->execute([$id]);
}

/**
 * Delete a comment.
 */
function deleteBlogComment(int $id): bool {
    $db = getDB();
    return $db->prepare('DELETE FROM blog_comments WHERE id = ?')->execute([$id]);
}

/**
 * Get approved comment count for a post.
 */
function getBlogCommentCount(int $postId): int {
    $db = getDB();
    $stmt = $db->prepare('SELECT COUNT(*) FROM blog_comments WHERE post_id = ? AND is_approved = 1');
    $stmt->execute([$postId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Get total pending comment count.
 */
function getPendingCommentCount(): int {
    $db = getDB();
    $stmt = $db->query('SELECT COUNT(*) FROM blog_comments WHERE is_approved = 0');
    return (int)$stmt->fetchColumn();
}

// ============================================================
// Blog–Product Integration
// ============================================================

/**
 * Get products linked to a blog post.
 */
function getPostProducts(int $postId): array {
    $db = getDB();
    $stmt = $db->prepare(
        'SELECT p.* FROM products p
         JOIN blog_post_products bpp ON bpp.product_id = p.id
         WHERE bpp.post_id = ? AND p.is_visible = 1
         ORDER BY bpp.sort_order ASC'
    );
    $stmt->execute([$postId]);
    return $stmt->fetchAll();
}

/**
 * Sync product links for a blog post.
 */
function syncPostProducts(int $postId, array $productIds): void {
    $db = getDB();
    $db->prepare('DELETE FROM blog_post_products WHERE post_id = ?')->execute([$postId]);

    $stmt = $db->prepare('INSERT IGNORE INTO blog_post_products (post_id, product_id, sort_order) VALUES (?, ?, ?)');
    foreach ($productIds as $i => $productId) {
        $productId = (int)$productId;
        if ($productId > 0) {
            $stmt->execute([$postId, $productId, $i]);
        }
    }
}

/**
 * Get blog posts that link to a specific product.
 */
function getProductBlogPosts(int $productId, int $limit = 3): array {
    $db = getDB();
    $stmt = $db->prepare(
        "SELECT bp.*, bc.name AS category_name, bc.slug AS category_slug
         FROM blog_posts bp
         JOIN blog_post_products bpp ON bpp.post_id = bp.id
         LEFT JOIN blog_categories bc ON bc.id = bp.category_id
         WHERE bpp.product_id = ? AND " . BLOG_PUBLISHED_CONDITION_PREFIXED . " AND bp.published_at <= NOW()
         ORDER BY bp.published_at DESC
         LIMIT ?"
    );
    $stmt->execute([$productId, $limit]);
    return $stmt->fetchAll();
}

// ============================================================
// Blog Stats (for admin dashboard)
// ============================================================

/**
 * Get blog statistics for admin dashboard.
 */
function getBlogStats(): array {
    $db = getDB();

    $totalPosts = (int)$db->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn();
    $publishedPosts = (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'")->fetchColumn();
    $draftPosts = (int)$db->query("SELECT COUNT(*) FROM blog_posts WHERE status = 'draft'")->fetchColumn();
    $pendingComments = getPendingCommentCount();
    $totalViews = (int)$db->query("SELECT COALESCE(SUM(view_count), 0) FROM blog_posts")->fetchColumn();

    return [
        'total_posts' => $totalPosts,
        'published_posts' => $publishedPosts,
        'draft_posts' => $draftPosts,
        'pending_comments' => $pendingComments,
        'total_views' => $totalViews,
    ];
}
