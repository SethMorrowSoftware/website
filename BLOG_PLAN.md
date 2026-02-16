# Blog Platform Implementation Plan

## Overview

Add a full-featured blog platform to the existing Business Website CMS, deeply integrated with the e-commerce system. The blog will include a rich post editor with image uploads, categories, tags, comments, featured product embedding, and public-facing blog pages with SEO support.

---

## Phase 1: Database Schema

### New Tables

**`blog_categories`** — Blog-specific categories (separate from product categories)
- `id` INTEGER PRIMARY KEY AUTOINCREMENT
- `name` TEXT NOT NULL
- `slug` TEXT UNIQUE NOT NULL
- `description` TEXT
- `image` TEXT (optional category banner)
- `sort_order` INTEGER DEFAULT 0
- `is_visible` INTEGER DEFAULT 1
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

**`blog_posts`** — Core blog post table
- `id` INTEGER PRIMARY KEY AUTOINCREMENT
- `title` TEXT NOT NULL
- `slug` TEXT UNIQUE NOT NULL
- `excerpt` TEXT (short summary for listing pages/SEO)
- `content` TEXT (full HTML body)
- `featured_image` TEXT (hero image path)
- `featured_image_alt` TEXT
- `category_id` INTEGER (FK → blog_categories.id, SET NULL on delete)
- `author_id` INTEGER (FK → users.id — the admin who authored it)
- `status` TEXT DEFAULT 'draft' (draft | published | scheduled)
- `is_featured` INTEGER DEFAULT 0 (pin to homepage/top of blog)
- `allow_comments` INTEGER DEFAULT 1
- `meta_description` TEXT (SEO)
- `og_image` TEXT (Open Graph override)
- `published_at` DATETIME (scheduled publish date — posts only visible when ≤ now)
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP
- `view_count` INTEGER DEFAULT 0

**`blog_tags`** — Tag definitions
- `id` INTEGER PRIMARY KEY AUTOINCREMENT
- `name` TEXT NOT NULL
- `slug` TEXT UNIQUE NOT NULL

**`blog_post_tags`** — Many-to-many post ↔ tag pivot
- `post_id` INTEGER NOT NULL (FK → blog_posts.id, CASCADE)
- `tag_id` INTEGER NOT NULL (FK → blog_tags.id, CASCADE)
- PRIMARY KEY (post_id, tag_id)

**`blog_comments`** — Reader comments
- `id` INTEGER PRIMARY KEY AUTOINCREMENT
- `post_id` INTEGER NOT NULL (FK → blog_posts.id, CASCADE)
- `parent_id` INTEGER DEFAULT NULL (self-referencing for threaded replies)
- `customer_id` INTEGER (FK → customers.id, SET NULL — optional, for logged-in users)
- `author_name` TEXT NOT NULL
- `author_email` TEXT NOT NULL
- `content` TEXT NOT NULL
- `is_approved` INTEGER DEFAULT 0 (moderation queue)
- `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP

**`blog_post_products`** — E-commerce integration: link products to posts
- `post_id` INTEGER NOT NULL (FK → blog_posts.id, CASCADE)
- `product_id` INTEGER NOT NULL (FK → products.id, CASCADE)
- `sort_order` INTEGER DEFAULT 0
- PRIMARY KEY (post_id, product_id)

### Indexes
- `idx_blog_posts_slug` ON blog_posts(slug)
- `idx_blog_posts_status_published` ON blog_posts(status, published_at)
- `idx_blog_posts_category` ON blog_posts(category_id)
- `idx_blog_posts_featured` ON blog_posts(is_featured, status)
- `idx_blog_comments_post` ON blog_comments(post_id, is_approved)
- `idx_blog_tags_slug` ON blog_tags(slug)

### New Settings Keys
- `enable_blog` → '1' (feature toggle)
- `blog_page_title` → 'Blog'
- `blog_posts_per_page` → '9'
- `blog_allow_comments` → '1'
- `blog_comment_moderation` → '1' (require approval)
- `blog_show_author` → '1'
- `blog_show_sidebar` → '1'

### Migration File
- Create `/database/migrations/013_create_blog_tables.sql`

---

## Phase 2: Backend Functions

### New File: `/includes/blog.php`
Loaded by `functions.php` alongside other modules.

**Post Functions:**
- `getBlogPosts($page, $perPage, $filters)` — paginated listing with category/tag/search filters; only published + published_at ≤ now
- `getBlogPost($slug)` — single post by slug (increments view_count)
- `getBlogPostById($id)` — admin fetch by ID
- `getRelatedPosts($postId, $limit)` — same category, excluding current
- `getFeaturedPosts($limit)` — featured flag + published
- `getRecentPosts($limit)` — latest published posts
- `getPopularPosts($limit)` — by view_count
- `saveBlogPost($data)` — insert or update
- `deleteBlogPost($id)` — delete post + cascade tags/comments
- `getBlogArchiveMonths()` — list of year/month combos with post counts

**Category Functions:**
- `getBlogCategories()` — all visible categories
- `getBlogCategory($slug)` — single by slug
- `saveBlogCategory($data)` — insert or update
- `deleteBlogCategory($id)` — delete (nullify post references)

**Tag Functions:**
- `getBlogTags()` — all tags
- `getPostTags($postId)` — tags for a specific post
- `getTagBySlug($slug)` — single tag
- `syncPostTags($postId, $tagNames)` — reconcile tag assignments (creates new tags as needed)
- `getPopularTags($limit)` — tags sorted by usage count

**Comment Functions:**
- `getPostComments($postId)` — approved comments, threaded
- `getPendingComments()` — admin moderation queue
- `submitComment($postId, $data)` — insert (respects moderation setting)
- `approveComment($id)` / `rejectComment($id)` — moderation actions
- `deleteComment($id)` — remove
- `getCommentCount($postId)` — approved count

**Product Integration Functions:**
- `getPostProducts($postId)` — linked products for a post
- `syncPostProducts($postId, $productIds)` — update product links

---

## Phase 3: Admin Pages

### 3a. Blog Posts Manager — `/admin/blog-posts.php`
- Table listing: title, category, status (draft/published/scheduled), date, view count, comments count, actions
- Filters: by status, by category
- Bulk actions: delete, publish, unpublish
- "New Post" button

### 3b. Blog Post Editor — `/admin/blog-post-edit.php`
- **Title field** with auto-slug generation
- **Enhanced WYSIWYG editor** for content body:
  - All existing editor features (bold, italic, lists, links, headings, source view)
  - **Image upload button**: opens file picker, uploads via AJAX to `/admin/api/upload.php`, inserts `<img>` into editor
  - **Media library picker**: browse existing uploads and insert
  - **Embed product card**: shortcode `[product id=X]` or button to pick products
  - **YouTube/video embed**: paste URL, auto-embed
- **Featured image** upload with preview and alt text
- **Excerpt** textarea (auto-generated from content if left blank)
- **Category** dropdown
- **Tags** input (comma-separated, autocomplete from existing)
- **Status** selector: Draft / Published / Scheduled
- **Published date** picker (for scheduling)
- **SEO fields**: meta description, OG image override
- **Featured toggle**: pin post as featured
- **Allow comments** toggle
- **Related Products** section: multi-select picker to link products (displayed at bottom of post)

### 3c. Blog Categories Manager — `/admin/blog-categories.php`
- Table listing with name, slug, post count, visibility
- Create/edit inline or in modal
- Delete with confirmation

### 3d. Blog Comments Manager — `/admin/blog-comments.php`
- Table listing: author, post, excerpt, date, status (pending/approved)
- Quick-actions: approve, reject, delete
- Filter by status, by post

### 3e. Admin Sidebar Update — `/admin/header.php`
- Add "Blog" nav section between "Engagement" and "System"
- Links: Blog Posts, Blog Categories, Blog Comments (with pending count badge)

### 3f. Dashboard Update — `/admin/index.php`
- Add blog stats card: total posts, published posts, pending comments
- Recent blog posts widget

### 3g. Settings Update — `/admin/settings.php`
- Add "Blog" section with toggle and configuration fields

---

## Phase 4: Public-Facing Pages

### 4a. Blog Listing — `/pages/blog.php`
- Hero section (reuses hero_sections table, page_slug='blog')
- Grid/list of published posts (configurable per-page)
- Each card: featured image, category badge, title, excerpt, date, author, read more link
- **Sidebar** (optional via setting):
  - Search box (blog-only search)
  - Categories list with post counts
  - Popular/recent posts widget
  - Tag cloud
  - Featured products widget (e-commerce cross-sell)
- Pagination (page numbers)
- Category filter (via query param `?category=slug`)
- Tag filter (via query param `?tag=slug`)

### 4b. Single Blog Post — `/pages/blog-post.php`
- Full article layout:
  - Featured image as banner
  - Title, author, date, category, tags
  - Full HTML content body
  - View count display
  - Social sharing links (Facebook, Twitter/X, LinkedIn, copy link)
- **Related Products section**: cards of linked products with add-to-cart buttons
- **Related Posts section**: 3 posts from same category
- **Comments section** (if enabled):
  - List of approved comments (threaded)
  - Comment form (name, email, message) — pre-filled if customer logged in
  - CSRF protection + rate limiting
- Previous/Next post navigation
- Breadcrumbs (Home > Blog > Category > Post Title)

### 4c. Blog Search — handled within `/pages/blog.php`
- `?search=query` parameter triggers full-text search of title + content + excerpt

### 4d. Blog Archive — `/pages/blog.php?archive=2026-02`
- Monthly archive listing support

---

## Phase 5: Router & Navigation Integration

### 5a. Router Updates — `/index.php`
- Add to `$allowedPages`: `'blog'`, `'blog-post'`
- Add feature gate: `'blog' => 'blog'` in `$featurePageMap`
- Add blog comment submission handler (action `submit_blog_comment`)
- Handle blog post slug routing: `?page=blog-post&slug=my-post`

### 5b. Navigation
- Add "Blog" to navigation table during migration (auto-added as nav item)
- `isFeatureEnabled('blog')` check in header for showing/hiding blog nav link

### 5c. Header Updates — `/includes/header.php`
- Conditionally add `<link rel="alternate" type="application/rss+xml">` for RSS
- Add Open Graph `article` type metadata for blog posts

---

## Phase 6: Enhanced Editor (JavaScript)

### 6a. Upgrade `/assets/js/editor.js`
- **Image upload button**: click triggers hidden `<input type="file">`, uploads to `/admin/api/upload.php` via `fetch()`, inserts resulting URL as `<img>` tag
- **Drag-and-drop image upload**: drop image onto editor, auto-upload and insert
- **Media library modal**: AJAX loads gallery of existing uploads, click to insert
- **Product embed button**: opens modal with product search/picker, inserts product card HTML or shortcode

### 6b. New: `/assets/js/blog-editor.js`
- Tag input widget (comma-separated with autocomplete)
- Featured image upload with preview
- Slug auto-generation from title
- Auto-save draft via AJAX (every 60 seconds)
- Publish date picker integration
- Word count display
- Product picker modal for related products

---

## Phase 7: CSS Styling

### 7a. Public blog styles — `/assets/css/blog.css`
- Blog listing grid (responsive 1/2/3 columns)
- Blog card styles (image, category badge, title, excerpt, meta)
- Single post article typography (readable line-height, max-width prose)
- Sidebar widgets
- Comment section & form
- Product embed cards within post content
- Tag cloud
- Pagination
- Social share buttons
- Related posts grid
- Archive listing

### 7b. Admin blog styles — additions to `/assets/css/admin.css`
- Post editor layout (two-column: editor + sidebar meta)
- Tag input widget
- Featured image picker
- Comment moderation table styles

---

## Phase 8: RSS Feed & SEO

### 8a. RSS Feed — `/rss.php`
- Standard RSS 2.0 XML feed of latest 20 published posts
- Includes title, link, description (excerpt), pubDate, category, author
- Auto-discoverable via `<link>` tag in header

### 8b. Sitemap Update — `/sitemap.php`
- Add blog post URLs to XML sitemap
- Add blog category URLs
- Include `<lastmod>` dates

### 8c. SEO Enhancements
- `<meta>` description from post excerpt or meta_description
- Open Graph tags (`og:type=article`, `og:image`, `og:published_time`, etc.)
- JSON-LD `BlogPosting` structured data
- Canonical URLs for posts

---

## Phase 9: E-Commerce Integration Points

### 9a. Product Cards in Blog Posts
- Admin can link products to posts via editor
- `[product id=X]` shortcode rendered in post content as product card
- Product cards include: image, name, price, add-to-cart button

### 9b. "Featured Products" Sidebar Widget
- Shows products from linked/related categories in blog sidebar
- Configurable: manual selection or auto from post's linked products

### 9c. Blog Widget on Homepage — `/pages/home.php`
- "Latest from the Blog" section with 3 recent posts
- Conditional on `enable_blog` setting

### 9d. Blog Posts on Product Pages — `/pages/product.php`
- "Related Articles" section showing posts that link to this product
- Helps with cross-selling and content marketing

---

## File Summary

### New Files to Create:
| File | Purpose |
|------|---------|
| `database/migrations/013_create_blog_tables.sql` | Schema migration |
| `includes/blog.php` | All blog backend functions |
| `admin/blog-posts.php` | Post list manager |
| `admin/blog-post-edit.php` | Post editor with WYSIWYG |
| `admin/blog-categories.php` | Category manager |
| `admin/blog-comments.php` | Comment moderation |
| `pages/blog.php` | Public blog listing |
| `pages/blog-post.php` | Public single post view |
| `assets/css/blog.css` | Public blog styles |
| `assets/js/blog-editor.js` | Editor enhancements for blog |
| `rss.php` | RSS feed generator |

### Existing Files to Modify:
| File | Changes |
|------|---------|
| `includes/functions.php` | Add `require_once 'blog.php'`, add `'blog'` to feature map |
| `index.php` | Add blog pages to router, add comment submission handler |
| `admin/header.php` | Add Blog section to sidebar nav |
| `admin/index.php` | Add blog stats to dashboard |
| `admin/settings.php` | Add blog settings section |
| `includes/header.php` | Add RSS auto-discovery, blog nav link, article OG tags |
| `pages/home.php` | Add "Latest from Blog" section |
| `pages/product.php` | Add "Related Articles" section |
| `sitemap.php` | Include blog post and category URLs |
| `assets/js/editor.js` | Add image upload button, drag-drop, media picker |
| `assets/css/admin.css` | Blog editor layout styles |
| `config.php` | Add blog default settings to migration defaults |

---

## Implementation Order

1. **Database migration** — create all blog tables
2. **`includes/blog.php`** — all backend functions
3. **Admin blog pages** — post editor, list, categories, comments
4. **Editor enhancements** — image upload, drag-drop, product embed
5. **Public pages** — blog listing, single post, comments
6. **Router & navigation** — wire everything into the front controller
7. **CSS styling** — public and admin blog styles
8. **E-commerce integration** — product cards, homepage widget, product page widget
9. **RSS feed & SEO** — feed, sitemap, structured data
10. **Settings & dashboard** — feature toggle, blog stats, admin sidebar
