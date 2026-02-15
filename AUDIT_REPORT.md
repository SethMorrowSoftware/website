# Full Platform Audit Report

Date: 2026-02-14
Project: Business Website CMS (`/workspace/website`)

## Scope & Method

This audit reviewed the full PHP application codebase, including:
- Public site routing and page templates
- Admin authentication, CMS flows, and AJAX endpoints
- Database schema and seeding behavior
- Upload handling and file/path controls
- Security-related server configuration (`.htaccess`, sessions, CSRF)
- Baseline operational checks (PHP syntax linting)

## Executive Summary

The platform has a good foundation (prepared SQL statements, escaped output in many places, CSRF support in standard forms), but it is **not yet "rock solid" for a non-technical business owner in production**.

Primary concerns are:
1. **Critical security risks** around default credentials and admin-stored raw HTML execution.
2. **High-risk hardening gaps** in CSRF coverage for admin APIs and destructive GET actions.
3. **Operational resilience gaps** (no rate limiting, no strong security headers/CSP, no reliable email queueing/logging).
4. **Business-owner usability gaps** (missing favicon rendering, broken sitemap reference, insufficient server-side validation depth).

## Findings (Prioritized)

## Critical

### 1) Default seeded admin credentials are predictable
- Evidence: Seeder previously created `admin` user with a known password (now fixed — password is randomly generated).
- Risk: If database is ever initialized in a reachable environment, account takeover is immediate.
- Suggested fix:
  - Remove hardcoded default password from seed routine.
  - Require first-run bootstrap flow to set admin credentials.
  - Optionally block startup if `users` table empty and setup not complete.

### 2) Stored HTML is rendered without sanitization in multiple user-facing contexts
- Evidence:
  - Custom page content renders raw (`$customPage['content']`).
  - Payment embed renders raw (`$swipesimpleEmbed`).
  - Footer text allows HTML and is output directly.
- Risk: Stored XSS is possible if admin account/session is compromised or content is imported unsafely.
- Suggested fix:
  - Sanitize allowed HTML via a whitelist sanitizer (e.g., HTML Purifier).
  - For embed snippets, restrict to validated `<iframe>` with allowlisted domains.
  - Treat risky settings as privileged and audit-log edits.

## High

### 3) Admin AJAX endpoints do not enforce CSRF token validation
- Evidence: `admin/api/upload.php`, `admin/api/delete.php`, `admin/api/reorder.php` verify auth and method but not CSRF.
- Risk: Cross-site requests from attacker-controlled pages can trigger admin-side changes while logged in.
- Suggested fix:
  - Require CSRF token in custom header (e.g., `X-CSRF-Token`) and validate server-side.
  - Rotate CSRF token on login/session refresh.

### 4) Destructive/admin state-changing actions performed via GET query links
- Evidence: delete/toggle/mark-read actions in admin pages use GET params + CSRF token in URL.
- Risk:
  - URL token leakage (logs, referrers, browser history).
  - GET semantics not safe for state-changing operations.
- Suggested fix:
  - Convert to POST-only forms/actions.
  - Keep CSRF token in POST body, not query string.

### 5) Login endpoint lacks brute-force/rate limiting protections
- Evidence: Login attempts only check credentials and CSRF; no lockout/backoff/captcha.
- Risk: Credential stuffing and password guessing.
- Suggested fix:
  - Add per-IP and per-username throttling.
  - Exponential backoff and temporary lockouts.
  - Optional captcha after failed-attempt threshold.

### 6) Session cookie hardening is incomplete for production security
- Evidence: `httponly` and strict mode are set; no explicit `session.cookie_secure` or `session.cookie_samesite`.
- Risk: Weaker browser/session protection, especially on mixed deployments.
- Suggested fix:
  - Set `session.cookie_secure=1` under HTTPS.
  - Set `session.cookie_samesite=Lax` (or Strict for admin if compatible).
  - Ensure consistent secure session params before `session_start()`.

## Medium

### 7) Upload pipeline trusts extension from original filename and allows SVG
- Evidence: MIME checked via finfo (good), but output extension comes from user filename and SVG is allowed.
- Risk:
  - SVG can carry active content depending on browser/CSP context.
  - Extension mismatch can complicate downstream policies.
- Suggested fix:
  - Derive extension from server-side MIME map, not original filename.
  - Consider disallowing SVG for non-admin previews, or sanitize SVG.
  - Serve uploads from separate domain or strict CSP sandbox.

### 8) Security headers are incomplete/outdated; CSP missing
- Evidence: `.htaccess` sets some headers, includes deprecated `X-XSS-Protection`, no CSP/HSTS.
- Risk: Reduced defense-in-depth against XSS/data injection and protocol downgrade.
- Suggested fix:
  - Add `Content-Security-Policy` (start report-only, then enforce).
  - Add `Strict-Transport-Security` in HTTPS production.
  - Add `Permissions-Policy` and modernize header set.

### 9) Mail send uses suppressed errors and no delivery observability
- Evidence: `@mail(...)` used for contact and order notifications.
- Risk: Silent message failures; owner may miss leads.
- Suggested fix:
  - Replace with transactional provider (SMTP/API).
  - Log delivery attempts/failures and alert on repeated failure.
  - Queue mail async if traffic grows.

### 10) Logout can be triggered via GET
- Evidence: `admin/login.php` handles `?logout=1` and POST logout.
- Risk: CSRF-ish nuisance / forced logout (availability/usability impact).
- Suggested fix:
  - Remove GET logout support.
  - Use POST + CSRF token only.

## Low / Business UX & Maintainability

### 11) Favicon setting exists but is not injected into public `<head>`
- Evidence: admin supports favicon upload, but header doesn't output favicon link tag.
- Risk: incomplete branding polish and confusion for owner.
- Suggested fix:
  - Render `<link rel="icon" ...>` using stored setting (fallback default file).

### 12) `robots.txt` references `/sitemap.xml`, but sitemap file is absent
- Evidence: `Sitemap: /sitemap.xml` present; no sitemap in repo root.
- Risk: SEO crawl inefficiency and quality signal reduction.
- Suggested fix:
  - Add generated sitemap endpoint/file and include canonical URLs.

### 13) Limited server-side validation for order payload depth
- Evidence: backend checks only top-level required fields; service-specific requirements rely mainly on frontend flow.
- Risk: malformed/low-quality leads, support overhead.
- Suggested fix:
  - Add server-side service-type-specific validation rules.
  - Normalize and validate phone/date/address formats.

## Positive Observations

- Prepared statements are used broadly, reducing SQL injection risk.
- Output escaping (`e()`) is used consistently in most display paths.
- CSRF token mechanism exists and is used for many forms.
- Session ID regeneration occurs on successful login.
- `.htaccess` blocks direct access to `database/` and `includes/`.

## Recommended Remediation Roadmap

### Phase 1 (Immediate: 1–3 days)
- Remove seeded default credentials and enforce first-run admin setup.
- Add CSRF validation to all admin API endpoints.
- Convert destructive GET operations to POST-only.
- Remove GET logout; require POST+CSRF.

### Phase 2 (Hardening: 3–7 days)
- Add login throttling/lockout.
- Implement CSP (report-only first), then enforce.
- Set secure + samesite session cookie policies.
- Restrict/sanitize stored HTML and embeds.

### Phase 3 (Reliability & polish: 1–2 weeks)
- Replace `mail()` with transactional mail provider + logging.
- Implement sitemap generation and wire into robots.
- Add favicon output in global header.
- Expand server-side validation + admin input guardrails.

## Checks Run

- PHP syntax linting across all PHP files: passed.
- Manual code review of routing, auth, admin CRUD, API endpoints, uploads, and templates.
