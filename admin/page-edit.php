<?php
/**
 * Admin — Page Editor
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();
requirePermission('manage_pages');

$db = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page = null;

if ($id) {
    $stmt = $db->prepare('SELECT * FROM pages WHERE id = ?');
    $stmt->execute([$id]);
    $page = $stmt->fetch();
}

// Handle save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $title = trim($_POST['title'] ?? '');
    $slug = createSlug($_POST['slug'] ?? $title);
    $content = $_POST['content'] ?? '';
    $meta_description = trim($_POST['meta_description'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $show_in_nav = isset($_POST['show_in_nav']) ? 1 : 0;
    $template = $_POST['template'] ?? 'default';
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    if ($title && $slug) {
        if ($id && $page) {
            $stmt = $db->prepare('UPDATE pages SET title=?, slug=?, content=?, meta_description=?, is_published=?, show_in_nav=?, template=?, sort_order=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $stmt->execute([$title, $slug, $content, $meta_description, $is_published, $show_in_nav, $template, $sort_order, $id]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Page updated!'];
        } else {
            $stmt = $db->prepare('INSERT INTO pages (title, slug, content, meta_description, is_published, show_in_nav, template, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $slug, $content, $meta_description, $is_published, $show_in_nav, $template, $sort_order]);
            $id = $db->lastInsertId();
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Page created!'];
        }
        redirect('admin/page-edit.php?id=' . $id);
    }
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-file-alt"></i> <?php echo $page ? 'Edit Page' : 'Add New Page'; ?></h1>
    <a href="<?php echo url('admin/pages.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Pages</a>
</div>

<form method="POST" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

    <div class="form-layout-sidebar">
        <div class="form-main">
            <div class="form-section">
                <div class="form-group">
                    <label>Page Title <span class="required">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?php echo e($page['title'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label>URL Slug</label>
                    <input type="text" name="slug" class="form-control" value="<?php echo e($page['slug'] ?? ''); ?>" placeholder="auto-generated from title">
                </div>

                <div class="form-group">
                    <label>Page Content</label>
                    <div class="editor-toolbar" id="editorToolbar">
                        <button type="button" onclick="execCmd('bold')" title="Bold"><i class="fas fa-bold"></i></button>
                        <button type="button" onclick="execCmd('italic')" title="Italic"><i class="fas fa-italic"></i></button>
                        <button type="button" onclick="execCmd('underline')" title="Underline"><i class="fas fa-underline"></i></button>
                        <span class="toolbar-divider"></span>
                        <button type="button" onclick="execCmd('formatBlock', 'h2')" title="Heading 2">H2</button>
                        <button type="button" onclick="execCmd('formatBlock', 'h3')" title="Heading 3">H3</button>
                        <button type="button" onclick="execCmd('formatBlock', 'p')" title="Paragraph">P</button>
                        <span class="toolbar-divider"></span>
                        <button type="button" onclick="execCmd('insertUnorderedList')" title="Bullet List"><i class="fas fa-list-ul"></i></button>
                        <button type="button" onclick="execCmd('insertOrderedList')" title="Numbered List"><i class="fas fa-list-ol"></i></button>
                        <span class="toolbar-divider"></span>
                        <button type="button" onclick="insertLink()" title="Insert Link"><i class="fas fa-link"></i></button>
                        <button type="button" onclick="insertImage()" title="Insert Image"><i class="fas fa-image"></i></button>
                        <span class="toolbar-divider"></span>
                        <button type="button" onclick="toggleSource()" title="HTML Source" id="sourceBtn"><i class="fas fa-code"></i></button>
                    </div>
                    <div class="editor-area" id="editorArea" contenteditable="true"><?php echo $page['content'] ?? ''; ?></div>
                    <textarea name="content" id="contentField" style="display:none;"><?php echo e($page['content'] ?? ''); ?></textarea>
                </div>

                <div class="form-group">
                    <label>Meta Description (SEO)</label>
                    <textarea name="meta_description" class="form-control" rows="2" maxlength="160"><?php echo e($page['meta_description'] ?? ''); ?></textarea>
                    <small class="form-help">Max 160 characters. Appears in search engine results.</small>
                </div>
            </div>
        </div>

        <div class="form-sidebar">
            <div class="form-section">
                <h4>Publish Settings</h4>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_published" value="1" <?php echo (!$page || $page['is_published']) ? 'checked' : ''; ?>>
                        Published
                    </label>
                </div>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="show_in_nav" value="1" <?php echo (!$page || $page['show_in_nav']) ? 'checked' : ''; ?>>
                        Show in Navigation
                    </label>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" value="<?php echo e($page['sort_order'] ?? '0'); ?>">
                </div>
                <div class="form-group">
                    <label>Template</label>
                    <select name="template" class="form-control">
                        <option value="default" <?php echo ($page['template'] ?? '') === 'default' ? 'selected' : ''; ?>>Default</option>
                        <option value="home" <?php echo ($page['template'] ?? '') === 'home' ? 'selected' : ''; ?>>Home</option>
                        <option value="products" <?php echo ($page['template'] ?? '') === 'products' ? 'selected' : ''; ?>>Products</option>
                        <option value="containers" <?php echo ($page['template'] ?? '') === 'containers' ? 'selected' : ''; ?>>Containers</option>
                        <option value="contact" <?php echo ($page['template'] ?? '') === 'contact' ? 'selected' : ''; ?>>Contact</option>
                        <option value="order" <?php echo ($page['template'] ?? '') === 'order' ? 'selected' : ''; ?>>Order</option>
                        <option value="payment" <?php echo ($page['template'] ?? '') === 'payment' ? 'selected' : ''; ?>>Payment</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="btn-admin btn-save" style="width:100%;"><i class="fas fa-save"></i> Save Page</button>
        </div>
    </div>
</form>

<script src="<?php echo asset('js/editor.js'); ?>"></script>

<?php require_once __DIR__ . '/footer.php'; ?>
