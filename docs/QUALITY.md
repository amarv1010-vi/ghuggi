# Quality, security and performance notes

## Security hardening (Phase 8)

- **HTTPS forced** and HSTS via root `.htaccess`.
- **Security headers**: Content-Security-Policy (no `unsafe-inline`, the site
  ships zero inline styles, scripts or handlers), X-Content-Type-Options,
  X-Frame-Options, Referrer-Policy, Permissions-Policy.
- **Uploads cannot execute code**: blocked in the root `.htaccess` and again by
  an auto-written `uploads/.htaccess` (engine off, handlers removed, deny rule).
- **Upload pipeline**: extension whitelist, `getimagesize` content check, size
  cap, decompression-bomb guard, full GD re-encode to WebP (strips EXIF and any
  embedded payload), randomised filenames, thumbnail generation.
- **Auth**: bcrypt hashes, session cookie `HttpOnly`/`SameSite=Lax`/`Secure`,
  `session_regenerate_id` on login, login rate-limiting, first-run setup that
  locks once an account exists.
- **CSRF** on the contact form and every admin POST. **Honeypot** on the contact
  form. **reCAPTCHA v3** hook points on contact and login (active only when keys
  are set).
- **Secrets** live in `jsd_config/config.php` above the web root; `.htaccess`
  denies direct access to include-only PHP and sensitive file types.
- **SQL**: PDO prepared statements everywhere.

## SEO

- Semantic sectioning, one `h1`, meta description, canonical, Open Graph and
  Twitter card, `theme-color`, `robots.txt`, `sitemap.xml`.
- JSON-LD `GeneralContractor` schema built from the verified business facts.
- OG image and schema image populate automatically from the featured project.

## Performance

- Self-hosted variable WebP-era fonts, preloaded, `font-display: swap`.
- Served images are WebP with thumbnails; gallery images lazy-load; hero uses
  `fetchpriority="high"`.
- Static assets cached and gzip/deflate compressed via `.htaccess`.
- No framework, no build step, minimal vanilla JS loaded with `defer`.

## Accessibility

- Skip link, visible focus styles, keyboard-operable nav, carousel (arrow keys,
  Home/End) and admin.
- All images carry alt text, all form controls have labels, ARIA roles on tabs
  and the carousel, `prefers-reduced-motion` respected.

## Lighthouse

Lighthouse needs headless Chrome, which is not available in the build sandbox,
so scores were **not** generated here (no fabricated numbers). The HTML was
self-audited against Lighthouse's checks and passes: lang, single h1, viewport,
meta description, alt text, labelled controls, JSON-LD, skip link, lazy loading
and an LCP hint.

Run Lighthouse on the live URL after deploy (Chrome DevTools > Lighthouse, or
`npx lighthouse https://jsdconstruction.com.au --view`). This is step 10 of the
deploy runbook. Expected 90+ across the board given the optimisations above;
the main live-only variable is image weight, which the WebP pipeline keeps low.
