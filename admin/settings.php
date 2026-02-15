<?php
/**
 * Admin — Site Settings
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $testTo = trim($_POST['test_email_to'] ?? '');
    if (filter_var($testTo, FILTER_VALIDATE_EMAIL)) {
        if (sendTestEmail($testTo)) {
            $_SESSION['flash_message'] = 'Test email sent successfully!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Failed to send test email. Check your SMTP settings.';
            $_SESSION['flash_type'] = 'error';
        }
    } else {
        $_SESSION['flash_message'] = 'Please enter a valid email address.';
        $_SESSION['flash_type'] = 'error';
    }
    header('Location: ' . url('admin/settings.php') . '#email');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $fields = [
        'company_name', 'company_phone', 'company_email', 'company_address',
        'contact_email', 'business_hours', 'tagline', 'about_text', 'service_area',
        'google_maps_embed', 'footer_text', 'primary_color', 'secondary_color',
        'facebook_url', 'instagram_url', 'twitter_url',
        'swipesimple_link', 'swipesimple_embed',
        // Store configuration
        'store_type', 'business_type',
        'catalog_page_title', 'catalog_section_title',
        'order_inquiry_title',
        'cta_heading', 'cta_subtext',
        'homepage_offerings_heading', 'homepage_offerings_subtext',
        'homepage_featured_heading', 'homepage_featured_subtext',
        // E-commerce settings
        'currency_code', 'currency_symbol', 'tax_rate',
        // Stripe
        'stripe_publishable_key', 'stripe_secret_key',
        // PayPal
        'paypal_client_id', 'paypal_secret',
        // Square
        'square_application_id', 'square_access_token', 'square_location_id',
        // BTCPay Server
        'btcpay_url', 'btcpay_api_key', 'btcpay_store_id', 'btcpay_webhook_secret',
        // SMTP
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_encryption', 'smtp_from_email', 'smtp_from_name',
        // Maintenance
        'maintenance_message',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            updateSetting($field, $_POST[$field]);
        }
    }

    // Handle checkbox toggles separately (unchecked = not sent)
    $checkboxes = [
        'enable_catalog', 'enable_cart', 'enable_order_inquiry', 'enable_contact_form',
        'enable_testimonials', 'enable_about_page',
        'show_phone_header', 'show_email_header', 'show_address', 'show_business_hours', 'show_map',
        'stripe_enabled', 'paypal_enabled', 'square_enabled', 'paypal_sandbox', 'square_sandbox', 'btcpay_enabled',
        'enable_maintenance', 'maintenance_mode',
    ];
    foreach ($checkboxes as $cb) {
        updateSetting($cb, isset($_POST[$cb]) ? '1' : '0');
    }

    // Handle logo upload
    if (!empty($_FILES['logo']['name'])) {
        $logoPath = handleUpload($_FILES['logo']);
        if ($logoPath) updateSetting('logo', $logoPath, 'image');
    }

    // Handle favicon upload
    if (!empty($_FILES['favicon']['name'])) {
        $faviconPath = handleUpload($_FILES['favicon']);
        if ($faviconPath) updateSetting('favicon', $faviconPath, 'image');
    }

    logAudit('settings_updated', 'settings');

    $_SESSION['admin_flash'] = ['type' => 'success', 'message' => 'Settings saved successfully!'];
    redirect('admin/settings.php');
}

$csrfToken = generateCSRFToken();
require_once __DIR__ . '/header.php';
?>

<div class="admin-page-header">
    <h1><i class="fas fa-cog"></i> Site Settings</h1>
    <p>Manage your website's global settings</p>
</div>

<form method="POST" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">

    <!-- Store Configuration -->
    <div class="form-section">
        <h3><i class="fas fa-sliders-h"></i> Store Configuration</h3>
        <p style="color:var(--color-gray-500);margin-bottom:var(--space-lg);">Configure what type of business this website represents and which features to enable.</p>

        <div class="form-row">
            <div class="form-group">
                <label>Store Type</label>
                <select name="store_type" class="form-control">
                    <?php $storeType = getSetting('store_type', 'products_and_services'); ?>
                    <option value="products_and_services" <?php echo $storeType === 'products_and_services' ? 'selected' : ''; ?>>Products & Services</option>
                    <option value="products_only" <?php echo $storeType === 'products_only' ? 'selected' : ''; ?>>Products Only</option>
                    <option value="services_only" <?php echo $storeType === 'services_only' ? 'selected' : ''; ?>>Services Only</option>
                    <option value="digital_only" <?php echo $storeType === 'digital_only' ? 'selected' : ''; ?>>Digital Products Only</option>
                    <option value="informational" <?php echo $storeType === 'informational' ? 'selected' : ''; ?>>Informational (No Store)</option>
                </select>
                <small class="form-help">Determines default layout and which sections appear on the site.</small>
            </div>
            <div class="form-group">
                <label>Business Type</label>
                <select name="business_type" class="form-control">
                    <?php $businessType = getSetting('business_type', 'local'); ?>
                    <option value="local" <?php echo $businessType === 'local' ? 'selected' : ''; ?>>Local Business</option>
                    <option value="online" <?php echo $businessType === 'online' ? 'selected' : ''; ?>>Online Business</option>
                    <option value="hybrid" <?php echo $businessType === 'hybrid' ? 'selected' : ''; ?>>Hybrid (Local + Online)</option>
                </select>
                <small class="form-help">Local shows address, map, hours. Online hides physical location details.</small>
            </div>
        </div>

        <h4 style="margin-top:var(--space-xl);margin-bottom:var(--space-md);">Feature Toggles</h4>
        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_catalog" value="1" <?php echo getSetting('enable_catalog', '1') === '1' ? 'checked' : ''; ?>>
                    Enable Catalog / Product Listings
                </label>
                <small class="form-help">Show the catalog page and product/service listings.</small>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_cart" value="1" <?php echo getSetting('enable_cart', '1') === '1' ? 'checked' : ''; ?>>
                    Enable Shopping Cart & Checkout
                </label>
                <small class="form-help">Allow customers to add items to cart and check out online.</small>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_order_inquiry" value="1" <?php echo getSetting('enable_order_inquiry', '1') === '1' ? 'checked' : ''; ?>>
                    Enable Order Inquiry Form
                </label>
                <small class="form-help">Show the multi-step order inquiry / quote request form.</small>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_contact_form" value="1" <?php echo getSetting('enable_contact_form', '1') === '1' ? 'checked' : ''; ?>>
                    Enable Contact Form
                </label>
                <small class="form-help">Show the contact us page and form.</small>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_testimonials" value="1" <?php echo getSetting('enable_testimonials', '1') === '1' ? 'checked' : ''; ?>>
                    Enable Testimonials
                </label>
                <small class="form-help">Show customer testimonials on the homepage.</small>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="enable_about_page" value="1" <?php echo getSetting('enable_about_page', '1') === '1' ? 'checked' : ''; ?>>
                    Enable About Page
                </label>
                <small class="form-help">Show the About Us page and link.</small>
            </div>
        </div>

        <h4 style="margin-top:var(--space-xl);margin-bottom:var(--space-md);">Visibility Options</h4>
        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="show_phone_header" value="1" <?php echo getSetting('show_phone_header', '1') === '1' ? 'checked' : ''; ?>>
                    Show Phone in Header
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="show_email_header" value="1" <?php echo getSetting('show_email_header', '1') === '1' ? 'checked' : ''; ?>>
                    Show Email in Header
                </label>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="show_address" value="1" <?php echo getSetting('show_address', '1') === '1' ? 'checked' : ''; ?>>
                    Show Physical Address
                </label>
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="show_business_hours" value="1" <?php echo getSetting('show_business_hours', '1') === '1' ? 'checked' : ''; ?>>
                    Show Business Hours
                </label>
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="show_map" value="1" <?php echo getSetting('show_map', '1') === '1' ? 'checked' : ''; ?>>
                Show Google Map
            </label>
        </div>

        <h4 style="margin-top:var(--space-xl);margin-bottom:var(--space-md);">Custom Labels</h4>
        <p style="color:var(--color-gray-500);margin-bottom:var(--space-md);">Customize the headings and labels used throughout the site.</p>
        <div class="form-row">
            <div class="form-group">
                <label>Catalog Page Title</label>
                <input type="text" name="catalog_page_title" class="form-control" value="<?php echo e(getSetting('catalog_page_title', 'Our Catalog')); ?>" placeholder="Our Catalog">
                <small class="form-help">e.g., "Our Menu", "Services", "Shop", "Products"</small>
            </div>
            <div class="form-group">
                <label>Catalog Section Heading</label>
                <input type="text" name="catalog_section_title" class="form-control" value="<?php echo e(getSetting('catalog_section_title', 'Browse Our Offerings')); ?>" placeholder="Browse Our Offerings">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Order Inquiry Page Title</label>
                <input type="text" name="order_inquiry_title" class="form-control" value="<?php echo e(getSetting('order_inquiry_title', 'Order Inquiry')); ?>" placeholder="Order Inquiry">
                <small class="form-help">e.g., "Request a Quote", "Book a Service", "Get an Estimate"</small>
            </div>
            <div class="form-group">
                <label>CTA Banner Heading</label>
                <input type="text" name="cta_heading" class="form-control" value="<?php echo e(getSetting('cta_heading', 'Ready to Get Started?')); ?>" placeholder="Ready to Get Started?">
            </div>
        </div>
        <div class="form-group">
            <label>CTA Banner Subtext</label>
            <input type="text" name="cta_subtext" class="form-control" value="<?php echo e(getSetting('cta_subtext')); ?>" placeholder="Give us a call or submit an order inquiry — we're here to help!">
            <small class="form-help">Leave blank for auto-generated text based on enabled features.</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Homepage Offerings Heading</label>
                <input type="text" name="homepage_offerings_heading" class="form-control" value="<?php echo e(getSetting('homepage_offerings_heading', 'What We Offer')); ?>" placeholder="What We Offer">
            </div>
            <div class="form-group">
                <label>Homepage Offerings Subtext</label>
                <input type="text" name="homepage_offerings_subtext" class="form-control" value="<?php echo e(getSetting('homepage_offerings_subtext', 'Explore our products and services')); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Homepage Featured Heading</label>
                <input type="text" name="homepage_featured_heading" class="form-control" value="<?php echo e(getSetting('homepage_featured_heading', 'Featured Products & Services')); ?>">
            </div>
            <div class="form-group">
                <label>Homepage Featured Subtext</label>
                <input type="text" name="homepage_featured_subtext" class="form-control" value="<?php echo e(getSetting('homepage_featured_subtext', 'A selection of what we have to offer')); ?>">
            </div>
        </div>
    </div>

    <!-- Company Info -->
    <div class="form-section">
        <h3><i class="fas fa-building"></i> Company Information</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Company Name</label>
                <input type="text" name="company_name" class="form-control" value="<?php echo e(getSetting('company_name')); ?>">
            </div>
            <div class="form-group">
                <label>Tagline</label>
                <input type="text" name="tagline" class="form-control" value="<?php echo e(getSetting('tagline')); ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="company_phone" class="form-control" value="<?php echo e(getSetting('company_phone')); ?>">
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="company_email" class="form-control" value="<?php echo e(getSetting('company_email')); ?>">
            </div>
        </div>
        <div class="form-group">
            <label>Address</label>
            <input type="text" name="company_address" class="form-control" value="<?php echo e(getSetting('company_address')); ?>">
        </div>
        <div class="form-group">
            <label>Contact Email (for form submissions)</label>
            <input type="email" name="contact_email" class="form-control" value="<?php echo e(getSetting('contact_email')); ?>">
        </div>
        <div class="form-group">
            <label>Business Hours</label>
            <textarea name="business_hours" class="form-control" rows="4"><?php echo e(getSetting('business_hours')); ?></textarea>
        </div>
    </div>

    <!-- About & Service Area -->
    <div class="form-section">
        <h3><i class="fas fa-info-circle"></i> About & Service Area</h3>
        <div class="form-group">
            <label>About Text (shown on About page)</label>
            <textarea name="about_text" class="form-control" rows="5"><?php echo e(getSetting('about_text')); ?></textarea>
        </div>
        <div class="form-group">
            <label>Service Area Description</label>
            <textarea name="service_area" class="form-control" rows="3"><?php echo e(getSetting('service_area')); ?></textarea>
        </div>
    </div>

    <!-- Branding -->
    <div class="form-section">
        <h3><i class="fas fa-palette"></i> Branding & Appearance</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Primary Color</label>
                <div class="color-input-wrap">
                    <input type="color" name="primary_color" value="<?php echo e(getSetting('primary_color', '#2563EB')); ?>" class="color-input">
                    <input type="text" value="<?php echo e(getSetting('primary_color', '#2563EB')); ?>" class="form-control color-text" readonly>
                </div>
            </div>
            <div class="form-group">
                <label>Secondary Color</label>
                <div class="color-input-wrap">
                    <input type="color" name="secondary_color" value="<?php echo e(getSetting('secondary_color', '#F59E0B')); ?>" class="color-input">
                    <input type="text" value="<?php echo e(getSetting('secondary_color', '#F59E0B')); ?>" class="form-control color-text" readonly>
                </div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Logo (upload image)</label>
                <?php $logo = getSetting('logo'); if ($logo): ?>
                    <div class="current-image"><img src="<?php echo e($logo); ?>" alt="Logo" style="max-height: 60px;"></div>
                <?php endif; ?>
                <input type="file" name="logo" class="form-control" accept="image/*">
            </div>
            <div class="form-group">
                <label>Favicon (upload image)</label>
                <?php $favicon = getSetting('favicon'); if ($favicon): ?>
                    <div class="current-image"><img src="<?php echo e($favicon); ?>" alt="Favicon" style="max-height: 32px;"></div>
                <?php endif; ?>
                <input type="file" name="favicon" class="form-control" accept="image/*">
            </div>
        </div>
    </div>

    <!-- Social Media -->
    <div class="form-section">
        <h3><i class="fas fa-share-alt"></i> Social Media</h3>
        <div class="form-row">
            <div class="form-group">
                <label><i class="fab fa-facebook"></i> Facebook URL</label>
                <input type="url" name="facebook_url" class="form-control" value="<?php echo e(getSetting('facebook_url')); ?>" placeholder="https://facebook.com/...">
            </div>
            <div class="form-group">
                <label><i class="fab fa-instagram"></i> Instagram URL</label>
                <input type="url" name="instagram_url" class="form-control" value="<?php echo e(getSetting('instagram_url')); ?>" placeholder="https://instagram.com/...">
            </div>
        </div>
        <div class="form-group" style="max-width: 50%;">
            <label><i class="fab fa-twitter"></i> Twitter URL</label>
            <input type="url" name="twitter_url" class="form-control" value="<?php echo e(getSetting('twitter_url')); ?>" placeholder="https://twitter.com/...">
        </div>
    </div>

    <!-- Google Maps -->
    <div class="form-section">
        <h3><i class="fas fa-map-marker-alt"></i> Google Maps</h3>
        <div class="form-group">
            <label>Google Maps Embed URL</label>
            <input type="url" name="google_maps_embed" class="form-control" value="<?php echo e(getSetting('google_maps_embed')); ?>" placeholder="https://www.google.com/maps/embed?pb=...">
            <small class="form-help">Go to Google Maps, click Share, then Embed, and paste the src URL here.</small>
        </div>
    </div>

    <!-- E-Commerce Settings -->
    <div class="form-section">
        <h3><i class="fas fa-store"></i> E-Commerce Settings</h3>
        <div class="form-row">
            <div class="form-group">
                <label>Currency Code</label>
                <input type="text" name="currency_code" class="form-control" value="<?php echo e(getSetting('currency_code', 'USD')); ?>" placeholder="USD" maxlength="3">
                <small class="form-help">ISO 4217 code (e.g., USD, EUR, GBP, CAD).</small>
            </div>
            <div class="form-group">
                <label>Currency Symbol</label>
                <input type="text" name="currency_symbol" class="form-control" value="<?php echo e(getSetting('currency_symbol', '$')); ?>" placeholder="$" maxlength="5">
            </div>
        </div>
        <div class="form-group" style="max-width: 50%;">
            <label>Tax Rate (%)</label>
            <input type="number" name="tax_rate" class="form-control" value="<?php echo e(getSetting('tax_rate', '0')); ?>" step="0.01" min="0" max="100" placeholder="0">
            <small class="form-help">Set to 0 to disable tax. Applied to all cart orders.</small>
        </div>
    </div>

    <!-- Stripe Payment -->
    <div class="form-section">
        <h3><i class="fab fa-stripe-s"></i> Stripe Payment Integration</h3>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="stripe_enabled" value="1" <?php echo getSetting('stripe_enabled') === '1' ? 'checked' : ''; ?>>
                Enable Stripe Payments
            </label>
            <small class="form-help">Accept credit and debit card payments via Stripe Checkout. Customers are redirected to Stripe's hosted payment page.</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Publishable Key</label>
                <input type="text" name="stripe_publishable_key" class="form-control" value="<?php echo e(getSetting('stripe_publishable_key')); ?>" placeholder="pk_live_...">
            </div>
            <div class="form-group">
                <label>Secret Key</label>
                <input type="password" name="stripe_secret_key" class="form-control" value="<?php echo e(getSetting('stripe_secret_key')); ?>" placeholder="sk_live_...">
            </div>
        </div>
        <small class="form-help">Get your API keys from the Stripe Dashboard. Use test keys (pk_test_ / sk_test_) for testing.</small>
    </div>

    <!-- PayPal Payment -->
    <div class="form-section">
        <h3><i class="fab fa-paypal"></i> PayPal Payment Integration</h3>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="paypal_enabled" value="1" <?php echo getSetting('paypal_enabled') === '1' ? 'checked' : ''; ?>>
                Enable PayPal Payments
            </label>
            <small class="form-help">Accept PayPal payments with PayPal Buttons. Customers pay without leaving your site.</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Client ID</label>
                <input type="text" name="paypal_client_id" class="form-control" value="<?php echo e(getSetting('paypal_client_id')); ?>" placeholder="PayPal Client ID">
            </div>
            <div class="form-group">
                <label>Secret</label>
                <input type="password" name="paypal_secret" class="form-control" value="<?php echo e(getSetting('paypal_secret')); ?>" placeholder="PayPal Secret">
            </div>
        </div>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="paypal_sandbox" value="1" <?php echo getSetting('paypal_sandbox', '1') === '1' ? 'checked' : ''; ?>>
                Sandbox Mode (for testing)
            </label>
            <small class="form-help">Get your credentials from the PayPal Developer Dashboard. Uncheck sandbox mode for live payments.</small>
        </div>
    </div>

    <!-- Square Payment -->
    <div class="form-section">
        <h3><i class="fas fa-square"></i> Square Payment Integration</h3>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="square_enabled" value="1" <?php echo getSetting('square_enabled') === '1' ? 'checked' : ''; ?>>
                Enable Square Payments
            </label>
            <small class="form-help">Accept credit card payments via Square Checkout. Customers are redirected to Square's hosted payment page.</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Application ID</label>
                <input type="text" name="square_application_id" class="form-control" value="<?php echo e(getSetting('square_application_id')); ?>" placeholder="sq0idp-...">
            </div>
            <div class="form-group">
                <label>Access Token</label>
                <input type="password" name="square_access_token" class="form-control" value="<?php echo e(getSetting('square_access_token')); ?>" placeholder="Access Token">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Location ID</label>
                <input type="text" name="square_location_id" class="form-control" value="<?php echo e(getSetting('square_location_id')); ?>" placeholder="Location ID">
            </div>
            <div class="form-group">
                <label class="checkbox-label" style="margin-top: var(--space-xl);">
                    <input type="checkbox" name="square_sandbox" value="1" <?php echo getSetting('square_sandbox', '1') === '1' ? 'checked' : ''; ?>>
                    Sandbox Mode (for testing)
                </label>
            </div>
        </div>
        <small class="form-help">Get your credentials from the Square Developer Dashboard.</small>
    </div>

    <!-- BTCPay Server -->
    <div class="form-section">
        <h3><i class="fab fa-bitcoin"></i> BTCPay Server (Bitcoin)</h3>
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="btcpay_enabled" value="1" <?php echo getSetting('btcpay_enabled') === '1' ? 'checked' : ''; ?>>
                Enable BTCPay Server Payments
            </label>
            <small class="form-help">Accept Bitcoin (on-chain and Lightning) payments via your self-hosted BTCPay Server instance. Fully self-custodial — you control your keys.</small>
        </div>
        <div class="form-group">
            <label>BTCPay Server URL</label>
            <input type="url" name="btcpay_url" class="form-control" value="<?php echo e(getSetting('btcpay_url')); ?>" placeholder="https://btcpay.yourdomain.com">
            <small class="form-help">The base URL of your BTCPay Server instance (no trailing slash).</small>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>API Key</label>
                <input type="password" name="btcpay_api_key" class="form-control" value="<?php echo e(getSetting('btcpay_api_key')); ?>" placeholder="BTCPay API Key">
                <small class="form-help">Generate in BTCPay > Account > API Keys. Needs <code>btcpay.store.cancreateinvoice</code> permission.</small>
            </div>
            <div class="form-group">
                <label>Store ID</label>
                <input type="text" name="btcpay_store_id" class="form-control" value="<?php echo e(getSetting('btcpay_store_id')); ?>" placeholder="Store ID">
                <small class="form-help">Found in BTCPay > Settings > General > Store ID.</small>
            </div>
        </div>
        <div class="form-group">
            <label>Webhook Secret (optional)</label>
            <input type="password" name="btcpay_webhook_secret" class="form-control" value="<?php echo e(getSetting('btcpay_webhook_secret')); ?>" placeholder="Webhook secret for signature verification">
            <small class="form-help">If you configure a webhook in BTCPay (recommended), paste the secret here to verify incoming notifications. Webhook URL: <code><?php echo e((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'yourdomain.com') . url('admin/api/btcpay-webhook.php')); ?></code></small>
        </div>
    </div>

    <!-- SwipeSimple (Legacy) -->
    <div class="form-section">
        <h3><i class="fas fa-credit-card"></i> SwipeSimple Payment Integration</h3>
        <div class="form-group">
            <label>SwipeSimple Payment Link</label>
            <input type="url" name="swipesimple_link" class="form-control" value="<?php echo e(getSetting('swipesimple_link')); ?>" placeholder="https://...">
            <small class="form-help">Paste your SwipeSimple payment link. Shown on the Payment page for manual payments.</small>
        </div>
        <div class="form-group">
            <label>SwipeSimple Embed Code (HTML)</label>
            <textarea name="swipesimple_embed" class="form-control" rows="4" placeholder="Paste embed code here..."><?php echo e(getSetting('swipesimple_embed')); ?></textarea>
            <small class="form-help">If SwipeSimple provides an embed/iframe code, paste it here instead of the link above.</small>
        </div>
    </div>

    <!-- Email Configuration -->
    <div class="admin-section" id="email">
        <h2><i class="fas fa-envelope"></i> Email Configuration</h2>
        <p class="section-desc">Configure SMTP for reliable email delivery. Leave blank to use PHP's built-in mail().</p>
        <div class="admin-card">
            <div class="form-group">
                <label for="smtp_host">SMTP Host</label>
                <input type="text" id="smtp_host" name="smtp_host" class="form-control" value="<?php echo e(getSetting('smtp_host')); ?>" placeholder="smtp.gmail.com">
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label for="smtp_port">SMTP Port</label>
                    <input type="number" id="smtp_port" name="smtp_port" class="form-control" value="<?php echo e(getSetting('smtp_port', '587')); ?>" placeholder="587">
                </div>
                <div class="form-group">
                    <label for="smtp_encryption">Encryption</label>
                    <select id="smtp_encryption" name="smtp_encryption" class="form-control">
                        <option value="tls" <?php echo getSetting('smtp_encryption', 'tls') === 'tls' ? 'selected' : ''; ?>>TLS (Recommended)</option>
                        <option value="ssl" <?php echo getSetting('smtp_encryption') === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="none" <?php echo getSetting('smtp_encryption') === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label for="smtp_username">SMTP Username</label>
                <input type="text" id="smtp_username" name="smtp_username" class="form-control" value="<?php echo e(getSetting('smtp_username')); ?>" placeholder="your@email.com">
            </div>
            <div class="form-group">
                <label for="smtp_password">SMTP Password</label>
                <input type="password" id="smtp_password" name="smtp_password" class="form-control" value="<?php echo e(getSetting('smtp_password')); ?>" placeholder="App password or SMTP password">
            </div>
            <div class="form-group">
                <label for="smtp_from_email">From Email</label>
                <input type="email" id="smtp_from_email" name="smtp_from_email" class="form-control" value="<?php echo e(getSetting('smtp_from_email')); ?>" placeholder="noreply@yourdomain.com">
            </div>
        </div>
    </div>

    <!-- Test Email -->
    <div class="admin-section">
        <h2><i class="fas fa-paper-plane"></i> Test Email</h2>
        <div class="admin-card">
            <form method="POST" style="display:flex;gap:var(--space-md);align-items:flex-end;">
                <input type="hidden" name="csrf_token" value="<?php echo e($csrfToken); ?>">
                <input type="hidden" name="test_email" value="1">
                <div class="form-group" style="flex:1;margin:0;">
                    <label for="test_email_to">Send test email to</label>
                    <input type="email" id="test_email_to" name="test_email_to" class="form-control" placeholder="test@example.com" required>
                </div>
                <button type="submit" class="btn-admin btn-primary"><i class="fas fa-paper-plane"></i> Send Test</button>
            </form>
        </div>
    </div>

    <!-- Maintenance Mode -->
    <div class="admin-section">
        <h2><i class="fas fa-tools"></i> Maintenance Mode</h2>
        <div class="admin-card">
            <div class="toggle-group">
                <label class="toggle-switch">
                    <input type="checkbox" name="maintenance_mode" value="1" <?php echo getSetting('maintenance_mode') === '1' ? 'checked' : ''; ?>>
                    <span class="toggle-slider"></span>
                </label>
                <div class="toggle-label">
                    <strong>Enable Maintenance Mode</strong>
                    <small>Public site will show a maintenance page. Admin panel remains accessible.</small>
                </div>
            </div>
            <div class="form-group" style="margin-top: var(--space-md);">
                <label for="maintenance_message">Maintenance Message</label>
                <textarea id="maintenance_message" name="maintenance_message" class="form-control" rows="3" placeholder="We are currently performing scheduled maintenance..."><?php echo e(getSetting('maintenance_message', 'We are currently performing scheduled maintenance. We will be back online shortly.')); ?></textarea>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="form-section">
        <h3><i class="fas fa-stream"></i> Footer</h3>
        <div class="form-group">
            <label>Footer Text (HTML allowed)</label>
            <input type="text" name="footer_text" class="form-control" value="<?php echo e(getSetting('footer_text')); ?>">
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn-admin btn-save"><i class="fas fa-save"></i> Save Settings</button>
    </div>
</form>

<?php require_once __DIR__ . '/footer.php'; ?>
