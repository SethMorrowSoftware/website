# E-Commerce Platform Improvement Plan

## Current State Summary

This is a vanilla PHP/SQLite self-hosted CMS with integrated e-commerce. It has a solid foundation: 13 database tables, 5 payment gateways (Stripe, PayPal, Square, BTCPay, SwipeSimple), session-based shopping cart, digital product downloads, WYSIWYG page editing, and a comprehensive admin panel with 40+ configurable settings.

**What's working well:** Zero-dependency vanilla stack, auto-initializing SQLite database, multiple payment providers, responsive design, CSRF protection, prepared statements throughout, digital product delivery with token-based downloads.

**What's missing to be a full e-commerce platform:** Customer accounts, inventory management, product variants, discount/coupon system, shipping calculation, product search, order tracking, analytics, and several security hardening items.

---

## Phase 1: Critical Security & Stability Fixes

These must be addressed before any feature work.

### 1.1 CSRF Protection on Admin AJAX Endpoints
- Add CSRF token validation to `admin/api/upload.php`, `admin/api/delete.php`, `admin/api/reorder.php`
- The `admin.js` already injects CSRF tokens into fetch headers — the server-side endpoints just need to check them

### 1.2 HTML Sanitization for Stored Content
- Add an `sanitizeHtml()` function that strips dangerous tags/attributes (script, onerror, onload, javascript: URIs) while preserving safe formatting HTML
- Apply to: custom page content, footer HTML, hero section fields, product descriptions
- This prevents XSS if the admin account is ever compromised

### 1.3 Logout Must Be POST-Only
- Change logout from GET to POST with CSRF token
- Prevents CSRF-based forced logout attacks

### 1.4 Server-Side Email Validation
- Add `filter_var($email, FILTER_VALIDATE_EMAIL)` to contact form and checkout server-side validation
- Currently only validated client-side

### 1.5 Favicon Rendering Fix
- The favicon setting exists in admin but is never injected into the public `<head>` — wire it up

### 1.6 Rate Limiting on Public Forms
- Add basic rate limiting (by IP + time window) to contact form, order inquiry, and checkout
- Prevents spam submission without requiring CAPTCHA

---

## Phase 2: Core E-Commerce Essentials

These are the features every e-commerce platform needs.

### 2.1 Customer Accounts & Order History
- **New `customers` table**: id, email (unique), password_hash, first_name, last_name, phone, default_shipping_address (JSON), created_at, last_login
- **Registration page** (`/register`): name, email, password, confirm password
- **Login page** (`/login`): email + password, rate limited
- **Account dashboard** (`/account`): order history, saved addresses, profile editing, password change
- **Guest checkout preserved**: customers can still check out without an account
- **Link orders to accounts**: orders table gets optional `customer_id` FK
- **"My Orders" page**: list past orders with status, click to view details, re-download digital products
- **Password reset**: token-based email flow (requires SMTP — see Phase 5)

### 2.2 Product Variants & Options
- **New `product_options` table**: id, product_id (FK), name (e.g., "Size", "Color"), sort_order
- **New `product_option_values` table**: id, option_id (FK), value (e.g., "Large", "Red"), price_modifier (decimal, +/- adjustment), sku_suffix, sort_order, is_available
- **Admin UI**: add option groups to products, each with multiple values
- **Frontend**: dropdown/swatch selectors on product cards, price updates dynamically
- **Cart integration**: store selected options per cart item, display in cart and order
- **Order items**: store selected variant info (JSON) so it persists after product changes

### 2.3 Inventory Management
- **New columns on `products`**: `track_inventory` (boolean), `stock_quantity` (integer), `low_stock_threshold` (integer, default 5), `allow_backorder` (boolean)
- **Stock per variant**: `product_option_values` gets `stock_quantity` column
- **Admin product editor**: stock quantity field, low-stock threshold, backorder toggle
- **Admin dashboard**: low-stock alerts widget showing products below threshold
- **Cart enforcement**: prevent adding more than available stock, show "Out of Stock" badge
- **Checkout enforcement**: verify stock at order creation, decrement on successful payment
- **Stock adjustment on cancel/refund**: return stock when order cancelled

### 2.4 Product Search
- **Search bar** in site header (public pages)
- **Full-text search**: query products by name, description, category name
- **Search results page** (`/search?q=...`): product cards matching query, grouped by relevance
- **Admin search**: search products, orders, customers from admin panel
- **SQLite FTS5**: use SQLite's built-in full-text search extension for performance

### 2.5 Discount & Coupon System
- **New `coupons` table**: id, code (unique), type (percentage/fixed/free_shipping), value, minimum_order, maximum_discount, usage_limit, used_count, valid_from, valid_until, applies_to (all/specific_categories/specific_products), is_active, created_at
- **New `coupon_products` table**: coupon_id, product_id (for product-specific coupons)
- **New `coupon_categories` table**: coupon_id, category_id (for category-specific coupons)
- **Cart page**: "Apply Coupon" input field, validates code, shows discount line item
- **Checkout**: coupon code stored on order, discount shown in order summary
- **Admin**: CRUD for coupons, usage stats, enable/disable, bulk generation
- **Stacking rules**: one coupon per order (simple rule to start)

### 2.6 Shipping Calculation
- **New `shipping_zones` table**: id, name, countries (JSON), states (JSON), is_default
- **New `shipping_methods` table**: id, zone_id (FK), name (e.g., "Standard", "Express"), type (flat_rate/free/weight_based/price_based), cost, free_threshold (min order for free shipping), min_weight, max_weight, min_price, max_price, estimated_days, sort_order, is_active
- **Checkout**: shipping method selector after address entry, cost added to order total
- **Admin**: manage zones and methods, set rates
- **Free shipping**: configurable threshold (e.g., free shipping over $50)
- **Digital-only orders**: skip shipping entirely

### 2.7 Tax Improvements
- **Tax by region**: new `tax_rules` table with state/province/country rates
- **Auto-detect**: apply tax based on shipping address state/country
- **Tax-exempt products**: per-product tax toggle
- **Tax display**: show tax breakdown in cart and checkout
- **Fallback**: keep current flat-rate tax as default for simple setups

---

## Phase 3: User Experience & Usability Improvements

### 3.1 Product Detail Pages
- **Individual product pages** (`/product/<slug>`): currently products only show in the catalog grid
- Full-size image (with zoom on hover), description, specifications, features, pricing
- Add-to-cart with quantity selector and variant options
- Related products section (same category)
- Breadcrumb navigation (Home > Category > Product)

### 3.2 Image Gallery & Zoom
- **Multiple product images**: new `product_images` table (id, product_id, image_path, alt_text, sort_order, is_primary)
- **Image gallery**: thumbnail strip below main image, click to switch
- **Lightbox**: click main image to open full-screen overlay with zoom
- **Admin**: drag-drop image ordering, set primary image, bulk upload

### 3.3 Wishlist / Save for Later
- **Session-based for guests, DB-based for logged-in customers**
- **New `wishlists` table**: id, customer_id (nullable), session_id (for guests), product_id, created_at
- **Heart icon** on product cards and detail pages
- **Wishlist page** (`/wishlist`): saved products with "Move to Cart" button
- **Persistence**: guest wishlist merges into account on login

### 3.4 Improved Cart Experience
- **Mini-cart dropdown**: click cart icon in header to see items without navigating away
- **Quantity +/- buttons**: instead of input field only
- **"You may also like"**: show related products on cart page
- **Empty cart state**: friendly message with CTA to browse catalog
- **Cart item count badge**: already exists, ensure it updates via AJAX on add-to-cart

### 3.5 AJAX Add-to-Cart
- **No page reload** when adding to cart from catalog or product pages
- Show success toast notification ("Product added to cart!")
- Update cart icon count dynamically
- Mini-cart dropdown refreshes automatically

### 3.6 Breadcrumb & Navigation Improvements
- Consistent breadcrumbs on all pages (some pages missing them)
- Category-level pages in catalog (`/catalog/mulch`, `/catalog/stone`)
- "Back to top" button on long pages

### 3.7 Mobile UX Polish
- Bottom navigation bar for mobile (cart, search, account, menu)
- Swipe gestures for product image gallery
- Sticky add-to-cart bar on product detail pages (mobile)
- Larger touch targets for buttons and links

### 3.8 Loading States & Feedback
- Skeleton loading screens while content loads
- Button loading spinners on form submission (prevent double-submit)
- Toast notifications for actions (add to cart, wishlist, coupon applied)
- Form validation inline (real-time as user types, not just on submit)

---

## Phase 4: Advanced E-Commerce Features

### 4.1 Order Tracking for Customers
- **Order status page** (`/order-status`): enter order number + email to check status
- **Status timeline**: visual progress (Pending → Processing → Shipped → Delivered)
- **Tracking number support**: admin can add carrier + tracking number to order
- **Email notifications**: auto-email customer on each status change
- **Logged-in customers**: see all order statuses in account dashboard

### 4.2 Reviews & Ratings
- **New `reviews` table**: id, product_id (FK), customer_id (FK, nullable), customer_name, customer_email, rating (1-5), title, body, is_verified_purchase, is_approved, created_at
- **Product detail pages**: show reviews with star ratings, average rating
- **Review form**: star selector, title, body text, requires order verification for "verified" badge
- **Admin moderation**: approve/reject reviews, respond to reviews
- **Aggregate ratings**: average stars shown on product cards in catalog

### 4.3 Email Notifications System
- **Configurable email templates**: new `email_templates` table with customizable HTML templates
- **Trigger-based emails**: order confirmation, payment received, order shipped, order delivered, download ready, password reset, welcome email, review request (7 days after delivery)
- **Admin settings**: enable/disable each email type, edit template content
- **Preview**: admin can preview email templates before enabling
- **Queue**: email sending through a simple queue to prevent blocking page loads

### 4.4 Product Bundles & Upsells
- **Product bundles**: group products together at a discounted bundle price
- **New `bundles` table**: id, name, slug, description, image, discount_type (percentage/fixed), discount_value, is_active
- **New `bundle_items` table**: bundle_id, product_id, quantity
- **Cross-sells**: "Frequently bought together" on product detail page
- **Upsells**: "Customers also viewed" based on category

### 4.5 Abandoned Cart Recovery
- **Track carts with email**: if customer enters email at checkout but doesn't complete, save cart
- **New `abandoned_carts` table**: id, customer_email, cart_items (JSON), created_at, reminder_sent_at, recovered
- **Auto-email**: send reminder after 1 hour, 24 hours (configurable intervals)
- **Recovery link**: unique URL that restores the cart contents
- **Admin dashboard**: abandoned cart count, recovery rate stats

### 4.6 PDF Invoices
- Generate downloadable PDF invoices for completed orders
- Include business logo, address, order details, line items, totals
- Available from admin order view and customer order history
- Pure PHP PDF generation (no external library — use simple HTML-to-PDF approach or include TCPDF)

---

## Phase 5: Administration & Operations

### 5.1 Admin Dashboard Overhaul
- **Sales analytics**: revenue by day/week/month chart, total orders, average order value
- **Top products**: best sellers by quantity and revenue
- **Recent activity feed**: latest orders, new customers, new reviews
- **Quick actions**: common tasks (add product, view pending orders, check low stock)
- **Status indicators**: orders needing attention, low stock alerts, unread inquiries

### 5.2 SMTP Email Configuration
- **Admin settings**: SMTP host, port, username, password, encryption (TLS/SSL), from name, from email
- **Replace `mail()`** with a simple SMTP class (built-in, no Composer)
- **Test email button**: send test email from settings to verify configuration
- **Fallback**: keep `mail()` as fallback if SMTP not configured

### 5.3 Data Export & Backup
- **Export orders**: CSV/JSON export with date range filter
- **Export products**: CSV export of full product catalog
- **Export customers**: CSV export of customer list
- **Database backup**: download full SQLite database file from admin
- **Import products**: CSV upload to bulk-create/update products

### 5.4 Audit Log
- **New `audit_log` table**: id, user_id, action, entity_type, entity_id, details (JSON), ip_address, created_at
- Track all admin actions: login, settings change, product CRUD, order status change, etc.
- **Admin page**: searchable, filterable audit log viewer
- **Retention**: auto-purge entries older than 90 days (configurable)

### 5.5 Multi-Admin & Roles
- **Roles**: owner (full access), manager (orders + products + inquiries), editor (pages + media only)
- **New `roles` table**: id, name, permissions (JSON)
- **Users table update**: add role_id FK, email, first_name, last_name
- **Admin UI**: user management page for owner role
- **Permission checks**: wrap admin actions in role-based checks

### 5.6 Refund Processing
- **Admin order view**: "Issue Refund" button (full or partial amount)
- **Stripe refunds**: automated via Stripe Refund API
- **PayPal refunds**: automated via PayPal Refund API
- **Other providers**: manual refund with status tracking
- **Stock restoration**: return items to inventory on refund
- **Customer notification**: email on refund processed
- **Refund log**: track all refund actions with reason

### 5.7 Maintenance Mode
- **Toggle in settings**: puts public site behind a "Under Maintenance" page
- Admin panel remains accessible
- Optional: allow specific IPs to bypass (for testing)

---

## Phase 6: Developer & Code Quality

### 6.1 Split `functions.php` Into Modules
The 1389-line monolithic `includes/functions.php` should be split:
- `includes/products.php` — product and category functions
- `includes/orders.php` — order processing, payment verification
- `includes/cart.php` — cart manipulation functions
- `includes/payments.php` — payment gateway integrations
- `includes/email.php` — email sending and templates
- `includes/media.php` — upload handling, file validation
- `includes/helpers.php` — utility functions (e(), slugify, flash messages, etc.)
- Keep `includes/functions.php` as a loader that includes all modules

### 6.2 Database Migration System
- Replace the monolithic `migrateDatabase()` with numbered migration files
- `database/migrations/001_initial_schema.sql`, `002_add_customers.sql`, etc.
- Track applied migrations in a `migrations` table
- Run pending migrations on page load (same auto-init pattern)

### 6.3 Input Validation Library
- Create a reusable `Validator` class in `includes/validator.php`
- Methods: `required()`, `email()`, `minLength()`, `maxLength()`, `numeric()`, `date()`, `in()`, `phone()`
- Returns structured errors for display
- Replace scattered validation logic across admin and public pages

### 6.4 Error Handling Improvements
- Consistent try/catch around all payment operations
- Logging to a `logs/app.log` file instead of just error_log
- Admin notification for critical errors (payment failures, DB errors)
- User-friendly error pages (404, 500) instead of blank screens

### 6.5 Performance Optimizations
- **Lazy loading images**: add `loading="lazy"` to product images, media gallery
- **Image srcset**: generate responsive image sizes on upload (thumbnail, medium, large)
- **Query caching**: cache settings lookups per-request (they're read repeatedly)
- **Asset versioning**: append file modification time to CSS/JS URLs for cache busting
- **Database indexes**: ensure all frequently-queried columns have indexes

### 6.6 SEO Enhancements
- **JSON-LD structured data**: LocalBusiness schema on homepage, Product schema on product pages, BreadcrumbList on all pages
- **Open Graph tags**: og:title, og:description, og:image per page
- **Canonical URLs**: prevent duplicate content
- **Sitemap improvements**: include products, categories, and custom pages with lastmod dates
- **Schema.org Review markup**: aggregate ratings on product pages

---

## Implementation Priority & Ordering

### Batch 1 — Foundation (do first)
1. Phase 1 (all security fixes) — fixes before features
2. Phase 6.1 (split functions.php) — makes all subsequent work cleaner
3. Phase 6.2 (migration system) — needed for all new tables

### Batch 2 — Core Commerce
4. Phase 2.4 (product search)
5. Phase 3.1 (product detail pages)
6. Phase 2.1 (customer accounts)
7. Phase 2.3 (inventory management)
8. Phase 2.2 (product variants)
9. Phase 2.5 (coupons/discounts)

### Batch 3 — User Experience
10. Phase 3.2 (image gallery)
11. Phase 3.4 + 3.5 (improved cart + AJAX)
12. Phase 3.3 (wishlist)
13. Phase 3.7 + 3.8 (mobile UX + loading states)
14. Phase 3.6 (navigation improvements)

### Batch 4 — Advanced Features
15. Phase 2.6 (shipping calculation)
16. Phase 2.7 (tax improvements)
17. Phase 4.1 (order tracking)
18. Phase 4.2 (reviews & ratings)
19. Phase 4.3 (email notifications)

### Batch 5 — Operations & Polish
20. Phase 5.1 (dashboard overhaul)
21. Phase 5.2 (SMTP configuration)
22. Phase 5.3 (data export/backup)
23. Phase 5.4 (audit log)
24. Phase 5.5 (multi-admin roles)
25. Phase 5.6 (refund processing)
26. Phase 5.7 (maintenance mode)

### Batch 6 — Polish & Quality
27. Phase 4.4 (bundles & upsells)
28. Phase 4.5 (abandoned cart recovery)
29. Phase 4.6 (PDF invoices)
30. Phase 6.3-6.6 (validation, errors, performance, SEO)

---

## Design Principles for All Changes

1. **No external dependencies** — maintain the vanilla PHP/JS/CSS stack, no Composer, no npm
2. **SQLite compatible** — all new tables and queries must work with SQLite
3. **Progressive enhancement** — new features are opt-in via admin settings toggles
4. **Mobile-first** — all new UI must be responsive
5. **Backward compatible** — existing installations auto-migrate, no data loss
6. **Admin-friendly** — every feature must be manageable from the admin panel without code changes
7. **Security by default** — prepared statements, CSRF, output escaping, input validation on all new code
