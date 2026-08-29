# Eduvixo CMS - sidebar scroll space correction

Date: 2026-08-29
Environment: production demo CMS at `demo.eduvixo.com`
Status: implemented, deployed and verified

## Root cause

The console stylesheet appended an empty `::after` pseudo-element to `.side-nav` with a height of `calc(100dvh - 150px)`. It allowed the final navigation section to be aligned near the top of the sidebar, but also created approximately one viewport of blank scrollable content below the License item.

## Change

- Removed the artificial pseudo-element from `public/theme/eduvixo-shell.css`.
- Added a cache-independent inline override in the console layout so browsers that cached the immutable shell stylesheet stop rendering the spacer immediately.
- Preserved the existing fixed sidebar, natural list scrolling, active-section positioning, responsive off-canvas behaviour and overscroll containment.

Files changed:

- `.cms/source/public/theme/eduvixo-shell.css`
- `.cms/source/app/Views/console.php`

The `.cms/` source tree remains intentionally excluded from the public website repository. This work record is versioned in the parent repository.

## Deployment

Production root:

`/var/www/clients/client9/web121/web`

Pre-deployment backup:

`/root/eduvixo-backups/sidebar-scroll-pre-20260829-230722`

Rollback consists of restoring `app/Views/console.php` and `public/theme/eduvixo-shell.css` from the backup. No database, service, DNS, SSL, firewall or server configuration changes were made.

## Verification

- PHP syntax validation passed locally, in staging and after deployment.
- The obsolete `calc(100dvh - 150px)` spacer is absent from the production stylesheet.
- The live pseudo-element computes to `display: none` even for a browser holding the previous immutable stylesheet.
- At a 987 px viewport height, sidebar scroll height decreased from 1991 px to 1149 px.
- Maximum sidebar scroll decreased from approximately 1073 px to 231 px.
- The final License item stops 12 px above the sidebar bottom instead of leaving a large blank scrollable area.
- Visual browser inspection confirmed the complete menu ends naturally at the bottom of the panel.
- Production logs contain no new PHP fatal errors, parse errors or uncaught exceptions.
