# Access Control and transactional e-mail - 2026-09-02

## Delivered

- Correct, visible and keyboard-accessible `Primary role` and `Direct campus scope` controls for create and edit flows.
- Persisted single primary role and direct campus assignments, including edit replacement semantics.
- Welcome invitations with one-time, hashed, 72-hour password links; no administrator-selected password is required.
- Password recovery with CAPTCHA, generic account-discovery-safe response, hourly rate limit and one-time 1-hour links.
- System - E-mail settings for SMTP/native/disabled delivery, encrypted SMTP secrets, sender identity, appearance, supplied or uploaded logo, and editable welcome/reset templates.
- Responsive multipart HTML/plain-text messages using the Eduvixo e-mail logo theme asset.
- Base CMS 1.0.21 Stable and Eduvixo theme 1.1.11 published to Marketplace and both production installations.

## Production

- `demo.eduvixo.com`: Base CMS 1.0.21, active Eduvixo theme preserved, SMTP configured with an encrypted password.
- `shoudu.lrn.asia`: Base CMS 1.0.21, active Shoudu Custom Theme preserved, existing transport configuration retained.
- Full deployment backup: `/root/eduvixo-backups/base-cms-1.0.19-pre-20260902-125542`.
- Core recovery points: `recovery-c0d35a398d54b902c2ef7630` (demo), `recovery-4d3ca0fcaa60b88f068f1a90` (Shoudu).

## Access Control layout hotfix - Base CMS 1.0.20

- Root cause: the legacy navigation selector `.eduvixo-check input` made Access Control radio buttons and checkboxes absolutely positioned, which placed them over the first characters of labels.
- The correction is scoped to `.eduvixo-access-shell`; it restores normal control positioning and uses a responsive grid for role names and descriptions without changing navigation toggles.
- Production backup: `/root/eduvixo-backups/access-layout-1.0.20-pre-20260902-132400`.
- Core recovery points: `recovery-9d0921ad6c7e521f63cdafd2` (demo), `recovery-b4301b10bdeef38a0a070303` (Shoudu).
- Live browser QA confirmed non-overlapping radio buttons, role labels, Demo User, campus scope and active-account controls.

## Test e-mail recipient correction - Base CMS 1.0.21

- Root cause: the shared AJAX form handler submitted the parent form action and ignored the button-level `formaction`, so `Send test to me` saved server settings instead of calling the test endpoint.
- Test delivery is now an isolated AJAX action that cannot save or modify server settings.
- The optional `Test E-Mail` field accepts a validated recipient without persisting it; when empty, delivery falls back to the signed-in user's account e-mail.
- Production backup: `/root/eduvixo-backups/email-test-recipient-1.0.21-pre-20260902-134855`.
- Core recovery points: `recovery-4b8288df951098a1a1e84729` (demo), `recovery-52c90ccc00527c6e5e806006` (Shoudu).
- Live browser QA confirmed AJAX delivery to the signed-in account with no page navigation and no server-settings save action.

## Evidence

- Release verification: 106 assertions.
- Access Control and e-mail integration: 17 assertions.
- signed Core update, preservation and forced rollback: 43 assertions.
- clean installation: 13 assertions.
- post-deployment production audit: 68 assertions.
- Live browser QA confirmed new-user selection, persisted edit state, E-mail settings and public password recovery.
- Apache configuration is valid; Apache, PHP-FPM, MariaDB and cron are active; no new critical PHP-FPM events were found.

## SMTP readiness

The live Base CMS 1.0.21 test was accepted by the configured SMTP service and delivered to the signed-in account. The earlier outbound SMTP restriction is no longer present on the production path used by the application.

## Rollback

Prefer the per-installation Core recovery point for a Core-only rollback. Use the full backup directory above when database, theme, Marketplace and filesystem restoration must be coordinated.
