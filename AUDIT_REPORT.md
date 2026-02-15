# E-Commerce Platform Audit Report

Date: 2026-02-15  
Repository: `/workspace/website`

## Scope

This audit covered the whole codebase with emphasis on production-readiness for a modern e-commerce website:

- Public storefront routing and page templates
- Checkout/account/order flows
- Admin area and APIs
- Migration and database bootstrap logic
- Security baseline controls (CSRF, sessions, headers)
- Runtime behavior checks using a local PHP server and endpoint crawl

## What Was Tested

1. PHP syntax lint across all PHP files
2. Local runtime crawl of all public routes plus key admin and SEO endpoints
3. Manual review of core files involved in routing, redirects, migrations, and admin security controls

## Executive Summary

The project has a solid base (broad CSRF coverage, secure session defaults, CSP, and many feature-complete flows), but there are **several high-impact reliability issues** that will break expected e-commerce behavior under normal use.

The most urgent issue is architectural: the layout header is rendered before page templates, while multiple templates attempt redirects / status changes. This causes `headers already sent` warnings and failed redirects for common scenarios like empty checkout carts and unauthenticated account access.

## Priority Findings

## Critical

### 1) Redirects and HTTP status changes fail on page templates (headers already sent)

**Evidence**
- `index.php` renders `includes/header.php` before page templates.  
- Multiple templates call `redirect(...)` after execution starts (`checkout`, `account`, `product`, `paypal-checkout`).  
- Runtime crawl produced warnings: `Cannot modify header information` and `http_response_code(): Cannot set response code - headers already sent`.

**Impact**
- Users can be shown the wrong page instead of being redirected correctly.
- Error handling paths (e.g., invalid product slug) degrade into 200 responses with partially rendered pages.

**Recommendation**
- Move all guard/redirect logic into a pre-render phase in `index.php` before including layout files, OR
- Use output buffering plus strict controller-style routing where templates never call `redirect()`.

## High

### 2) Migration runner marks failed migrations as executed

**Evidence**
- `includes/migrations.php` catches migration exceptions and then inserts the failed filename into `migrations` via `INSERT OR IGNORE`.

**Impact**
- Broken schema changes are silently “accepted,” leaving databases in inconsistent states that are hard to detect and repair.
- Fresh deployments may appear healthy while missing required columns/tables.

**Recommendation**
- Do not mark failed migrations as executed.
- Fail fast and block startup (or at least surface hard admin-visible error) until migration issues are resolved.

### 3) SQL migration parser drops valid statements when preceded by SQL comments

**Evidence**
- SQL files are split by `;` and any resulting chunk starting with `--` is skipped.
- In mixed comment+statement chunks, the statement is skipped as well.
- Runtime logs show shipping migration failure (`NOT NULL constraint failed: shipping_methods.zone_id`), consistent with skipping the zone insert while still running dependent inserts.

**Impact**
- Partial migration execution and nondeterministic schema/data setup.
- High risk of environment-specific failures on first boot.

**Recommendation**
- Replace the naive `explode(';', ...)` parser with a safer SQL execution strategy (execute whole SQL file where possible, or robust parser).
- Add integration test for full migration bootstrap on a fresh DB.

### 4) Duplicate/overlapping migration tracks create conflicting schema operations

**Evidence**
- Both `.php` and `.sql` migrations exist for related features (inventory, coupons, etc.).
- Runtime logs show duplicate column errors such as `duplicate column name: stock_quantity` and `duplicate column name: coupon_code`.

**Impact**
- Startup logs contain recurring migration errors.
- Fresh installs are at risk of subtle schema drift depending on execution order.

**Recommendation**
- Consolidate each migration into a single authoritative file.
- Introduce strict naming/versioning policy and migration CI check to prevent overlap.

## Medium

### 5) Mixed logout behavior in admin surface

**Evidence**
- `admin/login.php` uses POST logout.
- `admin/index.php` still accepts GET logout (`isset($_GET['logout'])`).

**Impact**
- Inconsistent semantics and potential forced-logout nuisance via GET links.

**Recommendation**
- Remove GET-based logout from admin dashboard; keep POST + CSRF only.

### 6) Route health appears “200 OK” even when logic attempted redirects/errors

**Evidence**
- Crawl returns 200 for pages that should redirect (`checkout`, `account`, invalid product) due to header-send ordering bug.

**Impact**
- Monitoring can miss broken flows because HTTP status does not reflect application intent.

**Recommendation**
- Fix render pipeline, then add route smoke tests asserting expected status codes and redirect targets.

## Positive Findings

- CSRF validation is enforced in admin API endpoints (`upload`, `delete`, `reorder`).
- Session cookie hardening is present (`httponly`, `strict_mode`, `samesite`, conditional `secure`).
- Security headers include CSP, HSTS (under HTTPS env), X-Frame-Options, and Referrer-Policy.
- `robots.txt` correctly points to an existing sitemap endpoint (`/sitemap.php`).

## Remediation Plan

### Phase 1 (Immediate, 1-2 days)
1. Refactor request lifecycle so redirects/status are resolved before any HTML output.
2. Make migration failures blocking and visible.
3. Fix SQL migration execution strategy and re-verify fresh bootstrap.

### Phase 2 (Short term, 3-5 days)
1. Consolidate duplicate migration tracks and regenerate a clean baseline.
2. Remove admin GET logout path.
3. Add automated smoke tests for key customer journeys:
   - empty cart -> checkout redirect
   - unauthenticated -> account redirect
   - invalid product -> correct 404/redirect behavior

### Phase 3 (Stabilization, 1 week)
1. Add CI job for fresh DB boot + migration verification.
2. Add runtime health checks asserting no PHP warnings in logs during route crawl.

## Commands Run During Audit

- `find . -name '*.php' -print0 | xargs -0 -n1 php -l`
- Local server crawl via `php -S 127.0.0.1:8090 -t .` and `curl` across all public pages/admin entry points
- Targeted code inspection with `sed`, `nl`, and `rg`

