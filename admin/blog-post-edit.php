<?php
/**
 * Admin — Blog Post Editor
 * Full-featured post editor with WYSIWYG, image uploads, tags, product linking.
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_blog');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$post = null;
$postTags = [];
$postProductIds = [];

if ($id) {
    $post = getBlogPostById($id);
    if (!$post) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Post not found.'];
        redirect('admin/blog-posts.php');
    }
    $postTags = getPostTags($id);
    $postProducts = getPostProducts($id);
    $postProductIds = array_column($postProducts, 'id');
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $data = [
        'id' => $id,
        'title' => trim($_POST['title'] ?? ''),
        'excerpt' => trim($_POST['excerpt'] ?? ''),
        'content' => $_POST['content'] ?? '',
        'featured_image' => trim($_POST['featured_image'] ?? ''),
        'featured_image_alt' => trim($_POST['featured_image_alt'] ?? ''),
        'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
        'author_id' => $post ? $post['author_id'] : getCurrentUserId(),
        'status' => $_POST['status'] ?? 'draft',
        'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
        'allow_comments' => isset($_POST['allow_comments']) ? 1 : 0,
        'meta_description' => trim($_POST['meta_description'] ?? ''),
        'og_image' => trim($_POST['og_image'] ?? ''),
        'published_at' => $_POST['published_at'] ?? null,
    ];

    // Sanitize HTML content
    $data['content'] = sanitizeHtml($data['content']);

    if (!$data['title']) {
        $_SESSION['admin_flash'] = ['type' => 'error', 'message' => 'Title is required.'];
    } else {
        // If publishing and no published_at, set to now
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = date('Y-m-d H:i:s');
        }

        $postId = saveBlogPost($data);

        // Sync tags
        $tagNames = array_filter(array_map('trim', explode(',', $_POST['tags'] ?? '')));
        syncPostTags($postId, $tagNames);

        // Sync products
        $productIds = $_POST['product_ids'] ?? [];
        syncPostProducts($postId, $productIds);

        logAudit($id ? 'update' : 'create', 'blog_post', $postId, ['title' => $data['title']]);
        $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Post saved successfully.'];
        redirect('admin/blog-post-edit.php?id=' . $postId);
    }
}

$categories = getBlogCategories(true);
$allTags = getBlogTags();
$allProducts = $db->query('SELECT id, name, image, price FROM products WHERE is_visible = 1 ORDER BY name ASC')->fetchAll();
$csrfToken = generateCSRFToken();

require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-edit"></i> <?php echo $id ? 'Edit Post' : 'New Post'; ?></h1>
    <a href="<?php echo url('admin/blog-posts.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> All Posts</a>
</div>

<form method="POST" class="blog-editor-form" id="blogPostForm">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

    <div class="blog-editor-layout">
        <!-- Main Content Column -->
        <div class="blog-editor-main">
            <!-- Title -->
            <div class="form-group">
                <input type="text" name="title" id="postTitle" placeholder="Post title..." value="<?php echo e($post['title'] ?? ''); ?>" class="form-control blog-title-input" required>
            </div>

            <!-- WYSIWYG Editor -->
            <div class="form-group">
                <label>Content</label>
                <div class="editor-toolbar">
                    <button type="button" onclick="execCmd('bold')" title="Bold"><i class="fas fa-bold"></i></button>
                    <button type="button" onclick="execCmd('italic')" title="Italic"><i class="fas fa-italic"></i></button>
                    <button type="button" onclick="execCmd('underline')" title="Underline"><i class="fas fa-underline"></i></button>
                    <button type="button" onclick="execCmd('strikeThrough')" title="Strikethrough"><i class="fas fa-strikethrough"></i></button>
                    <span class="toolbar-sep"></span>
                    <button type="button" onclick="execCmd('formatBlock', 'h2')" title="Heading 2">H2</button>
                    <button type="button" onclick="execCmd('formatBlock', 'h3')" title="Heading 3">H3</button>
                    <button type="button" onclick="execCmd('formatBlock', 'h4')" title="Heading 4">H4</button>
                    <button type="button" onclick="execCmd('formatBlock', 'p')" title="Paragraph">P</button>
                    <span class="toolbar-sep"></span>
                    <button type="button" onclick="execCmd('insertUnorderedList')" title="Bullet List"><i class="fas fa-list-ul"></i></button>
                    <button type="button" onclick="execCmd('insertOrderedList')" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                    <button type="button" onclick="execCmd('formatBlock', 'blockquote')" title="Quote"><i class="fas fa-quote-left"></i></button>
                    <span class="toolbar-sep"></span>
                    <button type="button" onclick="insertLink()" title="Insert Link"><i class="fas fa-link"></i></button>
                    <button type="button" onclick="triggerImageUpload()" title="Upload Image"><i class="fas fa-image"></i></button>
                    <button type="button" onclick="openMediaPicker()" title="Media Library"><i class="fas fa-photo-video"></i></button>
                    <button type="button" onclick="insertProductEmbed()" title="Embed Product"><i class="fas fa-shopping-cart"></i></button>
                    <span class="toolbar-sep"></span>
                    <button type="button" onclick="execCmd('removeFormat')" title="Clear Formatting"><i class="fas fa-eraser"></i></button>
                    <button type="button" onclick="toggleSource()" id="sourceBtn" title="HTML Source"><i class="fas fa-code"></i></button>
                </div>
                <div id="editorArea" contenteditable="true" class="editor-area blog-editor-area"><?php echo $post['content'] ?? ''; ?></div>
                <input type="hidden" name="content" id="contentField" value="<?php echo e($post['content'] ?? ''); ?>">
                <input type="file" id="editorImageUpload" accept="image/*" style="display:none;" multiple>
            </div>

            <!-- Excerpt -->
            <div class="form-group">
                <label for="excerpt">Excerpt <span class="form-help" style="display:inline; margin:0;">Auto-generated from content if blank</span></label>
                <textarea name="excerpt" id="excerpt" rows="3" class="form-control" placeholder="Brief summary for listing pages..."><?php echo e($post['excerpt'] ?? ''); ?></textarea>
            </div>

            <!-- SEO Section -->
            <div class="form-group">
                <div class="seo-panel">
                    <div class="seo-header"><i class="fas fa-search"></i> SEO Settings</div>
                    <div class="form-group">
                        <label for="meta_description">Meta Description</label>
                        <textarea name="meta_description" id="meta_description" rows="2" class="form-control" maxlength="160" placeholder="SEO description (max 160 chars)..."><?php echo e($post['meta_description'] ?? ''); ?></textarea>
                        <span class="meta-counter" id="metaCount">0/160</span>
                    </div>
                    <div class="form-group" style="margin-bottom:0;">
                        <label for="og_image">Open Graph Image URL</label>
                        <input type="text" name="og_image" id="og_image" value="<?php echo e($post['og_image'] ?? ''); ?>" class="form-control" placeholder="/uploads/images/...">
                        <span class="form-help">Override the default social sharing image</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="blog-editor-sidebar">
            <!-- Publish Box -->
            <div class="editor-panel">
                <h3><i class="fas fa-paper-plane"></i> Publish</h3>
                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="draft" <?php echo ($post['status'] ?? 'draft') === 'draft' ? 'selected' : ''; ?>>Draft</option>
                        <option value="published" <?php echo ($post['status'] ?? '') === 'published' ? 'selected' : ''; ?>>Published</option>
                        <option value="scheduled" <?php echo ($post['status'] ?? '') === 'scheduled' ? 'selected' : ''; ?>>Scheduled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="published_at">Publish Date</label>
                    <input type="datetime-local" name="published_at" id="published_at"
                           value="<?php echo ($post['published_at'] ?? null) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : ''; ?>"
                           class="form-control">
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_featured" value="1" <?php echo ($post['is_featured'] ?? 0) ? 'checked' : ''; ?>>
                        <span>Featured Post</span>
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="allow_comments" value="1" <?php echo ($post['allow_comments'] ?? 1) ? 'checked' : ''; ?>>
                        <span>Allow Comments</span>
                    </label>
                </div>
                <div class="publish-actions">
                    <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save</button>
                    <?php if ($id && ($post['status'] ?? '') === 'published'): ?>
                        <a href="<?php echo url('index.php?page=blog-post&slug=' . e($post['slug'])); ?>" target="_blank" class="btn-admin btn-outline" title="View live post"><i class="fas fa-external-link-alt"></i></a>
                    <?php endif; ?>
                </div>
                <?php if ($id): ?>
                    <div class="panel-meta">
                        <span><i class="fas fa-eye"></i> <?php echo number_format($post['view_count']); ?> views</span>
                        <span><i class="fas fa-calendar-plus"></i> Created <?php echo date('M j, Y', strtotime($post['created_at'])); ?></span>
                        <span><i class="fas fa-clock"></i> Updated <?php echo date('M j, Y g:ia', strtotime($post['updated_at'])); ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Category -->
            <div class="editor-panel">
                <h3><i class="fas fa-folder"></i> Category</h3>
                <select name="category_id" class="form-control">
                    <option value="">— No Category —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php echo ((int)($post['category_id'] ?? 0)) === $cat['id'] ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <a href="<?php echo url('admin/blog-categories.php'); ?>" class="panel-link"><i class="fas fa-plus"></i> Manage Categories</a>
            </div>

            <!-- Tags -->
            <div class="editor-panel">
                <h3><i class="fas fa-tags"></i> Tags</h3>
                <input type="text" name="tags" id="tagsInput" class="form-control" placeholder="tag1, tag2, tag3..."
                       value="<?php echo e(implode(', ', array_column($postTags, 'name'))); ?>">
                <?php if (!empty($allTags)): ?>
                    <div class="tag-suggestions">
                        <?php foreach (array_slice($allTags, 0, 15) as $tag): ?>
                            <span class="tag-chip" onclick="addTag('<?php echo e($tag['name']); ?>')"><?php echo e($tag['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Featured Image -->
            <div class="editor-panel">
                <h3><i class="fas fa-image"></i> Featured Image</h3>
                <div id="featuredImagePreview" class="featured-image-preview" style="<?php echo empty($post['featured_image']) ? 'display:none;' : ''; ?>">
                    <img id="featuredImageImg" src="<?php echo e($post['featured_image'] ?? ''); ?>" alt="">
                    <button type="button" onclick="removeFeaturedImage()" class="remove-featured" title="Remove image"><i class="fas fa-times"></i></button>
                </div>
                <input type="hidden" name="featured_image" id="featuredImageInput" value="<?php echo e($post['featured_image'] ?? ''); ?>">
                <input type="file" id="featuredImageUpload" accept="image/*" style="display:none;">
                <button type="button" onclick="document.getElementById('featuredImageUpload').click();" class="btn-admin btn-outline btn-small" id="featuredImageBtn" style="width:100%;">
                    <i class="fas fa-upload"></i> Upload Image
                </button>
                <div class="form-group" style="margin-top:10px; margin-bottom:0;">
                    <input type="text" name="featured_image_alt" placeholder="Alt text for accessibility..." value="<?php echo e($post['featured_image_alt'] ?? ''); ?>" class="form-control">
                </div>
            </div>

            <!-- Related Products -->
            <?php if (isFeatureEnabled('catalog')): ?>
            <div class="editor-panel">
                <h3><i class="fas fa-shopping-bag"></i> Related Products</h3>
                <div class="product-picker">
                    <?php foreach ($allProducts as $product): ?>
                        <label class="product-pick-item">
                            <input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>"
                                   <?php echo in_array($product['id'], $postProductIds) ? 'checked' : ''; ?>>
                            <?php if ($product['image']): ?>
                                <img src="<?php echo e($product['image']); ?>" alt="">
                            <?php endif; ?>
                            <span><?php echo e($product['name']); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- Media Picker Modal -->
<div id="mediaPickerModal" class="blog-modal-overlay">
    <div class="blog-modal" style="max-width:800px;">
        <div class="blog-modal-header">
            <h3><i class="fas fa-photo-video"></i> Media Library</h3>
            <button type="button" onclick="closeMediaPicker()" class="blog-modal-close">&times;</button>
        </div>
        <div class="blog-modal-body">
            <div id="mediaPickerGrid" class="media-picker-grid"></div>
        </div>
    </div>
</div>

<!-- Product Embed Modal -->
<div id="productEmbedModal" class="blog-modal-overlay">
    <div class="blog-modal" style="max-width:500px;">
        <div class="blog-modal-header">
            <h3><i class="fas fa-shopping-cart"></i> Embed Product</h3>
            <button type="button" onclick="closeProductEmbed()" class="blog-modal-close">&times;</button>
        </div>
        <div class="blog-modal-body">
            <?php foreach ($allProducts as $product): ?>
                <div class="product-embed-item" onclick="insertProductShortcode(<?php echo $product['id']; ?>)">
                    <?php if ($product['image']): ?>
                        <img src="<?php echo e($product['image']); ?>" alt="">
                    <?php endif; ?>
                    <div>
                        <div class="product-embed-name"><?php echo e($product['name']); ?></div>
                        <?php if ($product['price']): ?>
                            <div class="product-embed-price"><?php echo formatCurrency((float)$product['price']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script src="<?php echo asset('js/editor.js'); ?>"></script>
<script src="<?php echo asset('js/blog-editor.js'); ?>"></script>

<?php require_once __DIR__ . '/footer.php'; ?>
