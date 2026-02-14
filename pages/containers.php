<?php
/**
 * Roll Off Containers Page
 */

$hero = getHero('containers');
$containers = getContainers();
?>

<!-- Hero -->
<section class="hero">
    <?php if ($hero && $hero['background_image']): ?>
        <div class="hero-image" style="background-image: url('<?php echo e($hero['background_image']); ?>');"></div>
    <?php endif; ?>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <h1><?php echo e($hero['title'] ?? 'Roll Off Containers'); ?></h1>
        <p><?php echo e($hero['subtitle'] ?? 'Available in 10, 15, 20, 30 & 40 Yard Sizes'); ?></p>
        <?php if ($hero && $hero['cta_text']): ?>
            <a href="<?php echo e($hero['cta_link']); ?>" class="btn btn-primary btn-lg"><?php echo e($hero['cta_text']); ?></a>
        <?php endif; ?>
    </div>
</section>

<!-- Breadcrumb -->
<div class="container">
    <div class="breadcrumb">
        <a href="/">Home</a>
        <span>/</span>
        <span class="current">Roll Off Containers</span>
    </div>
</div>

<!-- Container Sizes -->
<section class="section">
    <div class="container">
        <div class="section-header fade-in">
            <h2>Choose Your Container Size</h2>
            <p>We offer a full range of roll-off containers to handle any project, big or small.</p>
        </div>

        <div class="grid grid-3">
            <?php foreach ($containers as $container): ?>
                <div class="container-card fade-in">
                    <div class="card-image">
                        <?php if ($container['image']): ?>
                            <img src="<?php echo e($container['image']); ?>" alt="<?php echo e($container['name']); ?>">
                        <?php else: ?>
                            <div class="size-badge"><?php echo e($container['size']); ?> <span>YD</span></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <h3><?php echo e($container['name']); ?></h3>
                        <?php if ($container['dimensions']): ?>
                            <span class="dimensions"><i class="fas fa-ruler-combined"></i> <?php echo e($container['dimensions']); ?></span>
                        <?php endif; ?>
                        <p><?php echo e($container['description']); ?></p>
                        <?php if ($container['use_cases']): ?>
                            <h5 style="margin-bottom: var(--space-sm); font-size: var(--text-sm); text-transform: uppercase; color: var(--color-gray-500);">Ideal For:</h5>
                            <ul class="use-cases">
                                <?php foreach (explode(',', $container['use_cases']) as $useCase): ?>
                                    <li><?php echo e(trim($useCase)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div>
                            <span class="card-price"><?php echo e($container['price'] ?: 'Call for Pricing'); ?></span>
                            <?php if ($container['price_note']): ?>
                                <br><span class="card-unit"><?php echo e($container['price_note']); ?></span>
                            <?php endif; ?>
                        </div>
                        <a href="/index.php?page=order" class="btn btn-sm btn-primary">Order Now</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- How It Works -->
<section class="section section-light">
    <div class="container">
        <div class="section-header fade-in">
            <h2>How It Works</h2>
            <p>Getting a roll-off container is easy</p>
        </div>

        <div class="steps fade-in">
            <div class="step">
                <h4>Choose Your Size</h4>
                <p>Select the container size that best fits your project needs.</p>
            </div>
            <div class="step">
                <h4>Schedule Delivery</h4>
                <p>Pick your preferred delivery date and drop-off location.</p>
            </div>
            <div class="step">
                <h4>Fill It Up</h4>
                <p>Take your time loading the container with your debris and waste.</p>
            </div>
            <div class="step">
                <h4>We Pick It Up</h4>
                <p>When you're done, give us a call and we'll haul it away!</p>
            </div>
        </div>
    </div>
</section>

<!-- FAQ / Info -->
<section class="section">
    <div class="container">
        <div style="max-width: 800px; margin: 0 auto;">
            <div class="section-header fade-in">
                <h2>Container Rental Info</h2>
            </div>
            <div class="fade-in" style="line-height: var(--leading-relaxed); color: var(--color-gray-600);">
                <h4 style="margin-bottom: var(--space-md); color: var(--color-dark);">What Can Go in a Roll Off Container?</h4>
                <p>Our roll-off containers can handle most types of construction and demolition debris, household junk, yard waste, roofing materials, concrete, and more. Please call us for specific questions about accepted materials.</p>

                <h4 style="margin: var(--space-xl) 0 var(--space-md); color: var(--color-dark);">Prohibited Items</h4>
                <p>Hazardous materials, chemicals, paint, tires, batteries, and appliances with refrigerants cannot be placed in roll-off containers. Contact us with any questions about prohibited materials.</p>

                <h4 style="margin: var(--space-xl) 0 var(--space-md); color: var(--color-dark);">Rental Duration</h4>
                <p>Standard rental periods are available with flexible terms. Extended rental periods can be arranged. Contact us for details and pricing for your specific project timeline.</p>

                <div class="text-center mt-3">
                    <a href="/index.php?page=order" class="btn btn-primary btn-lg">Request a Container</a>
                </div>
            </div>
        </div>
    </div>
</section>
