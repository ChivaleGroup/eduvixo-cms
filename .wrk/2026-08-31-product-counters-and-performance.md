# Product counters and website performance - 2026-08-31

## Scope

- Removed the decorative Product metric strip without replacing it with another non-verifiable counter.
- Removed the obsolete `product.metrics` translation branch from all seven locales.
- Implemented lazy PHP sessions: ordinary public GET pages no longer create `eduvixo_site`; Contact and Marketplace retain sessions and CSRF protection.
- Added a five-minute private browser cache policy with a 60-second stale-while-revalidate window for ordinary localized pages.
- Preserved strict `no-store` handling for Contact, Marketplace, JSON/API responses, language-dependent redirects and the Demo redirect.
- Added consent-aware native Core Web Vitals reporting for LCP, INP and CLS. Measurements are sent to the existing GA4 integration only after analytics consent.
- Windows application signing was explicitly deferred until the application is final.

## Implementation

- `app/Site.php`: lazy sessions and route-sensitive cache headers.
- `app/views/pages/product.php`, `resources/pages.css`: counter removal and balanced Product section spacing.
- `resources/vitals.js`, `scripts/build-assets.php`: dependency-free Web Vitals collection included in the existing minified JavaScript bundle.
- `public/.htaccess`: static assets remain immutable; PHP responses control their own safe cache policy; JSON stays `no-store`.
- `public/demo/index.php`: explicit redirect `no-store` policy.
- `lang/*.json`: 585 structurally identical translation leaves per locale after obsolete metric removal.
- `public/sitemap.xml`: regenerated, 84 localized URLs.

## Deployment

- Target: `/var/www/clients/client9/web123/web`.
- Published atomically as `web123:client9`, mode `0640`.
- Files published: 17.
- Database changes: none.
- Infrastructure/service configuration changes: none.
- Apache configuration test: `Syntax OK`. The pre-existing `NameVirtualHost has no effect` notice remains unrelated to this release.
- Initial deployment preflight stopped before backup or publication because the new `resources/vitals.js` did not exist in production. The procedure was corrected to classify it as a new file and the abandoned private staging directories were removed.

## Backup and rollback

- Backup: `/root/eduvixo-backups/product-performance-pre-20260830-223523`.
- `website-files.tar.gz`: SHA-256 `7d1ae0959454aba6df1f8c635cb5512dc41a08b4265f7727ab31c4d1fe6beb5c`.
- `ROLLBACK.txt`: SHA-256 `bd37c27543992d77f6ccfbd79b6f815d531c513a03505de9e033051407dfdd56`.
- Rollback: extract the backup over the website root, remove `resources/vitals.js`, restore `web123:client9` ownership and mode `0640`, then repeat the route/cache audit.

## Validation

- PHP syntax: application, Product view, Demo redirect, build script and deployment scripts passed.
- JavaScript syntax: source and compiled bundle passed `node --check`.
- Language audit: seven locales, 585 keys each, zero missing, empty, control-character, em-dash, placeholder or replacement-character issues.
- Local route audit: 84/84 localized routes passed.
- Production route/SEO audit: 84/84 localized routes passed.
- Production cache audit: 70 ordinary public routes use the cache policy without PHP sessions; 14 Contact/Marketplace routes use `no-store`, sessions and CSRF.
- Product checks: no counter markup, styles or translation data remain.
- Responsive visual verification: desktop 1440 x 900 and mobile 390 x 844; six capability modules present, no horizontal overflow and no browser console errors.
- Production ownership checked for representative application, Apache and new source files.
- Production error log showed no matching PHP fatal, warning or parse errors after verification traffic.

## Residual considerations

- Browser caching is deliberately private because localized HTML contains visitor-specific language selection and CSP nonces. A shared reverse-proxy/CDN cache would require nonce and language-aware edge design before it can be enabled safely.
- Core Web Vitals data will appear only for visitors who grant analytics consent, by design.
