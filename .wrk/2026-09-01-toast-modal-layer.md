# Global toaster/modal layer correction - 2026-09-01

## Root cause and correction

The global `.eduvixo-toasts` container used `z-index: 9999`, while My Calendar used `10020` for its blurred backdrop and `10021` for its modal. Calendar feedback therefore remained behind the backdrop and appeared dimmed and blurred.

The global toaster layer is now `12050`, above all standard CMS extension modals and the accessibility panel. The console and license views use the cache key `20260901-toast-layer-1`, so existing browsers fetch the corrected stylesheet immediately. The change is released through the signed Base CMS `1.0.12` Stable core and clean installer rather than as a Calendar-only override.

## Deployment

- Public Marketplace: `/var/www/clients/client9/web123/web` (`web123:client9`).
- Demo CMS: `/var/www/clients/client9/web121/web` (`web121:client9`).
- Shoudu CMS: `/var/www/clients/client59/web119/web` (`web119:client59`).
- Both CMS installations were updated from Base CMS `1.0.11` to `1.0.12` through the signed system updater.
- Active themes, extension state and production data were preserved.
- Apache configuration remained valid; Apache, PHP-FPM and cron remained active. PHP-FPM was reloaded after deployment.

## Backup and rollback

Production backup: `/root/eduvixo-backups/base-cms-1.0.12-toast-pre-20260901-154721`.

- `demo.sql`: `f27035f0e2db7e814ff6d13dbc0ddcc65629cb5b8114494e0c54e7ee0467a3c8`
- `demo.tar.gz`: `74e84b0e6a692ee33dca40a07f646948641e4717599804dd881b245912b3ec71`
- `shoudu.sql`: `1a83bc197ef83979ec3d20a5182ed42e9d36513b39adabb75e1ac403d4d26202`
- `shoudu.tar.gz`: `9f07ec040abb9f4d482c1324752390f176c2f96c8d49037a046acf0dc7ee4f20`
- `website-marketplace.tar.gz`: `39f9d92a6b1f6bb85cbafdbf180a4f10f8f2a0c9319df26a1d918acd9e89defd`
- Rollback instructions are stored as `ROLLBACK.txt` in the backup directory.

Local intermediate release artifacts moved outside the repository to `F:\Git\ChivaleGroup\.backups\toast-layer-rebuild-20260901`.

## Verification

- Signed release and Marketplace audit: 106 assertions.
- Clean installer test against an isolated MariaDB database: 12 assertions.
- Production audit: 13 assertions covering both releases, layer value, cache key, maintenance state, signed catalogue, package hashes, backup completeness and the absence of a test event.
- Full syntax/structure pass: 248 PHP files, 35 JavaScript files and 35 JSON files.
- Live CSS returned HTTP 200 from both domains and contained `z-index:12050`.
- Authenticated browser verification reproduced the six-reminder validation warning while the Calendar modal and blur backdrop were open. Computed layers were toaster `12050`, overlay `10020` and modal `10021`; the toast itself had no CSS `filter` and remained outside the blurred layer.
- The verification title was rejected before persistence; no Calendar event was created.
- No new PHP fatal, parse, warning or uncaught application errors appeared after deployment.
