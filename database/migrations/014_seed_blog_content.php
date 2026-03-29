<?php
/**
 * Migration 014: Seed blog with professional demo content.
 * Creates categories, tags, and sample blog posts so the blog
 * page has content out of the box.
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
        ['Build Journals', 'build-journals', 'Real project updates from bus conversions in progress and completed builds.', 0],
        ['Off-Grid Systems', 'off-grid-systems', 'Solar, batteries, inverters, and electrical planning for reliable life on the road.', 1],
        ['Skoolie Living Tips', 'skoolie-living-tips', 'Practical advice for design, budgeting, and full-time mobile living.', 2],
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
        'Skoolie' => 'skoolie',
        'Bus Conversion' => 'bus-conversion',
        'Electrical' => 'electrical',
        'Solar' => 'solar',
        'Victron' => 'victron',
        'Layout Planning' => 'layout-planning',
        'DIY Tips' => 'diy-tips',
        'Road Living' => 'road-living',
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
            'title'   => 'Welcome to the WuzaBus Build Journal',
            'slug'    => 'welcome-to-the-wuzabus-build-journal',
            'excerpt' => 'We are documenting real conversions, electrical installs, and lessons learned from life on the road.',
            'content' => '<p>Welcome to TheWuzaBus blog. This is where we share behind-the-scenes build updates, practical electrical guides, and real-world skoolie living advice.</p>
<h2>What We Cover</h2>
<ul>
<li>Current bus conversion progress and photo updates</li>
<li>Off-grid power system planning and troubleshooting</li>
<li>Layout choices, materials, and budget tradeoffs</li>
</ul>
<p>If there is a topic you want us to break down, <a href="index.php?page=contact">send us a message</a>.</p>',
            'category' => 'build-journals',
            'status'   => 'published',
            'featured' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'meta'     => 'Welcome to the WuzaBus blog for conversion journals, solar/electrical guides, and skoolie living tips.',
            'tags'     => ['skoolie', 'bus-conversion'],
        ],
        [
            'title'   => 'How We Size Victron + Lithium Systems for Full-Time Travel',
            'slug'    => 'how-we-size-victron-lithium-systems-for-full-time-travel',
            'excerpt' => 'A practical framework for inverter, battery bank, and solar sizing based on your daily loads.',
            'content' => '<p>Most power problems come from undersized systems. We start with your real daily loads, then design around those numbers.</p>
<h2>Our Basic Sizing Flow</h2>
<ol>
<li>List every daily load and runtime.</li>
<li>Calculate usable battery capacity for 1.5 to 2 days of autonomy.</li>
<li>Choose inverter size for peak surge and continuous draw.</li>
<li>Size solar to recover daily usage under average conditions.</li>
</ol>
<p>Need help with your setup? <a href="index.php?page=order">Request a quote</a>.</p>',
            'category' => 'off-grid-systems',
            'status'   => 'published',
            'featured' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'meta'     => 'Learn our process for sizing Victron inverters, lithium banks, and solar arrays for skoolie life.',
            'tags'     => ['victron', 'solar', 'electrical'],
        ],
        [
            'title'   => 'Bus Layout Planning: Avoid These 7 Costly Mistakes',
            'slug'    => 'bus-layout-planning-avoid-these-7-costly-mistakes',
            'excerpt' => 'Layout decisions drive your comfort, budget, and wiring complexity. Here are the biggest mistakes we see.',
            'content' => '<p>Your layout determines almost everything else in the build. Fixing a bad layout late is expensive.</p>
<h2>Top Mistakes</h2>
<ul>
<li>Not leaving service access to electrical and plumbing runs</li>
<li>Overbuilding storage and underestimating living space</li>
<li>Ignoring weight distribution when placing tanks and batteries</li>
<li>Skipping a full-scale floor mockup before permanent installs</li>
</ul>
<p>Before cutting materials, sketch your workflow from waking up to bedtime.</p>',
            'category' => 'skoolie-living-tips',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'meta'     => 'Seven common bus conversion layout mistakes and how to avoid expensive rework.',
            'tags'     => ['layout-planning', 'bus-conversion', 'diy-tips'],
        ],
        [
            'title'   => 'Before & After: Shuttle Bus Electrical Bay Upgrade',
            'slug'    => 'before-and-after-shuttle-bus-electrical-bay-upgrade',
            'excerpt' => 'A recent upgrade from a basic setup to a clean, monitorable off-grid electrical system.',
            'content' => '<p>We recently rebuilt a shuttle bus electrical bay with cleaner cable management, proper overcurrent protection, and remote monitoring.</p>
<h2>What Changed</h2>
<ul>
<li>Added properly sized fusing and disconnects</li>
<li>Integrated Victron monitoring for diagnostics</li>
<li>Improved serviceability with labeled circuits and access spacing</li>
</ul>
<p>The result: safer operation, easier troubleshooting, and better charging performance.</p>',
            'category' => 'build-journals',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'meta'     => 'Case study: shuttle bus electrical bay upgrade with Victron monitoring and safer circuit protection.',
            'tags'     => ['electrical', 'victron', 'bus-conversion'],
        ],
        [
            'title'   => 'DIY vs Pro Install: What to Tackle Yourself on a Skoolie',
            'slug'    => 'diy-vs-pro-install-what-to-tackle-yourself-on-a-skoolie',
            'excerpt' => 'Some build tasks are ideal DIY projects, while others are worth hiring out for safety and reliability.',
            'content' => '<p>DIY can save money, but strategic outsourcing can save your project.</p>
<h2>Great DIY Candidates</h2>
<ul>
<li>Demolition and prep</li>
<li>Insulation and basic finish carpentry</li>
<li>Non-critical interior trim work</li>
</ul>
<h2>Usually Best for Pros</h2>
<ul>
<li>High-current electrical design and commissioning</li>
<li>Complex plumbing penetrations and venting</li>
<li>Roof penetrations where leaks are expensive to fix</li>
</ul>',
            'category' => 'skoolie-living-tips',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'meta'     => 'A realistic guide to deciding which skoolie tasks to DIY and which to hire out.',
            'tags'     => ['diy-tips', 'skoolie', 'road-living'],
        ],
        [
            'title'   => 'Winter Off-Grid Checklist for Bus Conversions',
            'slug'    => 'winter-off-grid-checklist-for-bus-conversions',
            'excerpt' => 'A seasonal checklist to protect batteries, plumbing, and comfort when temperatures drop.',
            'content' => '<p>Cold weather changes how your bus systems behave. A quick pre-season check prevents most winter breakdowns.</p>
<h2>Checklist Highlights</h2>
<ul>
<li>Confirm low-temp charging protections on lithium batteries</li>
<li>Insulate exposed plumbing and validate heat tape operation</li>
<li>Test heater load profile against nightly battery capacity</li>
<li>Inspect door/window seals and address drafts early</li>
</ul>
<p>Small prep now means fewer surprises on the road.</p>',
            'category' => 'off-grid-systems',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-14 days')),
            'meta'     => 'Use this winter checklist to keep your skoolie electrical and plumbing systems reliable off-grid.',
            'tags'     => ['road-living', 'solar', 'electrical'],
        ],
    ];

    $postStmt = $db->prepare(
        'INSERT INTO blog_posts (title, slug, excerpt, content, category_id, author_id, status,
         is_featured, allow_comments, meta_description, published_at, created_at, updated_at, view_count)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, ?)'
    );
    $postTagStmt = $db->prepare('INSERT IGNORE INTO blog_post_tags (post_id, tag_id) VALUES (?, ?)');

    foreach ($posts as $post) {
        $catId = $catIds[$post['category']] ?? null;
        $views = rand(5, 120);
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
