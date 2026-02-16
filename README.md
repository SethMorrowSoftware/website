# Business Website CMS

A self-contained PHP/SQLite content management system for small businesses. Manages products, services, blog content, customer accounts, orders, and online payments. Everything runs on vanilla PHP with no framework dependencies. Customize the company name, contact info, colors, and content through the admin panel.

## Requirements

- PHP 8.0 or higher
- SQLite3 PHP extension (`php-sqlite3`)
- Apache with `mod_rewrite` enabled
- PHP extensions: `fileinfo`, `mbstring`, `session`
- Optional: `mod_headers`, `mod_expires`, `mod_deflate` (for security headers, caching, and compression via `.htaccess`)

## Installation

1. Clone or copy the project into your Apache document root (or a subdirectory):

```bash
git clone <repo-url> /var/www/html/mysite
```

2. Set directory permissions:

```bash
chmod 755 /var/www/html/mysite
chmod -R 775 /var/www/html/mysite/uploads
chmod -R 775 /var/www/html/mysite/database
```

The web server user (e.g., `www-data`) needs write access to `uploads/` and `database/`.

3. Visit the site in a browser. On first load, the database is automatically created at `database/database.sqlite`, the schema is applied, and seed data is inserted.

4. Admin credentials are generated randomly and written to `ADMIN_CREDENTIALS.txt` in the project root. This file is restricted to owner-read-only (`chmod 0600`) and blocked from web access by `.htaccess`. Read the credentials, log in, change your password at `/admin/profile.php`, then delete the file.

5. If deploying to a subdirectory (e.g., `/mysite/`), the `BASE_URL` is auto-detected. If auto-detection fails, set it manually in `config.php`:

```php
define('BASE_URL', '/mysite');
```

## Directory Structure

```
.
├── admin/                     # Admin panel
│   ├── api/                   # AJAX endpoints
│   │   ├── upload.php         #   Media file upload
│   │   ├── delete.php         #   Media file deletion
│   │   ├── reorder.php        #   Drag-and-drop reordering
│   │   ├── paypal-create.php  #   PayPal order creation
│   │   ├── paypal-capture.php #   PayPal payment capture
│   │   └── btcpay-webhook.php #   BTCPay Server webhook
│   ├── index.php              # Dashboard
│   ├── login.php              # Login page
│   ├── settings.php           # Global site settings
│   ├── pages.php              # Page manager
│   ├── page-edit.php          # WYSIWYG page editor
│   ├── products.php           # Product list manager
│   ├── product-edit.php       # Product editor (variants, images, options)
│   ├── categories.php         # Product category manager
│   ├── category-edit.php      # Category editor
│   ├── orders.php             # Order management
│   ├── order-view.php         # Order detail view
│   ├── coupons.php            # Discount code management
│   ├── shipping.php           # Shipping zone and method config
│   ├── blog-posts.php         # Blog post manager
│   ├── blog-post-edit.php     # Blog post editor
│   ├── blog-categories.php    # Blog category manager
│   ├── blog-comments.php      # Comment moderation
│   ├── testimonials.php       # Testimonial manager
│   ├── testimonial-edit.php   # Testimonial editor
│   ├── reviews.php            # Product review moderation
│   ├── inquiries.php          # Contact & order inquiry viewer
│   ├── inquiry-view.php       # Single inquiry detail
│   ├── hero.php               # Hero section editor
│   ├── navigation.php         # Navigation menu builder
│   ├── media.php              # Media library
│   ├── export.php             # Data export (CSV)
│   ├── audit-log.php          # Admin activity log
│   ├── profile.php            # Change admin password
│   ├── header.php             # Admin layout header/sidebar
│   └── footer.php             # Admin layout footer
├── assets/
│   ├── css/
│   │   ├── variables.css      # Design tokens (colors, spacing, fonts)
│   │   ├── style.css          # Main stylesheet
│   │   ├── responsive.css     # Mobile/tablet breakpoints
│   │   ├── blog.css           # Blog-specific styles
│   │   └── admin.css          # Admin panel styles
│   ├── js/
│   │   ├── main.js            # Frontend (nav, scroll, sliders)
│   │   ├── forms.js           # Form validation, multi-step checkout
│   │   ├── ajax-cart.js       # AJAX add-to-cart
│   │   ├── lightbox.js        # Image lightbox/gallery
│   │   ├── editor.js          # WYSIWYG editor for pages
│   │   ├── blog-editor.js     # Blog post editor enhancements
│   │   └── admin.js           # Admin panel (CSRF injection, media picker)
│   └── images/
│       └── placeholders/      # Default placeholder images
├── includes/
│   ├── functions.php          # Core helper functions
│   ├── auth.php               # Authentication and rate limiting
│   ├── customers.php          # Customer account management
│   ├── blog.php               # Blog platform functions
│   ├── search.php             # Full-text search
│   ├── reviews.php            # Product review functions
│   ├── inventory.php          # Stock/availability tracking
│   ├── coupons.php            # Discount code logic
│   ├── shipping.php           # Shipping calculation
│   ├── email.php              # Email notification templates
│   ├── audit.php              # Activity logging
│   ├── migrations.php         # Database migration system
│   ├── header.php             # Public site header/nav
│   └── footer.php             # Public site footer
├── pages/                     # Public page templates
│   ├── home.php               # Homepage (hero, categories, featured products, testimonials)
│   ├── about.php              # About page
│   ├── catalog.php            # Product/service catalog with category tabs
│   ├── product.php            # Product detail (images, variants, reviews)
│   ├── cart.php               # Shopping cart
│   ├── checkout.php           # Multi-step checkout
│   ├── order.php              # Order inquiry form
│   ├── order-complete.php     # Order confirmation
│   ├── order-status.php       # Order tracking
│   ├── payment.php            # Payment methods page
│   ├── paypal-checkout.php    # PayPal integration
│   ├── blog.php               # Blog listing (category, tag, archive, search filters)
│   ├── blog-post.php          # Blog post detail with comments
│   ├── contact.php            # Contact form + map + business info
│   ├── search.php             # Search results
│   ├── account.php            # Customer account dashboard
│   ├── wishlist.php           # Product wishlist
│   ├── login.php              # Customer login
│   ├── register.php           # Customer registration
│   ├── forgot-password.php    # Password reset request
│   ├── reset-password.php     # Password reset form
│   ├── download.php           # Digital product downloads
│   └── custom.php             # Template for admin-created pages
├── uploads/                   # User-uploaded media (gitignored)
│   ├── images/
│   └── videos/
├── config.php                 # Database connection, constants, helpers
├── index.php                  # Front controller / router
├── sitemap.php                # Dynamic XML sitemap
├── rss.php                    # Blog RSS feed
├── robots.txt                 # Crawler directives
└── .htaccess                  # URL rewrites, security headers, caching
```

## Configuration

All runtime configuration is in `config.php`:

| Constant | Default | Description |
|---|---|---|
| `DB_PATH` | `database/database.sqlite` | Path to SQLite database file |
| `UPLOADS_PATH` | `uploads/` | Filesystem path for uploaded files |
| `BASE_URL` | Auto-detected | URL prefix if site is in a subdirectory |
| `SITE_NAME` | `Your Business Name` | Fallback site name |
| `ADMIN_SESSION_TIMEOUT` | `3600` (1 hour) | Admin session inactivity timeout in seconds |

Error display is off by default (`display_errors = 0`). Errors are written to the PHP error log (`log_errors = 1`).

## Feature Flags

Features are toggled via admin settings. Each flag maps to a setting key:

| Feature | Setting Key | Controls |
|---|---|---|
| `catalog` | `enable_catalog` | Product catalog visibility |
| `cart` | `enable_cart` | Shopping cart and checkout |
| `order_inquiry` | `enable_order_inquiry` | Service inquiry form |
| `contact_form` | `enable_contact_form` | Contact form |
| `testimonials` | `enable_testimonials` | Testimonials section |
| `about_page` | `enable_about_page` | About page |
| `reviews` | `enable_reviews` | Product review system |
| `customer_accounts` | `enable_customer_accounts` | Customer registration/login |
| `search` | `enable_search` | Site-wide search |
| `wishlists` | `enable_wishlists` | Product wishlists |
| `blog` | `enable_blog` | Blog platform |
| `phone_header` | `show_phone_header` | Phone number in header |
| `email_header` | `show_email_header` | Email in header |
| `address` | `show_address` | Address in footer |
| `business_hours` | `show_business_hours` | Business hours display |
| `map` | `show_map` | Google Maps embed |

The store type setting (`store_type`) controls the overall business model: `products_and_services`, `products_only`, `services_only`, `digital_only`, or `informational`.

## Admin Panel

Access at `/admin/`. Login required for all pages.

### Dashboard

Overview counts for products, orders, blog posts, testimonials, and media. Displays unread inquiry and pending comment counts with links.

### Site Settings

Key-value configuration for:

- **Company Info**: name, phone, email, address, business hours
- **Branding**: logo, favicon, primary/secondary colors, tagline
- **Social Media**: Facebook, Instagram, Twitter URLs
- **Payment Gateways**: Stripe, PayPal, Square, BTCPay Server configuration
- **Email**: notification address, SMTP settings
- **Features**: toggle individual features on/off

### Content Management

- **Pages**: create, edit, publish/unpublish custom pages with a WYSIWYG editor. System pages (home, about, etc.) cannot be deleted.
- **Hero Sections**: per-page hero banners with title, subtitle, CTA button, background image/video, and overlay opacity.
- **Navigation**: drag-and-drop menu builder with parent-child nesting.

### Catalog

- **Categories**: name, slug, description, icon, image, sort order, visibility.
- **Products**: name, slug, category, description, images (multiple), price, unit, specifications, features. Supports product types: physical, digital, and service. Product options/variants with independent pricing and stock.

### E-commerce

- **Orders**: view and manage customer orders with status tracking (pending, processing, shipped, delivered, cancelled). Supports multiple payment providers.
- **Coupons**: percentage or fixed-amount discounts, minimum order amounts, usage limits, expiration dates. Can be restricted to specific products or categories.
- **Shipping**: zone-based shipping with configurable methods (flat rate, free shipping thresholds).

### Blog

- **Posts**: rich content editor with featured images, excerpts, SEO metadata (meta description, OG image), categories, tags, and scheduling. Supports draft/published/scheduled states. Posts can link to related products.
- **Categories**: organize posts by topic.
- **Comments**: moderation queue with approve/reject/delete. Auto-approval or manual moderation via settings.

### Engagement

- **Testimonials**: customer name, quote, optional photo. Displayed in a homepage slider.
- **Reviews**: moderate customer product reviews.
- **Inquiries**: view contact form submissions and order inquiries. Mark as read or delete.

### System

- **Media Library**: upload images and videos (JPG, PNG, GIF, WebP, MP4, WebM, max 50MB). Drag-and-drop support. Copy URL for use in editors.
- **Export**: download site data as CSV.
- **Audit Log**: tracks all admin actions with user, action type, entity, and timestamp.
- **Profile**: change admin password.

## Public Pages

### Homepage

Hero banner, category/service cards, featured products grid with add-to-cart, "Why Choose Us" section, testimonials slider, and latest blog posts.

### Catalog

Category tab navigation, product cards with images, descriptions, pricing, specifications, features, and add-to-cart or inquiry buttons.

### Product Detail

Image gallery with lightbox, product options/variants, pricing, add-to-cart, specifications, features, customer reviews, and related products.

### Blog

Post listing with category, tag, search, and archive filters. Sidebar with categories, popular posts, tags, and archive months. Individual posts with comments.

### Shopping Cart & Checkout

AJAX add-to-cart, quantity adjustment, coupon code application, multi-step checkout with shipping address, shipping method selection, and payment.

### Customer Accounts

Registration, login, password reset, order history, and account management.

### Other Pages

- **About**: company description, service area, business hours
- **Contact**: form with email notification, embedded map, business info
- **Order Inquiry**: multi-step form for service/product inquiries
- **Search**: full-text search across products, pages, and blog posts
- **Wishlist**: save products for later (requires account)

## Payment Gateways

| Provider | Type | Configuration |
|---|---|---|
| Stripe | Credit/debit cards | `stripe_publishable_key`, `stripe_secret_key` |
| PayPal | PayPal + cards | `paypal_client_id`, `paypal_secret`, `paypal_sandbox` |
| Square | Credit/debit cards | `square_application_id` |
| BTCPay Server | Bitcoin (on-chain + Lightning) | `btcpay_url`, `btcpay_api_key`, `btcpay_store_id`, `btcpay_webhook_secret` |

Each gateway is enabled individually via admin settings. Multiple gateways can be active simultaneously.

## Database

SQLite database with 30+ tables, automatically created on first request. Key table groups:

- **Core**: `settings`, `users`, `pages`, `navigation`, `hero_sections`, `media`
- **Catalog**: `product_categories`, `products`, `product_images`, `product_options`, `product_option_values`
- **E-commerce**: `orders`, `order_items`, `coupons`, `coupon_products`, `coupon_categories`, `shipping_zones`, `shipping_methods`, `download_tokens`
- **Customers**: `customers`, `wishlists`, `password_resets`
- **Blog**: `blog_posts`, `blog_categories`, `blog_tags`, `blog_post_tags`, `blog_comments`, `blog_post_products`
- **Engagement**: `testimonials`, `contact_submissions`, `order_inquiries`, `reviews`
- **System**: `login_attempts`, `form_submissions`, `audit_log`, `migrations`

The database migration system (`includes/migrations.php`) handles schema evolution. Migrations run automatically on each request.

## Security

### Authentication
- Admin passwords hashed with `password_hash()` (bcrypt)
- Customer passwords hashed with bcrypt
- Sessions regenerated on login
- 1-hour admin inactivity timeout
- Rate limiting: 5 failed login attempts per IP per 15-minute window
- Form submission rate limiting (anti-spam)

### CSRF Protection
- All POST forms include a session-bound CSRF token
- AJAX requests send the token via `X-CSRF-Token` header (auto-injected by `admin.js`)
- All state-changing operations require POST method

### Output Encoding
- User-controlled strings escaped with `htmlspecialchars()` via `e()` helper (null-safe)
- Admin-authored HTML sanitized with `sanitizeHtml()` — allows safe formatting tags, strips event handlers and `javascript:`/`data:` URIs

### File Uploads
- MIME type validated via `finfo` (not file extension)
- File extension derived from detected MIME type
- SVG uploads blocked (XSS vector)
- 50MB size limit
- Files stored with generated filenames (no user-controlled paths)

### HTTP Headers (via `.htaccess`)
- `Content-Security-Policy`: restricts scripts, styles, fonts, frames
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`: blocks camera, microphone, geolocation
- `Strict-Transport-Security`: enabled when HTTPS is active

### Access Control
- `database/` and `includes/` directories blocked from direct web access
- `.sqlite`, `.sql`, `.md`, `.log`, `.txt` files blocked from download
- Directory listing disabled

### Session Cookies
- `HttpOnly`, `SameSite=Lax`
- `Secure` flag set automatically over HTTPS

## Email Notifications

Contact and order inquiry submissions send email to the configured `contact_email` address. Order confirmation and password reset emails are sent to customers. Uses PHP `mail()` by default. Failures are logged to the PHP error log.

If `mail()` is not configured on your server, submissions and orders are still saved to the database. Configure a local MTA (Postfix, msmtp) or use an SMTP wrapper.

## Theming

Colors are controlled by CSS custom properties in `assets/css/variables.css`. The admin panel allows overriding primary and secondary colors, which are injected as inline `<style>` overrides in the header.

Default palette:
- Primary: `#2563EB` (blue)
- Secondary: `#F59E0B` (amber)

Font stack uses system fonts with Google Fonts loaded for headings.

## SEO

- Dynamic XML sitemap (`sitemap.php`) including all public pages, products, blog posts, and categories
- Blog RSS feed (`rss.php`)
- Canonical tags on blog pages (listing and individual posts)
- Open Graph meta tags (title, description, image, article metadata)
- Configurable meta descriptions per page and blog post
- `robots.txt` with sitemap reference

## Frontend JavaScript

All frontend JS is vanilla (no jQuery, no build step):

- `main.js`: mobile nav, sticky header, scroll-reveal animations, testimonials slider
- `forms.js`: client-side validation, multi-step checkout with progress indicator
- `ajax-cart.js`: add-to-cart without page reload, cart count badge updates
- `lightbox.js`: product image gallery with zoom
- `editor.js`: WYSIWYG content editor (bold, italic, lists, links, images, headings, HTML source)
- `blog-editor.js`: blog post editor with tag management and scheduling
- `admin.js`: CSRF token injection, media picker, drag-and-drop upload, admin UI

## Backup

The entire site state is in two locations:

1. `database/database.sqlite` — all content, settings, user accounts, orders
2. `uploads/` — uploaded images and videos

```bash
cp database/database.sqlite database/database.sqlite.bak
tar -czf uploads-backup.tar.gz uploads/
```

## Resetting the Database

Delete `database/database.sqlite` and reload the site. A fresh database will be created with seed data and new admin credentials written to `ADMIN_CREDENTIALS.txt`.

## HTTPS

The `.htaccess` file includes a commented-out HTTPS redirect rule. To enforce HTTPS in production, uncomment the redirect lines in `.htaccess`:

```apache
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

The `Strict-Transport-Security` header is automatically applied when HTTPS is active.

## Troubleshooting

**Blank page or 500 error**: Check PHP error log. Common causes: missing `php-sqlite3` extension, incorrect file permissions on `database/` or `uploads/`.

**Clean URLs not working**: Ensure `mod_rewrite` is enabled (`a2enmod rewrite`) and `AllowOverride All` is set in your Apache config.

**Database locked errors**: SQLite uses file-level locking. WAL mode is enabled for better concurrent reads. If persistent, check that no long-running process is holding the database open.

**Uploads failing**: Check that `uploads/` is writable by the web server user. Check PHP `upload_max_filesize` and `post_max_size` settings.

**Email not sending**: Check PHP error log for `[MAIL FAILURE]` entries. Ensure a mail transfer agent is installed.

**Admin credentials file not found**: If you deleted `ADMIN_CREDENTIALS.txt` before saving the password, delete `database/database.sqlite` to regenerate everything.
