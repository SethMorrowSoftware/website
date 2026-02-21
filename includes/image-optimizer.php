<?php
/**
 * Image Optimization & CDN Integration Module
 *
 * Provides:
 * - Server-side image resizing (thumbnail, medium, large)
 * - WebP conversion with fallback
 * - CDN URL prefixing
 * - Responsive image srcset generation
 * - Lazy loading helpers
 */

/**
 * Image size definitions.
 */
function getImageSizes(): array {
    return [
        'thumbnail' => ['width' => 150, 'height' => 150, 'crop' => true],
        'medium'    => ['width' => 600, 'height' => 600, 'crop' => false],
        'large'     => ['width' => 1200, 'height' => 1200, 'crop' => false],
    ];
}

/**
 * Process an uploaded image: resize and create variants.
 *
 * @param string $originalPath Absolute path to the uploaded image
 * @return array Map of variant names to paths
 */
function processImage(string $originalPath): array {
    if (!extension_loaded('gd')) return [];
    if (!file_exists($originalPath)) return [];

    $info = @getimagesize($originalPath);
    if (!$info) return [];

    $variants = [];
    $sizes = getImageSizes();
    $dir = dirname($originalPath);
    $ext = strtolower(pathinfo($originalPath, PATHINFO_EXTENSION));
    $baseName = pathinfo($originalPath, PATHINFO_FILENAME);

    // Load source image
    $source = loadImage($originalPath, $info[2]);
    if (!$source) return [];

    $originalWidth = imagesx($source);
    $originalHeight = imagesy($source);

    // Downscale original if needed
    $maxW = (int)getSetting('image_max_width', '2000');
    $maxH = (int)getSetting('image_max_height', '2000');
    if ($originalWidth > $maxW || $originalHeight > $maxH) {
        $ratio = min($maxW / $originalWidth, $maxH / $originalHeight);
        $newW = (int)($originalWidth * $ratio);
        $newH = (int)($originalHeight * $ratio);
        $resized = imagecreatetruecolor($newW, $newH);
        preserveTransparency($resized, $ext);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newW, $newH, $originalWidth, $originalHeight);
        saveImage($resized, $originalPath, $ext);
        imagedestroy($source);
        $source = $resized;
        $originalWidth = $newW;
        $originalHeight = $newH;
    }

    foreach ($sizes as $sizeName => $config) {
        $targetW = $config['width'];
        $targetH = $config['height'];

        // Skip if original is smaller
        if ($originalWidth <= $targetW && $originalHeight <= $targetH) continue;

        if ($config['crop']) {
            // Center crop
            $ratio = max($targetW / $originalWidth, $targetH / $originalHeight);
            $tmpW = (int)($originalWidth * $ratio);
            $tmpH = (int)($originalHeight * $ratio);
            $srcX = (int)(($tmpW - $targetW) / 2 / $ratio);
            $srcY = (int)(($tmpH - $targetH) / 2 / $ratio);
            $srcW = (int)($targetW / $ratio);
            $srcH = (int)($targetH / $ratio);

            $variant = imagecreatetruecolor($targetW, $targetH);
            preserveTransparency($variant, $ext);
            imagecopyresampled($variant, $source, 0, 0, $srcX, $srcY, $targetW, $targetH, $srcW, $srcH);
        } else {
            // Fit within bounds
            $ratio = min($targetW / $originalWidth, $targetH / $originalHeight);
            $newW = (int)($originalWidth * $ratio);
            $newH = (int)($originalHeight * $ratio);

            $variant = imagecreatetruecolor($newW, $newH);
            preserveTransparency($variant, $ext);
            imagecopyresampled($variant, $source, 0, 0, 0, 0, $newW, $newH, $originalWidth, $originalHeight);
        }

        $variantPath = $dir . '/' . $baseName . '-' . $sizeName . '.' . $ext;
        saveImage($variant, $variantPath, $ext);
        imagedestroy($variant);

        $variants[$sizeName] = $variantPath;

        // WebP variant
        if ($ext !== 'webp' && function_exists('imagewebp')) {
            $webpPath = $dir . '/' . $baseName . '-' . $sizeName . '.webp';
            $webpVariant = loadImage($variantPath, $info[2] === IMAGETYPE_PNG ? IMAGETYPE_PNG : IMAGETYPE_JPEG);
            if ($webpVariant) {
                $quality = (int)getSetting('image_quality_webp', '80');
                imagewebp($webpVariant, $webpPath, $quality);
                imagedestroy($webpVariant);
                $variants[$sizeName . '_webp'] = $webpPath;
            }
        }

        // Record in database
        try {
            $db = getDB();
            $relPath = str_replace(BASE_PATH . '/', '', $variantPath);
            $origRelPath = str_replace(BASE_PATH . '/', '', $originalPath);
            $variantInfo = @getimagesize($variantPath);
            $variantWidth = $variantInfo ? $variantInfo[0] : 0;
            $variantHeight = $variantInfo ? $variantInfo[1] : 0;
            $db->prepare("INSERT IGNORE INTO image_variants (original_path, variant_size, variant_path, width, height, file_size, format) VALUES (?, ?, ?, ?, ?, ?, ?)")
                ->execute([$origRelPath, $sizeName, $relPath, $variantWidth, $variantHeight, filesize($variantPath), $ext]);
        } catch (Exception $e) {}
    }

    imagedestroy($source);
    return $variants;
}

/**
 * Load an image resource from file.
 */
function loadImage(string $path, int $type) {
    switch ($type) {
        case IMAGETYPE_JPEG: return @imagecreatefromjpeg($path);
        case IMAGETYPE_PNG: return @imagecreatefrompng($path);
        case IMAGETYPE_GIF: return @imagecreatefromgif($path);
        case IMAGETYPE_WEBP: return function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
        default: return false;
    }
}

/**
 * Save an image to file.
 */
function saveImage($image, string $path, string $ext): bool {
    $quality = (int)getSetting('image_quality_jpeg', '82');
    switch ($ext) {
        case 'jpg': case 'jpeg': return imagejpeg($image, $path, $quality);
        case 'png': return imagepng($image, $path, 9);
        case 'gif': return imagegif($image, $path);
        case 'webp': return function_exists('imagewebp') ? imagewebp($image, $path, (int)getSetting('image_quality_webp', '80')) : false;
        default: return false;
    }
}

/**
 * Preserve transparency for PNG/GIF.
 */
function preserveTransparency($image, string $ext): void {
    if (in_array($ext, ['png', 'gif'])) {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }
}

// ============================================================
// CDN Integration
// ============================================================

/**
 * Get the CDN-prefixed URL for an asset.
 */
function cdnUrl(string $path): string {
    $cdnBase = getSetting('cdn_base_url', '');
    if ($cdnBase) {
        return rtrim($cdnBase, '/') . '/' . ltrim($path, '/');
    }
    return $path;
}

/**
 * Get image variant URL by size name.
 */
function getImageVariant(string $originalUrl, string $size = 'medium'): string {
    $ext = strtolower(pathinfo($originalUrl, PATHINFO_EXTENSION));
    $baseName = pathinfo($originalUrl, PATHINFO_FILENAME);
    $dir = dirname($originalUrl);
    $variantUrl = $dir . '/' . $baseName . '-' . $size . '.' . $ext;

    // Check if variant exists (from database)
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT variant_path FROM image_variants WHERE original_path = ? AND variant_size = ?");
        $stmt->execute([ltrim($originalUrl, '/'), $size]);
        $path = $stmt->fetchColumn();
        if ($path) return cdnUrl($path);
    } catch (Exception $e) {}

    return cdnUrl($originalUrl);
}

// ============================================================
// Responsive Images
// ============================================================

/**
 * Generate srcset attribute for responsive images.
 */
function getImageSrcset(string $originalUrl): string {
    $sizes = getImageSizes();
    $srcset = [];

    foreach ($sizes as $sizeName => $config) {
        $variantUrl = getImageVariant($originalUrl, $sizeName);
        if ($variantUrl !== cdnUrl($originalUrl)) {
            $srcset[] = $variantUrl . ' ' . $config['width'] . 'w';
        }
    }

    $srcset[] = cdnUrl($originalUrl) . ' 2000w'; // Original as largest
    return implode(', ', $srcset);
}

/**
 * Generate an optimized <img> tag.
 */
function optimizedImg(string $src, string $alt, string $class = '', string $sizes = '100vw'): string {
    $lazy = getSetting('image_lazy_loading', '1') === '1';
    $srcset = getImageSrcset($src);
    $cdnSrc = cdnUrl($src);

    $html = '<img src="' . e($cdnSrc) . '"';
    $html .= ' alt="' . e($alt) . '"';
    if ($srcset) $html .= ' srcset="' . e($srcset) . '"';
    $html .= ' sizes="' . e($sizes) . '"';
    if ($class) $html .= ' class="' . e($class) . '"';
    if ($lazy) $html .= ' loading="lazy"';
    $html .= '>';

    return $html;
}
