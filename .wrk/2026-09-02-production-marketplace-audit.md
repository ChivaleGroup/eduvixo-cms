# Production and Marketplace audit

Date: 2026-09-02

## Result

Both production installations and the public Marketplace are current. No corrective deployment or database change was required.

## Production state

- `demo.eduvixo.com`: Base CMS 1.0.15 Stable; Eduvixo theme 1.1.10 active; My Calendar 1.1.5, Google/Apple/Microsoft 365 Calendar 1.1.1, Telegram Notifications 1.1.0-beta.1 and WhatsApp Notifications 1.1.0-beta.2 are signature-verified and current.
- `shoudu.lrn.asia`: Base CMS 1.0.15 Stable; Shoudu Custom Theme 1.1.4 active and Eduvixo theme 1.1.10 available but inactive. No unintended Calendar or notification package is installed.
- Every installed Core file matches the SHA-256 inventory in the signed 1.0.15 release.
- Required migrations, license enforcement, update state, rollback artifacts, themes and bounded notification queues passed.
- Apache configuration is valid; Apache, PHP-FPM, MariaDB and cron are active; the previous hour contains no critical PHP-FPM events.

## Marketplace state

- The signed catalogue identifies Base CMS 1.0.15 Stable and 13 unique products.
- Every configured artifact exists and matches its declared byte size and SHA-256 digest, including both Windows architectures.
- The public official-catalogue endpoint matches the verified server file exactly.
- English, German, Chinese, Vietnamese, Thai, Lao and Polish Marketplace pages each expose all 13 products.
- Local and production Marketplace configuration, signed catalogue, Core release metadata and all seven language files match exactly (10/10 files).
- Unauthenticated Core download is rejected and the direct package path remains concealed.

## Verification

- Local signed-release suite: 106 assertions.
- Live production and Marketplace suite: 66 assertions.
- Clean installation from the published Base CMS 1.0.15 installer: 12 assertions.
- Total: 184 assertions, plus PHP syntax and Git whitespace checks.

The reusable live audit is `.wrk/production-current-audit.php`.
