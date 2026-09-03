# Web Push in My Settings - Base CMS 1.0.25

Status: COMPLETE. Base CMS 1.0.25 Stable is published and deployed to both production installations.

## Scope

- Move personal Web Push management from the compact Interface settings drawer to My Settings, immediately above Telegram Notifications.
- Present an accessible, responsive channel card consistent with Telegram.
- Preserve explicit browser consent, encrypted subscription storage, per-device enable/disable, all-device revocation, notification-category preferences and AJAX test delivery.
- Show clear states for enabled, available, blocked, unsupported and unavailable browsers.

## Release and deployment

- Signed Core: `eduvixo-core-1.0.25.zip`, 799,177 bytes, SHA-256 `6c4fc345c65ab75a31a560f063e553bc726396165562b2cc74ce5c9851a3e80a`.
- Clean installer: `eduvixo-install-1.0.25.zip`, 10,454,181 bytes, SHA-256 `e8ee0b0d73980ebce2701b168171f3b806eac7b4861521d4773adab3757a0503`.
- Demo recovery: `recovery-0def22c9f0f980a44248a673`.
- Shoudu recovery: `recovery-9e66a0a84958e49e35a6ed4c`.
- Pre-deployment file backup: `/root/eduvixo-backups/web-push-settings-1.0.25-pre-20260903-015937`.

## Validation

- PHP syntax passed for the complete private CMS source; JavaScript syntax passed for every public CMS script.
- Release and Marketplace verification passed 106 assertions.
- RFC 8291/VAPID cryptography passed; isolated MariaDB integration passed encrypted storage, status, queue, deduplication, preferences and revocation.
- Clean installation passed 13 assertions.
- Dedicated production post-check passed 17 assertions; the complete production audit passed 68 assertions.
- Both installations retain their active themes, protected mode `0600` VAPID identity, Web Push schema, bounded queues and healthy update state.
- Both Service Workers return HTTP 200 with JavaScript content type, root scope and no-cache headers.
- Apache syntax is valid; Apache, PHP-FPM, MariaDB and cron are active with no recent critical PHP-FPM events.

Browser permission was intentionally not granted automatically. Each user enables Web Push explicitly from My Settings; this consent boundary is part of the security design.
