# Marketplace security, CMS update channel and multilingual SEO

Date: 2026-08-29  
Environment: production (`www.eduvixo.com`) and demo CMS (`demo.eduvixo.com`)

## Outcome

The marketplace was rescanned, secured and deployed. The Eduvixo theme is available directly. Eduvixo CMS and the Shoudu theme are available only after a successful AJAX license check. Public package filenames and storage paths are not exposed, and package archives cannot be fetched directly.

The website SEO layer was rebuilt for seven languages: Chinese (`zh`), English (`en`), German (`de`), Lao (`lo`), Polish (`pl`), Thai (`th`) and Vietnamese (`vi`). The 12 public pages now produce 84 independently crawlable, localized URLs.

## Marketplace inventory

- Eduvixo CMS `1.0.0`: listed, browser download protected by license verification, updater download disabled
- Eduvixo theme `1.1.6`: listed, browser download enabled, updater download enabled
- Shoudu theme `1.1.1`: listed, browser download protected by license verification, updater download disabled
- older archive versions remain in private storage for rollback/history

Every ZIP was checked for integrity, unsafe paths and required package metadata before configuration.

## Marketplace security design

- Archives live in `storage/marketplace/packages`, outside Apache's public document root.
- Archive extensions are denied by both repository-root and public `.htaccess` rules.
- Browser downloads require a CSRF-protected POST request.
- The response is an opaque 43-character, single-use token with a three-minute lifetime.
- Tokens are bound to the requesting IP address and user agent and stored as HMAC-derived filenames.
- Tokens are atomically consumed, so a second request receives HTTP 404.
- Package size and SHA-256 are verified immediately before streaming.
- Download and updater requests are rate limited.
- Responses use private/no-store caching and `X-Robots-Tag` restrictions.
- The updater API requires a valid bearer license plus installation identity and delegates license validation to the existing Chivale license service.
- The public markup and public URL never reveal the underlying archive filename.

### Licensed browser downloads

- CMS and Shoudu buttons remain visibly locked but open an accessible native modal.
- The license field has matching browser and server limits of 128 characters; current 32-character keys remain supported.
- Verification is a same-origin JSON request protected by the existing session CSRF token.
- License keys are forwarded over HTTPS to the existing Chivale service and are never written to marketplace storage or logs.
- A successful verification issues the same opaque, IP/user-agent-bound, one-use download token as a public download.
- Invalid attempts are recorded only as counters and timestamps in private HMAC-named files.
- Three invalid keys from one `REMOTE_ADDR` within an hour apply a one-hour lock to licensed browser downloads.
- A valid license clears the failure counter immediately.
- Service/network failures do not count as invalid-license attempts.
- The modal, status messages, button states and errors are localized in all seven website languages.

## CMS update integration

The demo CMS now supplies authenticated marketplace headers from the installed, encrypted license data. Marketplace authorization headers are attached only when the update URL has the configured Eduvixo marketplace origin.

Only `theme:eduvixo` receives the official update URL. CMS and Shoudu remain unavailable through the updater API, while their browser downloads require an independently verified license.

The `extension_packages` row for `theme/eduvixo` was updated with the official catalog URL. No database schema was changed and no rows were added or removed.

End-to-end server verification returned a valid catalog for Eduvixo theme `1.1.6`, downloaded the package through the licensed endpoint and verified its SHA-256 checksum.

## SEO implementation

- unique localized canonical URLs using `/{code}/{page}/`
- reciprocal `hreflang` for all seven languages plus English `x-default`
- persistent explicit language selection without redirecting crawlers
- localized title, description, keywords and social image alternative text
- `robots` and `googlebot` directives allowing maximum image, snippet and video previews
- Open Graph title, description, URL, locale, alternate locales and 1200×630 JPEG image
- Twitter summary-large-image metadata
- canonical, sitemap and web-manifest discovery links
- optional `SITE_GOOGLE_VERIFICATION` meta token support; no token is required when Search Console ownership uses DNS
- JSON-LD graph for Organization, WebSite, WebPage/CollectionPage/ContactPage, BreadcrumbList, FAQPage and SoftwareApplication where appropriate
- no fabricated rating, review, price or social account data
- image sitemap entries for the localized home pages
- `robots.txt` advertises the sitemap and excludes technical download/API routes
- `site.webmanifest` added

### Language URL migration

Canonical public routes use the language-first structure:

- `/en/`, `/pl/`, `/de/`, `/zh/`, `/vi/`, `/th/`, `/lo/`
- `/{code}/product/`, `/{code}/services/`, `/{code}/marketplace/`
- `/{code}/support/`, `/{code}/support/docs/`, `/{code}/support/faq/`, `/{code}/support/knowledge-base/`
- `/{code}/updates/`, `/{code}/contact/`, `/{code}/privacy/`, `/{code}/terms/`

Migration behavior:

- old `?lang={code}` URLs permanently redirect with HTTP 301 to the matching language path
- changing `?lang` on an already localized route also returns HTTP 301 to the requested language path
- missing trailing slashes on localized routes return HTTP 301 to the canonical slash form
- unprefixed routes use existing browser/cookie/system/country detection and temporarily redirect with HTTP 302, avoiding a permanently cached language choice
- unknown localized routes return HTTP 404
- internal navigation, forms, canonical, Open Graph URLs, JSON-LD, breadcrumbs and language switcher all use the new paths
- technical API, download, assets and language-detection endpoints remain unprefixed

The default social image is `public/assets/images/og-default.jpg`, copied byte-for-byte from `.src/images/og-default.jpg`. It is JPEG, 1200×630 and 244,100 bytes.

`public/sitemap.xml` is generated by `scripts/build-sitemap.php` and contains:

- 84 localized URLs
- 672 alternate-language links
- 7 localized image entries
- per-page/per-language modification dates
- no language query parameters

Regenerate the sitemap after changing routes, translations or page templates:

`php scripts/build-sitemap.php`

The sitemap should be registered in Google Search Console as:

`https://www.eduvixo.com/sitemap.xml`

## Main implementation files

- `app/MarketplaceService.php`
- `app/Site.php`
- `app/views/layout.php`
- `app/views/pages/marketplace.php`
- `config/marketplace.php`
- `config/site.php`
- `lang/{zh,en,de,lo,pl,th,vi}.json`
- `public/.htaccess`
- `public/robots.txt`
- `public/site.webmanifest`
- `public/sitemap.xml`
- `public/assets/images/og-default.jpg`
- `public/download/`
- `public/api/marketplace/v1/`
- `resources/site.js`
- `resources/pages.css`
- `scripts/build-sitemap.php`
- `.cms/source/app/Core/LicenseService.php`
- `.cms/source/app/Core/PackageManager.php`
- `.cms/source/config/app.php`
- `.cms/source/public/index.php`

## Deployment and backups

Website document root remains:

`/var/www/clients/client9/web120/web/public`

Marketplace deployment backup:

`/root/eduvixo-backups/marketplace-pre-20260829-151035.tar.gz`

Demo CMS backups:

- `/root/eduvixo-backups/demo-marketplace-code-pre-20260829-151826.tar.gz`
- `/root/eduvixo-backups/demo-extension-packages-pre-20260829-151826.sql`

Final SEO/marketplace pre-deployment backup:

`/root/eduvixo-backups/seo-marketplace-pre-20260829-1432.tar.gz`

Final release:

- local: `tmp/release/eduvixo-seo-marketplace-20260829-1429.tar.gz`
- server: `/root/eduvixo-seo-marketplace-20260829-1429.tar.gz`
- SHA-256: `AAD57D833A96B35D250B6173CA71D1D24DCB876C9BD06892EFE5A67BDF82BF52`

Licensed-download deployment backup:

`/root/eduvixo-backups/license-download-pre-20260829-1524.tar.gz`

Licensed-download release:

- local: `tmp/release/eduvixo-license-download-20260829-1523.tar.gz`
- server: `/root/eduvixo-license-download-20260829-1523.tar.gz`
- SHA-256: `7C07CE792E96728EC0F090E6F9E02CCEB99EF2F13567081E59F8A5479A06168C`

Language-path migration backup:

`/root/eduvixo-backups/language-paths-pre-20260829-1541.tar.gz`

Language-path migration release:

- local: `tmp/release/eduvixo-language-paths-20260829-1540.tar.gz`
- server: `/root/eduvixo-language-paths-20260829-1540.tar.gz`
- SHA-256: `25E7D0728D69182449B42A9A65F320051C31020914FEC6B19BBCCCB37CF1A67E`

Rollback consists of restoring the relevant archive into the matching web root, restoring ownership, running PHP and service configuration tests, then reloading PHP-FPM/Apache only after successful validation. The database rollback is the exact `extension_packages` backup listed above.

## Validation

- 43 website PHP files passed syntax validation
- all four changed CMS PHP files passed syntax validation
- all seven compact translation JSON files decode correctly
- `git diff --check` passed
- all 84 local route/language combinations returned HTTP 200 with valid language, canonical, eight alternates, social metadata and parseable JSON-LD
- all 84 production HTTPS route/language combinations passed the same checks
- all 84 canonical production URLs use language-first paths, contain no `?lang`, have parseable JSON-LD and expose no legacy internal page links
- representative old query URLs return HTTP 301 to exact new equivalents
- localized routes without a trailing slash return HTTP 301; unprefixed auto-detected routes return HTTP 302
- sitemap contains 84 language-prefixed URLs, 672 alternates, 84 English `x-default` entries and no language query parameters
- localized contact form GET and invalid POST preserve their language route
- licensed CMS download still passes license verification, byte count and SHA-256 after the routing migration
- production sitemap, robots, manifest and social image return HTTP 200 with correct MIME types
- production marketplace shows two license-protected buttons with lock icons and one direct download form
- all seven marketplace languages render the modal, localized labels and 128-character field limit
- invalid-license flow returned HTTP 422 with two attempts remaining; the next valid key cleared the counter
- local anti-brute-force flow returned `422, 422, 429, 429` and disabled both protected buttons after the third error
- licensed production downloads streamed CMS (9,392,262 bytes) and Shoudu (9,450,487 bytes) and matched both SHA-256 checksums
- license endpoint rejects GET with 405, unsupported content types with 415 and invalid CSRF with 403
- marketplace HTML contains no `.zip` filename
- browser download produced an opaque token, streamed 1,565,831 bytes, and rejected token reuse with HTTP 404
- direct archive and private-storage URL probes return HTTP 404
- unauthenticated updater API request returns HTTP 401 with no-store JSON
- Apache and PHP-FPM configuration tests passed and both services are active
- no new web errors were recorded after deployment

## Risks and follow-up

- Google does not use the `keywords` meta tag for ranking. It is present because it was explicitly requested, but it is intentionally concise and non-spammy.
- Submit the sitemap in Search Console and request indexing for the home, product, marketplace and support pages if this has not already been done.
- Search visibility also depends on original content, editorial quality, authority/backlinks, performance and ongoing updates; metadata alone cannot guarantee ranking.
- All non-English translations should receive native-speaker editorial review before paid acquisition campaigns.
- The one-hour IP lock intentionally limits impact on institutions sharing a public address; it is not permanent. Distributed attacks remain a wider infrastructure/WAF concern.
- Credentials already tracked under `.cfg/` remain a separate repository-history risk and should be rotated/purged in a dedicated security operation.
