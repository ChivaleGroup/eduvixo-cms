# Footer partner credits

Date: 2026-08-29

Environment: local working copy and production (`www.eduvixo.com`)

## Outcome

- Replaced the former year-based Eduvixo copyright line with `© Copyright by Eduvixo & QUANT Software House. All rights reserved.`.
- Added a second line using the more natural wording `Hosting provided by Chivale Group.`.
- Linked QUANT Software House to `https://www.ittsp.com/?IdRef=eduvixo.com` and Chivale Group to `https://www.chivale.com/?IdRef=eduvixo.com`.
- Both links open in a new browser context with `noopener noreferrer` protection.
- Links retain the surrounding footer typography without bold styling or permanent decoration. Underlining appears only on hover and keyboard focus.
- The same partner credits are intentionally displayed on all seven language variants.

## Files

- `app/views/layout.php`
- `resources/pages.css`
- `public/assets/css/site.min.css`

## Deployment

Production root: `/var/www/clients/client9/web120/web`

Release: `/root/eduvixo-footer-credits-20260829-181705.tar.gz`

SHA-256: `8DD4AB19DFCF0A0115FA5B3726507FB36C4265CE4319A9FB5DFDFF445B475470`

Pre-deployment backup: `/root/eduvixo-backups/footer-credits-pre-20260829-181705.tar.gz`

Rollback: extract the backup into the production root, restore ownership to `web120:client9`, run PHP and Apache configuration validation, and reload PHP-FPM only after successful checks.

## Validation

- 43 PHP files passed syntax validation.
- JavaScript syntax, generated production assets and `git diff --check` passed.
- All 84 local and all 84 production page/language combinations returned HTTP 200 with the revised footer, both exact destination URLs and safe new-window attributes.
- Production CSS contains hover and keyboard-focus underline behavior without permanent link decoration.
- Apache and PHP-FPM are active, and recent application logs contain no matching PHP or Apache errors.

## Impact

- No database, DNS, SSL, firewall or document-root changes.
- PHP-FPM was reloaded after validation; no service interruption was required.
