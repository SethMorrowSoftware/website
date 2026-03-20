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
        ['company_name', 'Wuzabus', 'text'],
        ['company_phone', '', 'text'],
        ['company_email', '', 'text'],
        ['company_address', '', 'text'],
        ['contact_email', '', 'text'],
        ['business_hours', "By Appointment", 'text'],
        ['google_maps_embed', '', 'text'],
        ['footer_text', '&copy; ' . date('Y') . ' Wuzabus Off-Grid Electrical. All Rights Reserved.', 'html'],
        ['primary_color', '#D97706', 'text'],
        ['secondary_color', '#1E3A5F', 'text'],
        ['facebook_url', '', 'text'],
        ['instagram_url', '', 'text'],
        ['twitter_url', '', 'text'],
        ['swipesimple_link', '', 'text'],
        ['swipesimple_embed', '', 'html'],
        ['logo', '', 'image'],
        ['favicon', '', 'image'],
        ['tagline', 'Clean Wiring. Proper Fusing. No Guesswork.', 'text'],
        ['about_text', 'Wuzabus designs and installs clean, reliable off-grid electrical systems for buses, vans, box trucks, and mobile stage builds. From lithium battery banks to full solar installs, inverters, shore power, and system upgrades — every build is done safely and correctly the first time. No shortcuts. No guesswork. Just stress-free power for life on the road.', 'text'],
        ['service_area', 'Serving off-grid builders nationwide. Mobile service available — message for details.', 'text'],
        // Store configuration
        ['store_type', 'products_and_services', 'text'],
        ['business_type', 'local', 'text'],
        ['enable_catalog', '1', 'text'],
        ['enable_cart', '1', 'text'],
        ['enable_order_inquiry', '1', 'text'],
        ['enable_contact_form', '1', 'text'],
        ['enable_testimonials', '1', 'text'],
        ['enable_about_page', '1', 'text'],
        ['show_phone_header', '1', 'text'],
        ['show_email_header', '1', 'text'],
        ['show_address', '1', 'text'],
        ['show_business_hours', '1', 'text'],
        ['show_map', '1', 'text'],
        ['catalog_page_title', 'Our Services', 'text'],
        ['catalog_section_title', 'Off-Grid Electrical Services', 'text'],
        ['order_inquiry_title', 'Get a Quote', 'text'],
        ['cta_heading', 'Ready to Power Your Build?', 'text'],
        ['cta_subtext', 'Message me to discuss your off-grid electrical system.', 'text'],
        ['homepage_offerings_heading', 'What We Build', 'text'],
        ['homepage_offerings_subtext', 'Off-grid electrical systems built right the first time', 'text'],
        ['homepage_featured_heading', 'Our Services', 'text'],
        ['homepage_featured_subtext', 'Everything you need to go off-grid with confidence', 'text'],
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
        // BTCPay Server
        ['btcpay_enabled', '0', 'text'],
        ['btcpay_url', '', 'text'],
        ['btcpay_api_key', '', 'text'],
        ['btcpay_store_id', '', 'text'],
        ['btcpay_webhook_secret', '', 'text'],
        // New features
        ['enable_customer_accounts', '1', 'text'],
        ['enable_search', '1', 'text'],
        ['enable_wishlists', '1', 'text'],
        ['enable_reviews', '0', 'text'],
    ];

    $stmt = $db->prepare('INSERT INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?)');
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }

    // Pages
    $pages = [
        ['Home', 'home', '', 'Off-grid electrical systems for buses, vans, and trucks — built right the first time', 1, 1, 0, 1, 'home'],
        ['About', 'about', '', 'Meet the builder behind Wuzabus off-grid electrical systems', 1, 1, 1, 1, 'default'],
        ['Services', 'catalog', '', 'Off-grid electrical services: solar, lithium batteries, inverters, and more', 1, 1, 2, 1, 'catalog'],
        ['Gallery', 'gallery', '', 'Photos of completed off-grid electrical builds and bus conversions', 1, 1, 3, 1, 'default'],
        ['Blog', 'blog', '', 'Tips, builds, and stories from the off-grid life', 1, 1, 4, 1, 'default'],
        ['Contact', 'contact', '', 'Get in touch about your off-grid electrical build', 1, 1, 5, 1, 'contact'],
        ['Get a Quote', 'order', '', 'Request a quote for your off-grid electrical system', 1, 1, 6, 1, 'order'],
    ];

    $stmt = $db->prepare('INSERT INTO pages (title, slug, content, meta_description, is_system, is_published, sort_order, show_in_nav, template) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($pages as $page) {
        $stmt->execute($page);
    }

    // Navigation — store relative paths; url() is applied at render time
    $navItems = [
        ['Home', '/', 0],
        ['About', 'index.php?page=about', 1],
        ['Services', 'index.php?page=catalog', 2],
        ['Gallery', 'index.php?page=gallery', 3],
        ['Blog', 'index.php?page=blog', 4],
        ['Contact', 'index.php?page=contact', 5],
        ['Get a Quote', 'index.php?page=order', 6],
    ];

    $stmt = $db->prepare('INSERT INTO navigation (label, url, sort_order, is_visible) VALUES (?, ?, ?, 1)');
    foreach ($navItems as $nav) {
        $stmt->execute($nav);
    }

    // Hero sections — store relative paths; url() is applied at render time
    $heroes = [
        ['home', 'Wuzabus', 'Your electrical system shouldn\'t be the reason your trip ends early.', 'Get a Quote', 'index.php?page=order', '', 'uploads/images/wuzabus/bus-exterior-solar.jpg', 0.5],
        ['catalog', 'Our Services', 'Off-Grid Electrical Systems Built Right the First Time', 'Request a Quote', 'index.php?page=order', '', 'uploads/images/wuzabus/full-system-sok-batteries.jpg', 0.5],
        ['contact', 'Get In Touch', 'Ready to Build? Let\'s Talk About Your Power System', '', '', '', 'uploads/images/wuzabus/desert-inverter-install.jpg', 0.5],
        ['order', 'Get a Quote', 'Tell Me About Your Build — I\'ll Design the Right System', '', '', '', 'uploads/images/wuzabus/battery-bank-victron.jpg', 0.5],
        ['about', 'About Wuzabus', 'Built From Experience. Powered by Passion.', '', '', '', 'uploads/images/wuzabus/bus-workshop-build.jpg', 0.5],
    ];

    $stmt = $db->prepare('INSERT INTO hero_sections (page_slug, title, subtitle, cta_text, cta_link, background_video, background_image, overlay_opacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($heroes as $hero) {
        $stmt->execute($hero);
    }

    // Service categories
    $categories = [
        ['Lithium Battery Systems', 'lithium-battery-systems', 'Custom lithium iron phosphate (LiFePO4) battery banks designed for your build. SOK, Battle Born, and more.', 'uploads/images/wuzabus/battery-bank-victron.jpg', 'fa-battery-full', 0],
        ['Solar Installs', 'solar-installs', 'Rooftop solar panel installation with proper MPPT charge controllers for maximum efficiency.', 'uploads/images/wuzabus/bus-exterior-solar.jpg', 'fa-solar-panel', 1],
        ['Inverters & Shore Power', 'inverters-shore-power', 'Victron MultiPlus, EG4, and other quality inverter/charger installations with shore power hookup.', 'uploads/images/wuzabus/victron-multiplus-rack.jpg', 'fa-plug', 2],
        ['System Upgrades & Fixes', 'system-upgrades-fixes', 'Upgrading outdated systems or fixing someone else\'s wiring mistakes. Clean, safe, and to code.', 'uploads/images/wuzabus/electrical-victron-panel.jpg', 'fa-wrench', 3],
        ['Full Build Electrical', 'full-build-electrical', 'Complete electrical system design and installation for bus conversions, van builds, and mobile stages.', 'uploads/images/wuzabus/full-system-sok-batteries.jpg', 'fa-bus', 4],
    ];

    $stmt = $db->prepare('INSERT INTO product_categories (name, slug, description, image, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    // Services as products
    $products = [
        // Lithium Battery Systems (category_id = 1)
        [1, 'LiFePO4 Battery Bank Install', 'lifepo4-battery-bank-install', 'Custom lithium iron phosphate battery bank sizing and installation. SOK, Battle Born, and other top brands. Properly fused, properly wired, built to last.', 'uploads/images/wuzabus/battery-bank-victron.jpg', 'Contact for Quote', '', 'LiFePO4 chemistry, 12V/24V/48V configurations, BMS integrated', 'Proper fusing and cable sizing, Clean terminal connections, Battery monitor integration, Thermal management', '', 0],
        [1, 'Battery System Upgrade', 'battery-system-upgrade', 'Upgrade your existing lead-acid or AGM setup to lithium. Includes new wiring, fusing, and charge profile configuration.', '', 'Contact for Quote', '', '', 'Remove old system safely, New lithium bank install, Rewire and re-fuse, Configure charging sources', '', 1],

        // Solar Installs (category_id = 2)
        [2, 'Rooftop Solar Install', 'rooftop-solar-install', 'Solar panel mounting, wiring, and MPPT charge controller installation. Designed for maximum output in real-world conditions.', 'uploads/images/wuzabus/bus-exterior-solar.jpg', 'Contact for Quote', '', 'Roof-mounted panels, MPPT charge controllers, MC4 connections', 'Victron SmartSolar MPPT, Proper wire routing, Weatherproof connections, Bluetooth monitoring', '', 0],

        // Inverters & Shore Power (category_id = 3)
        [3, 'Inverter/Charger Install', 'inverter-charger-install', 'Victron MultiPlus, EG4 6000XP, or equivalent inverter/charger installation. Shore power input, AC distribution panel, and proper grounding.', 'uploads/images/wuzabus/victron-multiplus-rack.jpg', 'Contact for Quote', '', 'Pure sine wave, 2000W-6000W options, 120V AC output', 'Inverter mounting and wiring, Shore power inlet, AC breaker panel, Ground fault protection', '', 0],
        [3, 'Shore Power Setup', 'shore-power-setup', 'Add or upgrade shore power capability. 30A or 50A inlet with proper transfer switching and surge protection.', '', 'Contact for Quote', '', '', '30A or 50A inlet, Automatic transfer switch, Surge protection, Proper grounding', '', 1],

        // System Upgrades & Fixes (category_id = 4)
        [4, 'Electrical System Audit', 'electrical-system-audit', 'Full inspection of your current electrical system. Identify safety issues, undersized wiring, improper fusing, and recommend fixes.', '', 'Contact for Quote', '', '', 'Complete system inspection, Safety assessment, Written report, Recommended upgrades', '', 0],
        [4, 'Wiring Repair & Cleanup', 'wiring-repair-cleanup', 'Fix someone else\'s mess. Re-wire, re-fuse, and clean up unsafe electrical work. No judgment — just safe, clean results.', 'uploads/images/wuzabus/electrical-victron-panel.jpg', 'Contact for Quote', '', '', 'Remove unsafe wiring, Proper fuse sizing, Clean wire routing, Labeled circuits', '', 1],

        // Full Build Electrical (category_id = 5)
        [5, 'Complete Off-Grid Electrical Package', 'complete-off-grid-electrical', 'Full electrical system from scratch: battery bank, solar, inverter, shore power, DC distribution, lighting, and everything in between. Designed for your specific build.', 'uploads/images/wuzabus/full-system-sok-batteries.jpg', 'Contact for Quote', '', '', 'Custom system design, Lithium battery bank, Solar and MPPT, Inverter/charger, DC fuse panel, AC distribution, Shore power, 12V lighting and outlets', '', 0],
        [5, 'Mobile Stage Electrical', 'mobile-stage-electrical', 'High-capacity electrical systems for mobile stages and event vehicles. Heavy-duty inverters, large battery banks, and generator integration.', '', 'Contact for Quote', '', '', 'High-capacity inverters, Large battery banks, Generator integration, Heavy-duty wiring', '', 1],
    ];

    $stmt = $db->prepare('INSERT INTO products (category_id, name, slug, description, image, price, unit, specifications, features, price_note, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($products as $product) {
        $stmt->execute($product);
    }

    // Testimonials
    $testimonials = [
        ['Jake M.', 'Had my entire bus wired from scratch — battery bank, solar, inverter, the works. Everything is clean, labeled, and just works. Best money I spent on my build.', 0],
        ['Sarah & Tom K.', 'We tried to DIY our van electrical and made a mess of it. Wuzabus came in, fixed everything, and upgraded us to lithium. Night and day difference. Zero stress camping now.', 1],
        ['Mike R.', 'Professional work, fair price, and actually explains what he\'s doing so you understand your own system. The wiring is a work of art. Highly recommend for any skoolie build.', 2],
    ];

    $stmt = $db->prepare('INSERT INTO testimonials (customer_name, quote, sort_order) VALUES (?, ?, ?)');
    foreach ($testimonials as $testimonial) {
        $stmt->execute($testimonial);
    }
}
