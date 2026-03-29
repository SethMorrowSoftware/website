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
        ['company_name', 'TheWuzaBus', 'text'],
        ['company_phone', '(555) 000-0000', 'text'],
        ['company_email', 'info@thewuzabus.com', 'text'],
        ['company_address', '', 'text'],
        ['contact_email', 'info@thewuzabus.com', 'text'],
        ['business_hours', "Monday - Friday: 9:00 AM - 6:00 PM\nSaturday: By Appointment\nSunday: Closed", 'text'],
        ['google_maps_embed', '', 'text'],
        ['footer_text', '&copy; ' . date('Y') . ' TheWuzaBus. All Rights Reserved.', 'html'],
        ['primary_color', '#2B4C3F', 'text'],
        ['secondary_color', '#D4942A', 'text'],
        ['facebook_url', '', 'text'],
        ['instagram_url', '', 'text'],
        ['twitter_url', '', 'text'],
        ['swipesimple_link', '', 'text'],
        ['swipesimple_embed', '', 'html'],
        ['logo', '', 'image'],
        ['favicon', '', 'image'],
        ['tagline', 'Custom Bus Conversions & Off-Grid Living Solutions', 'text'],
        ['about_text', 'TheWuzaBus is a custom bus conversion company specializing in transforming buses into beautiful, fully-functional off-grid homes on wheels. With years of hands-on experience in vehicle conversions, solar power systems, and custom interior buildouts, we turn your dream of mobile living into reality. From full-scale skoolie builds to standalone electrical system installations, every project is crafted with care, quality materials, and a deep passion for the freedom of life on the road.', 'text'],
        ['service_area', 'We work with clients nationwide on custom bus conversion projects. Whether you are local or shipping your bus to our shop, we can bring your vision to life. Contact us to discuss your project.', 'text'],
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
        ['catalog_section_title', 'Browse Our Conversion Services', 'text'],
        ['order_inquiry_title', 'Get a Quote', 'text'],
        ['cta_heading', 'Ready to Start Your Build?', 'text'],
        ['cta_subtext', 'Whether you have a bus ready to convert or need help finding the perfect platform, we are here to make your off-grid dream a reality.', 'text'],
        ['homepage_offerings_heading', 'Our Conversion Services', 'text'],
        ['homepage_offerings_subtext', 'From full builds to individual system installations', 'text'],
        ['homepage_featured_heading', 'Featured Services & Packages', 'text'],
        ['homepage_featured_subtext', 'Popular conversion packages and standalone services', 'text'],
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
        ['enable_blog', '1', 'text'],
    ];

    $stmt = $db->prepare('INSERT INTO settings (`key`, `value`, `type`) VALUES (?, ?, ?)');
    foreach ($settings as $setting) {
        $stmt->execute($setting);
    }

    // Pages
    $pages = [
        ['Home', 'home', '', 'Custom bus conversions, off-grid solar systems, and skoolie builds by TheWuzaBus', 1, 1, 0, 1, 'home'],
        ['About Us', 'about', '', 'Learn about TheWuzaBus and our passion for custom bus conversions and off-grid living', 1, 1, 1, 1, 'default'],
        ['Our Services', 'catalog', '', 'Browse our bus conversion services, solar installations, and interior buildout packages', 1, 1, 2, 1, 'catalog'],
        ['Gallery', 'gallery', '', 'See our completed bus conversions, interior builds, and electrical system installations', 1, 1, 3, 1, 'default'],
        ['Contact Us', 'contact', '', 'Contact TheWuzaBus for a free consultation on your bus conversion project', 1, 1, 4, 1, 'contact'],
        ['Get a Quote', 'order', '', 'Request a quote for your custom bus conversion or off-grid system installation', 1, 1, 5, 1, 'order'],
        ['Payment', 'payment', '', 'Make a payment online', 1, 1, 6, 0, 'payment'],
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
        ['Contact', 'index.php?page=contact', 4],
        ['Get a Quote', 'index.php?page=order', 5],
    ];

    $stmt = $db->prepare('INSERT INTO navigation (label, url, sort_order, is_visible) VALUES (?, ?, ?, 1)');
    foreach ($navItems as $nav) {
        $stmt->execute($nav);
    }

    // Hero sections — store relative paths; url() is applied at render time
    $heroes = [
        ['home', 'TheWuzaBus', 'Custom Bus Conversions & Off-Grid Living Solutions', 'Start Your Build', 'index.php?page=order', '', 'wuzabus_photos/20200615_134615_fx.jpg', 0.6],
        ['catalog', 'Our Services', 'Full Bus Conversions, Solar Systems & Custom Interior Builds', 'Get a Quote', 'index.php?page=order', '', 'wuzabus_photos/20200508_215108.jpg', 0.5],
        ['contact', 'Contact Us', 'Let\'s Talk About Your Dream Build', '', '', '', '', 0.5],
        ['order', 'Get a Quote', 'Tell Us About Your Bus Conversion Project', '', '', '', '', 0.5],
        ['about', 'About TheWuzaBus', 'Turning Buses Into Homes Since 2020', '', '', '', 'wuzabus_photos/20200615_134615_fx.jpg', 0.5],
        ['gallery', 'Our Work', 'See Our Completed Builds & Projects', '', '', '', 'wuzabus_photos/20210508_162602.jpg', 0.5],
    ];

    $stmt = $db->prepare('INSERT INTO hero_sections (page_slug, title, subtitle, cta_text, cta_link, background_video, background_image, overlay_opacity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($heroes as $hero) {
        $stmt->execute($hero);
    }

    // Product categories — bus conversion services
    $categories = [
        ['Full Bus Conversions', 'full-conversions', 'Complete bus-to-home conversion builds, from bare shell to move-in ready. We handle everything from layout design to final finishes.', '', 'fa-bus', 0],
        ['Solar & Electrical', 'solar-electrical', 'Off-grid power system design and installation. Victron energy systems, lithium batteries, solar panels, and complete electrical wiring.', '', 'fa-solar-panel', 1],
        ['Interior Buildouts', 'interior-buildouts', 'Custom interior construction including kitchens, bedrooms, living areas, bathrooms, and storage solutions with premium materials.', '', 'fa-hammer', 2],
        ['Climate & Comfort', 'climate-comfort', 'Heating, cooling, and ventilation systems to keep you comfortable in any climate. Mini-splits, MaxxAir fans, insulation, and more.', '', 'fa-temperature-low', 3],
        ['Consultation & Design', 'consultation-design', 'Professional consultation services to help you plan your build, choose the right bus, and design your ideal layout.', '', 'fa-drafting-compass', 4],
    ];

    $stmt = $db->prepare('INSERT INTO product_categories (name, slug, description, image, icon, sort_order) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }

    // Products — bus conversion services and packages (mix of priced items and quote-based)
    $products = [
        // Full Bus Conversions (category_id = 1)
        [1, 'Complete Skoolie Conversion', 'complete-skoolie-conversion', 'Our flagship full-build package. We take your bus from a bare shell to a fully livable, off-grid home on wheels. Includes custom layout design, insulation, electrical system, plumbing, interior buildout, and finishing touches. Every build is unique to your lifestyle and travel plans.', 'wuzabus_photos/20210508_162602.jpg', '', '', 'Timeline: 3-6 months, Custom layout design, Full electrical & plumbing, Insulation & vapor barrier, Interior buildout & finishing', 'Custom layout design, Off-grid solar power system, Full kitchen & sleeping area, Climate control system, Premium wood finishes', 'Custom-priced by bus length and finish level', 0, 1],
        [1, 'Shuttle Bus Conversion', 'shuttle-bus-conversion', 'Perfect for solo travelers or couples. We convert smaller shuttle buses and short buses into compact but fully-featured mobile homes. Efficient use of space with all the essentials for life on the road.', 'wuzabus_photos/20200615_134615_fx.jpg', '$28,000', '', 'Timeline: 2-4 months, Compact & efficient layout, Full electrical system, Kitchen & sleeping area', 'Space-efficient design, Solar power system, Kitchenette with running water, Comfortable sleeping area, Storage solutions', 'Starting price — varies by bus size & options', 1, 0],
        [1, 'Partial Conversion Package', 'partial-conversion', 'Already started your build but need professional help finishing it? We offer partial conversion packages covering specific areas like electrical, plumbing, insulation, or interior finishing. Bring us your bus at any stage.', 'wuzabus_photos/20201231_153022.jpg', '', '', 'Timeline: 1-3 months, Pick the areas you need, Work with your existing build, Professional finishing', 'Flexible scope, Work around your existing build, Professional-grade results, Fill in the gaps, Save time on the hard stuff', 'Scope-based quote after inspection', 2, 1],

        // Solar & Electrical (category_id = 2)
        [2, 'Victron Energy System Package', 'victron-energy-system', 'Professional installation of a complete Victron energy system. Includes MultiPlus inverter/charger, SmartSolar MPPT charge controllers, battery monitor, and system programming. The gold standard for off-grid power.', 'wuzabus_photos/20241129_191724.jpg', '$8,500', '', 'Victron MultiPlus inverter/charger, SmartSolar MPPT controllers, Cerbo GX monitoring, Professional wiring & fusing', 'Remote monitoring via VRM, Shore power integration, Expandable system, Professional installation, System programming & setup', 'Parts & labor included', 0, 0],
        [2, 'Lithium Battery Bank Installation', 'lithium-battery-installation', 'SOK or equivalent lithium iron phosphate (LiFePO4) battery bank installation. Properly fused, wired, and integrated with your charging system. Built-in BMS for safety and longevity.', 'wuzabus_photos/20240515_151250.jpg', '$3,200', '', 'LiFePO4 chemistry, Built-in BMS, 2000-5000+ cycle life, Proper fusing & wiring', 'Long cycle life, Lightweight design, Safe LiFePO4 chemistry, Professional integration, Expandable capacity', '200Ah base — larger banks available', 1, 0],
        [2, 'Solar Panel Installation', 'solar-panel-installation', 'Rooftop solar panel installation with proper mounting, wiring, and charge controller integration. We size your solar array to match your power needs and travel habits.', 'wuzabus_photos/20250420_104130.jpg', '$2,800', '', 'Monocrystalline panels, Roof-mounted with proper sealing, MC4 connectors, MPPT charge controller integration', 'Custom array sizing, Professional roof mounting, Weatherproof installation, Optimized for your usage, Expandable design', '400W system — larger arrays available', 2, 0],
        [2, 'Complete Electrical Wiring', 'complete-electrical-wiring', 'Full 12V DC and 120V AC electrical system wiring. Includes breaker panels, outlets, lighting, switches, and shore power hookup. Built to code with proper protection.', 'wuzabus_photos/20250114_161911.jpg', '', '', '12V DC & 120V AC systems, Breaker panels, LED lighting throughout, Shore power connection', 'Code-compliant wiring, LED lighting throughout, USB & AC outlets, Shore power hookup, Proper circuit protection', 'Quoted by layout complexity and amp-hour target', 3, 1],
        [2, 'EG4 Inverter System', 'eg4-inverter-system', 'Budget-friendly but powerful off-grid inverter package featuring the EG4 6000XP. Great performance at a lower price point than Victron. Includes inverter, transfer switch, and breaker panel integration.', 'wuzabus_photos/20250114_161911.jpg', '$5,200', '', 'EG4 6000XP inverter, 6000W output, 120/240V split-phase, Built-in transfer switch', 'High power output, Split-phase capable, Budget-friendly, Built-in MPPT, Reliable performance', 'Parts & labor included', 4, 0],

        // Interior Buildouts (category_id = 3)
        [3, 'Custom Kitchen Build', 'custom-kitchen-build', 'Handcrafted kitchen with your choice of materials. Includes countertops, cabinetry, sink with running water, and appliance installation. We specialize in live-edge wood countertops and tile backsplashes.', 'wuzabus_photos/20210508_162602.jpg', '$7,500', '', 'Custom cabinetry, Countertop (live-edge wood available), Sink with water system, Appliance installation', 'Live-edge wood countertops, Custom tile backsplash, Soft-close cabinetry, Water pump & filtration, Propane or induction cooktop', 'Materials & labor included', 0, 0],
        [3, 'Bedroom & Living Area', 'bedroom-living-area', 'Comfortable sleeping and living quarters with smart storage solutions. Queen or king-sized beds, convertible seating, overhead storage, and beautiful wood paneling throughout.', 'wuzabus_photos/20210518_213104.jpg', '$5,500', '', 'Queen/King bed platform, Under-bed storage, Wood paneling, LED lighting', 'Comfortable mattress platform, Hidden storage compartments, Cedar or pine paneling, Ambient LED lighting, Convertible spaces', 'Materials & labor included', 1, 0],
        [3, 'Full Insulation & Vapor Barrier', 'insulation-vapor-barrier', 'Complete bus insulation package using closed-cell spray foam, Thinsulate, or rigid foam board. Includes proper vapor barrier installation to prevent condensation and mold. Critical for year-round comfort.', '', '$3,500', '', 'Closed-cell spray foam or Thinsulate, Vapor barrier, Floor-to-ceiling coverage, Wheel well insulation', 'Year-round comfort, Prevents condensation, Sound dampening, Energy efficient, Professional application', 'Price based on bus length', 2, 0],
        [3, 'Flooring Installation', 'flooring-installation', 'Professional flooring installation over properly prepared subfloor. Choose from luxury vinyl plank, hardwood, or tile. Includes subfloor leveling, moisture barrier, and trim work.', '', '$2,200', '', 'Luxury vinyl plank or hardwood, Subfloor preparation, Moisture barrier, Trim & transitions', 'Durable materials, Waterproof options, Easy to clean, Professional finish, Tongue & groove installation', 'Price based on bus length', 3, 0],

        // Climate & Comfort (category_id = 4)
        [4, 'Mini-Split AC/Heat Installation', 'mini-split-installation', 'Ductless mini-split air conditioning and heating system installation. Efficient climate control for year-round comfort in any environment. Runs on your off-grid power system.', 'wuzabus_photos/20210518_213104.jpg', '$2,800', '', 'Heating & cooling, Ductless design, Low power consumption, Remote control', 'Year-round comfort, Energy efficient, Quiet operation, Remote controlled, Works with solar power', 'Unit & installation included', 0, 0],
        [4, 'MaxxAir Fan & Ventilation', 'maxxair-fan-ventilation', 'MaxxAir fan installation with proper roof cutting, sealing, and wiring. Essential for air circulation, cooking ventilation, and temperature management. Rain-sensing auto-close available.', 'wuzabus_photos/20230519_100424.jpg', '$650', '', 'MaxxAir fan unit, Professional roof cut & seal, 12V wiring, Rain sensor option', 'Intake & exhaust modes, 10-speed control, Rain-sensing auto-close, Professional waterproof install, Low power draw', 'Fan & installation included', 1, 0],
        [4, 'Diesel Heater Installation', 'diesel-heater-install', 'Chinese diesel heater or Webasto/Espar installation for efficient cold-weather heating. Runs off your vehicle diesel tank with minimal power draw. Perfect for boondocking in cold climates.', '', '$1,200', '', '2kW or 5kW heater options, Fuel line tap to diesel tank, Thermostat control, Under-floor or wall mount', 'Burns minimal fuel, Very low power draw, Thermostat controlled, No propane needed, Works while driving', 'Heater & installation included', 2, 0],

        // Consultation & Design (category_id = 5)
        [5, 'Build Consultation (1 Hour)', 'build-consultation', 'One-on-one consultation to plan your bus conversion project. We cover layout design, system sizing, material selection, budget planning, and timeline. Perfect for DIY builders who want professional guidance.', '', '$150', 'hour', 'Layout design review, Electrical system sizing, Material recommendations, Budget planning', 'Personalized advice, Layout optimization, System sizing, Material sourcing help, Realistic timeline planning', 'In-person or video call', 0, 0],
        [5, 'Full Build Design Package', 'full-build-design', 'Comprehensive build planning package. Includes 3D layout design, complete electrical system diagram, materials list with sourcing, and a step-by-step build timeline. Everything you need to start your conversion with confidence.', '', '$750', '', '3D layout rendering, Electrical system diagram, Full materials list, Step-by-step timeline', '3D visualization, Detailed electrical plans, Sourcing guide, Build sequence planning, 2 revision rounds', '', 1, 0],
        [5, 'Bus Buying Guide & Inspection', 'bus-buying-guide', 'Not sure which bus to buy? We help you find and evaluate the right platform for your conversion. Includes guidance on engine types, body styles, condition assessment, and what to look for.', '', '$250', '', 'Bus type recommendations, Mechanical inspection checklist, Body condition assessment, Title & registration guidance', 'Find the right platform, Avoid costly mistakes, Engine & transmission advice, Rust & structural assessment, Negotiation tips', 'Includes written report', 2, 0],
    ];

    $stmt = $db->prepare('INSERT INTO products (category_id, name, slug, description, image, price, unit, specifications, features, price_note, sort_order, request_quote_only) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($products as $product) {
        $stmt->execute($product);
    }

    // Testimonials
    $testimonials = [
        ['Jake & Sarah M.', 'TheWuzaBus turned our old school bus into the most incredible home on wheels. The craftsmanship on the interior is stunning — the live-edge countertops and cedar paneling are works of art. We have been full-timing for 8 months now and everything is rock solid.', 0],
        ['Chris R.', 'I came in just for a solar system install and was blown away by the quality of work. My Victron setup runs flawlessly and I have more power than I know what to do with. Truly professional electrical work — clean, organized, and well-documented.', 1],
        ['Amanda & Tom K.', 'From the initial consultation to the final walkthrough, the whole experience was amazing. They really listened to what we wanted and designed a layout that is perfect for our family. The attention to detail in every corner of this build is incredible.', 2],
    ];

    $stmt = $db->prepare('INSERT INTO testimonials (customer_name, quote, sort_order) VALUES (?, ?, ?)');
    foreach ($testimonials as $testimonial) {
        $stmt->execute($testimonial);
    }
}
