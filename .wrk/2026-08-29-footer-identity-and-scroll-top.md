# Footer identity restoration and back-to-top control

Date: 2026-08-29

Environment: local working copy and production (`www.eduvixo.com`)

## Root cause

The original right-aligned platform identity was removed when the footer credits were changed from one row to two rows. The hosting credit was incorrectly treated as a replacement for the entire second footer item instead of an addition to the left credit block.

## Outcome

- Restored the localized `footer.identity` text on the right side below the footer divider. The English value is `Education Digital Experience & Communication Platform`.
- Preserved the two-line copyright and hosting credits on the left.
- Reduced the apparent `CH` mark stroke by removing the non-scaling stroke behavior and lowering its opacity. The source shape remains unchanged.
- Added a subtle fixed circular back-to-top control on the right side.
- The control is hidden, marked `aria-hidden` and removed from keyboard order at the top of the page.
- It becomes visible and keyboard-focusable after 480 pixels of vertical scrolling.
- Activation returns to the top with smooth scrolling, while respecting the operating system's reduced-motion preference.
- Added localized accessible labels for all seven website languages.
- Positioned the desktop control above the footer identity row to avoid covering text; the mobile layout uses a compact lower-right position.

## Files

- `app/views/layout.php`
- `lang/{zh,en,de,lo,pl,th,vi}.json`
- `resources/pages.css`
- `resources/site.js`
- `public/assets/css/site.min.css`
- `public/assets/js/site.min.js`
- `public/assets/images/chivale-mark-white.svg`

## Deployment

Production root: `/var/www/clients/client9/web120/web`

Release: `/root/eduvixo-footer-scroll-top-20260829-185041.tar.gz`

SHA-256: `633EDE5314A34727BB24E042B7852B67365E6FEF58FE23BF444505C1CD438D49`

Pre-deployment backup: `/root/eduvixo-backups/footer-scroll-top-pre-20260829-185041.tar.gz`

Rollback: extract the backup into the production root, restore ownership to `web120:client9`, validate PHP, JSON, JavaScript and SVG assets, run `apache2ctl configtest`, and reload PHP-FPM only after successful checks.

## Validation

- 43 PHP files passed syntax validation.
- All seven translation files decode and share the English schema.
- JavaScript syntax, generated assets and `git diff --check` passed.
- The `CH` SVG parses as XML, contains two paths, has no external references and no longer uses a non-scaling stroke.
- All 84 local and all 84 production page/language combinations returned HTTP 200 with the restored identity and localized back-to-top control.
- Desktop and 390-pixel mobile layouts were visually inspected.
- Browser interaction tests confirmed the control is hidden and non-focusable at position 0, visible and focusable after scrolling, and returns to position 0 when activated.
- Production Apache and PHP-FPM are active, and recent application logs contain no matching PHP or Apache errors.

## Impact

- No database, DNS, SSL, firewall or document-root changes.
- PHP-FPM was reloaded after validation; no service interruption was required.
