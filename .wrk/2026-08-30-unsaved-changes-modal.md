# Unsaved changes modal - 2026-08-30

## Objective

Replace the browser-native "leave site" prompt with a branded Eduvixo confirmation modal whenever navigation can be controlled by the application.

## Browser limitation

Browsers do not allow applications to style or replace the native `beforeunload` dialog used for closing a tab, refreshing, or manually navigating through the address bar. The implementation therefore uses:

- the Eduvixo modal for links, sidebar navigation, and logout;
- the native browser safeguard only for tab close, refresh, and address-bar navigation.

## Implementation

- Added `public/theme/eduvixo-unsaved-guard.js`:
  - detects dirty and saving states in content editors, Page Builder, and Surveys;
  - intercepts ordinary navigation links before other navigation handlers;
  - intercepts logout and resubmits it only after confirmation;
  - bypasses `beforeunload` only for the confirmed navigation;
  - supports cancel, backdrop click, Escape, focus restoration, and a short fail-safe bypass timeout.
- Added `public/theme/eduvixo-unsaved-guard.css`:
  - responsive warning modal in the Eduvixo visual language;
  - warning icon, branded header, clear consequence message, and asymmetric safe/destructive actions;
  - dark-mode and mobile layouts.
- Updated `app/Views/console.php` to load the new versioned CSS and JavaScript assets and refresh Page Builder/Surveys cache keys.
- Updated `public/theme/eduvixo-page-builder.js` and `public/theme/eduvixo-surveys.js` so their unavoidable native fallback is suppressed after the user confirms navigation in the Eduvixo modal.

Modal actions:

- `Stay here` - closes the modal without losing state;
- `Leave without saving` - continues link navigation;
- `Sign out anyway` - confirms logout when the editor contains unsaved changes.

## Deployment

- Environment: production demo instance, `demo.eduvixo.com`.
- Application root: `/var/www/clients/client9/web121/web`.
- Staging: `/root/eduvixo-deploy/unsaved-modal-20260830-110530`.
- Backup: `/root/eduvixo-backups/unsaved-modal-pre-20260830-110530`.
- Backed up the three existing files before replacement; the guard CSS and JavaScript are new files.
- Deployed files use owner `web121:client9` and mode `0644`.
- PHP 8.5 FPM was gracefully reloaded.
- No database or infrastructure configuration changes were made.

## Rollback

Restore `console.php`, `eduvixo-page-builder.js`, and `eduvixo-surveys.js` from the backup directory, remove the two new guard assets, reload PHP 8.5 FPM, and verify `/login` plus the administration shell.

## Validation

- PHP syntax validation passed for `console.php` locally and on the server.
- JavaScript syntax validation passed for the guard, Page Builder, and Surveys.
- Apache configuration validation passed (`Syntax OK`).
- Live asset requests return HTTP 200.
- Local and deployed SHA-256 hashes match for all five files.
- `apache2`, `php8.5-fpm`, and `mariadb` remain active.
- No new application error was recorded in the demo virtual-host error log after deployment.

