-- Blog Platform Tables
-- Migration 013: Create blog tables for full-featured blog platform

-- Blog categories (separate from product categories)
CREATE TABLE IF NOT EXISTS blog_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    image TEXT,
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Blog posts
CREATE TABLE IF NOT EXISTS blog_posts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    excerpt TEXT,
    content TEXT,
    featured_image TEXT,
    featured_image_alt TEXT,
    category_id INTEGER,
    author_id INTEGER,
    status TEXT DEFAULT 'draft',
    is_featured INTEGER DEFAULT 0,
    allow_comments INTEGER DEFAULT 1,
    meta_description TEXT,
    og_image TEXT,
    published_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    view_count INTEGER DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES blog_categories(id) ON DELETE SET NULL,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_blog_posts_slug ON blog_posts(slug);
CREATE INDEX IF NOT EXISTS idx_blog_posts_status_published ON blog_posts(status, published_at);
CREATE INDEX IF NOT EXISTS idx_blog_posts_category ON blog_posts(category_id);
CREATE INDEX IF NOT EXISTS idx_blog_posts_featured ON blog_posts(is_featured, status);

-- Blog tags
CREATE TABLE IF NOT EXISTS blog_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_blog_tags_slug ON blog_tags(slug);

-- Blog post-tag pivot
CREATE TABLE IF NOT EXISTS blog_post_tags (
    post_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    PRIMARY KEY (post_id, tag_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (tag_id) REFERENCES blog_tags(id) ON DELETE CASCADE
);

-- Blog comments
CREATE TABLE IF NOT EXISTS blog_comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    post_id INTEGER NOT NULL,
    parent_id INTEGER DEFAULT NULL,
    customer_id INTEGER,
    author_name TEXT NOT NULL,
    author_email TEXT NOT NULL,
    content TEXT NOT NULL,
    is_approved INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE INDEX IF NOT EXISTS idx_blog_comments_post ON blog_comments(post_id, is_approved);

-- Blog post-product links (e-commerce integration)
CREATE TABLE IF NOT EXISTS blog_post_products (
    post_id INTEGER NOT NULL,
    product_id INTEGER NOT NULL,
    sort_order INTEGER DEFAULT 0,
    PRIMARY KEY (post_id, product_id),
    FOREIGN KEY (post_id) REFERENCES blog_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Default blog settings
INSERT OR IGNORE INTO settings (key, value, type) VALUES ('enable_blog', '1', 'text');
INSERT OR IGNORE INTO settings (key, value, type) VALUES ('blog_page_title', 'Blog', 'text');
INSERT OR IGNORE INTO settings (key, value, type) VALUES ('blog_posts_per_page', '9', 'text');
INSERT OR IGNORE INTO settings (key, value, type) VALUES ('blog_allow_comments', '1', 'text');
INSERT OR IGNORE INTO settings (key, value, type) VALUES ('blog_comment_moderation', '1', 'text');
INSERT OR IGNORE INTO settings (key, value, type) VALUES ('blog_show_author', '1', 'text');
INSERT OR IGNORE INTO settings (key, value, type) VALUES ('blog_show_sidebar', '1', 'text');

-- Add Blog to navigation
INSERT OR IGNORE INTO navigation (label, url, sort_order, is_visible)
SELECT 'Blog', 'index.php?page=blog', COALESCE(MAX(sort_order), 0) + 1, 1 FROM navigation;

-- Add Blog page entry
INSERT OR IGNORE INTO pages (title, slug, content, is_system, is_published, show_in_nav, template)
VALUES ('Blog', 'blog', '', 1, 1, 1, 'default');
