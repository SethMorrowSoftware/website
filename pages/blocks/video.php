<?php
/**
 * Block: Video Embed
 * YouTube, Vimeo, or self-hosted video.
 */
$videoUrl = $url ?? '';
$allowedRatios = ['16:9', '4:3', '1:1', '21:9'];
$ratio = in_array($aspect_ratio ?? '16:9', $allowedRatios) ? ($aspect_ratio ?? '16:9') : '16:9';
$ratioClass = str_replace(':', '-', $ratio);

if (!$videoUrl) return;

// Parse YouTube/Vimeo URLs into embed format
$embedUrl = '';
if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $m)) {
    $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $m[1];
} elseif (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $m)) {
    $embedUrl = 'https://player.vimeo.com/video/' . $m[1];
}
?>
<section class="block-video">
    <div class="container">
        <?php if (!empty($heading)): ?>
            <h2 class="block-heading"><?php echo e($heading); ?></h2>
        <?php endif; ?>
        <div class="block-video-wrapper block-video--<?php echo $ratioClass; ?>">
            <?php if ($embedUrl): ?>
                <iframe src="<?php echo e($embedUrl); ?>" frameborder="0" allowfullscreen loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>
            <?php else: ?>
                <video controls preload="metadata">
                    <source src="<?php echo e($videoUrl); ?>">
                </video>
            <?php endif; ?>
        </div>
    </div>
</section>
