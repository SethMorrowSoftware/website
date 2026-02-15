# E-Commerce Codebase Audit Report

Date: 2026-02-15  
Repository: `/workspace/website`

## Scope and Method

This audit covered architecture, runtime behavior, and operational readiness for a professional e-commerce deployment.

### Areas reviewed
- Public storefront routing, product/cart/checkout, account flows
- Admin dashboard and API endpoints
- Database schema + migrations
- Security controls (CSRF/session handling, auth flow, upload path)
- SEO and support endpoints (`sitemap.php`, `robots.txt`)

### Validation checks run
1. PHP syntax lint across all PHP files
2. Runtime smoke test with PHP built-in server and endpoint crawl
3. Static review of core flow files (`index.php`, `includes/functions.php`, `includes/migrations.php`, admin order/auth pages)

---

## Executive Summary

The codebase is functionally broad and generally stable (all major routes load, checkout/account guards behave correctly, and baseline security practices are mostly present). However, there are a few **high-impact consistency and production-hardening gaps** that should be fixed before treating this as production-ready.

**Top risks:**
1. **Payment status inconsistency (`paid` vs `completed`)** causes inaccurate sales analytics in admin.
2. **Migration versioning collision** (`005_*.php` and `005_*.sql`) increases schema drift risk.
3. **Admin logout POST path lacks CSRF validation**, allowing forced-logout requests from third-party sites.

---

## Findings

## High

### 1) Payment status mismatch breaks revenue analytics

**Evidence**
- Core order updates treat successful payment as `completed` (`updateOrderPayment`).
- Admin dashboard revenue widgets query `orders` using `payment_status = 'paid'`.
- Order completion flow also checks/sets `completed`.

**Impact**
- Revenue widgets and “top selling products” can report zero/incorrect values even when orders are successfully paid.
- Admins can make incorrect decisions based on broken metrics.

**Recommendation**
- Standardize on one payment status vocabulary (`pending`, `processing`, `completed`, `failed`, etc.).
- Replace admin analytics filters from `'paid'` to the canonical paid state (currently `completed`).
- Add a regression check that seeds a completed order and asserts non-zero dashboard metrics.

## Medium

### 2) Migration numbering collision introduces long-term schema risk

**Evidence**
- Two migrations share version prefix `005`:
  - `005_add_reviews_table.php`
  - `005_product_variants.sql`

**Impact**
- Migration order depends on filename sort shape instead of explicit version progression.
- Future migrations become harder to reason about; conflict/debug time increases.
- Higher chance of partially applied changes in real deployments.

**Recommendation**
- Renumber migrations into a single monotonic sequence without duplicates.
- Add a pre-commit/CI check that enforces unique migration version prefixes.

### 3) Admin logout endpoint is POST-only but not CSRF-protected

**Evidence**
- `admin/login.php` accepts `POST logout` and executes `logout()` without CSRF validation.
- `admin/index.php` also accepts `POST logout` without CSRF token verification.

**Impact**
- Cross-site POST can force an administrator to log out unexpectedly.
- Not a data exfiltration issue, but disrupts workflow and weakens security posture.

**Recommendation**
- Require valid CSRF token for all logout POST handlers.
- Keep logout form tokenized consistently from admin header.

## Low

### 4) Legacy/readme drift may mislead deployment and QA

**Evidence**
- `README.md` still references older service-business pages/components that do not match current e-commerce page set.

**Impact**
- Slower onboarding, mistaken QA expectations, and possible missed route checks.

**Recommendation**
- Refresh README route inventory and module descriptions to match the live e-commerce architecture.

---

## Positive Findings

- Full PHP lint pass with no syntax errors.
- Runtime smoke crawl successful for storefront, account pages, admin login, sitemap, and robots.
- Redirect behavior works for guard routes (e.g., unauthenticated account access and empty checkout redirect).
- Core admin APIs enforce authenticated access + CSRF token checks.
- Upload handler validates MIME type and size and derives extensions from server-side MIME detection.

---

## Route Smoke Test Snapshot

### Pages returning expected `200`
- `/`, `/?page=home`, `/?page=catalog`, `/?page=cart`, `/?page=login`, `/?page=register`, `/?page=wishlist`, `/?page=search&q=test`, `/?page=order-status`, `/?page=order-complete&order=INVALID`, `/?page=forgot-password`, `/?page=reset-password&token=bad`, `/admin/login.php`, `/sitemap.php`, `/robots.txt`

### Pages returning expected redirects (`302`)
- `/?page=checkout` (empty-cart guard)
- `/?page=account` (auth guard)
- `/?page=product&slug=nonexistent` (invalid slug guard)
- `/?page=paypal-checkout&order=bad` (invalid/missing pending order guard)
- `/admin/` (redirect to login when unauthenticated)

---

## Recommended Action Plan

### Phase 1 (Immediate)
1. Normalize payment status semantics and fix admin analytics queries.
2. Add CSRF validation to all admin logout handlers.
3. Renumber migration files to a unique linear sequence.

### Phase 2 (Short-term)
1. Add automated smoke checks for core routes and redirect assertions.
2. Add migration integrity check in CI (fresh DB bootstrap + schema assertion).
3. Update README to e-commerce-specific documentation.

### Phase 3 (Hardening)
1. Add integration tests for checkout/order-complete/account flows.
2. Add admin analytics unit/integration test with seeded completed orders.
3. Add lightweight security checklist gating releases.

---

## Commands Executed During Audit

- `find . -name '*.php' -print0 | xargs -0 -n1 php -l`
- `php -S 127.0.0.1:8090 -t /workspace/website` (runtime smoke server)
- `curl` route sweep for public/admin/SEO endpoints
- `rg` + `nl` review of routing, payment, migration, and auth/logout code paths
