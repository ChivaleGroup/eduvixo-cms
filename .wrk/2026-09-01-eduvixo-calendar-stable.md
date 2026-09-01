# Eduvixo Calendar 1.1.0 Stable

Status: COMPLETE. Eduvixo Calendar and its Google Calendar, Apple Calendar and Microsoft 365 Calendar outbound connectors were released as signed version 1.1.0 Stable on 2026-09-01.

## Scope delivered

- Added production-grade all-day events with inclusive end dates in the UI and exclusive provider boundaries, including daylight-saving transitions.
- Added explicit IANA time-zone selection and local-time round trips while retaining UTC storage.
- Added recurring-series operations for one occurrence, this and following occurrences, or the complete series. Existing event identifiers and audit history are preserved where possible; surplus occurrences are cancelled rather than deleted.
- Added equivalent cancellation scopes with participant notifications and provider deletion jobs.
- Added complete Calendar administration copy for `de`, `en`, `lo`, `pl`, `th`, `vi` and `zh`, with browser/request detection and English fallback.
- Added localized event types, reminder offsets, provider settings, accessibility labels and feedback.
- Added safe delivery health reporting. Only deliveries with an unambiguous idempotent retry path are automatically requeued; unknown/processing external results remain for manual review.
- Separated the worker advisory lock from the calendar write lock. Event saves no longer wait behind slow external provider traffic. The worker revalidates reminder/event state before each delivery.
- Added notification action links restricted to same-origin paths.
- Updated Google, RFC 5545/CalDAV and Microsoft Graph all-day serialization.
- Updated the signed official catalogue and Marketplace entries to 1.1.0 Stable. Paid licensed-download gates and existing annual prices remain unchanged.

No database schema change was necessary for 1.1.0. Existing additive Calendar migrations remain idempotent. No customer events or settings were removed.

## Backups and rollback

Local pre-change source backup (confidential, outside web roots and Git):

- `F:\Git\ChivaleGroup\.backups\eduvixo-calendar-pre-stable-20260901-073339.tar.gz`
- SHA-256: `8e990d07405ac2b3c328b3fd7ae51f5dc583d50ce67e408c637e069becbfc802`

Local final 1.1.0 source/package archive (confidential, outside web roots and Git):

- `F:\Git\ChivaleGroup\.backups\eduvixo-calendar-1.1.0-stable-source-20260901-0815.tar.gz`
- Size: 145,859 bytes
- SHA-256: `9e04f0c8fece0cfaca247d6fb0ee2bd8cb00afa14eb3a04f1d9b9abbf0be3bdb`

Production pre-deployment backup:

- `/root/eduvixo-backups/calendar-stable-pre-20260901-010509`
- `demo.sql`: `c5a4d488839ecda749b8e7de8df9d47c54f95f3c5aeebcc0a466bda6a6eee796`
- `demo-extensions.tar.gz`: `c079cc7faf17ff286e73622bb41395a0b83a6cd036bc94343886e5c16b7f46d5`
- `website-marketplace.tar.gz`: `8a5adc7f259930a80b420053d175232c763d2bdaa2f18103716cefc54fa38414`
- `ROLLBACK.txt`: `401cb07cfbb0e0e9a69f21317f9d4faeaad0dc79481649cb47922fdf33bddc75`

Rollback instructions are stored with the server backup. Preserve a fresh database dump before rollback. Restore the demo extensions and website Marketplace archive to their matching roots, restore `web121:client9` and `web123:client9` ownership, reload PHP-FPM, then rerun the worker and health checks. Restore the SQL dump only if file rollback is insufficient and only after preserving newer data. The immutable 1.1.0 package files may safely remain unused.

## Validation

- PHP syntax: Calendar runtime, controller, repository, dispatcher, view and all three providers passed.
- JavaScript syntax: Calendar UI passed `node --check`.
- Language audit: 7/7 JSON catalogues, 198 matching leaf keys, no missing/extra/empty value.
- Isolated MariaDB integration suite: 40/40 passed. Coverage includes repeat-safe migrations, campus/participant/private visibility, conflict rejection, cross-campus conflicts, adjacent events, reminders, DST recurrence, all-day DST boundaries, one/following/all series updates, identifier preservation, cancellation scopes, strict invalid input, exactly-once internal reminder delivery, notification ownership, write serialization and worker/write lock separation.
- Load check: 2,000-event range loaded in 0.0551 seconds on the deployment host, below the 3-second release threshold.
- Provider serialization: 3/3 all-day/DST checks passed for Google, Apple RFC 5545 and Microsoft Graph.
- Provider security: 9/9 input/SSRF checks passed with zero outbound requests.
- Package/catalogue validation: 13 products, 14 files and 7 Marketplace languages; every installable package signature and payload hash verified.
- UI: localized Polish desktop and 390 px mobile views checked; no horizontal overflow or browser console errors. Event modal, time-zone default and all-day field switching verified.
- Production: four installed extensions report version 1.1.0, `signature_status=verified`, `active=1`; Calendar cron worker completed with zero failures; Apache, PHP-FPM and cron are active.
- HTTP: Marketplace 200, Calendar unauthenticated redirect 302, API 401, extension CSS 200, direct addon manifest access 403.
- Official catalogue endpoint reports all four releases as 1.1.0 Stable, installable and licensed.

Real provider credentials are intentionally not stored in the repository. The adapters validate credentials when an administrator enables a provider. Production currently has zero configured providers, so no real external calendar or recipient was contacted during this release.

## Infrastructure and files

Targets:

- Demo CMS: `/var/www/clients/client9/web121/web`, owner `web121:client9`.
- Public website/Marketplace: `/var/www/clients/client9/web123/web`, owner `web123:client9`.

The existing `/etc/cron.d/eduvixo-calendar` schedule remains once per minute. No service, firewall, DNS or SSL configuration was changed. PHP-FPM was reloaded after the atomic package and catalogue publication.

Private extension source remains ignored by Git under `.plugins/` according to the repository security policy. Reproducible validation/deployment tools and public catalogue metadata are tracked in Git.

## Standards referenced

- Google Calendar Events API and event concepts for `start.date`/`end.date` all-day boundaries.
- RFC 5545 VEVENT semantics, where `DTSTART` is inclusive and `DTEND` is non-inclusive.
- Microsoft Graph event and `dateTimeTimeZone` requirements for all-day local-midnight boundaries.

## Next planned point

Point 2: implement real Web Push using a service worker, VAPID keys, subscription management, consent/revocation, delivery telemetry and notification preferences. This should become the common delivery foundation for the website, Eduvixo backend and, later, Windows/mobile applications. Browser polling remains available as a fallback until that point is complete.
