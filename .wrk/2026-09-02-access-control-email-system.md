# Access Control and transactional e-mail - 2026-09-02

## Delivered

- Correct, visible and keyboard-accessible `Primary role` and `Direct campus scope` controls for create and edit flows.
- Persisted single primary role and direct campus assignments, including edit replacement semantics.
- Welcome invitations with one-time, hashed, 72-hour password links; no administrator-selected password is required.
- Password recovery with CAPTCHA, generic account-discovery-safe response, hourly rate limit and one-time 1-hour links.
- System - E-mail settings for SMTP/native/disabled delivery, encrypted SMTP secrets, sender identity, appearance, supplied or uploaded logo, and editable welcome/reset templates.
- Responsive multipart HTML/plain-text messages using the public PNG Eduvixo e-mail logo theme asset.
- Base CMS 1.0.24 Stable and Eduvixo theme 1.1.12 published to Marketplace and both production installations.

## Production

- `demo.eduvixo.com`: Base CMS 1.0.24, active Eduvixo theme preserved, SMTP configured with an encrypted password.
- `shoudu.lrn.asia`: Base CMS 1.0.24, active Shoudu Custom Theme preserved, existing transport configuration retained.
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

## PNG e-mail logo and compact actions - Base CMS 1.0.22 / Eduvixo theme 1.1.12

- Replaced the supplied SVG e-mail logo with the PNG asset at `/theme-assets/eduvixo/images/eduvixo-logo-email.png` for broader mail-client compatibility.
- Existing installations that still store the supplied SVG path are migrated to the PNG path at runtime; custom uploaded logo URLs remain unchanged.
- The SMTP action row now bottom-aligns its controls, keeping `Save server settings` and `Send test e-mail` at the standard 40 px height instead of stretching them to the taller labelled input row.
- Production backup: `/root/eduvixo-backups/email-logo-png-1.0.22-pre-20260902-140915`.
- Core recovery points: `recovery-07529e57e63fd76f05613bd8` (demo), `recovery-01aded8f00bcee6045c7d529` (Shoudu).
- Both public PNG assets return `200 image/png` and match SHA-256 `a860b438137c77dc24daf77be31f4be1400af01bdfe74e6abc78ffe51849d960`.
- Live browser QA confirmed the PNG path and preview as well as 40 px action-button heights.

## Exact Test E-Mail row alignment - Base CMS 1.0.24

- Root cause: flex end-alignment used the helper text as part of the field height, so the buttons aligned with the complete label block rather than the input itself.
- The input and both actions now share one explicit CSS grid row; all three controls have the same 40 px height and identical top and bottom coordinates.
- The layout collapses to three full-width controls below 760 px.
- The e-mail stylesheet URL was cache-busted so immutable browser caches cannot retain the previous layout. Base CMS 1.0.23 was superseded during live acceptance QA before the task was closed.
- Production backup: `/root/eduvixo-backups/email-action-alignment-1.0.24-pre-20260902-145733`.
- Core recovery points: `recovery-cf18c360d57401ed266b0ad9` (demo), `recovery-7fe601190d7965337cc19984` (Shoudu).
- Live browser measurements on demo: input and both buttons `top=439`, `bottom=479`, `height=40`.

## Evidence

- Release verification: 106 assertions.
- Access Control and e-mail integration: 17 assertions.
- signed Core update, preservation and forced rollback: 43 assertions.
- clean installation: 13 assertions.
- post-deployment production audit: 68 assertions.
- Live browser QA confirmed new-user selection, persisted edit state, E-mail settings and public password recovery.
- Apache configuration is valid; Apache, PHP-FPM, MariaDB and cron are active; no new critical PHP-FPM events were found.

## SMTP readiness

The live Base CMS 1.0.21 test was accepted by the configured SMTP service and delivered to the signed-in account. Base CMS 1.0.24 preserves that delivery path and its message fixture confirms the public PNG logo URL. The earlier outbound SMTP restriction is no longer present on the production path used by the application.

## Rollback

Prefer the per-installation Core recovery point for a Core-only rollback. Use the full backup directory above when database, theme, Marketplace and filesystem restoration must be coordinated.
