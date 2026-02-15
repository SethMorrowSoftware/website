<?php
/**
 * Admin — Site Settings
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $fields = [
        'company_name', 'company_phone', 'company_email', 'company_address',
        'contact_email', 'business_hours', 'tagline', 'about_text', 'service_area',
        'google_maps_embed', 'footer_text', 'primary_color', 'secondary_color',
        'facebook_url', 'instagram_url', 'twitter_url',
        'swipesimple_link', 'swipesimple_embed',
        // E-commerce settings
        'currency_code', 'currency_symbol', 'tax_rate',
        // Stripe
        'stripe_publishable_key', 'stripe_secret_key',
        // PayPal
        'paypal_client_id', 'paypal_secret',
        // Square
        'square_application_id', 'square_access_token', 'square_location_id',
    ];

    foreach ($fields as $field) {
        if (isset($_POST[$field])) {
            updateSetting($field, $_POST[$field]);
        }
    }

    // Handle checkbox toggles separately (unchecked = not sent)
    $checkboxes = ['stripe_enabled', 'paypal_enabled', 'square_enabled', 'paypal_sandbox', 'square_sandbox'];
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
