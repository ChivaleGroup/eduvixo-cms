# Footer row alignment and CH icon removal

Date: 2026-08-29

Environment: local working copy and production (`www.eduvixo.com`)

## Outcome

- Removed the decorative `CH` mark from the Chivale hosting credit.
- Removed the unused `public/assets/images/chivale-mark-white.svg` asset and all markup and CSS references to it.
- Preserved the linked `Hosting provided by Chivale Group.` text as the second line of the left credit block.
- Aligned the localized `footer.identity` text with the first copyright line on desktop by using top alignment for the footer row.
- Preserved the responsive stacked footer layout on small screens.

## Files

- `app/views/layout.php`
- `resources/pages.css`
- `public/assets/css/site.min.css`
- Removed: `public/assets/images/chivale-mark-white.svg`

## Deployment

Production root: `/var/www/clients/client9/web120/web`

Release: `/root/eduvixo-footer-row-20260829-190127.tar.gz`

SHA-256: `C10B213054E798F1FA84D96201476D7B1174274B786D2FEA035BEF2DFF887E47`

Pre-deployment backup: `/root/eduvixo-backups/footer-row-pre-20260829-190127.tar.gz`

Rollback: extract the backup into the production root, restore ownership to `web120:client9`, validate PHP and the Apache configuration, then reload PHP-FPM. The backup restores both the previous footer files and the removed SVG asset.

## Validation

- All 43 PHP files passed syntax validation.
- Source and generated JavaScript passed syntax validation.
- The generated CSS asset was rebuilt successfully and `git diff --check` passed.
- All 84 local and all 84 production page/language combinations returned HTTP 200 without a CH image or obsolete asset reference.
- The removed production SVG returns HTTP 404.
- Browser measurements on local and production pages confirmed that the copyright and platform identity top positions are identical, with a difference of `0 px`.
- The footer contains no image, the computed row alignment is `flex-start`, and the browser console contains no warnings or errors.
- Apache and PHP-FPM are active, with no recent critical log entries.

## Impact

- No database, DNS, SSL, firewall or document-root changes.
- PHP-FPM was reloaded after validation; no service interruption was required.
