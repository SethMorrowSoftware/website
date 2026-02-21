# E-Commerce Platform Roadmap: Brainstorm & Enhancement Proposals

> A comprehensive set of ideas for transforming this self-hosted PHP e-commerce platform into a
> highly customizable, all-in-one solution for a wide range of small business sites.

---

## Table of Contents

1. [Plugin / Extension System](#1-plugin--extension-system)
2. [Theming Engine](#2-theming-engine)
3. [Multi-Admin Roles & Permissions](#3-multi-admin-roles--permissions)
4. [REST API for Third-Party Integrations](#4-rest-api-for-third-party-integrations)
5. [Internationalization & Localization (i18n/l10n)](#5-internationalization--localization-i18nl10n)
6. [Advanced Analytics & Reporting Dashboard](#6-advanced-analytics--reporting-dashboard)
7. [Subscription & Recurring Billing](#7-subscription--recurring-billing)
8. [Multi-Vendor / Marketplace Mode](#8-multi-vendor--marketplace-mode)
9. [Advanced SEO Toolkit](#9-advanced-seo-toolkit)
10. [Image Optimization & CDN Integration](#10-image-optimization--cdn-integration)
11. [Backup, Export & GDPR Compliance](#11-backup-export--gdpr-compliance)
12. [Enhanced Email & Notification System](#12-enhanced-email--notification-system)
13. [Advanced Shipping & Tax Engine](#13-advanced-shipping--tax-engine)
14. [Customer Loyalty & Marketing Tools](#14-customer-loyalty--marketing-tools)
15. [Developer Experience & Deployment](#15-developer-experience--deployment)
16. [Accessibility & Performance](#16-accessibility--performance)
17. [Security Hardening](#17-security-hardening)
18. [Content & Layout Builder](#18-content--layout-builder)

---

## 1. Plugin / Extension System

**Why:** The single most impactful change for customizability. A plugin system lets site owners
add or remove features without touching core code, and lets the community contribute extensions.

**Current state:** All features are hard-coded in `includes/` and `admin/`. There is no hook or
event system. Adding a feature means editing core files.

**Proposals:**

- **Event/Hook dispatcher:** Create a lightweight event system (similar to WordPress actions/filters).
  Define named hooks at key lifecycle points: `before_checkout`, `after_order_created`,
  `product_display`, `admin_menu_render`, `cart_item_added`, etc. Plugins register callbacks for
  these hooks.
- **Plugin directory structure:** Each plugin lives in `plugins/<plugin-name>/` with a manifest
  file (`plugin.json`) declaring name, version, author, required hooks, and an entry point
  (`init.php`). On boot, the system scans active plugins and loads them.
- **Admin UI for plugins:** Add a "Plugins" section in the admin panel to enable/disable installed
  plugins, view their metadata, and configure plugin-specific settings.
- **Plugin settings storage:** Add a `plugin_settings` table (plugin_name, key, value) so plugins
  can persist their own configuration without modifying the core schema.
- **Safe implementation path:** Start by defining hooks in `index.php` and key `includes/` files
  at natural extension points. Existing functionality continues to work as-is; plugins are purely
  additive. No existing code needs to change behavior, only gain `do_action()` / `apply_filter()`
  calls at strategic points.

**Example plugin ideas:** Custom shipping calculators, loyalty points, abandoned cart recovery,
social login, live chat widget, advanced product filters, wishlist sharing, PDF invoice generator.

---

## 2. Theming Engine

**Why:** Different businesses need radically different visual identities. Right now the look is
controlled by a handful of CSS variables and hard-coded template files.

**Current state:** Templates live in `pages/` and `includes/header.php`/`footer.php`. Styling
is in `assets/css/` with CSS custom properties for primary/secondary colors.

**Proposals:**

- **Theme directory structure:** `themes/<theme-name>/` containing template overrides, CSS, JS,
  and a `theme.json` manifest (name, version, description, thumbnail, settings schema). The
  active theme is stored in the `settings` table.
- **Template override system:** When rendering a page, check if the active theme has an override
  file (e.g., `themes/starter/pages/catalog.php`). If yes, use it. If not, fall back to the
  default `pages/catalog.php`. This is non-breaking: the existing templates become the "default"
  theme.
- **Theme settings schema:** Let each theme declare customizable variables in `theme.json`
  (colors, fonts, layout options, component visibility). The admin panel renders a settings form
  dynamically from this schema and injects the values as CSS variables.
- **Expanded CSS variable palette:** Go beyond primary/secondary colors. Add variables for:
  background colors, text colors, border radius, spacing scale, font families (heading, body,
  mono), button styles, card shadows, header/footer background, accent colors.
- **Layout variants:** Themes can define layout options (sidebar left/right/none, grid vs. list
  catalog, full-width vs. boxed). These are stored as settings and applied via CSS classes on
  the `<body>` tag.
- **Live preview:** In the admin panel, show a preview iframe of the site with theme changes
  applied in real-time before saving, so owners can experiment safely.
- **Child themes:** Allow a theme to declare a parent theme, inheriting all templates and styles
  and only overriding what it changes.

---

## 3. Multi-Admin Roles & Permissions

**Why:** Real businesses have multiple people: an owner, a content editor, an order fulfillment
person, an accountant. A single admin account is a limitation and a security risk.

**Current state:** Single admin user created at bootstrap. One password, one session, full access
to everything.

**Proposals:**

- **Roles table:** Create a `roles` table with columns: id, name, description, permissions (JSON
  array of permission slugs). Ship with default roles: `super_admin`, `store_manager`,
  `content_editor`, `order_fulfillment`, `viewer`.
- **Permissions granularity:** Define permissions like: `manage_products`, `manage_orders`,
  `manage_pages`, `manage_blog`, `manage_settings`, `manage_users`, `view_analytics`,
  `manage_coupons`, `manage_shipping`, `manage_media`. Each admin page checks for the relevant
  permission before rendering.
- **Users table expansion:** Add `role_id` foreign key to the existing `users` table. The
  bootstrap user gets `super_admin`.
- **Admin UI:** A "Team" or "Users" section where super_admins can invite/create additional admin
  accounts, assign roles, and revoke access.
- **Audit log integration:** The existing audit log already tracks user actions. With multiple
  users, this becomes much more valuable for accountability.
- **Safe implementation:** Wrap existing `requireLogin()` with a `requirePermission('slug')`
  helper. Super_admin bypasses all checks. Existing single-admin setups continue to work
  unchanged.

---

## 4. REST API for Third-Party Integrations

**Why:** Modern businesses use many tools: mobile apps, POS systems, accounting software,
inventory management, CRM, marketing platforms. An API lets them all connect.

**Current state:** Only internal AJAX endpoints exist under `admin/api/`. No public API. No
authentication tokens. No versioning.

**Proposals:**

- **API versioning:** All endpoints under `/api/v1/`. Versioning from day one prevents breaking
  changes later.
- **Authentication:** API key + secret scheme. Store keys in an `api_keys` table with: key,
  hashed_secret, label, permissions, rate_limit, created_at, last_used_at. Keys are generated
  from the admin panel. Authenticate via `Authorization: Bearer <key>:<secret>` header or HMAC
  signature.
- **Core endpoints:**
  - `GET/POST /api/v1/products` - List/create products
  - `GET/PUT/DELETE /api/v1/products/{id}` - Read/update/delete product
  - `GET/POST /api/v1/orders` - List/create orders
  - `GET/PUT /api/v1/orders/{id}` - Read/update order status
  - `GET /api/v1/customers` - List customers
  - `GET/PUT /api/v1/inventory` - Stock levels
  - `GET/POST /api/v1/coupons` - Coupon management
  - `GET /api/v1/analytics/sales` - Sales summary
- **Response format:** JSON with consistent envelope: `{ "data": ..., "meta": { "page", "total" }, "errors": [] }`.
- **Rate limiting:** Per-key rate limits tracked in-memory or via a `rate_limits` table.
  Return `429 Too Many Requests` with `Retry-After` header.
- **Webhooks (outbound):** Allow site owners to register webhook URLs that receive POST
  notifications on events: `order.created`, `order.status_changed`, `product.stock_low`,
  `customer.registered`. Store subscriptions in a `webhook_subscriptions` table. Deliver with
  retry logic and HMAC signature verification.
- **Safe implementation:** New `/api/` directory, completely separate from existing admin AJAX
  endpoints. No changes to existing code paths. The API calls the same functions in `includes/`
  that the admin panel uses.

---

## 5. Internationalization & Localization (i18n/l10n)

**Why:** Many small businesses serve multilingual communities or operate in non-English-speaking
regions. Even English-only sites benefit from centralized string management.

**Current state:** All user-facing strings are hard-coded in English throughout PHP templates and
JS files.

**Proposals:**

- **Translation function:** Introduce a `__('string_key')` or `t('string_key')` function that
  looks up a translation. Default behavior: return the key itself (which is the English string),
  so existing templates work without modification.
- **Language files:** `languages/<locale>.php` returning an associative array of
  `'English string' => 'Translated string'`. Start with `en.php` as the identity mapping.
  Community can contribute `es.php`, `fr.php`, `de.php`, etc.
- **Admin setting:** Add a "Language" dropdown in Site Settings. When changed, the system loads
  the corresponding language file.
- **Multi-language content:** For product names, descriptions, page content, and blog posts,
  add a `translations` table: `entity_type`, `entity_id`, `field`, `locale`, `value`. The
  rendering functions check for a translation in the active locale before falling back to the
  default.
- **Right-to-left (RTL) support:** Add a `dir="rtl"` attribute to `<html>` when the active
  locale is RTL (Arabic, Hebrew, etc.). Add a `rtl.css` stylesheet that mirrors layout
  properties. Use CSS logical properties (`margin-inline-start` instead of `margin-left`)
  in new CSS going forward.
- **Currency & date formatting:** Use `Intl` PHP extension (or polyfill) for locale-aware
  number, currency, and date formatting. The `formatPrice()` function already exists and can
  be extended.
- **Safe implementation:** Wrapping strings in `__()` is additive. Without a language file
  loaded, the function returns the original string. Existing behavior is unchanged.

---

## 6. Advanced Analytics & Reporting Dashboard

**Why:** Business owners need to understand their sales, traffic, and customer behavior without
relying on third-party tools that raise privacy concerns.

**Current state:** No analytics dashboard exists. Order data is in the database but not
aggregated or visualized.

**Proposals:**

- **Sales dashboard widgets:**
  - Revenue over time (daily/weekly/monthly chart)
  - Orders count by status
  - Average order value trend
  - Top-selling products (by quantity and revenue)
  - Revenue by category
  - Coupon usage and discount impact
  - Refund/cancellation rate
- **Customer insights:**
  - New vs. returning customer ratio
  - Customer lifetime value estimates
  - Registration trends
  - Geographic distribution (from shipping addresses)
- **Inventory reports:**
  - Low-stock alerts summary
  - Stock turnover rate
  - Products never ordered
  - Backorder queue
- **Blog analytics:**
  - Most-viewed posts (requires a `page_views` table with simple hit counting)
  - Comment engagement rates
  - Posts driving product views (track referrals from blog to product pages)
- **Implementation approach:**
  - Create aggregation queries against existing `orders`, `order_items`, `customers`, and
    `products` tables. No new data collection needed for sales analytics.
  - For traffic analytics, add a lightweight `page_views` table (page, referrer, user_agent,
    session_id, timestamp) with a simple middleware that logs hits. Keep it privacy-friendly:
    no cookies, no IP storage, just aggregate counts.
  - Render charts using a lightweight JS library (Chart.js is ~60KB, no build step needed,
    MIT licensed). Load via CDN or bundle locally.
  - Cache expensive aggregation queries with a `report_cache` table (report_key, data_json,
    generated_at) that refreshes on a configurable interval.
- **Export:** Allow CSV/PDF export of any report for accounting purposes.

---

## 7. Subscription & Recurring Billing

**Why:** Many small businesses sell memberships, subscription boxes, SaaS access, or recurring
services. This is a major revenue model not currently supported.

**Current state:** One-time purchases only. No concept of recurring orders or subscription plans.

**Proposals:**

- **Subscription plans:** New `subscription_plans` table: id, product_id, name, interval
  (weekly/monthly/quarterly/yearly), interval_count, price, trial_days, setup_fee.
- **Customer subscriptions:** `subscriptions` table: id, customer_id, plan_id, status
  (active/paused/cancelled/past_due), current_period_start, current_period_end,
  next_billing_date, payment_method_token, cancelled_at.
- **Billing engine:** A cron job or scheduled task that runs daily, finds subscriptions due for
  renewal, and processes charges via the stored payment method. Stripe and PayPal both support
  tokenized recurring charges.
- **Customer portal:** Let customers view, pause, resume, or cancel subscriptions from their
  account page. Show billing history.
- **Admin management:** View all active subscriptions, filter by status, manually pause/cancel,
  see upcoming renewals.
- **Dunning management:** When a renewal charge fails, retry with configurable intervals
  (e.g., retry after 3 days, 7 days, then cancel). Send email notifications at each stage.
- **Safe implementation:** Subscriptions are a new module. New tables, new admin section, new
  customer account tab. The existing one-time purchase flow remains completely untouched. Products
  can optionally have subscription plans attached; those without them work as they always have.

---

## 8. Multi-Vendor / Marketplace Mode

**Why:** Some site owners want to host a marketplace where multiple sellers list products. This
dramatically expands the platform's use cases.

**Current state:** Single-seller model. All products belong to the site owner.

**Proposals:**

- **Vendor accounts:** New `vendors` table: id, user_id, store_name, slug, description, logo,
  commission_rate, status (pending/approved/suspended), payout_info, created_at.
- **Product ownership:** Add `vendor_id` column to `products` table (nullable; NULL = site-owner
  products). Existing products remain unaffected.
- **Vendor dashboard:** A new `/vendor/` panel (separate from `/admin/`) where approved vendors
  can manage their own products, view their orders, and see their earnings. Vendors only see
  their own data.
- **Commission system:** On each order, calculate vendor payout = item price - commission.
  Track in a `vendor_payouts` table with status (pending/paid). Admin can mark payouts as
  completed.
- **Approval workflow:** New vendor applications go to "pending." Admin reviews and approves.
  New product listings from vendors can optionally require admin approval.
- **Storefront pages:** Each vendor gets a public storefront at `/vendor/<slug>` showing their
  products, description, and rating.
- **Safe implementation:** This is an entirely additive feature. When marketplace mode is
  disabled (default), the vendor system is invisible. The existing single-seller flow works
  identically. Marketplace mode is toggled via a feature flag in settings.

---

## 9. Advanced SEO Toolkit

**Why:** Organic search traffic is the lifeblood of many small businesses. The platform should
make good SEO effortless.

**Current state:** Basic meta descriptions, OG tags, XML sitemap, RSS feed, canonical URLs on
blog posts. No structured data. No breadcrumbs markup. No per-product SEO fields.

**Proposals:**

- **JSON-LD structured data:** Automatically output Schema.org markup for:
  - `Product` (name, price, availability, images, rating, reviews)
  - `Organization` / `LocalBusiness` (from company info settings)
  - `BreadcrumbList` (auto-generated from page hierarchy)
  - `BlogPosting` (title, author, date, image)
  - `FAQPage` (if a FAQ section is added)
  - `WebSite` with `SearchAction` (for sitelinks search box)
- **Per-entity SEO fields:** Add `meta_title`, `meta_description`, `meta_keywords`,
  `og_image` columns to products, categories, and pages. Show these in the admin edit forms
  with character count indicators and preview snippets.
- **Auto-generated meta descriptions:** If no custom meta description is set, auto-generate
  from the first ~160 characters of the content.
- **URL slug management:** Allow custom URL slugs for products and categories. Add a
  `url_redirects` table so old slugs 301-redirect to new ones (prevents broken links).
- **Breadcrumb navigation:** Render visible breadcrumbs (Home > Category > Product) and mark
  them up with BreadcrumbList schema.
- **Image SEO:** Auto-generate `alt` text suggestions from product names. Warn in admin when
  images are missing alt text.
- **Sitemap enhancements:** Include `<image:image>` tags in the sitemap. Add `<lastmod>` dates.
  Support sitemap index for sites with 1000+ URLs.
- **Safe implementation:** SEO additions are purely additive to the HTML output. Structured data
  goes in `<script type="application/ld+json">` tags that don't affect rendering. Existing URLs
  and page behavior remain unchanged.

---

## 10. Image Optimization & CDN Integration

**Why:** Images are typically the heaviest assets on e-commerce sites. Optimization directly
impacts load time, SEO rankings, and conversion rates.

**Current state:** Images are served as-uploaded from `/uploads/`. No resizing, no format
conversion, no CDN support.

**Proposals:**

- **Server-side image processing:** On upload, generate multiple sizes (thumbnail 150px,
  medium 600px, large 1200px, original). Use PHP's GD library (already commonly available)
  or ImageMagick. Store variants alongside the original:
  `uploads/images/<hash>-thumb.webp`, `uploads/images/<hash>-medium.webp`, etc.
- **WebP conversion:** Automatically convert uploaded JPG/PNG to WebP with fallback. WebP
  typically saves 25-35% file size. Use `<picture>` element with WebP source and JPG/PNG
  fallback for older browsers.
- **Lazy loading:** Add `loading="lazy"` to all product and blog images. Already supported
  natively by modern browsers, zero JS required.
- **Responsive images:** Use `srcset` and `sizes` attributes so browsers download the
  appropriate size for the viewport.
- **CDN configuration:** Add admin settings for CDN base URL. When configured, all asset URLs
  are prefixed with the CDN domain. This works with any pull-based CDN (Cloudflare, BunnyCDN,
  KeyCDN, etc.) and requires zero changes to the upload flow.
- **Image compression settings:** Admin setting for JPEG quality (default 82), WebP quality
  (default 80), and max upload dimensions (auto-downscale oversized uploads).
- **Safe implementation:** Image processing happens at upload time; existing images continue
  to work as-is. A one-time migration script can process existing uploads into optimized
  variants. The CDN URL prefix is optional; when not set, local URLs are used.

---

## 11. Backup, Export & GDPR Compliance

**Why:** Data loss is catastrophic for a business. Regulatory compliance (GDPR, CCPA) is legally
required in many jurisdictions. Both are expected of a serious e-commerce solution.

**Current state:** No backup system. No data export tools (except a CSV export mentioned in
admin). No privacy policy automation. No customer data deletion workflow.

**Proposals:**

- **Automated backups:**
  - Database dump via `mysqldump` triggered from an admin button or cron job.
  - File backup (uploads directory) as a tar/zip archive.
  - Store backups locally in a `backups/` directory (gitignored, not web-accessible).
  - Optional: configure remote backup destination (S3-compatible endpoint, FTP) via admin
    settings.
  - Retention policy: keep last N backups, auto-delete older ones.
- **One-click restore:** Admin UI to list available backups and restore from a selected one.
  Show warnings about data overwrite.
- **Customer data export (GDPR Article 15/20):**
  - "Download my data" button in the customer account page.
  - Generates a JSON/CSV file containing: profile info, order history, reviews, wishlist,
    contact submissions.
- **Right to deletion (GDPR Article 17):**
  - "Delete my account" flow in the customer portal.
  - Anonymizes order records (replaces personal info with "deleted customer") rather than
    deleting orders (needed for accounting).
  - Deletes: customer account, wishlist, reviews, contact submissions.
  - Sends confirmation email.
- **Cookie consent:** A configurable cookie consent banner. The platform currently uses only
  session cookies (which are exempt in many jurisdictions), but if analytics or third-party
  scripts are added, consent becomes mandatory.
- **Privacy policy generator:** A settings-driven privacy policy page template that
  auto-populates with the business name, data practices, cookie usage, and payment processor
  information based on the site's active configuration.
- **Safe implementation:** Backup features are new admin pages and cron scripts. GDPR features
  add new routes to the customer account section. No existing functionality changes.

---

## 12. Enhanced Email & Notification System

**Why:** Transactional emails drive repeat business and keep customers informed. The current
system sends basic order confirmations but lacks templates and automation.

**Current state:** `includes/email.php` sends HTML emails via SMTP or PHP `mail()`. Order
confirmation emails exist. No email templates, no drip campaigns, no notification preferences.

**Proposals:**

- **Email template system:**
  - Store email templates in the database: `email_templates` table (slug, subject, body_html,
    body_text, variables).
  - Ship defaults for: order_confirmation, order_shipped, order_delivered, password_reset,
    welcome_email, abandoned_cart_reminder, review_request, low_stock_alert (admin).
  - Admin UI to edit templates with a visual editor. Support variable placeholders like
    `{{customer_name}}`, `{{order_number}}`, `{{tracking_url}}`.
- **Admin notification preferences:** Let admins choose which events trigger email/notifications:
  new order, low stock, new customer registration, new contact submission, new review.
- **Customer notification preferences:** Let customers opt in/out of marketing emails,
  order updates, and review requests from their account settings.
- **Abandoned cart recovery:**
  - Track carts in the database (already session-based; add a `saved_carts` table for
    logged-in users).
  - After configurable delay (e.g., 24 hours), send a reminder email with cart contents and
    a link to resume checkout.
  - Optionally include a discount code incentive.
- **Email queue:** Instead of sending emails synchronously during request handling, write to an
  `email_queue` table and process via cron. This prevents slow SMTP connections from blocking
  page loads.
- **Safe implementation:** Email templates default to the current hard-coded content. The queue
  is optional (fall back to synchronous sending if cron isn't configured). All additive.

---

## 13. Advanced Shipping & Tax Engine

**Why:** Shipping and tax complexity is one of the biggest pain points for e-commerce. The
current flat-rate system works for simple cases but falls short for growing businesses.

**Current state:** Shipping zones with flat-rate or free-threshold methods. Single global tax
rate.

**Proposals:**

- **Weight/dimension-based shipping:** Add weight, length, width, height fields to products.
  Calculate shipping cost based on total weight or dimensional weight. Support tiered rates
  (0-1kg = $X, 1-5kg = $Y, etc.).
- **Real-time carrier rates:** Plugin-ready integration points for USPS, UPS, FedEx, and
  Canada Post APIs. Calculate live rates at checkout based on origin, destination, and package
  dimensions. Implement as plugins (see Plugin System above) so the core stays lightweight.
- **Multi-zone tax:** Replace single tax rate with a `tax_rules` table: zone/country/state,
  rate, product_type_applicability. Digital goods, physical goods, and services often have
  different tax rates.
- **Tax-inclusive pricing:** Admin toggle for whether displayed prices include or exclude tax.
  Important for EU/UK/AU markets where tax-inclusive display is legally required.
- **Automatic tax calculation:** Integration point for tax APIs (e.g., TaxJar, Avalara) via
  the plugin system. These handle the complexity of US sales tax nexus, EU VAT, etc.
- **Shipping label generation:** Integration point for label APIs. At minimum, provide a
  "print packing slip" feature that generates a printer-friendly HTML page with order details,
  shipping address, and item list.
- **Free shipping rules:** More flexible conditions: free shipping over $X, free shipping for
  specific categories, free shipping with certain coupon codes, free shipping for subscription
  orders.
- **Safe implementation:** New database columns on products are nullable (existing products
  default to zero weight). The tax rules table supplements the existing single rate. When no
  rules match, fall back to the global rate. All backwards-compatible.

---

## 14. Customer Loyalty & Marketing Tools

**Why:** Acquiring a new customer costs 5-7x more than retaining one. Built-in loyalty and
marketing tools add significant value.

**Current state:** Coupon system exists. No loyalty points, no referral program, no email
marketing integration, no wishlists sharing.

**Proposals:**

- **Loyalty points system:**
  - Customers earn points per dollar spent (configurable ratio).
  - Points can be redeemed at checkout as a discount (e.g., 100 points = $1).
  - Bonus points for actions: first purchase, writing a review, birthday.
  - Points history visible in customer account.
  - Admin can manually adjust points.
  - New tables: `loyalty_points` (customer_id, points_balance), `loyalty_transactions`
    (customer_id, points, type, description, order_id, created_at).
- **Referral program:**
  - Each customer gets a unique referral code/link.
  - When a new customer makes their first purchase using a referral link, both parties receive
    a reward (points, discount code, or fixed credit).
  - Track referrals in a `referrals` table.
- **Wishlist enhancements:**
  - Shareable wishlist via unique URL.
  - "Back in stock" notifications: if a wishlisted item is out of stock, email the customer
    when it's restocked.
  - Wishlist analytics for the admin: most-wishlisted products (demand signal).
- **Product bundles & upsells:**
  - "Frequently bought together" associations (admin-configured or auto-calculated from order
    history).
  - Bundle discounts: buy products X + Y together for Z% off.
  - "Customers also bought" section on product pages.
- **Flash sales / time-limited offers:**
  - Schedule a sale price with start/end dates on products.
  - Display countdown timer on product page during active sales.
  - Auto-revert to regular price when the sale ends.
- **Safe implementation:** All new tables and modules. Loyalty points are optional (disabled by
  default, enabled via feature flag). The existing coupon system remains unchanged. Bundles are
  a new relationship table between products.

---

## 15. Developer Experience & Deployment

**Why:** Making the platform easy to develop, deploy, and maintain increases adoption and reduces
the barrier for self-hosting.

**Current state:** Vanilla PHP with no build step, Apache-dependent, manual setup. Docker not
included. No CI/CD. No test suite.

**Proposals:**

- **Docker Compose setup:**
  - `docker-compose.yml` with PHP-Apache and MySQL containers.
  - `Dockerfile` with all required PHP extensions pre-installed.
  - `.env.example` with all configuration variables documented.
  - One-command startup: `docker-compose up -d` gives a working development environment.
  - Health check integration with the existing `/admin/api/healthcheck.php`.
- **Nginx support:**
  - Provide an `nginx.conf` equivalent of the current `.htaccess` rules.
  - Document nginx setup alongside Apache.
  - This opens deployment on more hosting platforms and generally offers better performance.
- **CLI installer:**
  - A `php install.php` script that walks through: database setup, admin account creation,
    basic site settings, and optional sample data import.
  - Replaces the current bootstrap credentials file approach with a more guided setup.
- **Testing framework:**
  - Add PHPUnit for unit tests on core functions (`includes/` logic).
  - Add basic integration tests for critical paths: checkout flow, payment webhook handling,
    authentication.
  - Tests run against a test database, not production.
  - Provides confidence when making changes and enables community contributions.
- **Configuration management:**
  - Support `.env` files natively (a simple `parse_ini_file` or custom parser).
  - Allow all settings currently in `config.php` and database to be overridden via environment
    variables. This is essential for containerized deployments and 12-factor app compliance.
- **Database seeder improvements:**
  - `php seed.php --demo` to install a complete demo store with sample products, categories,
    blog posts, and pages. Great for evaluation and theme development.
- **Safe implementation:** Docker and nginx configs are new files that don't affect the existing
  Apache setup. Tests are in a new `tests/` directory. The CLI installer is an optional
  alternative to the current bootstrap flow.

---

## 16. Accessibility & Performance

**Why:** Accessibility is both a legal requirement (ADA, EAA) and a moral imperative.
Performance directly impacts conversion rates and SEO.

**Current state:** Responsive design exists. No explicit accessibility audit or ARIA landmarks.
No performance optimization beyond browser caching headers.

**Proposals:**

- **Accessibility audit and fixes:**
  - Add proper ARIA landmarks (`role="navigation"`, `role="main"`, `role="complementary"`).
  - Ensure all interactive elements are keyboard-navigable (focus outlines, tab order).
  - Add `aria-label` to icon-only buttons.
  - Ensure color contrast meets WCAG 2.1 AA standards (4.5:1 for text).
  - Add `alt` text to all images (enforce in the media upload flow).
  - Screen reader announcements for AJAX operations (add-to-cart, wishlist updates) using
    `aria-live` regions.
  - Skip-to-content link at the top of every page.
- **Performance optimizations:**
  - **CSS/JS minification:** A simple build script (or PHP-based runtime minifier with caching)
    to reduce asset file sizes.
  - **Critical CSS inlining:** Inline above-the-fold CSS in the `<head>` and async-load the
    rest.
  - **Database query optimization:** Add indexes on frequently queried columns (product
    category lookups, order status filters, blog post date sorting). Review `EXPLAIN` output
    for key queries.
  - **Object caching:** Simple file-based or APCu cache for expensive queries (settings lookup
    on every page load, navigation menu, category list). Invalidate on admin save.
  - **HTTP/2 server push hints:** Add `Link` headers for critical CSS/JS resources.
- **Core Web Vitals focus:**
  - Measure and optimize LCP (largest contentful paint) - typically the hero image.
  - Minimize CLS (cumulative layout shift) - set explicit dimensions on images and embeds.
  - Optimize FID/INP (interaction to next paint) - defer non-critical JS.
- **Safe implementation:** Accessibility fixes are HTML attribute additions. Performance
  optimizations are additive (caching layers, index additions). Nothing changes existing
  behavior.

---

## 17. Security Hardening

**Why:** E-commerce sites handle payment data and personal information. Security must be
continuously strengthened.

**Current state:** Already solid: bcrypt passwords, CSRF protection, rate limiting, CSP headers,
HTML sanitization, secure session handling, upload restrictions. But there's always room for more.

**Proposals:**

- **Two-factor authentication (2FA):**
  - TOTP-based 2FA (Google Authenticator, Authy) for admin accounts.
  - Use PHP's `HOTP`/`TOTP` implementation (small library or built from `hash_hmac`).
  - QR code generation for initial setup.
  - Recovery codes stored hashed in the database.
  - Optional 2FA for customer accounts.
- **Content Security Policy refinement:**
  - Move from static CSP to a nonce-based CSP. Generate a unique nonce per request, add it
    to all inline scripts and styles, and include it in the CSP header. This eliminates the
    need for `'unsafe-inline'`.
- **Subresource Integrity (SRI):**
  - Add `integrity` attributes to all externally loaded scripts and stylesheets (FontAwesome
    CDN, any other CDN resources).
- **Security.txt:**
  - Add `/.well-known/security.txt` with contact information for responsible disclosure.
- **Dependency scanning:**
  - If Composer is adopted (even just for dev dependencies like PHPUnit), use `composer audit`
    to check for known vulnerabilities.
- **Login security enhancements:**
  - Account lockout notification emails.
  - "New device" login alerts for admin accounts (track user-agent + IP hash).
  - Session listing: show active sessions in admin profile with ability to revoke.
- **File upload hardening:**
  - Re-process all uploaded images through GD/ImageMagick to strip EXIF data and neutralize
    image-based exploits.
  - Randomize upload filenames (already partially done, but ensure all paths are unpredictable).
- **Safe implementation:** 2FA is opt-in per account. CSP nonces require updating the header
  generation and template output, but the result is strictly more secure. All changes are
  backwards-compatible.

---

## 18. Content & Layout Builder

**Why:** Non-technical business owners need to build custom page layouts without editing PHP
templates. A visual builder dramatically increases the platform's appeal and flexibility.

**Current state:** Pages have a WYSIWYG editor for body content and a hero section. Layout is
fixed by the template.

**Proposals:**

- **Block-based content system:**
  - Define reusable content blocks: hero, text, image+text, product grid, testimonial slider,
    CTA banner, FAQ accordion, pricing table, team grid, video embed, gallery, contact form,
    map, HTML embed.
  - Each page's content is stored as a JSON array of ordered blocks, each with a type and
    configuration data.
  - Render function iterates over blocks and calls the appropriate template partial for each.
- **Admin block editor:**
  - Drag-and-drop interface for ordering blocks.
  - Each block type has a configuration panel (e.g., product grid block lets you choose
    category, number of items, columns).
  - Live preview alongside the editor.
  - "Add block" button with a visual picker showing all available block types.
- **Custom block types (via plugins):**
  - Plugins can register new block types by providing: a config schema (JSON), an admin
    editor partial (HTML/JS), and a frontend render partial (PHP).
  - This makes the layout system infinitely extensible.
- **Template sections:**
  - Beyond page content, allow block-based customization of: header layout, footer content,
    sidebar widgets, and the homepage layout.
  - Store section configurations in the settings table.
- **Reusable block groups:**
  - Save a group of configured blocks as a "pattern" or "section template" that can be
    inserted into any page. Useful for consistent CTAs, promotional banners, etc.
- **Safe implementation:** The existing WYSIWYG page content becomes a "Classic Content" block
  type. Pages that were created before the block system use this single block and render exactly
  as before. New pages can use the full block editor. Zero breaking changes.

---

## Implementation Priority Recommendation

### Phase 1 - Foundation (High impact, enables everything else)
1. **Plugin/Extension System** - Unlocks community-driven growth
2. **Theming Engine** - Unlocks visual customization for all site types
3. **Multi-Admin Roles** - Required for any real business operation

### Phase 2 - Growth & Reach
4. **REST API** - Enables integrations and mobile apps
5. **i18n/l10n** - Opens non-English markets
6. **Content & Layout Builder** - Empowers non-technical users

### Phase 3 - Business Value
7. **Advanced Analytics** - Data-driven decisions
8. **Enhanced Email System** - Customer retention
9. **Customer Loyalty & Marketing** - Revenue growth
10. **Subscription Billing** - New revenue model

### Phase 4 - Maturity & Scale
11. **Advanced SEO Toolkit** - Organic growth
12. **Image Optimization & CDN** - Performance at scale
13. **Advanced Shipping & Tax** - Handle complex logistics
14. **Backup & GDPR Compliance** - Legal and operational safety

### Phase 5 - Polish & Ecosystem
15. **Developer Experience & Deployment** - Docker, tests, CLI
16. **Accessibility & Performance** - Inclusive and fast
17. **Security Hardening** - 2FA, CSP nonces, SRI
18. **Multi-Vendor Marketplace** - Platform play (optional, niche)

---

## Design Principles for All Changes

1. **Backwards compatibility first:** Every feature must be additive. Existing sites upgrading
   should see zero breakage. New features default to "off" or behave identically to the old
   behavior until explicitly configured.

2. **Feature flags for everything:** Every major feature is toggled in admin settings. The
   existing `features` toggles pattern (catalog, cart, blog, reviews, etc.) should be extended
   to all new features.

3. **No new hard dependencies:** Keep the stack requirement minimal (PHP + MySQL + Apache/Nginx).
   New features should degrade gracefully when optional extensions or services aren't available.

4. **Database migrations for all schema changes:** Use the existing migration system in
   `database/migrations/`. Every new table or column change gets a migration file. Never
   require manual SQL.

5. **Consistent code patterns:** Follow the existing coding conventions (PDO prepared statements,
   function-based organization in `includes/`, admin page structure). Don't introduce frameworks
   or ORMs mid-project.

6. **Mobile-first always:** Every new UI component must be responsive. Test at 320px viewport.

7. **Security by default:** New features must maintain the existing security posture: CSRF on
   all forms, prepared statements for all queries, output escaping, input validation.
