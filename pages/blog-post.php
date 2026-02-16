<?php
/**
 * Single Blog Post Page
 * Full article view with comments, related posts, and product integration.
 */

$slug = $_GET['slug'] ?? '';
if (!$slug) {
    redirect('index.php?page=blog');
}

$post = $GLOBALS['_cached_blog_post'] ?? getBlogPost($slug);
if (!$post) {
    http_response_code(404);
    ?>
    <section class="section">
        <div class="container" style="text-align:center; padding:80px 20px;">
            <i class="fas fa-newspaper" style="font-size:3rem; color:#cbd5e1; margin-bottom:20px;"></i>
            <h1>Post Not Found</h1>
            <p style="color:#64748b;">The article you're looking for doesn't exist or has been removed.</p>
            <a href="<?php echo url('index.php?page=blog'); ?>" class="btn btn-primary" style="margin-top:20px;">Back to Blog</a>
        </div>
    </section>
    <?php
    return;
}

// Increment view count
incrementBlogPostViews($post['id']);

// Get additional data
$tags = getPostTags($post['id']);
$relatedPosts = getRelatedPosts($post['id'], 3);
$postProducts = getPostProducts($post['id']);
$adjacent = getAdjacentPosts($post['id'], $post['published_at']);
$commentCount = getBlogCommentCount($post['id']);
$comments = $post['allow_comments'] ? getPostComments($post['id']) : [];
$allowComments = $post['allow_comments'] && getSetting('blog_allow_comments', '1') === '1';

// Process shortcodes in content
$content = processBlogShortcodes($post['content']);

$companyName = getSetting('company_name', SITE_NAME);
?>

<!-- Override page title and meta for this post -->
<script>
document.title = <?php echo json_encode(e($post['title']) . ' | ' . e($companyName)); ?>;
</script>

<!-- Article Schema -->
<script type="application/ld+json">
<?php echo json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $post['title'],
    'description' => $post['meta_description'] ?: $post['excerpt'],
    'image' => $post['featured_image'] ?: null,
    'datePublished' => $post['published_at'],
    'dateModified' => $post['updated_at'],
    'author' => [
        '@type' => 'Person',
        'name' => $post['author_name'] ?: $companyName,
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => $companyName,
    ],
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?>
</script>

<!-- Featured Image Banner -->
<?php if ($post['featured_image']): ?>
<div class="blog-post-banner">
    <img src="<?php echo e($post['featured_image']); ?>" alt="<?php echo e($post['featured_image_alt'] ?: $post['title']); ?>">
</div>
<?php endif; ?>

<article class="blog-post-article">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav class="blog-breadcrumbs" aria-label="Breadcrumb">
            <a href="<?php echo url('/'); ?>">Home</a>
            <span class="sep"><i class="fas fa-chevron-right"></i></span>
            <a href="<?php echo url('index.php?page=blog'); ?>">Blog</a>
            <?php if ($post['category_name']): ?>
                <span class="sep"><i class="fas fa-chevron-right"></i></span>
                <a href="<?php echo url('index.php?page=blog&category=' . e($post['category_slug'])); ?>"><?php echo e($post['category_name']); ?></a>
            <?php endif; ?>
            <span class="sep"><i class="fas fa-chevron-right"></i></span>
            <span class="current"><?php echo e(mb_substr($post['title'], 0, 50)); ?></span>
        </nav>

        <div class="blog-post-layout">
            <!-- Main Article Content -->
            <div class="blog-post-content">
                <header class="blog-post-header">
                    <?php if ($post['category_name']): ?>
                        <a href="<?php echo url('index.php?page=blog&category=' . e($post['category_slug'])); ?>" class="blog-post-category-badge"><?php echo e($post['category_name']); ?></a>
                    <?php endif; ?>
                    <h1 class="blog-post-title"><?php echo e($post['title']); ?></h1>
                    <div class="blog-post-meta">
                        <?php if (getSetting('blog_show_author', '1') === '1' && $post['author_name']): ?>
                            <span><i class="fas fa-user-circle"></i> <?php echo e($post['author_name']); ?></span>
                        <?php endif; ?>
                        <span><i class="fas fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($post['published_at'])); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo number_format($post['view_count'] + 1); ?> views</span>
                        <?php if ($commentCount > 0): ?>
                            <a href="#comments"><i class="fas fa-comments"></i> <?php echo $commentCount; ?> comment<?php echo $commentCount !== 1 ? 's' : ''; ?></a>
                        <?php endif; ?>
                    </div>
                </header>

                <!-- Article Body -->
                <div class="blog-post-body prose">
                    <?php echo $content; ?>
                </div>

                <!-- Tags -->
                <?php if (!empty($tags)): ?>
                <div class="blog-post-tags">
                    <i class="fas fa-tags"></i>
                    <?php foreach ($tags as $tag): ?>
                        <a href="<?php echo url('index.php?page=blog&tag=' . e($tag['slug'])); ?>" class="blog-tag"><?php echo e($tag['name']); ?></a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Social Sharing -->
                <div class="blog-post-share">
                    <span class="share-label">Share this article:</span>
                    <?php
                    $shareUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '');
                    $shareTitle = urlencode($post['title']);
                    $shareUrlEncoded = urlencode($shareUrl);
                    ?>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $shareUrlEncoded; ?>" target="_blank" rel="noopener" class="share-btn share-facebook" title="Share on Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo $shareUrlEncoded; ?>&text=<?php echo $shareTitle; ?>" target="_blank" rel="noopener" class="share-btn share-twitter" title="Share on X/Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo $shareUrlEncoded; ?>&title=<?php echo $shareTitle; ?>" target="_blank" rel="noopener" class="share-btn share-linkedin" title="Share on LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <button type="button" class="share-btn share-copy" title="Copy link" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($shareUrl, ENT_QUOTES); ?>').then(function(){alert('Link copied!')})"><i class="fas fa-link"></i></button>
                </div>

                <!-- Related Products -->
                <?php if (!empty($postProducts)): ?>
                <section class="blog-post-products">
                    <h2><i class="fas fa-shopping-bag"></i> Featured Products</h2>
                    <div class="blog-products-grid">
                        <?php foreach ($postProducts as $product): ?>
                            <a href="<?php echo url('index.php?page=product&slug=' . e($product['slug'])); ?>" class="blog-product-card-link">
                                <?php if ($product['image']): ?>
                                    <img src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>" loading="lazy">
                                <?php endif; ?>
                                <div class="blog-product-card-info">
                                    <h4><?php echo e($product['name']); ?></h4>
                                    <?php if ($product['price']): ?>
                                        <span class="blog-product-price"><?php echo formatCurrency((float)$product['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <!-- Post Navigation -->
                <nav class="blog-post-nav">
                    <?php if ($adjacent['prev']): ?>
                        <a href="<?php echo url('index.php?page=blog-post&slug=' . e($adjacent['prev']['slug'])); ?>" class="blog-post-nav-link prev">
                            <span class="nav-label"><i class="fas fa-arrow-left"></i> Previous</span>
                            <span class="nav-title"><?php echo e(mb_substr($adjacent['prev']['title'], 0, 60)); ?></span>
                        </a>
                    <?php else: ?>
                        <div></div>
                    <?php endif; ?>
                    <?php if ($adjacent['next']): ?>
                        <a href="<?php echo url('index.php?page=blog-post&slug=' . e($adjacent['next']['slug'])); ?>" class="blog-post-nav-link next">
                            <span class="nav-label">Next <i class="fas fa-arrow-right"></i></span>
                            <span class="nav-title"><?php echo e(mb_substr($adjacent['next']['title'], 0, 60)); ?></span>
                        </a>
                    <?php endif; ?>
                </nav>

                <!-- Comments Section -->
                <?php if ($allowComments): ?>
                <section class="blog-comments-section" id="comments">
                    <h2><i class="fas fa-comments"></i> Comments (<?php echo $commentCount; ?>)</h2>

                    <!-- Comment List -->
                    <?php if (!empty($comments)): ?>
                        <div class="blog-comments-list">
                            <?php
                            function renderComments($comments, $depth = 0) {
                                foreach ($comments as $comment):
                                    $indent = min($depth, 3) * 30;
                            ?>
                                <div class="blog-comment" style="margin-left:<?php echo $indent; ?>px;">
                                    <div class="blog-comment-header">
                                        <div class="blog-comment-avatar">
                                            <i class="fas fa-user-circle"></i>
                                        </div>
                                        <div>
                                            <strong class="blog-comment-author"><?php echo e($comment['author_name']); ?></strong>
                                            <span class="blog-comment-date"><?php echo date('M j, Y \a\t g:ia', strtotime($comment['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="blog-comment-body"><?php echo nl2br(e($comment['content'])); ?></div>
                                    <button type="button" class="blog-comment-reply-btn" onclick="showReplyForm(<?php echo $comment['id']; ?>)">
                                        <i class="fas fa-reply"></i> Reply
                                    </button>
                                    <div id="replyForm-<?php echo $comment['id']; ?>" style="display:none;"></div>
                                    <?php if (!empty($comment['replies'])): ?>
                                        <?php renderComments($comment['replies'], $depth + 1); ?>
                                    <?php endif; ?>
                                </div>
                            <?php
                                endforeach;
                            }
                            renderComments($comments);
                            ?>
                        </div>
                    <?php elseif ($commentCount === 0): ?>
                        <p class="blog-no-comments">Be the first to leave a comment!</p>
                    <?php endif; ?>

                    <!-- Comment Form -->
                    <div class="blog-comment-form-wrap" id="commentFormWrap">
                        <h3>Leave a Comment</h3>
                        <form method="POST" action="<?php echo url('index.php'); ?>" class="blog-comment-form">
                            <input type="hidden" name="csrf_token" value="<?php echo e(generateCSRFToken()); ?>">
                            <input type="hidden" name="action" value="submit_blog_comment">
                            <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                            <input type="hidden" name="post_slug" value="<?php echo e($post['slug']); ?>">
                            <input type="hidden" name="parent_id" id="commentParentId" value="">

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="commenter_name">Name *</label>
                                    <input type="text" id="commenter_name" name="commenter_name" required
                                           value="<?php echo isCustomerLoggedIn() ? e(($_SESSION['customer_name'] ?? '')) : ''; ?>" class="form-input">
                                </div>
                                <div class="form-group">
                                    <label for="commenter_email">Email *</label>
                                    <input type="email" id="commenter_email" name="commenter_email" required
                                           value="<?php echo isCustomerLoggedIn() ? e(($_SESSION['customer_email'] ?? '')) : ''; ?>" class="form-input">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="comment_content">Comment *</label>
                                <textarea id="comment_content" name="comment_content" rows="5" required class="form-input" placeholder="Write your comment..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Comment</button>
                            <?php if (getSetting('blog_comment_moderation', '1') === '1'): ?>
                                <small style="display:block; margin-top:8px; color:#64748b;">Comments are moderated and will appear after approval.</small>
                            <?php endif; ?>
                        </form>
                    </div>
                </section>
                <?php endif; ?>
            </div>
        </div>

        <!-- Related Posts -->
        <?php if (!empty($relatedPosts)): ?>
        <section class="blog-related-posts">
            <h2>Related Articles</h2>
            <div class="blog-grid blog-grid-3">
                <?php foreach ($relatedPosts as $rp): ?>
                    <article class="blog-card">
                        <?php if ($rp['featured_image']): ?>
                            <a href="<?php echo url('index.php?page=blog-post&slug=' . e($rp['slug'])); ?>" class="blog-card-image">
                                <img src="<?php echo e($rp['featured_image']); ?>" alt="<?php echo e($rp['title']); ?>" loading="lazy">
                            </a>
                        <?php endif; ?>
                        <div class="blog-card-body">
                            <?php if ($rp['category_name']): ?>
                                <span class="blog-card-category"><?php echo e($rp['category_name']); ?></span>
                            <?php endif; ?>
                            <h3 class="blog-card-title">
                                <a href="<?php echo url('index.php?page=blog-post&slug=' . e($rp['slug'])); ?>"><?php echo e($rp['title']); ?></a>
                            </h3>
                            <div class="blog-card-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($rp['published_at'])); ?></span>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
</article>

<script>
function showReplyForm(commentId) {
    var container = document.getElementById('replyForm-' + commentId);
    if (!container) return;
    if (container.innerHTML) {
        container.style.display = container.style.display === 'none' ? 'block' : 'none';
        return;
    }
    container.style.display = 'block';
    container.innerHTML = '<div style="margin-top:10px;padding:10px;background:#f8fafc;border-radius:8px;">' +
        '<button type="button" onclick="setReplyParent(' + commentId + ')" class="btn btn-sm btn-primary">Reply to this comment</button>' +
        '</div>';
}

function setReplyParent(parentId) {
    document.getElementById('commentParentId').value = parentId;
    document.getElementById('commentFormWrap').scrollIntoView({behavior:'smooth'});
    document.getElementById('comment_content').focus();
    document.getElementById('comment_content').placeholder = 'Replying to comment #' + parentId + '...';
}
</script>
