# Access Control and transactional e-mail - 2026-09-02

## Delivered

- Correct, visible and keyboard-accessible `Primary role` and `Direct campus scope` controls for create and edit flows.
- Persisted single primary role and direct campus assignments, including edit replacement semantics.
- Welcome invitations with one-time, hashed, 72-hour password links; no administrator-selected password is required.
- Password recovery with CAPTCHA, generic account-discovery-safe response, hourly rate limit and one-time 1-hour links.
- System - E-mail settings for SMTP/native/disabled delivery, encrypted SMTP secrets, sender identity, appearance, supplied or uploaded logo, and editable welcome/reset templates.
- Responsive multipart HTML/plain-text messages using the Eduvixo e-mail logo theme asset.
- Base CMS 1.0.20 Stable and Eduvixo theme 1.1.11 published to Marketplace and both production installations.

## Production

- `demo.eduvixo.com`: Base CMS 1.0.20, active Eduvixo theme preserved, SMTP configured with an encrypted password.
- `shoudu.lrn.asia`: Base CMS 1.0.20, active Shoudu Custom Theme preserved, existing transport configuration retained.
- Full deployment backup: `/root/eduvixo-backups/base-cms-1.0.19-pre-20260902-125542`.
- Core recovery points: `recovery-c0d35a398d54b902c2ef7630` (demo), `recovery-4d3ca0fcaa60b88f068f1a90` (Shoudu).

## Access Control layout hotfix - Base CMS 1.0.20

- Root cause: the legacy navigation selector `.eduvixo-check input` made Access Control radio buttons and checkboxes absolutely positioned, which placed them over the first characters of labels.
- The correction is scoped to `.eduvixo-access-shell`; it restores normal control positioning and uses a responsive grid for role names and descriptions without changing navigation toggles.
- Production backup: `/root/eduvixo-backups/access-layout-1.0.20-pre-20260902-132400`.
- Core recovery points: `recovery-9d0921ad6c7e521f63cdafd2` (demo), `recovery-b4301b10bdeef38a0a070303` (Shoudu).
- Live browser QA confirmed non-overlapping radio buttons, role labels, Demo User, campus scope and active-account controls.

## Evidence

- Release verification: 106 assertions.
- Access Control and e-mail integration: 15 assertions.
- signed Core update, preservation and forced rollback: 43 assertions.
- clean installation: 13 assertions.
- post-deployment production audit: 68 assertions.
- Live browser QA confirmed new-user selection, persisted edit state, E-mail settings and public password recovery.
- Apache configuration is valid; Apache, PHP-FPM, MariaDB and cron are active; no new critical PHP-FPM events were found.

## Infrastructure limitation

The production host cannot currently open outbound TCP connections to the configured mail host on ports 25, 465, 587 or 2525. Its local OUTPUT policy is permissive and HTTPS egress works; the SMTP endpoint is reachable from an independent network. This identifies an upstream hosting-provider SMTP egress block rather than an application or mail-server failure. Configuration remains encrypted and ready, and account creation degrades safely with a clear invitation-delivery warning until outbound SMTP is enabled or an HTTPS mail provider is configured.

## Rollback

Prefer the per-installation Core recovery point for a Core-only rollback. Use the full backup directory above when database, theme, Marketplace and filesystem restoration must be coordinated.
