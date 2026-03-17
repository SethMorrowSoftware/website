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
        ['company_phone', '(555) 742-2877', 'text'],
        ['company_email', 'hello@wuzabus.com', 'text'],
        ['company_address', 'Custom Build Shop · By Appointment', 'text'],
        ['contact_email', 'hello@wuzabus.com', 'text'],
        ['business_hours', "Monday - Friday: 8:00 AM - 5:00 PM\nSaturday: 9:00 AM - 1:00 PM\nSunday: Closed", 'text'],
        ['google_maps_embed', '', 'text'],
        ['footer_text', '&copy; ' . date('Y') . ' Wuzabus. All Rights Reserved.', 'html'],
        ['primary_color', '#1E3A8A', 'text'],
        ['secondary_color', '#F97316', 'text'],
        ['facebook_url', '', 'text'],
        ['instagram_url', '', 'text'],
        ['twitter_url', '', 'text'],
        ['swipesimple_link', '', 'text'],
        ['swipesimple_embed', '', 'html'],
        ['logo', '', 'image'],
        ['favicon', '', 'image'],
        ['tagline', 'Custom Bus, Truck & RV Conversions Built for Real Life', 'text'],
        ['about_text', 'Wuzabus transforms buses, trucks, and RVs into durable, livable spaces tailored to your lifestyle. From layout planning and cabinetry to off-grid power and climate systems, we build each rig for full-time living, travel, and adventure.', 'text'],
        ['service_area', 'Serving clients nationwide. We build in-shop and coordinate remote projects with clear timelines, milestone updates, and transparent communication.', 'text'],
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
        ['catalog_page_title', 'Build Options', 'text'],
        ['catalog_section_title', 'Explore Wuzabus Conversion Services', 'text'],
        ['order_inquiry_title', 'Project Inquiry', 'text'],
        ['cta_heading', 'Ready to Get Started?', 'text'],
        ['cta_subtext', '', 'text'],
        ['homepage_offerings_heading', 'What We Build', 'text'],
        ['homepage_offerings_subtext', 'Complete vehicle-to-home conversion services for bus, truck, and RV platforms', 'text'],
        ['homepage_featured_heading', 'Featured Conversion Packages', 'text'],
        ['homepage_featured_subtext', 'Popular Wuzabus options to get your rig road-ready and livable', 'text'],
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
        ['Home', 'home', '', 'Quality products and services for your needs', 1, 1, 0, 1, 'home'],
        ['About Us', 'about', '', 'Learn about our company and our commitment to quality', 1, 1, 1, 1, 'default'],
        ['Build Options', 'catalog', '', 'Explore conversion options for buses, trucks, and RVs', 1, 1, 2, 1, 'catalog'],
        ['Contact Us', 'contact', '', 'Talk with Wuzabus about your custom conversion project', 1, 1, 3, 1, 'contact'],
        ['Project Inquiry', 'order', '', 'Tell us about your rig and build goals', 1, 1, 4, 1, 'order'],
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
        ['Build Options', 'index.php?page=catalog', 2],
        ['Contact', 'index.php?page=contact', 3],
        ['Project Inquiry', 'index.php?page=order', 4],
    ];

    $stmt = $db->prepare('INSERT INTO navigation (label, url, sort_order, is_visible) VALUES (?, ?, ?, 1)');
    foreach ($navItems as $nav) {
        $stmt->execute($nav);
    }

    // Hero sections — store relative paths; url() is applied at render time
    $heroes = [
        ['home', 'Wuzabus', 'We turn buses, trucks, and RVs into beautiful, livable homes on wheels.', 'Start Your Build', 'index.php?page=order', '', '', 0.45],
        ['catalog', 'Build Options', 'From shell prep to full off-grid systems, explore our conversion services', 'Plan My Conversion', 'index.php?page=order', '', '', 0.5],
        ['contact', 'Contact Wuzabus', 'Let\'s design a conversion that fits your travel life', '', '', '', '', 0.5],
        ['order', 'Project Inquiry', 'Share your vehicle, timeline, and goals — we\'ll map the build', '', '', '', '', 0.5],
        ['about', 'About Wuzabus', 'Craftsmanship, systems expertise, and practical design for full-time mobile living', '', '', '', '', 0.5],
    ];

    $stmt = $db->prepare('INSERT INTO hero_sections (page_slug, title, subtitle, cta_text, cta_link, background_video, background_image, overlay_opacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($heroes as $hero) {
        $stmt->execute($hero);
    }

    // Sample product categories
    $categories = [
        ['Interior Conversions', 'interior-conversions', 'Kitchens, sleeping areas, storage, and finish carpentry designed for compact living.', '', 'fa-couch', 0],
        ['Electrical & Off-Grid', 'electrical-off-grid', 'Solar, battery banks, inverter systems, shore power integration, and smart energy layouts.', '', 'fa-bolt', 1],
        ['Exterior & Utility Upgrades', 'exterior-utility-upgrades', 'Roof decks, ventilation, cargo systems, and utility-focused modifications.', '', 'fa-tools', 2],
    ];

    $stmt = $db->prepare('INSERT INTO product_categories (name, slug, description, image, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    // Sample products
    $products = [
        // Products (category_id = 1)
        [1, 'Skoolie Full Interior Build', 'skoolie-full-interior-build', 'Complete interior conversion for bus platforms, including layout planning, cabinetry, living area, and sleeping configuration.', '', 'Custom Quote', '', '', 'Layout planning, Precision carpentry, Space-maximizing storage', '', 0],
        [1, 'Truck & RV Interior Renovation', 'truck-rv-interior-renovation', 'Upgrade existing RV or truck interiors with modern finishes, custom cabinetry, and livability-focused redesigns.', '', 'Custom Quote', '', '', 'Cabinet redesign, Surface upgrades, Functional living zones', '', 1],

        // Electrical & Off-Grid (category_id = 2)
        [2, 'Off-Grid Power System', 'off-grid-power-system', 'Professional installation of solar, charge controllers, inverters, breakers, and battery banks for reliable off-grid living.', '', 'Custom Quote', '', '', 'Victron-ready designs, Clean wiring bays, Expandable battery architecture', '', 0],
        [2, 'Climate & Ventilation Package', 'climate-ventilation-package', 'Install mini-split systems, roof ventilation, and airflow enhancements for comfort in varied climates.', '', 'Custom Quote', '', '', 'Mini-split integration, Roof fan installation, Balanced airflow planning', '', 1],

        // Exterior & Utility Upgrades (category_id = 3)
        [3, 'Roof Deck & Storage Buildout', 'roof-deck-storage-buildout', 'Custom roof deck, ladder, and modular storage solutions to increase utility and adventure readiness.', '', 'Custom Quote', '', '', 'Aluminum structures, Secure cargo strategy, Adventure-ready finish', '', 0],
        [3, 'Rear Utility Tray & Shop Storage', 'rear-utility-tray-shop-storage', 'Fabricated rear utility systems and tool storage integrations for builders and mobile professionals.', '', 'Custom Quote', '', '', 'Slide-out trays, Tool organization, Service-friendly access', '', 1],
    ];

    $stmt = $db->prepare('INSERT INTO products (category_id, name, slug, description, image, price, unit, specifications, features, price_note, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($products as $product) {
        $stmt->execute($product);
    }

    // Testimonials
    $testimonials = [
        ['J. Rivera', 'Wuzabus took our retired shuttle bus and turned it into a true tiny home. The layout, cabinetry, and finish quality exceeded our expectations.', 0],
        ['M. Thompson', 'The electrical build was incredibly clean and thoughtfully designed. We now run fully off-grid with confidence.', 1],
        ['A. Patel', 'Great communication, smart design ideas, and craftsmanship that holds up on the road. Highly recommended for serious conversion projects.', 2],
    ];

    $stmt = $db->prepare('INSERT INTO testimonials (customer_name, quote, sort_order) VALUES (?, ?, ?)');
    foreach ($testimonials as $testimonial) {
        $stmt->execute($testimonial);
    }
}
