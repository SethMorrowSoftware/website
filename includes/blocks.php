<?php
/**
 * Content Block System
 *
 * Provides a block-based content model for pages. Each page stores an ordered
 * array of blocks (JSON), where each block has a type and configuration data.
 *
 * Block types:
 *   classic       - Legacy WYSIWYG HTML content
 *   text          - Rich text section with heading
 *   image_text    - Image + text side by side
 *   hero          - Full-width hero banner
 *   product_grid  - Grid of products from a category
 *   testimonials  - Testimonial slider/grid
 *   cta           - Call-to-action banner
 *   faq           - FAQ accordion
 *   gallery       - Image gallery grid
 *   video         - Video embed (YouTube/Vimeo/self-hosted)
 *   html          - Raw HTML embed
 *   spacer        - Vertical spacing
 *   divider       - Horizontal rule
 *   contact_form  - Embedded contact form
 *   map           - Google Maps embed
 */

/** @var array<string, array> Registry of block types */
$_block_registry = [];

/**
 * Register a block type.
 *
 * @param string $type    Block type slug
 * @param array  $config  Block configuration:
 *   'label'       => Display name
 *   'icon'        => FontAwesome icon class
 *   'description' => Short description
 *   'fields'      => Array of field definitions for the editor
 *   'render'      => Callable(array $data): string  OR  null to use template file
 */
function registerBlock(string $type, array $config): void {
    global $_block_registry;
    $_block_registry[$type] = $config;
}

/**
 * Get all registered block types.
 *
 * @return array<string, array>
 */
function getBlockTypes(): array {
    global $_block_registry;
    return $_block_registry;
}

/**
 * Get a single block type definition.
 */
function getBlockType(string $type): ?array {
    global $_block_registry;
    return $_block_registry[$type] ?? null;
}

/**
 * Render a single block to HTML.
 *
 * @param array $block  Block data: ['type' => string, 'data' => array]
 * @return string Rendered HTML
 */
function renderBlock(array $block): string {
    $type = $block['type'] ?? '';
    $data = $block['data'] ?? [];

    $blockDef = getBlockType($type);
    if (!$blockDef) {
        return ''; // Unknown block type — skip silently
    }

    // Custom render callback
    if (isset($blockDef['render']) && is_callable($blockDef['render'])) {
        return $blockDef['render']($data);
    }

    // Template-based rendering
    $templateFile = BASE_PATH . '/pages/blocks/' . $type . '.php';

    // Allow theme override
    if (function_exists('resolveTemplate')) {
        $themeTemplate = resolveTemplate('pages/blocks/' . $type . '.php');
        if ($themeTemplate !== 'pages/blocks/' . $type . '.php') {
            $templateFile = BASE_PATH . '/' . $themeTemplate;
        }
    }

    if (!file_exists($templateFile)) {
        return '';
    }

    // Render template with block data extracted into scope
    ob_start();
    extract($data, EXTR_SKIP);
    include $templateFile;
    return ob_get_clean();
}

/**
 * Render all blocks for a page.
 *
 * @param array $blocks  Array of block data
 * @return string Combined HTML
 */
function renderBlocks(array $blocks): string {
    $html = '';
    foreach ($blocks as $block) {
        $html .= renderBlock($block);
    }
    return $html;
}

/**
 * Parse blocks JSON from a page record.
 *
 * @param string|null $blocksJson  JSON string from database
 * @return array Parsed blocks array (empty array if no blocks)
 */
function parseBlocks(?string $blocksJson): array {
    if (!$blocksJson) return [];
    $blocks = json_decode($blocksJson, true);
    return is_array($blocks) ? $blocks : [];
}

/**
 * Check if a page uses the block editor (has blocks content).
 */
function pageHasBlocks(?string $blocksJson): bool {
    if (!$blocksJson) return false;
    $blocks = json_decode($blocksJson, true);
    return is_array($blocks) && !empty($blocks);
}

// ============================================================
// Register Default Block Types
// ============================================================

registerBlock('classic', [
    'label' => 'Classic Content',
    'icon' => 'fas fa-file-alt',
    'description' => 'Legacy WYSIWYG HTML content block.',
    'fields' => [
        'content' => ['type' => 'richtext', 'label' => 'Content'],
    ],
]);

registerBlock('text', [
    'label' => 'Text Section',
    'icon' => 'fas fa-align-left',
    'description' => 'Rich text with optional heading.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading', 'required' => false],
        'content' => ['type' => 'richtext', 'label' => 'Content'],
        'alignment' => ['type' => 'select', 'label' => 'Alignment', 'options' => ['left', 'center', 'right'], 'default' => 'left'],
    ],
]);

registerBlock('image_text', [
    'label' => 'Image + Text',
    'icon' => 'fas fa-columns',
    'description' => 'Image alongside text content.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading', 'required' => false],
        'content' => ['type' => 'richtext', 'label' => 'Text Content'],
        'image' => ['type' => 'image', 'label' => 'Image'],
        'image_alt' => ['type' => 'text', 'label' => 'Image Alt Text'],
        'layout' => ['type' => 'select', 'label' => 'Layout', 'options' => ['image-left', 'image-right'], 'default' => 'image-left'],
    ],
]);

registerBlock('hero', [
    'label' => 'Hero Banner',
    'icon' => 'fas fa-image',
    'description' => 'Full-width hero section with background image.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading'],
        'subheading' => ['type' => 'text', 'label' => 'Subheading', 'required' => false],
        'background_image' => ['type' => 'image', 'label' => 'Background Image'],
        'cta_text' => ['type' => 'text', 'label' => 'Button Text', 'required' => false],
        'cta_link' => ['type' => 'text', 'label' => 'Button Link', 'required' => false],
        'overlay_opacity' => ['type' => 'select', 'label' => 'Overlay', 'options' => ['none', 'light', 'medium', 'dark'], 'default' => 'medium'],
    ],
]);

registerBlock('product_grid', [
    'label' => 'Product Grid',
    'icon' => 'fas fa-th',
    'description' => 'Display products from a category.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading', 'required' => false],
        'category_id' => ['type' => 'number', 'label' => 'Category ID (0 = featured)'],
        'count' => ['type' => 'number', 'label' => 'Number of Products', 'default' => 6],
        'columns' => ['type' => 'select', 'label' => 'Columns', 'options' => ['2', '3', '4'], 'default' => '3'],
    ],
]);

registerBlock('testimonials', [
    'label' => 'Testimonials',
    'icon' => 'fas fa-quote-right',
    'description' => 'Customer testimonials display.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'What Our Customers Say'],
        'count' => ['type' => 'number', 'label' => 'Number to Show', 'default' => 3],
        'layout' => ['type' => 'select', 'label' => 'Layout', 'options' => ['grid', 'slider'], 'default' => 'grid'],
    ],
]);

registerBlock('cta', [
    'label' => 'Call to Action',
    'icon' => 'fas fa-bullhorn',
    'description' => 'Attention-grabbing CTA banner.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading'],
        'subtext' => ['type' => 'text', 'label' => 'Subtext', 'required' => false],
        'button_text' => ['type' => 'text', 'label' => 'Button Text'],
        'button_link' => ['type' => 'text', 'label' => 'Button Link'],
        'style' => ['type' => 'select', 'label' => 'Style', 'options' => ['primary', 'secondary', 'dark'], 'default' => 'primary'],
    ],
]);

registerBlock('faq', [
    'label' => 'FAQ Accordion',
    'icon' => 'fas fa-question-circle',
    'description' => 'Frequently asked questions with expandable answers.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Section Heading', 'default' => 'Frequently Asked Questions'],
        'items' => ['type' => 'repeater', 'label' => 'FAQ Items', 'fields' => [
            'question' => ['type' => 'text', 'label' => 'Question'],
            'answer' => ['type' => 'textarea', 'label' => 'Answer'],
        ]],
    ],
]);

registerBlock('gallery', [
    'label' => 'Image Gallery',
    'icon' => 'fas fa-images',
    'description' => 'Grid of images with lightbox.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading', 'required' => false],
        'images' => ['type' => 'repeater', 'label' => 'Images', 'fields' => [
            'url' => ['type' => 'image', 'label' => 'Image'],
            'alt' => ['type' => 'text', 'label' => 'Alt Text'],
            'caption' => ['type' => 'text', 'label' => 'Caption', 'required' => false],
        ]],
        'columns' => ['type' => 'select', 'label' => 'Columns', 'options' => ['2', '3', '4'], 'default' => '3'],
    ],
]);

registerBlock('video', [
    'label' => 'Video Embed',
    'icon' => 'fas fa-video',
    'description' => 'Embed a YouTube, Vimeo, or self-hosted video.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading', 'required' => false],
        'url' => ['type' => 'text', 'label' => 'Video URL (YouTube, Vimeo, or MP4)'],
        'aspect_ratio' => ['type' => 'select', 'label' => 'Aspect Ratio', 'options' => ['16:9', '4:3', '1:1'], 'default' => '16:9'],
    ],
]);

registerBlock('html', [
    'label' => 'HTML Embed',
    'icon' => 'fas fa-code',
    'description' => 'Custom HTML code block.',
    'fields' => [
        'content' => ['type' => 'code', 'label' => 'HTML Code'],
    ],
]);

registerBlock('spacer', [
    'label' => 'Spacer',
    'icon' => 'fas fa-arrows-alt-v',
    'description' => 'Add vertical spacing.',
    'fields' => [
        'height' => ['type' => 'select', 'label' => 'Height', 'options' => ['small', 'medium', 'large', 'xlarge'], 'default' => 'medium'],
    ],
]);

registerBlock('divider', [
    'label' => 'Divider',
    'icon' => 'fas fa-minus',
    'description' => 'Horizontal line separator.',
    'fields' => [
        'style' => ['type' => 'select', 'label' => 'Style', 'options' => ['solid', 'dashed', 'dotted'], 'default' => 'solid'],
        'width' => ['type' => 'select', 'label' => 'Width', 'options' => ['full', 'medium', 'short'], 'default' => 'full'],
    ],
]);

registerBlock('contact_form', [
    'label' => 'Contact Form',
    'icon' => 'fas fa-envelope',
    'description' => 'Embedded contact form.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading', 'default' => 'Get in Touch'],
        'show_phone' => ['type' => 'select', 'label' => 'Show Phone Field', 'options' => ['yes', 'no'], 'default' => 'yes'],
    ],
]);

registerBlock('map', [
    'label' => 'Map',
    'icon' => 'fas fa-map-marker-alt',
    'description' => 'Google Maps embed.',
    'fields' => [
        'heading' => ['type' => 'text', 'label' => 'Heading', 'required' => false],
        'embed_url' => ['type' => 'text', 'label' => 'Google Maps Embed URL', 'required' => false],
        'height' => ['type' => 'select', 'label' => 'Height', 'options' => ['small', 'medium', 'large'], 'default' => 'medium'],
    ],
]);

// Allow plugins to register custom block types
if (function_exists('do_action')) {
    do_action('register_blocks');
}
