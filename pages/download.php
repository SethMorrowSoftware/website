<?php
/**
 * Download Page — Validates token and serves digital product file
 */

$token = $_GET['token'] ?? '';
$download = null;
$error = null;

if ($token) {
    $result = validateDownloadToken($token);
    if (!$result) {
        $error = 'Invalid download link. Please check your email for the correct link.';
    } elseif (isset($result['error'])) {
        $error = $result['error'];
    } else {
        $download = $result;

        // Check if file exists
        $filePath = BASE_PATH . '/' . $download['download_file'];
        if (!file_exists($filePath)) {
            $error = 'The download file is not available. Please contact support.';
            $download = null;
        }
    }
} else {
    $error = 'No download token provided.';
}

// If direct download is requested
if ($download && isset($_GET['start'])) {
    processDownload($token);
    // processDownload calls exit, so this line is only reached on error
    $error = 'Unable to serve the file. Please try again or contact support.';
    $download = null;
}
?>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="<?php echo url('/'); ?>">Home</a>
        <span>/</span>
        <span class="current">Download</span>
    </div>
</div>

<section class="section">
    <div class="container" style="max-width: 600px;">
        <?php if ($error): ?>
            <div class="download-status fade-in" style="text-align: center; padding: var(--space-4xl) 0;">
                <i class="fas fa-exclamation-circle" style="font-size: 4rem; color: var(--color-error); margin-bottom: var(--space-xl);"></i>
                <h2 style="margin-bottom: var(--space-md);">Download Unavailable</h2>
                <p style="color: var(--color-gray-600); margin-bottom: var(--space-xl);"><?php echo e($error); ?></p>
                <a href="<?php echo url('index.php?page=contact'); ?>" class="btn btn-primary">Contact Support</a>
            </div>
        <?php elseif ($download): ?>
            <div class="download-ready fade-in" style="text-align: center;">
                <div style="background: var(--color-light); border-radius: var(--radius-lg); padding: var(--space-3xl); margin-bottom: var(--space-xl);">
                    <i class="fas fa-file-download" style="font-size: 4rem; color: var(--color-primary); margin-bottom: var(--space-xl);"></i>
                    <h2 style="margin-bottom: var(--space-md);"><?php echo e($download['product_name']); ?></h2>

                    <?php
                    $filePath = BASE_PATH . '/' . $download['download_file'];
                    $fileSize = filesize($filePath);
                    $sizeDisplay = $fileSize > 1048576 ? number_format($fileSize / 1048576, 1) . ' MB' : number_format($fileSize / 1024, 0) . ' KB';
                    ?>
                    <p style="color: var(--color-gray-500); margin-bottom: var(--space-xl);">
                        File size: <?php echo $sizeDisplay; ?>
                    </p>

                    <a href="<?php echo url('index.php?page=download&token=' . e($token) . '&start=1'); ?>" class="btn btn-primary btn-lg">
                        <i class="fas fa-download"></i> Download Now
                    </a>

                    <?php if ($download['max_downloads'] > 0): ?>
                        <p style="font-size: var(--text-sm); color: var(--color-gray-500); margin-top: var(--space-lg);">
                            <i class="fas fa-info-circle"></i>
                            Downloads: <?php echo $download['download_count']; ?> / <?php echo $download['max_downloads']; ?>
                        </p>
                    <?php endif; ?>

                    <?php if ($download['expires_at']): ?>
                        <p style="font-size: var(--text-sm); color: var(--color-gray-500); margin-top: var(--space-sm);">
                            <i class="fas fa-clock"></i>
                            Expires: <?php echo formatDate($download['expires_at']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
