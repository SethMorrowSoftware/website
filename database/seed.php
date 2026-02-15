<?php
/**
 * Database seeder — populates initial data
 */

function seedDatabase(PDO $db): void {
    // Create admin user with a random password (written to ADMIN_CREDENTIALS.txt on first run)
    $randomPassword = bin2hex(random_bytes(8));
    $db->exec("INSERT INTO users (username, password_hash) VALUES ('admin', '" . password_hash($randomPassword, PASSWORD_DEFAULT) . "')");
    $credFile = dirname(__DIR__) . '/ADMIN_CREDENTIALS.txt';
    file_put_contents($credFile, "Admin Username: admin\nAdmin Password: $randomPassword\n\nChange this password immediately after first login at: /admin/profile.php\nThen delete this file.\n");
    @chmod($credFile, 0600);

    // Site settings
    $settings = [
        ['company_name', 'Your Business Name', 'text'],
        ['company_phone', '(555) 000-0000', 'text'],
        ['company_email', 'info@yourbusiness.com', 'text'],
        ['company_address', '123 Main Street, Anytown, ST 12345', 'text'],
        ['contact_email', 'info@yourbusiness.com', 'text'],
        ['business_hours', "Monday - Friday: 8:00 AM - 5:00 PM\nSaturday: 9:00 AM - 1:00 PM\nSunday: Closed", 'text'],
        ['google_maps_embed', '', 'text'],
        ['footer_text', '&copy; ' . date('Y') . ' Your Business Name. All Rights Reserved.', 'html'],
        ['primary_color', '#2563EB', 'text'],
        ['secondary_color', '#F59E0B', 'text'],
        ['facebook_url', '', 'text'],
        ['instagram_url', '', 'text'],
        ['twitter_url', '', 'text'],
        ['swipesimple_link', '', 'text'],
        ['swipesimple_embed', '', 'html'],
        ['logo', '', 'image'],
        ['favicon', '', 'image'],
        ['tagline', 'Quality Products & Services You Can Count On', 'text'],
        ['about_text', 'We are a locally owned and operated business proudly serving our community. With years of experience in the industry, we provide reliable products and professional services to residential and commercial customers alike.', 'text'],
        ['service_area', 'Proudly serving our local community and surrounding areas. Contact us to confirm service availability in your location.', 'text'],
        // E-commerce
        ['currency_code', 'USD', 'text'],
        ['currency_symbol', '$', 'text'],
        ['tax_rate', '0', 'text'],
        // Stripe
        ['stripe_enabled', '0', 'text'],
        ['stripe_publishable_key', '', 'text'],
        ['stripe_secret_key', '', 'text'],
        // PayPal
        ['paypal_enabled', '0', 'text'],
        ['paypal_client_id', '', 'text'],
        ['paypal_secret', '', 'text'],
        ['paypal_sandbox', '1', 'text'],
        // Square
        ['square_enabled', '0', 'text'],
        ['square_application_id', '', 'text'],
        ['square_access_token', '', 'text'],
        ['square_location_id', '', 'text'],
        ['square_sandbox', '1', 'text'],
    ];

    $stmt = $db->prepare('INSERT INTO settings (key, value, type) VALUES (?, ?, ?)');
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }

    // Pages
    $pages = [
        ['Home', 'home', '', 'Quality products and services for your needs', 1, 1, 0, 1, 'home'],
        ['About Us', 'about', '', 'Learn about our company and our commitment to quality', 1, 1, 1, 1, 'default'],
        ['Our Catalog', 'catalog', '', 'Browse our full selection of products and services', 1, 1, 2, 1, 'catalog'],
        ['Contact Us', 'contact', '', 'Contact us for quotes and information', 1, 1, 3, 1, 'contact'],
        ['Order Inquiry', 'order', '', 'Submit an order inquiry — we will get back to you promptly', 1, 1, 4, 1, 'order'],
        ['Payment', 'payment', '', 'Make a payment online', 1, 1, 5, 0, 'payment'],
    ];

    $stmt = $db->prepare('INSERT INTO pages (title, slug, content, meta_description, is_system, is_published, sort_order, show_in_nav, template) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($pages as $page) {
        $stmt->execute($page);
    }

    // Navigation — store relative paths; url() is applied at render time
    $navItems = [
        ['Home', '/', 0],
        ['About Us', 'index.php?page=about', 1],
        ['Catalog', 'index.php?page=catalog', 2],
        ['Contact', 'index.php?page=contact', 3],
        ['Order Inquiry', 'index.php?page=order', 4],
    ];

    $stmt = $db->prepare('INSERT INTO navigation (label, url, sort_order, is_visible) VALUES (?, ?, ?, 1)');
    foreach ($navItems as $nav) {
        $stmt->execute($nav);
    }

    // Hero sections — store relative paths; url() is applied at render time
    $heroes = [
        ['home', 'Your Business Name', 'Quality Products & Services You Can Count On', 'Get a Free Quote', 'index.php?page=order', '', '', 0.5],
        ['catalog', 'Our Catalog', 'Browse Our Full Selection of Products & Services', 'Request a Quote', 'index.php?page=order', '', '', 0.5],
        ['contact', 'Contact Us', 'We\'re Here to Help — Reach Out Today', '', '', '', '', 0.5],
        ['order', 'Order Inquiry', 'Tell Us What You Need — We\'ll Get Back to You Fast', '', '', '', '', 0.5],
        ['about', 'About Us', 'Locally Owned & Operated — Serving Our Community', '', '', '', '', 0.5],
    ];

    $stmt = $db->prepare('INSERT INTO hero_sections (page_slug, title, subtitle, cta_text, cta_link, background_video, background_image, overlay_opacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($heroes as $hero) {
        $stmt->execute($hero);
    }

    // Sample product categories
    $categories = [
        ['Products', 'products', 'Browse our selection of quality products available for purchase or delivery.', '', 'fa-box', 0],
        ['Services', 'services', 'Professional services tailored to meet your needs.', '', 'fa-concierge-bell', 1],
    ];

    $stmt = $db->prepare('INSERT INTO product_categories (name, slug, description, image, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    // Sample products
    $products = [
        // Products (category_id = 1)
        [1, 'Sample Product A', 'sample-product-a', 'A high-quality product for your needs. Edit this from the admin panel to match your actual offerings.', '', 'Call for Pricing', 'per unit', '', '', '', 0],
        [1, 'Sample Product B', 'sample-product-b', 'Another great product available for order. Update the name, description, and pricing in the admin panel.', '', 'Call for Pricing', 'per unit', '', '', '', 1],
        [1, 'Sample Product C', 'sample-product-c', 'We offer a wide range of products. Add as many as you need through the admin panel.', '', 'Call for Pricing', 'per unit', '', '', '', 2],

        // Services (category_id = 2)
        [2, 'Sample Service A', 'sample-service-a', 'A professional service we provide to our customers. Customize this from the admin panel.', '', 'Contact for Quote', '', '', 'Fast turnaround, Professional quality, Satisfaction guaranteed', '', 0],
        [2, 'Sample Service B', 'sample-service-b', 'Another service offering. Update the details to match your actual services.', '', 'Contact for Quote', '', '', 'Experienced team, Flexible scheduling, Competitive rates', '', 1],
    ];

    $stmt = $db->prepare('INSERT INTO products (category_id, name, slug, description, image, price, unit, specifications, features, price_note, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($products as $product) {
        $stmt->execute($product);
    }

    // Testimonials
    $testimonials = [
        ['Sample Customer A.', 'Outstanding service and quality! We have been customers for years and always receive prompt, professional attention. Highly recommend!', 0],
        ['Sample Customer B.', 'The process was seamless from start to finish. Fair pricing and great customer service. Will definitely use again!', 1],
        ['Sample Customer C.', 'Best in town. We use them for all our needs. The team is always professional and reliable.', 2],
    ];

    $stmt = $db->prepare('INSERT INTO testimonials (customer_name, quote, sort_order) VALUES (?, ?, ?)');
    foreach ($testimonials as $testimonial) {
        $stmt->execute($testimonial);
    }
}
