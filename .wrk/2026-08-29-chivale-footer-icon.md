# Chivale footer icon

Date: 2026-08-29

Environment: local working copy and production (`www.eduvixo.com`)

## Outcome

- Derived a compact white `CH` vector mark from the Chivale header logo supplied by the project owner.
- Stored the sanitized two-path SVG locally at `public/assets/images/chivale-mark-white.svg`; the website does not depend on an external Chivale asset at runtime.
- Added the 24 by 16 pixel mark before `Hosting provided by Chivale Group.` in the footer link.
- Preserved the existing normal-text appearance, hover and keyboard-focus underline, safe new-window attributes and accessible link name.
- Marked the image as decorative so assistive technology reads the visible hosting credit once.
- Applied a non-scaling stroke so the thin source artwork remains legible at footer size.

Source references:

- `https://www.chivale.com/img/logo-preloader.svg`
- `https://www.chivale.com/img/logo-header.svg`

## Files

- `public/assets/images/chivale-mark-white.svg`
- `app/views/layout.php`
- `resources/pages.css`
- `public/assets/css/site.min.css`

## Deployment

Production root: `/var/www/clients/client9/web120/web`

Release: `/root/eduvixo-chivale-footer-icon-20260829-182645.tar.gz`

SHA-256: `459F4C22837FA171C587521A1578CB969D11F44DEE0C13C34D11D256D15623B1`

Pre-deployment backup: `/root/eduvixo-backups/chivale-footer-icon-pre-20260829-182645.tar.gz`

Rollback: extract the backup into the production root, remove only `public/assets/images/chivale-mark-white.svg`, restore ownership to `web120:client9`, run PHP and Apache configuration validation, and reload PHP-FPM only after successful checks.

## Validation

- The SVG parses as XML, contains exactly two paths and no scripts or external references.
- 43 PHP files, JavaScript syntax, generated production assets and `git diff --check` passed.
- All 84 local and all 84 production page/language combinations returned HTTP 200 with the icon and hosting credit.
- The production asset returns HTTP 200 as `image/svg+xml` and matches the expected white two-path vector.
- Apache and PHP-FPM are active, and recent application logs contain no matching PHP or Apache errors.

## Impact

- No database, DNS, SSL, firewall or document-root changes.
- PHP-FPM was reloaded after validation; no service interruption was required.
