# E-Commerce Codebase Audit Report

Date: 2026-02-15  
Repository: `/workspace/website`

## Scope and Method

This review focused on production readiness for a modern e-commerce storefront and admin system.

### Coverage
- Storefront routing and feature guards (`index.php`, `pages/*`, `includes/header.php`)
- Checkout, payment callbacks, and order state transitions (`index.php`, `includes/functions.php`, `admin/api/*`)
- Admin configuration and controls (`admin/settings.php`, `admin/*`)
- Security controls (session/cookie settings, CSRF, webhook verification, upload/delete/reorder APIs)
- Deployment/documentation consistency (`README.md`, `.htaccess`, schema/migrations)

### Validation checks executed
1. PHP lint across all PHP files
2. Local runtime smoke checks on public/admin routes
3. Link spider smoke check from homepage
4. Admin/public API auth behavior checks
5. Static code review of critical paths

---

## Executive Summary

The project is in **good functional shape** at a baseline: pages render, syntax is clean, and the major flows are implemented with CSRF/session protections. However, there are several **high-impact configuration and security-hardening issues** that should be addressed before production rollout.

### Overall readiness (current)
- **Reliability:** Moderate–High
- **Security:** Moderate (needs tightening for webhooks + credential hygiene)
- **Operational readiness:** Moderate (feature toggles are inconsistent with admin UI)
- **Documentation quality:** Moderate-Low (README drift from current architecture)

---

## Findings

## High Severity

### 1) Feature flags are inconsistent, so some "disabled" features may still render as enabled

**Evidence**
- `isFeatureEnabled()` maps `catalog/cart/order/contact/testimonials/about/reviews`, but **does not map** `customer_accounts`, `search`, or `wishlists` to their stored keys (`enable_customer_accounts`, `enable_search`, `enable_wishlists`).
- Header and routing checks call `isFeatureEnabled('search')`, `isFeatureEnabled('wishlists')`, `isFeatureEnabled('customer_accounts')`, which will default to key names that are not the persisted setting keys.
- Admin settings UI includes checkboxes for core features, but not customer accounts/search/wishlists/reviews toggles in this screen.

**Impact**
- Feature gating can be misleading: admins may expect a feature to be disabled while it still appears to users.
- Increased support/debug burden because behavior depends on mismatched key naming conventions.

**Recommendation**
- Add explicit mappings in `isFeatureEnabled()` for:
  - `customer_accounts => enable_customer_accounts`
  - `search => enable_search`
  - `wishlists => enable_wishlists`
- Add corresponding toggle controls in `admin/settings.php` and persist them in checkbox handling.
- Add a regression check that asserts nav/page guard behavior for all feature switches.

## Medium Severity

### 2) BTCPay webhook signature verification can be bypassed when secret is unset

**Evidence**
- `verifyBTCPayWebhookSignature()` returns `true` when `btcpay_webhook_secret` is empty.
- Public webhook endpoint accepts requests and processes order status events if signature validation returns true.

**Impact**
- In environments where admins forget to set a secret, webhook authenticity is not enforced.
- Attackers could spoof status events and alter payment state.

**Recommendation**
- Fail closed for production: reject all webhook requests unless a secret is configured.
- Add explicit admin warning banner in settings when BTCPay is enabled but webhook secret is missing.
- Optionally add source IP allowlisting or replay protection.

### 3) First-run credential artifact risk (plaintext admin password file)

**Evidence**
- Seeder generates random admin credentials and writes them to `ADMIN_CREDENTIALS.txt` at repo root.
- README instructs manual deletion after first login.

**Impact**
- If this file is left on disk in production (or exposed via misconfigured web server), admin takeover risk increases.

**Recommendation**
- Keep creation behavior for bootstrap, but add forced expiration flow:
  - Require password change on first login.
  - Auto-delete credential file after successful first admin login.
- Add startup health check warning if file still exists.

## Low Severity

### 4) Documentation drift (service-business template docs vs current e-commerce implementation)

**Evidence**
- README sections still reference routes/modules that no longer reflect current page inventory and e-commerce feature set.

**Impact**
- Slower onboarding and QA confusion.
- Higher risk of missed regression checks due to stale docs.

**Recommendation**
- Update README route map, feature inventory, and payment integration behavior to match the actual codebase.

---

## Positive Findings

- All PHP files passed syntax lint (`php -l`) during this audit.
- Public storefront pages and major flows returned expected status codes in smoke checks.
- Internal link spider (depth-limited) reported no broken links from homepage crawl.
- Admin mutation APIs (`upload/delete/reorder`) correctly require authentication and return 401 when unauthenticated.
- CSRF checks are broadly and consistently present in public and admin form handlers.

---

## Smoke Check Snapshot

### Expected `200`
- `/`, `/?page=home`, `/?page=catalog`, `/?page=cart`, `/?page=login`, `/?page=register`, `/?page=wishlist`, `/?page=contact`, `/?page=order`, `/?page=search&q=test`, `/admin/login.php`

### Expected redirects (`302`)
- `/?page=checkout` (empty-cart guard)
- `/?page=account` (auth guard)
- `/?page=product&slug=nonexistent` (invalid product slug guard)
- `/admin/` (admin auth guard)

### API guard behavior
- `POST /admin/api/upload.php` → 401 unauthenticated
- `POST /admin/api/delete.php` → 401 unauthenticated
- `POST /admin/api/reorder.php` → 401 unauthenticated
- `POST /admin/api/paypal-create.php` → 403 without CSRF/session
- `POST /admin/api/paypal-capture.php` → 403 without CSRF/session

---

## Priority Action Plan

### Immediate (P0)
1. Fix feature-flag key mapping + expose missing toggles in admin settings.
2. Enforce mandatory BTCPay webhook secret verification when BTCPay is enabled.
3. Mitigate credential-file lifecycle risk (auto-delete + first-login password enforcement).

### Short-term (P1)
1. Add route + feature-toggle integration checks to CI.
2. Add payment-webhook negative tests (invalid signatures should always fail).
3. Refresh README to match current architecture and route list.

### Hardening (P2)
1. Add end-to-end happy-path checkout tests for each payment provider mode.
2. Add admin configuration health dashboard checks (missing secrets, insecure defaults).
3. Add deployment checklist for Apache/Nginx parity (sensitive file blocking, security headers).

---

## Commands Executed During Audit

- `find . -name '*.php' -print0 | xargs -0 -n1 php -l`
- `php -S 127.0.0.1:8080 -t /workspace/website`
- `curl` sweep of key public/admin routes for status validation
- `wget --spider --recursive --level=2 --no-verbose --adjust-extension --reject-regex 'logout|delete|api' http://127.0.0.1:8080/`
- `curl -X POST` checks against admin APIs for auth/CSRF guard behavior
- `rg`/`sed` static review across routing, settings, payment, and security code paths
