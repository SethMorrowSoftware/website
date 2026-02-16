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
        ['Company News',   'company-news',   'Updates, announcements, and milestones from our team.',             0],
        ['Tips & Guides',  'tips-and-guides', 'Practical advice and how-to articles to help you succeed.',        1],
        ['Industry Trends', 'industry-trends', 'Insights on the latest developments shaping our industry.',       2],
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
        'Business'     => 'business',
        'Growth'       => 'growth',
        'Tips'         => 'tips',
        'Strategy'     => 'strategy',
        'Quality'      => 'quality',
        'Customer Service' => 'customer-service',
        'Innovation'   => 'innovation',
        'Sustainability'   => 'sustainability',
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
            'title'   => 'Welcome to Our Blog',
            'slug'    => 'welcome-to-our-blog',
            'excerpt' => 'We are excited to launch our blog where we will share company updates, industry insights, and practical tips to help you make informed decisions.',
            'content' => '<p>Welcome to our official blog! We are thrilled to open this new channel of communication with our customers, partners, and community.</p>

<h2>What to Expect</h2>
<p>Our blog will be a hub for:</p>
<ul>
<li><strong>Company News</strong> &mdash; Stay up to date with our latest announcements, product launches, and milestones.</li>
<li><strong>Tips &amp; Guides</strong> &mdash; Practical, actionable advice you can use right away.</li>
<li><strong>Industry Trends</strong> &mdash; Our take on the developments that matter most to you.</li>
</ul>

<h2>Our Commitment</h2>
<p>We believe in transparency and adding real value. Every article we publish is written with one goal in mind: to help you make better, more informed decisions. Whether you are a long-time customer or discovering us for the first time, we want this blog to be a resource you can count on.</p>

<p>Have a topic you would like us to cover? <a href="index.php?page=contact">Get in touch</a> and let us know. We love hearing from our community.</p>

<p>Stay tuned for more content coming soon!</p>',
            'category' => 'company-news',
            'status'   => 'published',
            'featured' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-1 day')),
            'meta'     => 'Welcome to our blog. Follow along for company news, expert tips, and industry insights.',
            'tags'     => ['business', 'growth'],
        ],
        [
            'title'   => '5 Ways to Get the Most Out of Our Products and Services',
            'slug'    => '5-ways-to-get-the-most-out-of-our-products-and-services',
            'excerpt' => 'Whether you are a new customer or have been with us for years, these five tips will help you maximize the value of what we offer.',
            'content' => '<p>We are committed to delivering the best possible experience for every customer. Here are five ways to make sure you are getting the full value of our products and services.</p>

<h2>1. Take Advantage of Our Full Catalog</h2>
<p>Many customers come to us for one specific product or service, but our catalog has much more to offer. Take a few minutes to <a href="index.php?page=catalog">browse our full selection</a> &mdash; you might find exactly what you need for your next project.</p>

<h2>2. Ask Questions</h2>
<p>Our team is here to help. If you are unsure which option is the best fit for your needs, do not hesitate to reach out. We would rather spend time helping you find the right solution than have you settle for something that does not quite work.</p>

<h2>3. Plan Ahead</h2>
<p>For larger orders or projects with tight timelines, giving us advance notice makes a big difference. It allows us to prepare properly and ensures you get the best possible outcome.</p>

<h2>4. Share Your Feedback</h2>
<p>We take customer feedback seriously. Your input directly shapes how we improve our products and services. If something could be better, we want to know about it.</p>

<h2>5. Stay Connected</h2>
<p>Follow our blog and social media channels for the latest updates, promotions, and tips. Being in the loop means you will never miss an opportunity to save time or money.</p>

<p>Have more questions? <a href="index.php?page=contact">Contact our team</a> &mdash; we are always happy to help.</p>',
            'category' => 'tips-and-guides',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'meta'     => 'Five practical tips to help you get the most value out of our products and services.',
            'tags'     => ['tips', 'customer-service', 'quality'],
        ],
        [
            'title'   => 'Why Quality Matters: Our Approach to Excellence',
            'slug'    => 'why-quality-matters-our-approach-to-excellence',
            'excerpt' => 'Quality is not just a buzzword for us. Learn about the standards and processes that set our products and services apart from the competition.',
            'content' => '<p>In an era of shortcuts and cut corners, we have chosen a different path. Quality is the foundation of everything we do, and it is the reason our customers keep coming back.</p>

<h2>Our Quality Standards</h2>
<p>Every product and service we offer goes through a rigorous quality assurance process. We do not believe in shipping something that does not meet our own high standards. From sourcing materials to final delivery, we pay attention to every detail.</p>

<h2>The Difference You Can See</h2>
<p>Quality is not always visible at first glance, but it becomes apparent over time. Products that last longer, services that are done right the first time, and a team that stands behind its work &mdash; these are the things that matter in the long run.</p>

<h2>Continuous Improvement</h2>
<p>We are never satisfied with "good enough." Our team regularly reviews our processes and looks for ways to improve. Whether it is adopting new technology, refining our techniques, or expanding our training programs, we are always pushing forward.</p>

<blockquote><p>"Quality is not an act, it is a habit." &mdash; Aristotle</p></blockquote>

<h2>What This Means for You</h2>
<p>When you choose us, you are choosing a partner who takes your satisfaction seriously. We stand behind every product we sell and every service we provide. That is not just a promise &mdash; it is how we do business.</p>

<p>Experience the difference for yourself. <a href="index.php?page=catalog">Explore our catalog</a> or <a href="index.php?page=contact">get in touch</a> today.</p>',
            'category' => 'company-news',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-5 days')),
            'meta'     => 'Learn about our commitment to quality and the standards that set our products and services apart.',
            'tags'     => ['quality', 'business', 'strategy'],
        ],
        [
            'title'   => 'The Future of Our Industry: Trends to Watch',
            'slug'    => 'the-future-of-our-industry-trends-to-watch',
            'excerpt' => 'The landscape is changing fast. Here are the key trends we are watching and how they might affect you.',
            'content' => '<p>Our industry is evolving rapidly, driven by technological advances, shifting customer expectations, and a growing emphasis on sustainability. Here is what we see on the horizon.</p>

<h2>Digital Transformation</h2>
<p>The move toward digital tools and platforms is accelerating. From online ordering to real-time tracking, customers increasingly expect seamless digital experiences. We have been investing in our online presence to make it easier than ever to do business with us.</p>

<h2>Sustainability and Responsibility</h2>
<p>Environmental consciousness is no longer optional &mdash; it is a business imperative. Customers, regulators, and communities are all pushing for more sustainable practices. We are committed to doing our part and are actively exploring ways to reduce our environmental footprint.</p>

<h2>Personalization</h2>
<p>One-size-fits-all is becoming a thing of the past. Customers want products and services tailored to their specific needs. We are expanding our options and working closely with customers to deliver customized solutions.</p>

<h2>What This Means for Our Customers</h2>
<p>Change can be daunting, but it also brings opportunity. As we adapt to these trends, our customers benefit from better products, more convenient services, and a partner who is prepared for the future.</p>

<p>Want to discuss how these trends might affect your needs? <a href="index.php?page=contact">Let us know</a> &mdash; we are always happy to talk strategy.</p>',
            'category' => 'industry-trends',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-7 days')),
            'meta'     => 'Key industry trends to watch: digital transformation, sustainability, and personalization.',
            'tags'     => ['innovation', 'strategy', 'sustainability'],
        ],
        [
            'title'   => 'How to Choose the Right Solution for Your Needs',
            'slug'    => 'how-to-choose-the-right-solution-for-your-needs',
            'excerpt' => 'With so many options available, choosing the right product or service can feel overwhelming. This guide will help you narrow down your choices.',
            'content' => '<p>We understand that choosing the right product or service can sometimes feel like a big decision. Here is a straightforward framework to help you make the best choice.</p>

<h2>Step 1: Define Your Requirements</h2>
<p>Before looking at options, take a moment to clearly define what you need. Consider factors like:</p>
<ul>
<li>What problem are you trying to solve?</li>
<li>What is your timeline?</li>
<li>What is your budget range?</li>
<li>Are there any specific features or specifications that are non-negotiable?</li>
</ul>

<h2>Step 2: Research Your Options</h2>
<p>Once you know what you need, explore what is available. Our <a href="index.php?page=catalog">product catalog</a> is a great place to start. Compare features, read descriptions, and note any questions that come up.</p>

<h2>Step 3: Ask the Experts</h2>
<p>You do not have to figure it all out on your own. Our team has years of experience helping customers find the right fit. Reach out and we will walk you through the options that make sense for your situation.</p>

<h2>Step 4: Consider Long-Term Value</h2>
<p>The cheapest option is not always the most cost-effective in the long run. Think about durability, maintenance requirements, and how well the solution will scale with your needs over time.</p>

<h2>Step 5: Make Your Decision with Confidence</h2>
<p>Armed with clear requirements, thorough research, and expert guidance, you can make your decision knowing you have done your due diligence. And remember, we stand behind everything we offer.</p>

<p>Ready to find the right solution? <a href="index.php?page=order">Submit an inquiry</a> and we will help you get started.</p>',
            'category' => 'tips-and-guides',
            'status'   => 'published',
            'featured' => 1,
            'published_at' => date('Y-m-d H:i:s', strtotime('-10 days')),
            'meta'     => 'A step-by-step guide to choosing the right product or service for your specific needs.',
            'tags'     => ['tips', 'customer-service', 'strategy'],
        ],
        [
            'title'   => 'Building Lasting Relationships: Our Customer-First Philosophy',
            'slug'    => 'building-lasting-relationships-our-customer-first-philosophy',
            'excerpt' => 'At the heart of our business is a simple belief: when our customers succeed, we succeed. Here is how that philosophy shapes everything we do.',
            'content' => '<p>In business, transactions come and go. What endures are relationships. That is why we have built our company around a customer-first philosophy that goes beyond the sale.</p>

<h2>More Than a Transaction</h2>
<p>When you work with us, you are not just a number. We take the time to understand your needs, your goals, and what success looks like for you. This deeper understanding allows us to provide solutions that truly make a difference.</p>

<h2>Communication Is Key</h2>
<p>We believe in keeping you informed every step of the way. Whether it is an update on your order, a heads-up about a new product, or a check-in to see how things are going, we make communication a priority.</p>

<h2>Standing Behind Our Work</h2>
<p>If something is not right, we want to know about it. We do not dodge problems &mdash; we solve them. Our reputation has been built on trust, and we intend to keep it that way.</p>

<h2>Growing Together</h2>
<p>Many of our best customers have been with us for years. As their needs have evolved, so have our offerings. That is the power of a true partnership &mdash; both sides grow and improve together.</p>

<p>We value every customer relationship. If there is anything we can do to serve you better, <a href="index.php?page=contact">please let us know</a>.</p>',
            'category' => 'company-news',
            'status'   => 'published',
            'featured' => 0,
            'published_at' => date('Y-m-d H:i:s', strtotime('-14 days')),
            'meta'     => 'How our customer-first philosophy shapes our approach to business and builds lasting relationships.',
            'tags'     => ['customer-service', 'business', 'growth'],
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
