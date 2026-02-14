# Hudson Valley Supply & Recycling LLC — Website

A self-contained PHP/SQLite content management system built for a supply and recycling business. Manages roll-off containers, landscaping materials, trucking services, customer inquiries, and online payments. Everything runs on vanilla PHP with no framework dependencies.

## Requirements

- PHP 8.0 or higher
- SQLite3 PHP extension (`php-sqlite3`)
- Apache with `mod_rewrite` enabled
- PHP extensions: `fileinfo`, `mbstring`, `session`
- Optional: `mod_headers`, `mod_expires`, `mod_deflate` (for security headers, caching, and compression — handled in `.htaccess`)

## Installation

1. Clone or copy the project into your Apache document root (or a subdirectory):

```bash
git clone <repo-url> /var/www/html/hvsr
```

2. Set directory permissions:

```bash
chmod 755 /var/www/html/hvsr
chmod -R 775 /var/www/html/hvsr/uploads
chmod -R 775 /var/www/html/hvsr/database
```

The web server user (e.g., `www-data`) needs write access to `uploads/` and `database/`.

3. Visit the site in a browser. On first load, the database is automatically created at `database/database.sqlite`, the schema is applied, and seed data is inserted.

4. Admin credentials are generated randomly and written to `ADMIN_CREDENTIALS.txt` in the project root. This file is restricted to owner-read-only (`chmod 0600`) and is blocked from web access by `.htaccess`. Read the credentials, log in, change your password at `/admin/profile.php`, then delete the file.

5. If deploying to a subdirectory (e.g., `/hvsr/`), the `BASE_URL` is auto-detected. If auto-detection fails, set it manually in `config.php`:

```php
define('BASE_URL', '/hvsr');
```

## Directory Structure

```
.
├── admin/                  # Admin panel
│   ├── api/                # AJAX endpoints (upload, delete, reorder)
│   ├── index.php           # Dashboard
│   ├── login.php           # Login page
│   ├── settings.php        # Global site settings
│   ├── pages.php           # Page manager
│   ├── page-edit.php       # WYSIWYG page editor
│   ├── products.php        # Product list manager
│   ├── product-edit.php    # Product editor
│   ├── categories.php      # Product category manager
│   ├── category-edit.php   # Category editor
│   ├── containers.php      # Roll-off container manager
│   ├── container-edit.php  # Container editor
│   ├── testimonials.php    # Testimonial manager
│   ├── testimonial-edit.php# Testimonial editor
│   ├── hero.php            # Hero section editor
│   ├── navigation.php      # Navigation menu manager
│   ├── media.php           # Media library (upload/browse/delete)
│   ├── inquiries.php       # Contact & order inquiry viewer
│   ├── inquiry-view.php    # Single inquiry detail view
│   ├── profile.php         # Change password
│   ├── header.php          # Admin layout header
│   └── footer.php          # Admin layout footer
├── assets/
│   ├── css/
│   │   ├── variables.css   # Design tokens (colors, spacing, fonts)
│   │   ├── style.css       # Main stylesheet
│   │   ├── responsive.css  # Mobile/tablet breakpoints
│   │   └── admin.css       # Admin panel styles
│   ├── js/
│   │   ├── main.js         # Frontend JS (nav, scroll, sliders)
│   │   ├── forms.js        # Form validation and multi-step order form
│   │   ├── editor.js       # WYSIWYG editor for admin page editing
│   │   └── admin.js        # Admin panel JS (CSRF injection, media picker)
│   └── images/
│       └── placeholders/   # Default placeholder images
├── database/
│   ├── schema.sql          # Full database schema
│   └── seed.php            # Initial data seeder
├── includes/
│   ├── functions.php       # Shared helper functions
│   ├── auth.php            # Authentication and rate limiting
│   ├── header.php          # Public site header/nav
│   └── footer.php          # Public site footer
├── pages/                  # Public page templates
│   ├── home.php            # Homepage (hero, services, testimonials, CTA)
│   ├── about.php           # About page
│   ├── containers.php      # Roll-off container listings
│   ├── materials.php       # Materials/products catalog with category tabs
│   ├── trucking.php        # Trucking services
│   ├── contact.php         # Contact form + map + business info
│   ├── order.php           # Multi-step order inquiry form
│   ├── payment.php         # SwipeSimple payment embed
│   └── custom.php          # Template for admin-created custom pages
├── uploads/                # User-uploaded media (gitignored)
│   ├── images/
│   └── videos/
├── config.php              # Database connection, settings, helpers
├── index.php               # Front controller / router
├── sitemap.php             # Dynamic XML sitemap generator
├── robots.txt              # Crawler directives
└── .htaccess               # URL rewrites, security headers, caching
```

## Configuration

All runtime configuration is in `config.php`:

| Constant | Default | Description |
|---|---|---|
| `DB_PATH` | `database/database.sqlite` | Path to SQLite database file |
| `UPLOADS_PATH` | `uploads/` | Filesystem path for uploaded files |
| `BASE_URL` | Auto-detected | URL prefix if site is in a subdirectory |
| `SITE_NAME` | `Hudson Valley Supply & Recycling LLC` | Fallback site name |
| `ADMIN_SESSION_TIMEOUT` | `3600` (1 hour) | Admin session inactivity timeout in seconds |

Error display is off by default (`display_errors = 0`). Errors are written to the PHP error log (`log_errors = 1`).

## Admin Panel

Access the admin panel at `/admin/`. Login is required for all admin pages.

### Dashboard (`/admin/index.php`)

Shows counts for products, containers, testimonials, pages, and media. Displays unread contact and order inquiry counts with links to view them.

### Site Settings (`/admin/settings.php`)

All settings are stored as key-value pairs in the `settings` table. Configurable fields:

- **Company Info**: name, phone, email, address, business hours
- **Branding**: logo upload, favicon upload, primary color, secondary color, tagline
- **Content**: about text, service area description, footer text (HTML)
- **Social Media**: Facebook, Instagram, Twitter URLs
- **Integrations**: Google Maps embed URL, SwipeSimple payment link and embed code
- **Contact**: notification email address (receives form submissions)

### Pages (`/admin/pages.php`)

Lists all pages. System pages (home, about, containers, etc.) cannot be deleted. Custom pages can be created, edited, published/unpublished, and deleted. Each page has:

- Title, URL slug, meta description
- Rich content via WYSIWYG editor (TinyMCE-style toolbar built with `editor.js`)
- Published/draft status
- Navigation visibility toggle

### Products (`/admin/products.php`)

Manage material products (mulch, stone, topsoil, sand, salt). Each product has:

- Name, slug, category assignment
- Description, image, price, unit (per yard, per ton, etc.)
- Visibility toggle, sort order

### Categories (`/admin/categories.php`)

Manage product categories. Each has a name, slug, description, image, sort order, and visibility toggle. Deleting a category cascades to its products.

### Containers (`/admin/containers.php`)

Manage roll-off container listings. Each container has:

- Name, size (numeric), unit
- Dimensions, description, use cases
- Image, price, price note
- Visibility and sort order

### Testimonials (`/admin/testimonials.php`)

Manage customer testimonials. Each has a customer name, quote, optional photo, visibility toggle, and sort order. Displayed in a slider on the homepage.

### Hero Sections (`/admin/hero.php`)

Each page can have a hero banner with:

- Title, subtitle
- Call-to-action text and link
- Background image or video upload
- Overlay opacity slider

### Navigation (`/admin/navigation.php`)

Drag-and-drop reordering of navigation menu items. Add custom links or link to existing pages. Items can be shown/hidden. Supports parent-child nesting. Reorder is saved via AJAX (`/admin/api/reorder.php`).

### Media Library (`/admin/media.php`)

Upload and manage images and videos. Supports drag-and-drop upload. Accepted formats: JPG, PNG, GIF, WebP, MP4, WebM (max 50MB per file). Files are stored in `uploads/images/` or `uploads/videos/`. Copy URL to clipboard for use in content editors.

### Inquiries (`/admin/inquiries.php`)

View contact form submissions and order inquiries in tabbed interface. Mark individual items as read, mark all as read, or delete. Click into individual inquiries for full detail view.

### Profile (`/admin/profile.php`)

Change the admin password. Requires current password for verification.

## Public Pages

### Home (`/?page=home` or `/`)

Hero banner, three service cards (containers, materials, trucking), about preview, testimonials slider, call-to-action section.

### About (`/?page=about`)

Company description, service area, business hours, and info pulled from site settings.

### Containers (`/?page=containers`)

Lists all visible containers with size, dimensions, description, use cases, pricing, and images. Each has a "Request This Container" link to the order form.

### Materials (`/?page=materials`)

Tabbed category interface. Clicking a category tab filters the product grid. Shows product cards with image, name, description, price, and unit.

### Trucking (`/?page=trucking`)

Static service page with trucking capabilities, service list, and CTA.

### Contact (`/?page=contact`)

Contact form (name, email, phone, message), embedded Google Map, business hours, phone/email links. Submissions are stored in the database and trigger an email notification to the configured contact address.

### Order Inquiry (`/?page=order`)

Multi-step form:
1. Select service type (containers, materials, trucking)
2. Select specific products/containers
3. Enter delivery address and preferred date
4. Enter contact information and notes

Submissions are stored in `order_inquiries` and trigger an email notification.

### Payment (`/?page=payment`)

Displays a SwipeSimple payment widget if configured in settings. Otherwise shows a message to contact the business.

### Custom Pages

Pages created through the admin panel are served at `/?page=<slug>` (or `/<slug>` with clean URLs). Content is rendered through `pages/custom.php` with HTML sanitization applied.

## URL Routing

The `.htaccess` file provides clean URLs:

- `/about` rewrites to `index.php?page=about`
- Same for: `home`, `containers`, `materials`, `trucking`, `contact`, `order`, `payment`

Custom page slugs are not in the rewrite rules — they use the query string format (`?page=slug`) or need manual addition to `.htaccess`.

The `index.php` front controller checks the `page` parameter against an allowlist of system pages, then falls back to a database lookup for custom pages, then falls back to the homepage with a 404 status.

## Database Schema

SQLite database with 13 tables:

| Table | Purpose |
|---|---|
| `settings` | Key-value site configuration |
| `users` | Admin accounts (bcrypt password hashes) |
| `pages` | CMS pages (system and custom) |
| `product_categories` | Material categories |
| `products` | Individual products with category FK |
| `containers` | Roll-off container listings |
| `testimonials` | Customer testimonials |
| `contact_submissions` | Contact form entries |
| `order_inquiries` | Order form entries |
| `navigation` | Menu items with parent-child support |
| `media` | Uploaded file metadata |
| `hero_sections` | Per-page hero banner config |
| `login_attempts` | Failed login tracking for rate limiting |

The database is created automatically on first request. Schema is in `database/schema.sql`, seed data in `database/seed.php`.

## Security

### Authentication
- Passwords hashed with `password_hash()` (bcrypt)
- Sessions regenerated on login (`session_regenerate_id`)
- 1-hour inactivity timeout
- Rate limiting: 5 failed login attempts per IP per 15-minute window

### CSRF Protection
- All forms include a CSRF token (session-bound, verified server-side)
- AJAX requests send the token via `X-CSRF-Token` header (auto-injected by `admin.js`)
- All state-changing operations require POST method

### Output Encoding
- User-controlled strings escaped with `htmlspecialchars()` via `e()` helper
- Admin-authored HTML content sanitized with `sanitizeHtml()` — allows safe formatting tags, strips event handlers and `javascript:`/`data:` URIs

### File Uploads
- MIME type validated via `finfo` (not file extension)
- File extension derived from detected MIME type
- SVG uploads blocked (XSS vector)
- 50MB size limit
- Files stored with generated filenames (no user-controlled paths)

### HTTP Headers (via `.htaccess`)
- `Content-Security-Policy`: restricts scripts, styles, fonts, frames to self and specific CDNs
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: strict-origin-when-cross-origin`
- `Permissions-Policy`: blocks camera, microphone, geolocation
- `Strict-Transport-Security`: enabled when HTTPS is active

### Access Control
- `database/` and `includes/` directories blocked from direct web access
- `.sqlite`, `.sql`, `.md`, `.log`, `.txt` files blocked from download
- Directory listing disabled (`Options -Indexes`)

### Session Cookies
- `HttpOnly`, `SameSite=Lax`
- `Secure` flag set automatically when serving over HTTPS

## Email Notifications

Contact form and order inquiry submissions send email to the address configured in `contact_email` setting. Uses PHP `mail()` function. Failures are logged to the PHP error log rather than silently suppressed.

If `mail()` is not configured on your server, submissions are still saved to the database. Configure a local MTA (Postfix, msmtp) or use an SMTP wrapper if outbound email is needed.

## Theming

Colors are controlled by CSS custom properties defined in `assets/css/variables.css`. The admin panel allows overriding the primary and secondary colors, which are injected as inline `<style>` overrides in the header.

Default palette:
- Primary: `#1B4D3E` (dark green)
- Secondary: `#D4A843` (gold)

Font stack uses system fonts with Google Fonts loaded for headings.

## Frontend JavaScript

All frontend JS is vanilla (no jQuery, no build step):

- `main.js`: Mobile nav toggle, sticky header on scroll, scroll-reveal animations, testimonials auto-slider
- `forms.js`: Client-side form validation, multi-step order form with step navigation and progress indicator
- `editor.js`: Simple WYSIWYG content editor for admin page editing (bold, italic, lists, links, images, headings, HTML source view)
- `admin.js`: CSRF token auto-injection for XHR/fetch, media picker integration, drag-and-drop upload area, admin UI interactions

## Sitemap

`sitemap.php` generates a dynamic XML sitemap including all system pages and published custom pages from the database. Referenced in `robots.txt`.

## Backup

The entire site state is in two locations:

1. `database/database.sqlite` — all content, settings, user accounts
2. `uploads/` — uploaded images and videos

To back up:

```bash
cp database/database.sqlite database/database.sqlite.bak
tar -czf uploads-backup.tar.gz uploads/
```

To restore, replace the files and ensure permissions are correct.

## Resetting the Database

Delete `database/database.sqlite` and reload the site. A fresh database will be created with seed data and new admin credentials written to `ADMIN_CREDENTIALS.txt`.

## HTTPS

The `.htaccess` file includes a commented-out HTTPS redirect rule. To enforce HTTPS in production, uncomment lines 8-9 in `.htaccess`:

```apache
RewriteCond %{HTTPS} !=on
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

The `Strict-Transport-Security` header is automatically applied when HTTPS is active.

## Troubleshooting

**Blank page or 500 error**: Check PHP error log. Common causes: missing `php-sqlite3` extension, incorrect file permissions on `database/` or `uploads/`.

**Clean URLs not working**: Ensure `mod_rewrite` is enabled (`a2enmod rewrite`) and `AllowOverride All` is set for the site directory in your Apache config.

**Database locked errors**: SQLite uses file-level locking. The schema enables WAL mode (`journal_mode=WAL`) for better concurrent read performance. If you get persistent lock errors, check that no long-running process is holding the database open.

**Uploads failing**: Check that `uploads/` is writable by the web server user. Check PHP `upload_max_filesize` and `post_max_size` (set to 50M/55M in `.htaccess` but may be overridden by `php.ini`).

**Email not sending**: Check PHP error log for `[MAIL FAILURE]` entries. Ensure a mail transfer agent is installed and configured on the server.

**Admin credentials file not found**: If you deleted `ADMIN_CREDENTIALS.txt` before saving the password, delete `database/database.sqlite` to regenerate everything.
