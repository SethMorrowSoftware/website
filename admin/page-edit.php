<?php
/**
 * Admin — Page Editor
 *
 * Supports both the classic WYSIWYG editor and the block-based content builder.
 * Existing pages with HTML content use the classic editor by default.
 * New pages and pages with blocks use the block editor.
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
    $blocksJson = $_POST['blocks_json'] ?? '';
    $editorMode = $_POST['editor_mode'] ?? 'classic';
    $meta_description = trim($_POST['meta_description'] ?? '');
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    $show_in_nav = isset($_POST['show_in_nav']) ? 1 : 0;
    $template = $_POST['template'] ?? 'default';
    $sort_order = (int)($_POST['sort_order'] ?? 0);

    // Validate blocks JSON if using block editor
    $blocks = null;
    if ($editorMode === 'blocks' && $blocksJson) {
        $decoded = json_decode($blocksJson, true);
        if (is_array($decoded)) {
            $blocks = $blocksJson;
        }
    }

    if ($title && $slug) {
        if ($id && $page) {
            $stmt = $db->prepare('UPDATE pages SET title=?, slug=?, content=?, blocks=?, meta_description=?, is_published=?, show_in_nav=?, template=?, sort_order=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
            $stmt->execute([$title, $slug, $content, $blocks, $meta_description, $is_published, $show_in_nav, $template, $sort_order, $id]);
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Page updated!'];
        } else {
            $stmt = $db->prepare('INSERT INTO pages (title, slug, content, blocks, meta_description, is_published, show_in_nav, template, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$title, $slug, $content, $blocks, $meta_description, $is_published, $show_in_nav, $template, $sort_order]);
            $id = $db->lastInsertId();
            $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Page created!'];
        }
        redirect('admin/page-edit.php?id=' . $id);
    }
}

// Determine editor mode
$hasBlocks = $page && pageHasBlocks($page['blocks'] ?? null);
$editorMode = $hasBlocks ? 'blocks' : 'classic';
// Allow switching via query param
if (isset($_GET['editor'])) {
    $editorMode = $_GET['editor'] === 'blocks' ? 'blocks' : 'classic';
}

$currentBlocks = $hasBlocks ? parseBlocks($page['blocks']) : [];
$blockTypes = getBlockTypes();

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-file-alt"></i> <?php echo $page ? 'Edit Page' : 'Add New Page'; ?></h1>
    <div style="display: flex; gap: 0.5rem; align-items: center;">
        <?php if ($editorMode === 'classic'): ?>
            <a href="<?php echo url('admin/page-edit.php') . ($id ? '?id=' . $id . '&editor=blocks' : '?editor=blocks'); ?>" class="btn-admin btn-outline" title="Switch to Block Editor">
                <i class="fas fa-th-large"></i> Block Editor
            </a>
        <?php else: ?>
            <a href="<?php echo url('admin/page-edit.php') . ($id ? '?id=' . $id . '&editor=classic' : '?editor=classic'); ?>" class="btn-admin btn-outline" title="Switch to Classic Editor">
                <i class="fas fa-file-alt"></i> Classic Editor
            </a>
        <?php endif; ?>
        <a href="<?php echo url('admin/pages.php'); ?>" class="btn-admin btn-back"><i class="fas fa-arrow-left"></i> Back to Pages</a>
    </div>
</div>

<form method="POST" class="admin-form" id="pageForm">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
    <input type="hidden" name="editor_mode" value="<?php echo e($editorMode); ?>">
    <input type="hidden" name="blocks_json" id="blocksJsonField" value="<?php echo e(json_encode($currentBlocks)); ?>">

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

                <?php if ($editorMode === 'classic'): ?>
                <!-- Classic WYSIWYG Editor -->
                <div class="form-group" id="classicEditor">
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
                <?php else: ?>
                <!-- Block Editor -->
                <div class="form-group" id="blockEditor">
                    <label>Page Content (Blocks)</label>
                    <div id="blockList" class="block-editor-list">
                        <!-- Blocks rendered by JS -->
                    </div>
                    <div class="block-editor-add">
                        <button type="button" class="btn-admin btn-outline" id="addBlockBtn">
                            <i class="fas fa-plus-circle"></i> Add Block
                        </button>
                    </div>
                </div>

                <!-- Block Type Picker Modal -->
                <div id="blockPickerModal" class="block-picker-modal" style="display: none;">
                    <div class="block-picker-overlay" onclick="closeBlockPicker()"></div>
                    <div class="block-picker-content">
                        <div class="block-picker-header">
                            <h3>Add Block</h3>
                            <button type="button" onclick="closeBlockPicker()" class="block-picker-close"><i class="fas fa-times"></i></button>
                        </div>
                        <div class="block-picker-grid">
                            <?php foreach ($blockTypes as $type => $def): ?>
                                <?php if ($type === 'classic') continue; // Don't show classic in picker ?>
                                <button type="button" class="block-picker-item" onclick="addBlock('<?php echo e($type); ?>')">
                                    <i class="<?php echo e($def['icon']); ?>"></i>
                                    <span><?php echo e($def['label']); ?></span>
                                    <small><?php echo e($def['description']); ?></small>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Hidden textarea for classic content fallback -->
                <textarea name="content" id="contentField" style="display:none;"><?php echo e($page['content'] ?? ''); ?></textarea>
                <?php endif; ?>

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

<?php if ($editorMode === 'classic'): ?>
<script src="<?php echo asset('js/editor.js'); ?>"></script>
<?php else: ?>
<!-- Block type definitions for JS -->
<script>
window.blockTypeDefinitions = <?php echo json_encode(array_map(function($def) {
    return [
        'label' => $def['label'],
        'icon' => $def['icon'],
        'description' => $def['description'],
        'fields' => $def['fields'] ?? [],
    ];
}, $blockTypes)); ?>;
window.initialBlocks = <?php echo json_encode($currentBlocks); ?>;
</script>
<script src="<?php echo asset('js/block-editor.js'); ?>"></script>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
