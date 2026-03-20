<?php
/**
 * Migration 014: Seed blog with Wuzabus off-grid electrical content.
 * Creates categories, tags, and sample blog posts.
 */

return function (PDO $db): void {
    // Only seed if no blog posts exist yet
    $count = (int)$db->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
    if ($count > 0) return;

    // Get admin user ID (for author)
    $authorId = $db->query('SELECT id FROM users LIMIT 1')->fetchColumn() ?: null;

    $now = date('Y-m-d H:i:s');

    // ── Blog Categories ──────────────────────────────────────
    $categories = [
        ['Build Tips',     'build-tips',     'Practical advice for off-grid electrical builds.',                    0],
        ['Project Updates', 'project-updates', 'Recent builds, installs, and project showcases.',                   1],
        ['Off-Grid Life',  'off-grid-life',  'Stories, lessons, and real talk from life on the road.',              2],
        ['Gear Reviews',   'gear-reviews',   'Honest reviews of electrical components and off-grid gear.',          3],
    ];

    $catStmt = $db->prepare(
        'INSERT INTO blog_categories (name, slug, description, sort_order, is_visible) VALUES (?, ?, ?, ?, 1)'
    );
    $catIds = [];
    foreach ($categories as $cat) {
        $catStmt->execute($cat);
        $catIds[$cat[1]] = (int)$db->lastInsertId();
    }

    // ── Blog Tags ────────────────────────────────────────────
    $tags = [
        'Solar'         => 'solar',
        'Lithium'       => 'lithium',
        'Victron'       => 'victron',
        'Skoolie'       => 'skoolie',
        'Van Life'      => 'van-life',
        'Wiring'        => 'wiring',
        'Inverter'      => 'inverter',
        'Off-Grid'      => 'off-grid',
        'Battery'       => 'battery',
        'Safety'        => 'safety',
        'Bus Build'     => 'bus-build',
        'SOK'           => 'sok',
    ];

    $tagStmt = $db->prepare('INSERT INTO blog_tags (name, slug) VALUES (?, ?)');
    $tagIds = [];
    foreach ($tags as $name => $slug) {
        $tagStmt->execute([$name, $slug]);
        $tagIds[$slug] = (int)$db->lastInsertId();
    }

    // ── Blog Posts ───────────────────────────────────────────
    $posts = [
        [
            'title'   => 'Why Your Off-Grid Electrical System Should Be Built by Someone Who Lives This Life',
            'slug'    => 'why-off-grid-electrical-should-be-built-by-someone-who-lives-it',
            'excerpt' => 'Living the off-grid life means your electrical system better be built right. Out here there\'s no second chance if your power setup fails.',
            'content' => '<p>I\'ve seen it too many times. Someone buys a bus, watches a few YouTube videos, and tries to wire their own electrical system. Sometimes it works. A lot of times it doesn\'t. And when it fails, you\'re stuck in the middle of nowhere with no power, no lights, and a very expensive problem.</p>

<h2>The Difference Between "Working" and "Built Right"</h2>
<p>There\'s a huge gap between a system that technically turns on and one that\'s actually built to handle real off-grid use. Here\'s what I mean:</p>
<ul>
<li><strong>Wire gauge matters.</strong> Undersized wire creates heat, which creates fire risk. Every circuit in my builds uses properly rated wire for the actual current draw.</li>
<li><strong>Fusing isn\'t optional.</strong> Every positive wire gets a fuse. Period. The fuse protects the wire, not the device. If you don\'t understand that, you need someone who does.</li>
<li><strong>Connections need to be solid.</strong> Loose terminals, cheap crimp connectors, and electrical tape don\'t cut it on a vehicle that vibrates down the highway at 60mph.</li>
</ul>

<h2>Built From Experience</h2>
<p>I don\'t just install these systems — I live with them. My own rig runs on the same components I install for clients. When I\'m camped in the desert in July or parked in the mountains in December, my electrical system has to work. That experience shapes every build I do.</p>

<p>If you\'re planning a build or mid-build and want it done right, <a href="index.php?page=contact">reach out</a>. Let\'s talk about your power needs.</p>',
            'category' => 'off-grid-life',
            'status'   => 'published',
            'featured' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'meta'     => 'Why your off-grid electrical system should be installed by someone with real-world experience living off-grid.',
            'tags'     => ['off-grid', 'wiring', 'safety'],
            'image'    => 'uploads/images/wuzabus/desert-inverter-install.jpg',
        ],
        [
            'title'   => 'Victron vs EG4: Which Inverter System Is Right for Your Build?',
            'slug'    => 'victron-vs-eg4-which-inverter-for-your-build',
            'excerpt' => 'Two popular inverter choices for off-grid builds. Here\'s an honest comparison based on real installs.',
            'content' => '<p>I get asked this question constantly: "Should I go with Victron or EG4?" The answer, like most things in off-grid builds, is: it depends on your build.</p>

<h2>Victron MultiPlus</h2>
<p>The Victron MultiPlus is the gold standard for mobile off-grid inverter/chargers. Here\'s why I use it so often:</p>
<ul>
<li><strong>Reliability.</strong> Victron has been in the game for decades. These units just work.</li>
<li><strong>Ecosystem.</strong> The SmartSolar MPPTs, Lynx Distributor, and Cerbo GX monitoring all talk to each other beautifully.</li>
<li><strong>Support.</strong> Victron\'s community and support network is unmatched.</li>
<li><strong>Size.</strong> Compact enough to fit in tight bus and van builds.</li>
</ul>

<h2>EG4 6000XP</h2>
<p>The EG4 6000XP has become a popular choice, especially for larger builds:</p>
<ul>
<li><strong>Power output.</strong> 6000W continuous is serious power for the price point.</li>
<li><strong>Value.</strong> You get a lot of inverter for the money.</li>
<li><strong>48V native.</strong> Great if you\'re building a 48V system from the start.</li>
</ul>

<h2>My Recommendation</h2>
<p>For most bus and van builds, I lean toward Victron. The ecosystem integration and compact size make it ideal for mobile use. For larger stationary or semi-stationary builds where budget matters and you have the space, the EG4 is hard to beat.</p>

<p>Not sure which is right for your build? <a href="index.php?page=order">Get a quote</a> and I\'ll help you figure it out.</p>',
            'category' => 'gear-reviews',
            'status'   => 'published',
            'featured' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-4 days')),
            'meta'     => 'Honest comparison of Victron MultiPlus vs EG4 6000XP inverters for off-grid bus and van builds.',
            'tags'     => ['victron', 'inverter', 'off-grid'],
            'image'    => 'uploads/images/wuzabus/victron-multiplus-rack.jpg',
        ],
        [
            'title'   => 'The 5 Most Common Off-Grid Wiring Mistakes I Fix',
            'slug'    => '5-most-common-off-grid-wiring-mistakes',
            'excerpt' => 'I\'ve fixed a lot of other people\'s wiring. Here are the mistakes I see over and over — and how to avoid them.',
            'content' => '<p>A solid chunk of my work is fixing electrical systems that someone else installed. No judgment — everyone starts somewhere. But these mistakes keep showing up, and they\'re all preventable.</p>

<h2>1. Undersized Wire</h2>
<p>This is the big one. Wire that\'s too small for the current creates heat. Heat melts insulation. Melted insulation starts fires. Every wire needs to be sized for the actual current it carries, plus the length of the run. There are charts for this — use them.</p>

<h2>2. Missing or Wrong Fuses</h2>
<p>The fuse protects the wire, not the device. You size the fuse based on the wire gauge, not the appliance. I\'ve seen 30A fuses on 16-gauge wire. That wire will melt long before that fuse blows.</p>

<h2>3. Poor Connections</h2>
<p>Butt connectors and electrical tape don\'t belong in a vehicle electrical system. Use properly crimped ring terminals, heat shrink, and appropriate torque on terminal bolts. Vibration is the enemy — everything loosens over time if it\'s not done right.</p>

<h2>4. No Disconnect Switch</h2>
<p>Every battery bank needs a way to be completely disconnected. A Class T fuse and a battery disconnect switch are not optional. When something goes wrong, you need to kill all power immediately.</p>

<h2>5. Grounding Issues</h2>
<p>Bad grounds cause more weird electrical problems than anything else. Use a proper bus bar for your negative/ground connections, star ground where appropriate, and make sure chassis grounds are clean metal-to-metal.</p>

<p>If any of this sounds like your build, don\'t feel bad — just <a href="index.php?page=contact">get in touch</a> and let\'s make it right.</p>',
            'category' => 'build-tips',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'meta'     => 'The 5 most common wiring mistakes in off-grid bus and van builds, and how to avoid them.',
            'tags'     => ['wiring', 'safety', 'bus-build'],
            'image'    => 'uploads/images/wuzabus/electrical-victron-panel.jpg',
        ],
        [
            'title'   => 'SOK Batteries: Why I Keep Installing Them',
            'slug'    => 'sok-batteries-why-i-keep-installing-them',
            'excerpt' => 'I\'ve installed a lot of lithium batteries. SOK keeps earning a spot in my builds. Here\'s why.',
            'content' => '<p>When it comes to lithium iron phosphate (LiFePO4) batteries for off-grid builds, there are a lot of options. I\'ve worked with most of them. SOK keeps showing up in my builds for good reason.</p>

<h2>What I Like About SOK</h2>
<ul>
<li><strong>Built-in BMS.</strong> The battery management system handles cell balancing, over-charge protection, over-discharge protection, and temperature cutoffs. It just works.</li>
<li><strong>Consistent quality.</strong> I\'ve installed dozens of SOK batteries. They\'re consistent, well-built, and the terminals are solid.</li>
<li><strong>Price point.</strong> SOK hits the sweet spot between budget batteries with questionable quality and premium brands that charge a fortune for the same chemistry.</li>
<li><strong>Warranty.</strong> They stand behind their product, and their customer service has been solid in my experience.</li>
</ul>

<h2>Typical Configurations</h2>
<p>For most bus builds, I install a 12V system with 2-4 SOK 206Ah batteries in parallel. That gives you 400-800Ah of usable capacity — enough to run a mini-split AC, lights, outlets, and charge everything for days without shore power or sun.</p>

<p>For larger builds or 48V systems, SOK\'s server rack batteries are a clean, space-efficient option.</p>

<h2>The Bottom Line</h2>
<p>No battery is perfect, but SOK consistently delivers for the off-grid builds I do. If you\'re speccing out a battery bank, <a href="index.php?page=order">reach out</a> and I\'ll help you size it right for your build.</p>',
            'category' => 'gear-reviews',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'meta'     => 'Why SOK LiFePO4 batteries are a solid choice for off-grid bus and van electrical builds.',
            'tags'     => ['sok', 'lithium', 'battery'],
            'image'    => 'uploads/images/wuzabus/full-system-sok-batteries.jpg',
        ],
        [
            'title'   => 'Solar Panel Sizing: How Much Solar Do You Actually Need?',
            'slug'    => 'solar-panel-sizing-how-much-do-you-need',
            'excerpt' => 'Everyone wants solar on their build. But how much do you actually need? It depends on how you use your rig.',
            'content' => '<p>Solar is one of the first things people ask about when they start planning their off-grid build. "How many panels do I need?" The answer starts with a different question: how much power do you actually use?</p>

<h2>Start With Your Load</h2>
<p>Before you buy a single panel, figure out your daily power consumption. Add up everything you plan to run:</p>
<ul>
<li>Lights (LED, so not much)</li>
<li>Phone/laptop charging</li>
<li>Refrigerator (this is usually the big one that runs 24/7)</li>
<li>Water pump</li>
<li>Fan or mini-split AC</li>
<li>Anything else — router, TV, coffee maker, etc.</li>
</ul>

<h2>The Math</h2>
<p>Multiply watts by hours of use to get watt-hours per day. A 12V fridge might pull 40-60W and run about 8-12 hours total per day (cycling). That\'s 320-720Wh just for the fridge. Add everything else and you might be at 1,500-3,000Wh per day for a typical bus build.</p>

<h2>Panel Output Reality</h2>
<p>A 200W panel doesn\'t produce 200W all day. Real-world output depends on angle, shade, weather, and season. A reasonable estimate is 4-5 peak sun hours per day in most of the US. So a 200W panel might give you 800-1000Wh per day in good conditions.</p>

<h2>My Typical Recommendation</h2>
<p>For a full-time bus build, I usually recommend 600-1000W of rooftop solar paired with a properly sized MPPT charge controller. That covers most people\'s needs with room to spare. For part-time use or smaller vans, 400W might be plenty.</p>

<p>Want help sizing your solar setup? <a href="index.php?page=order">Get a quote</a> and I\'ll design a system around your actual usage.</p>',
            'category' => 'build-tips',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-14 days')),
            'meta'     => 'How to calculate how much solar panel capacity you need for your off-grid bus or van build.',
            'tags'     => ['solar', 'off-grid', 'bus-build'],
            'image'    => 'uploads/images/wuzabus/bus-exterior-solar.jpg',
        ],
        [
            'title'   => 'Powered Builds = Stress-Free Camping',
            'slug'    => 'powered-builds-stress-free-camping',
            'excerpt' => 'When your power system is built right, you stop worrying about electricity and start enjoying the trip.',
            'content' => '<p>The whole point of building an off-grid rig is freedom. Freedom to park anywhere, stay as long as you want, and not worry about hookups. But that freedom evaporates real quick if your electrical system can\'t keep up.</p>

<h2>What "Stress-Free" Looks Like</h2>
<p>When I finish a build, here\'s what my clients get:</p>
<ul>
<li><strong>Run the AC.</strong> Mini-split or roof unit, powered by your battery bank. Sleep comfortable in any weather.</li>
<li><strong>Never think about your fridge.</strong> It just runs. 24/7. Your food stays cold.</li>
<li><strong>Charge everything.</strong> Phones, laptops, cameras — plug them in and forget about it.</li>
<li><strong>Real lights.</strong> Not dim, flickering 12V LED strips. Proper lighting throughout your rig.</li>
<li><strong>Monitor everything.</strong> Victron\'s app shows your battery level, solar input, and power consumption in real time from your phone.</li>
</ul>

<h2>The Campfire, Not the Circuit Breaker</h2>
<p>When your power system just works, you spend your evenings around the campfire instead of troubleshooting why the inverter keeps shutting off. That\'s the goal. That\'s what a properly built system gives you.</p>

<p>Ready for stress-free camping? <a href="index.php?page=contact">Message me</a> and let\'s talk about your build.</p>',
            'category' => 'off-grid-life',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-18 days')),
            'meta'     => 'How a properly built off-grid electrical system means stress-free camping and true freedom on the road.',
            'tags'     => ['off-grid', 'van-life', 'skoolie'],
            'image'    => 'uploads/images/wuzabus/bus-interior-living.jpg',
        ],
    ];

    $postStmt = $db->prepare(
        'INSERT INTO blog_posts (title, slug, excerpt, content, category_id, author_id, status,
         is_featured, allow_comments, meta_description, featured_image, published_at, created_at, updated_at, view_count)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?, ?)'
    );
    $postTagStmt = $db->prepare('INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)');

    foreach ($posts as $post) {
        $catId = $catIds[$post['category']] ?? null;
        $views = rand(15, 250);
        $postStmt->execute([
            $post['title'],
            $post['slug'],
            $post['excerpt'],
            $post['content'],
            $catId,
            $authorId,
            $post['status'],
            $post['featured'],
            $post['meta'],
            $post['image'] ?? null,
            $post['published_at'],
            $now,
            $now,
            $views,
        ]);
        $postId = (int)$db->lastInsertId();

        // Assign tags
        foreach ($post['tags'] as $tagSlug) {
            if (isset($tagIds[$tagSlug])) {
                $postTagStmt->execute([$postId, $tagIds[$tagSlug]]);
            }
        }
    }
};
