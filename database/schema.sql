-- Business Website CMS
-- Database Schema

-- Site-wide settings (key-value store)
CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT,
    type TEXT DEFAULT 'text'
);

-- Admin users
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Pages
CREATE TABLE IF NOT EXISTS pages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    content TEXT,
    meta_description TEXT,
    is_system INTEGER DEFAULT 0,
    is_published INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    show_in_nav INTEGER DEFAULT 1,
    parent_id INTEGER DEFAULT NULL,
    template TEXT DEFAULT 'default',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Product categories
CREATE TABLE IF NOT EXISTS product_categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    image TEXT,
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1
);

-- Products
CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category_id INTEGER NOT NULL,
    name TEXT NOT NULL,
    slug TEXT UNIQUE NOT NULL,
    description TEXT,
    image TEXT,
    price TEXT,
    unit TEXT,
    is_available INTEGER DEFAULT 1,
    is_visible INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES product_categories(id) ON DELETE CASCADE
);

-- Containers (roll-off)
CREATE TABLE IF NOT EXISTS containers (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    size TEXT NOT NULL,
    unit TEXT DEFAULT 'yard',
    dimensions TEXT,
    description TEXT,
    use_cases TEXT,
    image TEXT,
    price TEXT,
    price_note TEXT,
    is_visible INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0
);

-- Testimonials
CREATE TABLE IF NOT EXISTS testimonials (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    customer_name TEXT NOT NULL,
    quote TEXT NOT NULL,
    customer_photo TEXT,
    is_visible INTEGER DEFAULT 1,
    sort_order INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Contact form submissions
CREATE TABLE IF NOT EXISTS contact_submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT,
    message TEXT NOT NULL,
    is_read INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Order inquiry submissions
CREATE TABLE IF NOT EXISTS order_inquiries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    service_type TEXT NOT NULL,
    product_details TEXT,
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
CREATE TABLE IF NOT EXISTS navigation (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_id INTEGER,
    label TEXT NOT NULL,
    url TEXT,
    parent_id INTEGER DEFAULT NULL,
    sort_order INTEGER DEFAULT 0,
    is_visible INTEGER DEFAULT 1,
    FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE SET NULL
);

-- Media library
CREATE TABLE IF NOT EXISTS media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    original_name TEXT NOT NULL,
    mime_type TEXT,
    file_size INTEGER,
    alt_text TEXT,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Login rate limiting
CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ip_address TEXT NOT NULL,
    username TEXT NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Hero sections
CREATE TABLE IF NOT EXISTS hero_sections (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    page_slug TEXT,
    title TEXT,
    subtitle TEXT,
    cta_text TEXT,
    cta_link TEXT,
    background_video TEXT,
    background_image TEXT,
    overlay_opacity REAL DEFAULT 0.5,
    is_active INTEGER DEFAULT 1
);
