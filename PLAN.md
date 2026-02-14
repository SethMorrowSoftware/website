# Hudson Valley Supply & Recycling LLC — Website Plan

## Overview

A professional, fully-featured business website built with vanilla HTML/CSS/JS and PHP. Includes a complete admin CMS backend so the business owner (non-programmer) can manage all content — text, images, pages, products, and site settings — without touching code.

All dynamic content is stored in a SQLite database (zero-config, no MySQL setup needed). The admin panel provides a WYSIWYG-style interface for full site management.

---

## Technology Stack

| Layer | Technology |
|-------|-----------|
| Frontend | Vanilla HTML5, CSS3, JavaScript (ES6+) |
| Backend | PHP 8+ |
| Database | SQLite3 (file-based, zero config) |
| Media | File uploads stored in `/uploads/` directory |
| Styling | Custom CSS with CSS variables for easy theming |
| Icons | Font Awesome (CDN) |
| Fonts | Google Fonts (CDN) |

---

## Site Architecture

### Public-Facing Pages (7 pages)

#### 1. **Home Page** (`/`)
- Hero section with background video (MP4/WebM) — admin can swap the video file
- Company tagline and call-to-action buttons
- Services overview grid (icon + short description for each service)
- "Why Choose Us" section with key differentiators
- Testimonials carousel (admin-managed)
- Quick contact/CTA banner
- Footer with contact info, hours, quick links

#### 2. **About Page** (`/about`)
- Company story and history
- Mission statement
- Team photos (optional, admin-managed)
- Certifications / licenses
- Service area map or description

#### 3. **Roll Off Containers Page** (`/containers`)
- Overview of roll-off container rental service
- Container size cards (10yd, 15yd, 20yd, 30yd, 40yd) each with:
  - Photo
  - Dimensions
  - Ideal use cases
  - Pricing (if desired, togglable by admin)
- Rental terms / how it works steps
- CTA to order inquiry

#### 4. **Materials & Products Page** (`/materials`)
- Category sections, each with product cards:
  - **Mulch** — Hardwood, dyed black, dyed brown, dyed red, playground, natural, etc.
  - **Stone** — Bluestone, river rock, pea gravel, #2 stone, recycled concrete, etc.
  - **Top Soil** — Screened, unscreened, garden mix
  - **Sand** — Mason sand, concrete sand, fill sand
  - **Bulk Salt** — Road salt, treated salt
- Each product has: photo, name, description, unit pricing (per yard/ton), admin-editable
- Delivery info callout

#### 5. **Trucking Services Page** (`/trucking`)
- Fleet overview with truck photos
- Services offered (delivery, hauling, site cleanup)
- Service area
- Capacity information
- CTA to request a quote

#### 6. **Contact Page** (`/contact`)
- Contact form (name, email, phone, message) — submissions emailed + stored in DB
- Google Maps embed (address configurable in admin)
- Business hours (admin-managed)
- Phone, email, physical address
- Social media links

#### 7. **Order Inquiry Page** (`/order`)
- Multi-step form:
  - Step 1: Select service type (container rental, material delivery, trucking)
  - Step 2: Product/container selection with quantities
  - Step 3: Delivery address and preferred date
  - Step 4: Contact information
  - Step 5: Review and submit
- Submissions stored in DB and emailed to business owner
- Optional: link/redirect to SwipeSimple payment page

### Payment Integration Page (`/payment`)
- Information about payment methods accepted
- Embedded or linked SwipeSimple payment integration
- Admin can configure the SwipeSimple link/embed code
- Secure payment instructions

---

## Admin Panel (`/admin/`)

### Authentication
- Username/password login (hashed with `password_hash()`)
- Session-based auth
- Password change functionality
- Single admin user (expandable)

### Dashboard
- Overview stats: recent inquiries count, recent contact submissions
- Quick links to common tasks

### Content Management Sections

#### 1. **Pages Manager**
- List all pages
- Add new pages / remove pages
- Edit page title, meta description, slug
- Rich text editor (lightweight JS-based, e.g., custom toolbar) for page content
- Toggle page visibility (publish/unpublish)
- Reorder pages in navigation

#### 2. **Hero / Header Manager**
- Upload/replace background video
- Edit hero title text, subtitle, CTA button text and link
- Upload fallback hero image (for mobile/slow connections)

#### 3. **Products Manager**
- CRUD for product categories (Mulch, Stone, Sand, etc.)
- CRUD for individual products within categories
- Upload product photos
- Edit name, description, pricing, unit, availability
- Reorder products within categories
- Toggle product visibility

#### 4. **Containers Manager**
- CRUD for container sizes
- Upload container photos
- Edit size, dimensions, description, pricing, use cases
- Toggle visibility

#### 5. **Testimonials Manager**
- Add/edit/delete testimonials
- Customer name, quote text, optional photo
- Toggle visibility

#### 6. **Contact / Inquiries**
- View all contact form submissions (sortable, searchable)
- View all order inquiry submissions with details
- Mark as read/unread
- Delete old submissions
- Email notification settings

#### 7. **Site Settings**
- Company name, phone, email, address
- Business hours
- Social media URLs
- Google Maps embed URL or coordinates
- Footer text
- SwipeSimple payment link/embed code
- Color theme (primary color, accent color via CSS variables)
- Logo upload
- Favicon upload

#### 8. **Media Library**
- Upload and manage images
- View all uploaded media
- Delete unused media
- Used-by reference tracking

#### 9. **Navigation Manager**
- Reorder menu items
- Add/remove pages from navigation
- Set page as dropdown sub-item

---

## Database Schema (SQLite)

```sql
-- Site-wide settings (key-value store)
CREATE TABLE settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    type TEXT DEFAULT 'text' -- text, image, html, json
);

-- Admin users
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Pages
CREATE TABLE pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT, -- HTML content
    meta_description TEXT,
    is_system INTEGER DEFAULT 0, -- 1 = cannot delete (home, contact, etc.)
    is_published INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    show_in_nav INTEGER DEFAULT 1,
    parent_id INTEGER DEFAULT NULL,
    template TEXT DEFAULT 'default', -- default, products, contact, order
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Product categories
CREATE TABLE product_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    image TEXT,
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1
);

-- Products
CREATE TABLE products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    image TEXT,
    price TEXT,
    unit TEXT, -- per yard, per ton, per load, per day, etc.
    is_available INTEGER DEFAULT 1,
    is_visible INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id)
);

-- Containers (roll-off)
CREATE TABLE containers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL, -- "10 Yard Container"
    size TEXT NOT NULL, -- "10"
    unit TEXT DEFAULT 'yard',
    dimensions TEXT, -- "12' x 8' x 3.5'"
    description TEXT,
    use_cases TEXT, -- JSON array or comma-separated
    image TEXT,
    price TEXT,
    price_note TEXT, -- "Starting at", "Call for pricing", etc.
    is_visible INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0
);

-- Testimonials
CREATE TABLE testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    quote TEXT NOT NULL,
    customer_photo TEXT,
    is_visible INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Contact form submissions
CREATE TABLE contact_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Order inquiry submissions
CREATE TABLE order_inquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_type TEXT NOT NULL, -- container, material, trucking
    product_details TEXT, -- JSON with selections
    delivery_address TEXT,
    preferred_date TEXT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    notes TEXT,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Navigation items
CREATE TABLE navigation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER,
    label TEXT NOT NULL,
    url TEXT,
    parent_id INTEGER DEFAULT NULL,
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    FOREIGN KEY (page_id) REFERENCES pages(id)
);

-- Media library
CREATE TABLE media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    original_name TEXT NOT NULL,
    mime_type TEXT,
    file_size INTEGER,
    alt_text TEXT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Hero sections (per page)
CREATE TABLE hero_sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER,
    title TEXT,
    subtitle TEXT,
    cta_text TEXT,
    cta_link TEXT,
    background_video TEXT,
    background_image TEXT, -- fallback
    overlay_opacity REAL DEFAULT 0.5,
    is_active INTEGER DEFAULT 1,
    FOREIGN KEY (page_id) REFERENCES pages(id)
);
```

---

## File Structure

```
/website/
├── index.php                  # Router / front controller
├── config.php                 # Database connection, constants
├── .htaccess                  # URL rewriting
│
├── database/
│   ├── database.sqlite        # SQLite database file
│   └── seed.php               # Initial data seeder
│
├── includes/
│   ├── header.php             # Site header with nav
│   ├── footer.php             # Site footer
│   ├── functions.php          # Helper functions
│   └── auth.php               # Authentication helpers
│
├── pages/
│   ├── home.php
│   ├── about.php
│   ├── containers.php
│   ├── materials.php
│   ├── trucking.php
│   ├── contact.php
│   ├── order.php
│   ├── payment.php
│   └── custom.php             # Template for admin-created pages
│
├── admin/
│   ├── index.php              # Admin dashboard
│   ├── login.php              # Admin login
│   ├── header.php             # Admin header/nav
│   ├── footer.php             # Admin footer
│   ├── pages.php              # Pages manager
│   ├── page-edit.php          # Page editor
│   ├── products.php           # Products manager
│   ├── product-edit.php       # Product editor
│   ├── categories.php         # Categories manager
│   ├── category-edit.php      # Category editor
│   ├── containers.php         # Containers manager
│   ├── container-edit.php     # Container editor
│   ├── testimonials.php       # Testimonials manager
│   ├── testimonial-edit.php   # Testimonial editor
│   ├── inquiries.php          # View contact/order submissions
│   ├── inquiry-view.php       # View single inquiry
│   ├── settings.php           # Site settings
│   ├── hero.php               # Hero section manager
│   ├── media.php              # Media library
│   ├── navigation.php         # Navigation manager
│   ├── profile.php            # Change password
│   └── api/
│       ├── upload.php         # AJAX file upload handler
│       ├── reorder.php        # AJAX drag-and-drop reorder
│       └── delete.php         # AJAX delete handler
│
├── assets/
│   ├── css/
│   │   ├── style.css          # Main site styles
│   │   ├── variables.css      # CSS custom properties / theme
│   │   ├── responsive.css     # Mobile/tablet breakpoints
│   │   └── admin.css          # Admin panel styles
│   ├── js/
│   │   ├── main.js            # Main site JS (nav, animations)
│   │   ├── forms.js           # Form validation & multi-step
│   │   ├── admin.js           # Admin panel JS
│   │   └── editor.js          # Rich text editor
│   └── images/
│       ├── logo.png           # Default logo
│       ├── favicon.ico
│       └── placeholders/      # Default placeholder images
│
└── uploads/                   # User-uploaded media
    ├── images/
    └── videos/
```

---

## Design Specifications

### Color Palette (Admin-configurable via CSS variables)
- **Primary:** #1B4D3E (dark forest green — earthy, construction/landscape feel)
- **Secondary:** #D4A843 (warm gold — professional accent)
- **Dark:** #1a1a1a
- **Light:** #f5f5f0 (warm off-white)
- **Text:** #333333
- **White:** #ffffff

### Typography
- **Headings:** "Montserrat" (bold, professional)
- **Body:** "Open Sans" (clean, readable)

### Design Principles
- Mobile-first responsive design
- Large, high-quality imagery throughout
- Clear visual hierarchy
- Prominent CTAs (phone number, order inquiry)
- Fast loading (optimized images, minimal dependencies)
- Sticky header with phone number visible
- Breadcrumb navigation on inner pages

---

## Key Features Detail

### Background Video Hero
- Auto-playing, muted, looping MP4/WebM
- Dark overlay for text readability (opacity admin-adjustable)
- Fallback image for mobile and slow connections
- Admin can upload replacement video from admin panel

### Multi-Step Order Inquiry Form
- Progressive disclosure (step-by-step)
- Visual progress indicator
- Form validation at each step
- Summary review before submission
- Stores in database + emails business owner
- Success confirmation with estimated response time

### SwipeSimple Payment Integration
- Admin enters their SwipeSimple payment link or embed code in settings
- Payment page displays the configured integration
- Instructions for customers on how to pay
- Supports both link-out and iframe embed approaches

### Admin Rich Text Editor
- Custom-built lightweight toolbar (no heavy dependencies)
- Bold, italic, underline, headings, lists, links, images
- Insert images from media library
- HTML source view toggle
- Auto-save drafts

### Responsive Design Breakpoints
- Mobile: < 768px (single column, hamburger nav)
- Tablet: 768px–1024px (adjusted grid)
- Desktop: > 1024px (full layout)

---

## Security Measures

- All user input sanitized and validated
- Prepared statements for all database queries (PDO)
- Password hashing with `password_hash()` / `password_verify()`
- CSRF tokens on all forms
- File upload validation (type, size, extension whitelist)
- Admin session management with timeout
- XSS prevention via `htmlspecialchars()` output encoding
- Directory listing disabled in `.htaccess`
- SQLite database file outside web root or protected by `.htaccess`

---

## SEO Foundations

- Semantic HTML5 markup
- Configurable meta titles and descriptions per page
- Open Graph tags for social sharing
- Clean URL structure (`/about`, `/materials`, not `?page=about`)
- Alt text on all images (admin-editable)
- Structured data for local business (JSON-LD)
- XML sitemap generation
- robots.txt

---

## Implementation Order

1. **Phase 1 — Foundation**
   - Project structure and config
   - Database schema and seeder
   - Router (front controller)
   - Base templates (header, footer)
   - CSS framework with variables
   - Responsive navigation

2. **Phase 2 — Public Pages**
   - Home page with video hero
   - About page
   - Containers page
   - Materials/Products page
   - Trucking page
   - Contact page with form
   - Order inquiry multi-step form
   - Payment page

3. **Phase 3 — Admin Backend**
   - Authentication (login/logout/sessions)
   - Admin dashboard
   - Site settings manager
   - Pages manager (CRUD)
   - Products & categories manager
   - Containers manager
   - Testimonials manager
   - Hero section manager
   - Media library
   - Navigation manager
   - Contact/inquiry viewer

4. **Phase 4 — Polish**
   - Rich text editor
   - Drag-and-drop reordering
   - Image optimization
   - Final responsive testing
   - Seed with realistic sample data
   - Default admin credentials documentation

---

## Default Admin Credentials (for initial setup)

- **URL:** `/admin`
- **Username:** `admin`
- **Password:** `HVSupply2024!` (must be changed on first login)
