# Business Website CMS

A self-contained PHP/MySQL content management system for small businesses. Manages products, services, blog content, customer accounts, orders, and online payments. Everything runs on vanilla PHP with no framework dependencies. Customize the company name, contact info, colors, and content through the admin panel.

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with `mod_rewrite` enabled
- PHP extensions: `pdo_mysql`, `fileinfo`, `mbstring`, `session`, `curl`
- Optional: `mod_headers`, `mod_expires`, `mod_deflate` (for security headers, caching, and compression via `.htaccess`)

## Installation

1. Clone or copy the project into your Apache document root (or a subdirectory):

```bash
git clone <repo-url> /var/www/html/mysite
```

2. Create a MySQL database and user:

```sql
CREATE DATABASE business_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cms_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON business_cms.* TO 'cms_user'@'localhost';
FLUSH PRIVILEGES;
```

3. Configure database credentials via environment variables or edit `config.php`:

```bash
export DB_HOST=127.0.0.1
export DB_NAME=business_cms
export DB_USER=cms_user
export DB_PASS=your_password
```

4. Set directory permissions:

```bash
chmod 755 /var/www/html/mysite
chmod -R 775 /var/www/html/mysite/uploads
```

The web server user (e.g., `www-data`) needs write access to `uploads/`.

5. Visit the site in a browser. On first load, the schema is applied to the MySQL database and seed data is inserted.

6. Admin credentials are generated randomly and written to `ADMIN_CREDENTIALS.txt` in the project root. This file is restricted to owner-read-only (`chmod 0600`) and blocked from web access by `.htaccess`. Read the credentials, log in, change your password at `/admin/profile.php`, then delete the file.

7. If deploying to a subdirectory (e.g., `/mysite/`), the `BASE_URL` is auto-detected. If auto-detection fails, set it manually in `config.php`:

```php
define('BASE_URL', '/mysite');
```

## Directory Structure

```
.
├── admin/                     # Admin panel
│   ├── api/                   # AJAX endpoints & webhooks
│   │   ├── upload.php         #   Media file upload
│   │   ├── delete.php         #   Media file deletion
│   │   ├── reorder.php        #   Drag-and-drop reordering
│   │   ├── paypal-create.php  #   PayPal order creation
│   │   ├── paypal-capture.php #   PayPal payment capture
│   │   ├── stripe-webhook.php #   Stripe payment webhook
│   │   ├── btcpay-webhook.php #   BTCPay Server webhook
│   │   ├── square-webhook.php #   Square payment webhook
│   │   └── healthcheck.php    #   Liveness probe & diagnostics
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
├── database/
│   ├── migrations/            # Numbered migration files (.sql and .php)
│   ├── schema.sql             # Initial database schema
│   └── seed.php               # Seed data (settings, sample content)
├── uploads/                   # User-uploaded media (gitignored)
│   ├── .htaccess              # Blocks PHP execution in uploads
│   ├── images/
│   ├── videos/
│   └── downloads/             # Digital product files (blocked from direct access)
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
| `DB_HOST` | `127.0.0.1` (env `DB_HOST`) | MySQL server hostname |
| `DB_PORT` | `3306` (env `DB_PORT`) | MySQL server port |
| `DB_NAME` | `business_cms` (env `DB_NAME`) | MySQL database name |
| `DB_USER` | `root` (env `DB_USER`) | MySQL username |
| `DB_PASS` | _(empty)_ (env `DB_PASS`) | MySQL password |
| `UPLOADS_PATH` | `uploads/` | Filesystem path for uploaded files |
| `BASE_URL` | Auto-detected | URL prefix if site is in a subdirectory |
| `SITE_NAME` | `Your Business Name` | Fallback site name |
| `ADMIN_SESSION_TIMEOUT` | `3600` (1 hour) | Admin session inactivity timeout in seconds |

Additional settings configured through the admin panel:

| Setting Key | Default | Description |
|---|---|---|
| `site_url` | _(empty)_ | Canonical base URL for external links (payment callbacks, emails). Auto-detected if blank. |
| `currency_code` | _(empty)_ | ISO currency code (e.g. `USD`) |
| `currency_symbol` | `$` | Currency symbol displayed in prices |
| `tax_rate` | `0` | Tax rate percentage applied to cart orders |
| `trusted_proxy_enabled` | `0` | Trust `X-Forwarded-*` headers from proxy IPs |
| `trusted_proxy_ips` | _(empty)_ | Comma-separated allowlist of proxy IPs (supports CIDR) |
| `healthcheck_token` | _(empty)_ | Bearer token for authenticated healthcheck diagnostics |

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

Blog-specific settings:

| Setting Key | Default | Controls |
|---|---|---|
| `blog_page_title` | `Blog` | Blog listing page title |
| `blog_posts_per_page` | `9` | Number of posts per page |
| `blog_allow_comments` | `1` | Enable comment system on posts |
| `blog_comment_moderation` | `1` | Require approval before comments are published |
| `blog_show_author` | `1` | Display author name on posts |
| `blog_show_sidebar` | `1` | Show sidebar with categories, tags, and popular posts |

The store type setting (`store_type`) controls the overall business model: `products_and_services`, `products_only`, `services_only`, `digital_only`, or `informational`.

## Admin Panel

Access at `/admin/`. Login required for all pages.

### Dashboard

Overview counts for products, orders, blog posts, testimonials, and media. Displays unread inquiry and pending comment counts with links.

### Site Settings

Key-value configuration for:

- **Store Configuration**: store type, business type, feature toggles
- **Company Info**: name, phone, email, address, business hours
- **Branding**: logo, favicon, primary/secondary colors, tagline
- **Social Media**: Facebook, Instagram, Twitter URLs
- **E-commerce**: currency code/symbol, tax rate
- **Payment Gateways**: Stripe, PayPal, Square, BTCPay Server, SwipeSimple configuration
- **Email**: notification address, full SMTP configuration (host, port, encryption, credentials), test email sender
- **Blog**: page title, posts per page, comments, moderation, author display, sidebar
- **Maintenance Mode**: enable/disable with custom message (admin panel remains accessible)
- **Advanced**: canonical site URL, trusted proxy configuration (IP allowlist with CIDR support)
- **Features**: toggle individual features on/off

### Content Management

- **Pages**: create, edit, publish/unpublish custom pages with a WYSIWYG editor. System pages (home, about, etc.) cannot be deleted.
- **Hero Sections**: per-page hero banners with title, subtitle, CTA button, background image/video, and overlay opacity.
- **Navigation**: drag-and-drop menu builder with parent-child nesting.

### Catalog

- **Categories**: name, slug, description, icon, image, sort order, visibility.
- **Products**: name, slug, category, description, images (multiple), price, unit, specifications, features. Supports product types: physical, digital, and service. Product options/variants with independent pricing and stock.

### E-commerce

- **Orders**: view and manage customer orders with status tracking (pending, processing, shipped, delivered, cancelled, needs_review). Supports multiple payment providers.
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
| Stripe | Credit/debit cards | `stripe_enabled`, `stripe_publishable_key`, `stripe_secret_key`, `stripe_webhook_secret` |
| PayPal | PayPal + cards | `paypal_enabled`, `paypal_client_id`, `paypal_secret`, `paypal_sandbox` |
| Square | Credit/debit cards | `square_enabled`, `square_application_id`, `square_access_token`, `square_location_id`, `square_sandbox`, `square_webhook_signature_key` |
| BTCPay Server | Bitcoin (on-chain + Lightning) | `btcpay_enabled`, `btcpay_url`, `btcpay_api_key`, `btcpay_store_id`, `btcpay_webhook_secret` |
| SwipeSimple | Manual payment link | `swipesimple_link`, `swipesimple_embed` |

Each gateway is enabled individually via admin settings. Multiple gateways can be active simultaneously. Stripe and Square use webhook endpoints for payment confirmation (`admin/api/stripe-webhook.php`, `admin/api/square-webhook.php`). BTCPay Server uses `admin/api/btcpay-webhook.php`. Webhooks verify signatures using the configured secrets to prevent spoofing.

## Database

MySQL database with 30+ tables, automatically created on first request. Key table groups:

- **Core**: `settings`, `users`, `pages`, `navigation`, `hero_sections`, `media`
- **Catalog**: `product_categories`, `products`, `product_images`, `product_options`, `product_option_values`
- **E-commerce**: `orders`, `order_items`, `coupons`, `coupon_products`, `coupon_categories`, `shipping_zones`, `shipping_methods`, `download_tokens`, `webhook_events`
- **Customers**: `customers`, `wishlists`, `password_resets`
- **Blog**: `blog_posts`, `blog_categories`, `blog_tags`, `blog_post_tags`, `blog_comments`, `blog_post_products`
- **Engagement**: `testimonials`, `contact_submissions`, `order_inquiries`, `reviews`
- **System**: `login_attempts`, `form_submissions`, `audit_log`, `migrations`

### Migrations

The database migration system (`includes/migrations.php`) handles schema evolution. Migration files live in `database/migrations/` and support both `.sql` and `.php` formats. PHP migrations return a closure that receives the `$db` (PDO) instance. Migrations are tracked in the `migrations` table and run automatically on each request.

Migrations are protected by a MySQL advisory lock (`GET_LOCK`) so concurrent requests never execute DDL simultaneously. If the lock cannot be acquired within 10 seconds, migrations are skipped for that request. Schema introspection helpers (`columnExists()`, `tableExists()`, `indexExists()`) use `INFORMATION_SCHEMA` for idempotent DDL checks.

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
- PHP execution disabled in `uploads/` via `.htaccess` (engine off, CGI disabled)
- Direct access to `uploads/downloads/` blocked (digital files served through PHP)

### HTTP Headers (via `.htaccess`)
- `Content-Security-Policy`: restricts scripts, styles, fonts, frames
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`: blocks camera, microphone, geolocation
- `Strict-Transport-Security`: enabled when HTTPS is active

### Access Control
- `database/`, `includes/`, and `uploads/downloads/` directories blocked from direct web access
- `.sqlite`, `.sql`, `.md`, `.log`, `.txt` files blocked from download
- Directory listing disabled

### Session Cookies
- `HttpOnly`, `SameSite=Lax`
- `Secure` flag set automatically over HTTPS
- Strict mode enabled (`session.use_strict_mode = 1`)

### Trusted Proxy
- `X-Forwarded-For` and `X-Forwarded-Proto` headers are only trusted when `trusted_proxy_enabled` is on and `REMOTE_ADDR` matches the `trusted_proxy_ips` allowlist
- Supports exact IP matching and CIDR notation (IPv4 and IPv6)
- Fail-closed: enabling trusted proxy without an allowlist does not trust any peer
- `getClientIp()` resolves the real client IP with proxy awareness
- `isRequestSecure()` detects HTTPS through direct connection or trusted proxy headers

## Email Notifications

Contact and order inquiry submissions send email to the configured `contact_email` address. Order confirmation and password reset emails are sent to customers.

Email is sent via SMTP when configured (host, port, encryption, credentials in admin settings). Falls back to PHP `mail()` if SMTP is not configured. A test email sender in the admin panel verifies that email delivery is working. Failures are logged to the PHP error log.

If neither SMTP nor `mail()` is configured on your server, submissions and orders are still saved to the database.

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

1. MySQL database (`business_cms`) — all content, settings, user accounts, orders
2. `uploads/` — uploaded images and videos

```bash
mysqldump -u cms_user -p business_cms > business_cms_backup.sql
tar -czf uploads-backup.tar.gz uploads/
```

## Resetting the Database

Drop and recreate the database, then reload the site. A fresh schema will be applied with seed data and new admin credentials written to `ADMIN_CREDENTIALS.txt`.

```bash
mysql -u cms_user -p -e "DROP DATABASE business_cms; CREATE DATABASE business_cms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

## Testing

A lightweight test suite covers the side-effect-free helpers (no database required). Run all suites with:

```bash
php tests/run.php
```

Or run an individual suite directly, e.g. `php tests/sanitize_html_test.php` or `php tests/pure_functions_test.php`. Each suite exits non-zero on failure. The same lint + test steps run automatically in CI (`.github/workflows/ci.yml`) on every push and pull request.

## Maintenance Mode

Enable maintenance mode from admin settings to display a maintenance page to all public visitors. The admin panel (`/admin/`) remains accessible during maintenance. Configure a custom maintenance message from the settings page.

## Healthcheck

A healthcheck endpoint is available at `/admin/api/healthcheck.php`:

- **Unauthenticated requests** receive a minimal liveness response (`200 OK` or `503`) with no internal details. Checks database connectivity and uploads directory writability.
- **Authenticated requests** (admin session or `Authorization: Bearer <token>` matching the `healthcheck_token` setting) receive full diagnostics: migration drift detection, webhook/payment gateway readiness, and mail configuration status.

## HTTPS

The `.htaccess` file includes a commented-out HTTPS redirect rule. To enforce HTTPS in production, uncomment the redirect lines in `.htaccess`:

```apache
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

The `Strict-Transport-Security` header is automatically applied when HTTPS is active.

## Troubleshooting

**Blank page or 500 error**: Check PHP error log. Common causes: missing `pdo_mysql` extension, incorrect database credentials, or wrong file permissions on `uploads/`.

**Clean URLs not working**: Ensure `mod_rewrite` is enabled (`a2enmod rewrite`) and `AllowOverride All` is set in your Apache config.

**Database connection errors**: Verify MySQL is running, credentials are correct, and the database exists. Check that PHP has the `pdo_mysql` extension enabled.

**Uploads failing**: Check that `uploads/` is writable by the web server user. Check PHP `upload_max_filesize` and `post_max_size` settings.

**Email not sending**: Check PHP error log for `[MAIL FAILURE]` entries. If using SMTP, verify host, port, and credentials in admin settings. Use the test email feature in admin settings to diagnose. If not using SMTP, ensure a local mail transfer agent is installed.

**Admin credentials file not found**: If you deleted `ADMIN_CREDENTIALS.txt` before saving the password, drop and recreate the MySQL database (see "Resetting the Database" above) to regenerate everything.
