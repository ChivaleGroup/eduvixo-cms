# Telegram bot profile management

## Scope

- Added Owner-only bot profile-photo management to `SYSTEM -> Notifications`.
- Added client-side square crop controls and server-side revalidation/normalization.
- Added secure current-photo proxy; the Telegram bot token never reaches the browser.
- Added Telegram Bot API profile-photo support to Telegram Notifications 1.1.0-beta.1.
- Released Base CMS 1.0.15 Stable and refreshed the signed Marketplace catalogue and installer.

## Security and behavior

- Writable non-demo Owners may change or remove the shared bot photo.
- Authenticated users with `system.manage` may view the current photo through a private no-store proxy.
- Accepted input: PNG, JPEG or WebP, maximum 2 MB, 128-8000 px per side.
- The server decodes, center-crops and writes a temporary 640 x 640 JPEG with mode 0600, then removes it in `finally`.
- No test changed or removed the live bot photo.

## Release identity

- Base CMS 1.0.15 Stable.
- Telegram Notifications 1.1.0-beta.1.
- Deployment updates the existing active Telegram package only on demo; Shoudu remains without Telegram.

## Recovery

The production deployment creates a timestamped recovery point under `/root/eduvixo-backups/telegram-profile-pre-*` with Marketplace metadata, targeted site files, both database dumps and rollback instructions. Prefer updater/package recovery archives; restore SQL only after preserving later writes.

Production recovery point: `/root/eduvixo-backups/telegram-profile-pre-20260902-063312`.

## Verification

- Local PHP/JavaScript syntax, focused profile tests (8 assertions), release verification (106 assertions) and `git diff --check` passed.
- Both production installations report Base CMS 1.0.15 Stable without maintenance state.
- Demo reports Telegram Notifications 1.1.0-beta.1 active with a verified package signature; Shoudu remains intentionally without Telegram.
- A read-only live Telegram API check retrieved the current bot-photo state without exposing credentials and without changing the photo.
- Clean-install validation passed 12 assertions against the published 1.0.15 installer.
- Marketplace, login and protected profile routes returned the expected HTTP statuses; both new assets return 200 and direct package access returns 404.
- Apache configuration and Apache, PHP-FPM, MariaDB and cron health checks passed; no new critical PHP-FPM errors were found.
