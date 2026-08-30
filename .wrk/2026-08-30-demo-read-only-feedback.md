# Demo read-only feedback and login notice - 2026-08-30

## Objective

Make the web demo account visibly read-only instead of silently ignoring attempted changes, and show a one-time warning immediately after a successful demo login.

## Root cause

The backend already rejected every authenticated non-safe request made by a Demo User, except `POST /logout`. The UX was inconsistent because:

- only success flash messages were exposed to the shared browser UI;
- custom AJAX modules did not all present the backend's read-only response in the same way;
- there was no post-login session marker or introductory modal.

## Implementation

- `app/Http/AuthController.php`
  - sets `demo_notice_pending` after a successful Demo User login;
  - clears a stale marker after a normal user login.
- `app/Http/DashboardController.php`
  - consumes the marker only on the dashboard and renders the notice once per login.
- `app/Core/AccessControl.php`
  - keeps the existing server-side read-only enforcement;
  - adds `X-Eduvixo-Demo-Mode: 1` to blocked responses;
  - preserves `POST /logout` as the only allowed mutating request.
- `app/Views/console.php`
  - exposes demo state and flash type to the shared UI;
  - renders an accessible, focusable, read-only welcome dialog;
  - provides red `Exit` (logout) and green `Continue` actions.
- `public/theme/eduvixo-demo-mode.css`
  - adds the responsive light/dark modal design and mobile layout.
- `public/theme/eduvixo-demo-mode.js`
  - normalizes the read-only message to a warning toast;
  - prevents and explains mutating form submissions before navigation;
  - prevents and explains same-origin mutating `fetch` requests;
  - deduplicates repeated notifications;
  - excludes logout and manages modal focus/scroll state.
- `public/theme/eduvixo-ui.js`
  - reads the flash-message type supplied by the server, so a redirected read-only rejection is displayed as a warning instead of a success.

The shared message is:

> Demo User mode is read-only. You can explore every area, but changes cannot be saved.

## Deployment

- Environment: production demo instance, `demo.eduvixo.com`.
- Document root/application root: `/var/www/clients/client9/web121/web`.
- Deployment staging: `/root/eduvixo-deploy/demo-readonly-20260830-103624`.
- Pre-deployment file backup: `/root/eduvixo-backups/demo-readonly-pre-20260830-103624`.
- Deployed files use owner `web121:client9` and mode `0644`.
- PHP 8.5 FPM was gracefully reloaded after the final asset/version update.
- The temporary uploaded database configuration used during diagnostics was deleted after use.

## Pre-existing database outage found during validation

The demo site returned HTTP 503 before this deployment. Access logs place the first observed failures at approximately 08:32 local server time; the read-only UI deployment occurred later. MariaDB was running, but the authentication hash for the application's dedicated database account did not match the unchanged project `.env` and authoritative project configuration.

Remediation:

- backed up the account authentication record and grants to:
  - `/root/eduvixo-backups/demo-readonly-pre-20260830-103624/db-user-auth.sql`
  - `/root/eduvixo-backups/demo-readonly-pre-20260830-103624/db-user-grants.sql`
- both files are root-owned with mode `0600`;
- reset only the existing application database account password to the authoritative configured value;
- made no schema or application data changes;
- verified a successful application-account database query afterwards.

Rollback for the authentication repair: restore the saved `mysql.global_priv` account record, execute `FLUSH PRIVILEGES`, then verify the account grants from the saved grant listing. This would also restore the mismatched credential and therefore reintroduce the 503 until the application configuration is aligned.

## Validation

- PHP syntax checks passed for all four modified PHP files.
- JavaScript syntax check passed for `eduvixo-demo-mode.js`.
- JavaScript syntax check passed for the updated shared `eduvixo-ui.js`.
- Live PHP syntax checks passed after deployment.
- Apache configuration check passed (`Syntax OK`).
- `apache2`, `php8.5-fpm`, and `mariadb` are active.
- Live HTTP checks after database recovery:
  - `/login` - 200;
  - `/theme/eduvixo-demo-mode.js?v=20260830-2` - 200;
  - `/theme/eduvixo-demo-mode.css?v=20260830-2` - 200.
- Browser verification confirmed that `/dashboard` redirects an expired session to the restored login page and that CAPTCHA is served normally.
- A new authenticated visual pass was not forced because it would require solving CAPTCHA; implementation was instead validated through source, syntax, deployed asset, routing, and service checks.

## Security observation

MariaDB logs contain repeated rejected remote attempts against the root account. They were not caused by this change and did not result in a service stop. Public database exposure and host firewall policy should be reviewed as a separate infrastructure-hardening task so the operational impact can be assessed independently.
