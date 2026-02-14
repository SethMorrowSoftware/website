<?php
/**
 * Database seeder — populates initial data
 */

function seedDatabase(PDO $db): void {
    // Create admin user (password: HVSupply2024!)
    $db->exec("INSERT INTO users (username, password_hash) VALUES ('admin', '" . password_hash('HVSupply2024!', PASSWORD_DEFAULT) . "')");

    // Site settings
    $settings = [
        ['company_name', 'Hudson Valley Supply & Recycling LLC', 'text'],
        ['company_phone', '(845) 555-0123', 'text'],
        ['company_email', 'info@hudsonvalleysupply.com', 'text'],
        ['company_address', '123 Industrial Park Drive, Newburgh, NY 12550', 'text'],
        ['contact_email', 'info@hudsonvalleysupply.com', 'text'],
        ['business_hours', "Monday - Friday: 7:00 AM - 5:00 PM\nSaturday: 8:00 AM - 2:00 PM\nSunday: Closed", 'text'],
        ['google_maps_embed', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d95890.21!2d-74.05!3d41.50!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zNDHCsDMwJzAwLjAiTiA3NMKwMDMnMDAuMCJX!5e0!3m2!1sen!2sus!4v1234567890', 'text'],
        ['footer_text', '&copy; ' . date('Y') . ' Hudson Valley Supply & Recycling LLC. All Rights Reserved.', 'html'],
        ['primary_color', '#1B4D3E', 'text'],
        ['secondary_color', '#D4A843', 'text'],
        ['facebook_url', '', 'text'],
        ['instagram_url', '', 'text'],
        ['twitter_url', '', 'text'],
        ['swipesimple_link', '', 'text'],
        ['swipesimple_embed', '', 'html'],
        ['logo', '', 'image'],
        ['favicon', '', 'image'],
        ['tagline', 'Your Trusted Source for Containers, Materials & Hauling', 'text'],
        ['about_text', 'Hudson Valley Supply & Recycling LLC is a locally owned and operated business proudly serving the Hudson Valley region. With years of experience in the industry, we provide reliable roll-off container services, premium landscaping materials, and professional trucking services to residential and commercial customers alike.', 'text'],
        ['service_area', 'Proudly serving Orange County, Dutchess County, Ulster County, Rockland County, and surrounding areas throughout the Hudson Valley region of New York.', 'text'],
    ];

    $stmt = $db->prepare('INSERT INTO settings (key, value, type) VALUES (?, ?, ?)');
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }

    // Pages
    $pages = [
        ['Home', 'home', '', 'Hudson Valley Supply & Recycling - Roll Off Containers, Mulch, Stone, Sand & Trucking Services', 1, 1, 0, 1, 'home'],
        ['About Us', 'about', '', 'Learn about Hudson Valley Supply & Recycling LLC - your trusted local supplier', 1, 1, 1, 1, 'default'],
        ['Roll Off Containers', 'containers', '', 'Roll off container rentals in various sizes - 10, 15, 20, 30, 40 yard containers', 1, 1, 2, 1, 'containers'],
        ['Materials & Products', 'materials', '', 'Premium mulch, stone, topsoil, sand and bulk salt for delivery', 1, 1, 3, 1, 'products'],
        ['Trucking Services', 'trucking', '', 'Professional trucking and hauling services in the Hudson Valley', 1, 1, 4, 1, 'default'],
        ['Contact Us', 'contact', '', 'Contact Hudson Valley Supply & Recycling for quotes and information', 1, 1, 5, 1, 'contact'],
        ['Order Inquiry', 'order', '', 'Submit an order inquiry for containers, materials, or trucking services', 1, 1, 6, 1, 'order'],
        ['Payment', 'payment', '', 'Make a payment to Hudson Valley Supply & Recycling', 1, 1, 7, 0, 'payment'],
    ];

    $stmt = $db->prepare('INSERT INTO pages (title, slug, content, meta_description, is_system, is_published, sort_order, show_in_nav, template) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($pages as $page) {
        $stmt->execute($page);
    }

    // Navigation — store relative paths; url() is applied at render time
    $navItems = [
        ['Home', '/', 0],
        ['About Us', 'index.php?page=about', 1],
        ['Containers', 'index.php?page=containers', 2],
        ['Materials', 'index.php?page=materials', 3],
        ['Trucking', 'index.php?page=trucking', 4],
        ['Contact', 'index.php?page=contact', 5],
        ['Order Inquiry', 'index.php?page=order', 6],
    ];

    $stmt = $db->prepare('INSERT INTO navigation (label, url, sort_order, is_visible) VALUES (?, ?, ?, 1)');
    foreach ($navItems as $nav) {
        $stmt->execute($nav);
    }

    // Hero sections — store relative paths; url() is applied at render time
    $heroes = [
        ['home', 'Hudson Valley Supply & Recycling', 'Your Trusted Source for Containers, Materials & Hauling Services', 'Get a Free Quote', 'index.php?page=order', '', '', 0.5],
        ['containers', 'Roll Off Containers', 'Available in 10, 15, 20, 30 & 40 Yard Sizes', 'Request a Container', 'index.php?page=order', '', '', 0.5],
        ['materials', 'Materials & Products', 'Premium Mulch, Stone, Topsoil, Sand & Bulk Salt', 'Order Materials', 'index.php?page=order', '', '', 0.5],
        ['trucking', 'Trucking Services', 'Reliable Delivery & Hauling Throughout the Hudson Valley', 'Get a Quote', 'index.php?page=order', '', '', 0.5],
        ['contact', 'Contact Us', 'We\'re Here to Help — Reach Out Today', '', '', '', '', 0.5],
        ['order', 'Order Inquiry', 'Tell Us What You Need — We\'ll Get Back to You Fast', '', '', '', '', 0.5],
        ['about', 'About Us', 'Locally Owned & Operated — Serving the Hudson Valley', '', '', '', '', 0.5],
    ];

    $stmt = $db->prepare('INSERT INTO hero_sections (page_slug, title, subtitle, cta_text, cta_link, background_video, background_image, overlay_opacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($heroes as $hero) {
        $stmt->execute($hero);
    }

    // Product categories
    $categories = [
        ['Mulch', 'mulch', 'Premium mulch available in multiple varieties for landscaping, gardens, and playgrounds.', '', 0],
        ['Stone', 'stone', 'A wide variety of stone products for driveways, walkways, drainage, and decorative landscaping.', '', 1],
        ['Topsoil', 'topsoil', 'High-quality topsoil for gardens, lawns, and landscaping projects.', '', 2],
        ['Sand', 'sand', 'Various grades of sand for construction, masonry, and landscaping.', '', 3],
        ['Bulk Salt', 'bulk-salt', 'Road salt and treated salt for winter ice management and commercial applications.', '', 4],
    ];

    $stmt = $db->prepare('INSERT INTO product_categories (name, slug, description, image, sort_order) VALUES (?, ?, ?, ?, ?)');
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    // Products
    $products = [
        // Mulch (category_id = 1)
        [1, 'Natural Hardwood Mulch', 'natural-hardwood-mulch', 'Double-ground natural hardwood mulch. Excellent for flower beds, tree rings, and garden paths.', '', 'Call for Pricing', 'per yard', 0],
        [1, 'Black Dyed Mulch', 'black-dyed-mulch', 'Rich black dyed mulch that maintains its color throughout the season. Non-toxic, safe for plants and pets.', '', 'Call for Pricing', 'per yard', 1],
        [1, 'Brown Dyed Mulch', 'brown-dyed-mulch', 'Premium brown dyed mulch with long-lasting color. Perfect for a natural, polished landscape look.', '', 'Call for Pricing', 'per yard', 2],
        [1, 'Red Dyed Mulch', 'red-dyed-mulch', 'Vibrant red dyed mulch for bold landscape accents. Color-enhanced to last through the season.', '', 'Call for Pricing', 'per yard', 3],
        [1, 'Playground Mulch', 'playground-mulch', 'Certified playground-safe mulch meeting ASTM standards. Ideal for playgrounds and recreational areas.', '', 'Call for Pricing', 'per yard', 4],

        // Stone (category_id = 2)
        [2, 'Bluestone Gravel', 'bluestone-gravel', 'Crushed bluestone in various sizes. Great for driveways, walkways, and drainage applications.', '', 'Call for Pricing', 'per ton', 0],
        [2, 'River Rock', 'river-rock', 'Smooth, rounded river stones in assorted natural colors. Perfect for decorative landscaping and dry creek beds.', '', 'Call for Pricing', 'per ton', 1],
        [2, 'Pea Gravel', 'pea-gravel', 'Small, smooth stones ideal for walkways, patios, and drainage. Available in natural earth tones.', '', 'Call for Pricing', 'per ton', 2],
        [2, '#2 Crushed Stone', 'number-2-crushed-stone', 'Versatile crushed stone for driveways, foundations, and drainage projects. Approximately 1.5" - 2.5" in size.', '', 'Call for Pricing', 'per ton', 3],
        [2, 'Recycled Concrete', 'recycled-concrete', 'Eco-friendly crushed recycled concrete. Excellent for driveways, parking areas, and base material.', '', 'Call for Pricing', 'per ton', 4],

        // Topsoil (category_id = 3)
        [3, 'Screened Topsoil', 'screened-topsoil', 'Premium screened topsoil, free of rocks and debris. Ideal for lawns, gardens, and raised beds.', '', 'Call for Pricing', 'per yard', 0],
        [3, 'Unscreened Topsoil', 'unscreened-topsoil', 'Cost-effective unscreened topsoil for grading, filling, and large landscaping projects.', '', 'Call for Pricing', 'per yard', 1],
        [3, 'Garden Mix', 'garden-mix', 'Nutrient-rich blend of topsoil and compost. Perfect for vegetable gardens and flower beds.', '', 'Call for Pricing', 'per yard', 2],

        // Sand (category_id = 4)
        [4, 'Mason Sand', 'mason-sand', 'Fine, washed mason sand for masonry work, sandboxes, and paver installations.', '', 'Call for Pricing', 'per ton', 0],
        [4, 'Concrete Sand', 'concrete-sand', 'Coarse washed sand for concrete mixing, bedding, and construction applications.', '', 'Call for Pricing', 'per ton', 1],
        [4, 'Fill Sand', 'fill-sand', 'General-purpose fill sand for grading, backfilling, and leveling projects.', '', 'Call for Pricing', 'per ton', 2],

        // Bulk Salt (category_id = 5)
        [5, 'Road Salt (Rock Salt)', 'road-salt', 'Bulk road salt for de-icing roads, parking lots, and walkways. Available for pickup or delivery.', '', 'Call for Pricing', 'per ton', 0],
        [5, 'Treated Salt', 'treated-salt', 'Pre-treated salt blend for enhanced ice melting performance at lower temperatures.', '', 'Call for Pricing', 'per ton', 1],
    ];

    $stmt = $db->prepare('INSERT INTO products (category_id, name, slug, description, image, price, unit, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($products as $product) {
        $stmt->execute($product);
    }

    // Containers
    $containers = [
        ['10 Yard Container', '10', 'yard', '12\' x 8\' x 3.5\'', 'Our smallest roll-off container, perfect for small cleanouts, bathroom or kitchen renovations, and single-room projects.', 'Small renovations, garage cleanouts, yard debris', '', 'Call for Pricing', 'Pricing varies by material and location', 0],
        ['15 Yard Container', '15', 'yard', '16\' x 8\' x 4\'', 'A versatile mid-size option ideal for medium renovation projects, roofing jobs, and larger cleanouts.', 'Medium renovations, roofing projects, basement cleanouts', '', 'Call for Pricing', 'Pricing varies by material and location', 1],
        ['20 Yard Container', '20', 'yard', '22\' x 8\' x 4.5\'', 'Our most popular size. Great for large home renovation projects, construction debris, and major cleanouts.', 'Large renovations, construction projects, whole-house cleanouts', '', 'Call for Pricing', 'Pricing varies by material and location', 2],
        ['30 Yard Container', '30', 'yard', '22\' x 8\' x 6\'', 'Large capacity container for major construction and demolition projects. Handles significant volumes of debris.', 'Major construction, commercial projects, large demolitions', '', 'Call for Pricing', 'Pricing varies by material and location', 3],
        ['40 Yard Container', '40', 'yard', '22\' x 8\' x 8\'', 'Our largest container for the biggest jobs. Ideal for commercial construction, industrial cleanouts, and large-scale projects.', 'Commercial construction, industrial projects, large-scale demolitions', '', 'Call for Pricing', 'Pricing varies by material and location', 4],
    ];

    $stmt = $db->prepare('INSERT INTO containers (name, size, unit, dimensions, description, use_cases, image, price, price_note, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($containers as $container) {
        $stmt->execute($container);
    }

    // Testimonials
    $testimonials = [
        ['Mike R.', 'Hudson Valley Supply delivered our mulch right on time and the quality was outstanding. We\'ve been using them for our landscaping projects for three years now. Highly recommend!', 0],
        ['Sarah L.', 'We needed a 20-yard container for our home renovation. The process was seamless from ordering to pickup. Fair pricing and great customer service.', 1],
        ['Tom B.', 'Best stone prices in the Hudson Valley. We use them for all our commercial projects. Their trucking team is always professional and reliable.', 2],
        ['Jennifer K.', 'Called on a Monday morning and had a container in my driveway by afternoon. Can\'t beat that kind of service. Will definitely use again!', 3],
    ];

    $stmt = $db->prepare('INSERT INTO testimonials (customer_name, quote, sort_order) VALUES (?, ?, ?)');
    foreach ($testimonials as $testimonial) {
        $stmt->execute($testimonial);
    }
}
