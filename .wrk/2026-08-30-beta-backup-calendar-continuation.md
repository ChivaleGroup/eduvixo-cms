# Eduvixo 1.0 Beta backup and Calendar continuation

Status: Calendar recovery deployed as 1.0.1-beta.1. Full Calendar roadmap remains IN PROGRESS; external integrations are not connected or end-to-end verified.

## Restore point

- Local full project archive: `F:\Git\ChivaleGroup\.backups\eduvixo-1.0-beta-local-20260830-140041.tar.gz`, 459753266 bytes; SHA-256 `887d4985603f26ecb5ac7133b1ab7713d902193dfbb15004ed18781062f66b79`.
- Server full archive: `/root/eduvixo-backups/eduvixo-1.0-beta-server-20260830-150155.tar.gz`, 427381827 bytes; SHA-256 `f420ee73a717af47a9e4d4cd969dff57ac5622ac2c564317c63b29920f668582`.
- Server archive contains both full hosting accounts (web123 website, web121 demo), database dump, infrastructure configuration and RESTORE instructions.
- Git annotated tag `eduvixo-1.0-beta` points to commit `bf1be31834d0c617f7c0ca52bf0dab2c61d5cdcc` and was pushed before Calendar work.
- Archives contain confidential configuration. Keep outside public roots and Git; do not publish them.

Restore: extract into a separate protected staging directory first, validate component SHA256SUMS and gzip/tar integrity, follow RESTORE.txt. Preserve a fresh dump before database restoration. Restore the intended hosting account and matching database as a coordinated operation; restore ownership (web123/client9 for website, web121/client9 for demo), validate Apache/PHP and repeat health checks. Restoring the database discards changes after the backup: do not do so without preserving them.

## Interrupted work recovered

Previous turn deployed six newly signed packages, added Marketplace entries/translations, installed Calendar tables and activated packages on demo. It stopped before runtime, browser, functional, security and notification verification. No worker cron was installed. New source remains ignored by Git under `.plugins/` and `.cms/` by existing repository security policy.

## Audit findings to resolve before release

- Plugin install paths are stored relative to CMS root; integration loader currently resolves against process CWD.
- Signed package lacks navigation metadata consumed by new console navigation.
- Calendar request permission errors are not caught at runtime boundary.
- Event visibility, race-safe conflict prevention, timezone recurrence, notifications delivery and browser accessibility need verification/corrections.
- Provider adapters require live credentials and validation; outbound-only adapters must not claim two-way synchronization. WhatsApp scheduled notifications require approved templates and a messages endpoint.
- Marketplace IDs/checksums/layout and package stability labels need verification before treating downloads as released.

Production changes will be scoped, backed up and checked. No existing data deletion or broad rollback is planned.

## Recovery delivered

- Corrected actual campus schema, authenticated runtime boundary, relative provider paths and signed navigation metadata.
- Enforced participant/private visibility, including conflict-detail redaction. Conflicts span campuses and serialize writes with a database advisory lock.
- Verified recurrence across DST and skipped missing month-days, strict date/reminder validation, owner-only private participants, cancellation and notification read isolation.
- Repaired form element lookup and capture-phase AJAX submission; added accessible confirmation and resource dialogs, focus handling, request race protection and periodic in-app notification refresh.
- Added recipient delivery ledger with atomic internal notifications; ambiguous external delivery is not automatically replayed. Installation-wide exports exclude private/participant-only events.
- Provider hardening: iCloud host/port allowlist and pinned public IPv4 resolution, no redirects; Google deterministic event IDs; Microsoft transaction IDs; WhatsApp template payload and `/messages` endpoint; explicit user-recipient maps for messengers.
- Rebuilt and signed six packages as Beta; preserved USD 120/year Calendar and USD 12/year/plugin with licensed download gate. Website has 11 products/12 files, localized Beta notice in all seven languages, matching icons and corrected grid flow.
- Fixed CMS root `.htaccess` missing `addons` denial. Direct PHP/schema/manifest access is now blocked. Refreshed clean CMS installer with the same fix.

## Deployment / rollback

Targets: website web123 and demo web121 only, group client9. Applied six signed upgrades through PackageManager, preserving its release archives. No credentials or production event fixtures added.

Pre-repair snapshot: `/root/eduvixo-backups/calendar-repair-pre-20260830-081027/`:

- `demo-files.tar.gz`: SHA256 `8ea7f6e8e05f2b8937dcc96599c5d6a3d8fe8ced5ffb8bb9e75db9d7918a88f9`
- `demo.sql`: SHA256 `3aa015ce4c9672b4a715e53260291361682b8194ef4134859b173b4e20985844`
- `website-files.tar.gz`: SHA256 `67e4566ce5cb58ad3cb202e4c283aab3effac8514a795501425503ea010677c9`
- `demo.htaccess`: separate pre-hardening copy (do not restore without retaining the new addons denial).

Database change in this recovery: one additive `calendar_deliveries` table. Its down migration is deliberately non-destructive (`DO 1`); audit records survive rollback. Original Calendar tables were created by the interrupted turn. No pre-existing customer data was removed.

Infrastructure: added `/etc/cron.d/eduvixo-calendar`, runs every minute as web121, and `/etc/logrotate.d/eduvixo-calendar` (weekly, four retained compressed rotations). Worker log: `storage/logs/calendar-worker.log`. Corrected the task-created `/tmp/eduvixo-calendar-9c25f205cda0fb41.lock` owner to web121/client9 and mode 0600 before switching from root diagnostics to service-user execution.

Rollback: stop/remove this specific new cron entry first, preserve current DB and logs, restore appropriate files into the matching hosting root from the pre-repair archives or use recorded PackageManager release rollback. Restore matching DB only if necessary after preserving subsequent customer changes. Keep `addons` private-directory denial even when restoring old files. The new delivery ledger can remain. Do not restore the original Calendar 1.0.0 runtime without addressing its known flaws. Remove only the new Calendar logrotate entry if fully reverting infrastructure. Validate ownership, hashes, PHP syntax, HTTP and DB state again.

## Validation

- Original local and server Beta archive SHA256 rechecked and matched.
- 30 real MariaDB integration checks in a uniquely named scratch database, including additive/reverse migration, visibility, conflicts, concurrent lock contention, DST, recurrence, cancellation, disabled reminders and exactly-once internal recipient delivery. No production events or outbound messages created. Scratch databases removed; one failed test left a scratch DB that was identified by its three `example.invalid` fixtures and then removed.
- Nine provider input/SSRF checks, zero outbound requests.
- PHP syntax and JavaScript syntax checks passed; `git diff --check` passed.
- Marketplace verifier checks every artifact size/hash, six Ed25519 signatures and payload hashes, catalogue/manifest versions, icons and seven language catalogues.
- Seven live Marketplace language routes return 200. Calendar redirects unauthenticated users to login (302), API returns 401, public extension asset returns 200, direct addon source/schema and private marketplace archive return 403.
- Browser: local actual view/new-event modal and error handling tested; live Marketplace catalogue checked. Live Calendar correctly reaches login; authenticated browser operations remain unverified because CAPTCHA sign-in was not performed.
- Worker executed successfully as web121 and subsequent cron log showed successful empty-queue run. No integrations configured, events/reminders counts still zero.
- Apache configuration valid; an existing unrelated `NameVirtualHost has no effect` warning remains in ISPConfig configuration. Apache/PHP/cron services active.

## Remaining work / release gates

1. Configure real Google OAuth, Apple app-specific password/private CalDAV URL, Microsoft Graph application permissions, Telegram bot/private recipient mapping and WhatsApp credentials/approved two-body-parameter template. No such settings are currently stored. Validate each provider with dedicated test calendars/recipients before enabling customer traffic. Recipients must have consented.
2. Real background Web Push (service worker/subscriptions/VAPID), Windows and mobile delivery are NOT implemented. Browser delivery is intentionally unavailable in the UI; current in-app refresh is not Web Push.
3. Calendar exports currently flow outward only, and only campus/public events go to shared external destinations. Bidirectional sync, inbound conflict policy and re-sync/backfill on destination changes remain future work.
4. Messenger reminders exist, but schedule-change/cancellation messages currently use internal notifications only. Add channel-aware immediate change jobs, recipient preferences and delivery-status/retry administration before Stable. External unknown deliveries need manual review, not blind replay.
5. Complete all-day/timezone UX and provider all-day serialization, series editing, notification links, live authenticated UI/E2E and seven-language Calendar admin localization. Current CMS/admin Calendar text is English. Marketplace is localized in all seven languages.
6. Load-test worker and reduce advisory-lock duration for slow external deliveries if required. Current worker serializes scheduling with dispatch to prevent stale/cancelled reminders.
7. Operationally verify product-specific license issuance/renewals for all six products. Paid download is gated using product-specific names/model, but no real purchase/license flow was exercised.

Operator checks: `.wrk/calendar-marketplace-verify.php`, `.wrk/calendar-integration-tests.php`, `.wrk/calendar-provider-security-tests.php`, `.wrk/calendar-audit.php`. The local `.wrk/calendar-ui-check.php` is an isolated view fixture, never a production route. Private CMS/plugin source stays outside public Git by policy; retain the new private source archive as well as the original Beta snapshot.

Provider references used: Google Calendar create-events and insert API guides; Microsoft Graph create-event/event `transactionId` documentation; Meta's official Postman WhatsApp template-message example; Apple Support app-specific-password documentation. No third-party runtime dependency added.

## Final checkpoint

- Website, tooling and recovery documentation committed and pushed to `origin/main`: `f761fabf1e5ce1696ff3b33ac972e77e68453720`.
- Additional private local backup: `F:\Git\ChivaleGroup\.backups\eduvixo-calendar-beta-local-20260830-082000.tar.gz`, 460203962 bytes, SHA256 `d5943d6ebe3223f3756a55334c951c7e930977691f2ce0a62c4a48e3b4c6168a`.
- Archive contains the current project including ignored CMS/plugin sources, configuration/signing key and Git; excludes only regenerable `tmp/`. Tar listing completed successfully and explicitly confirmed CalendarRepository and the signing-key file. This backup is confidential and must remain outside the web root/public Git.
- Local UI test server stopped. Cron log permissions normalized to 0640 and cron uses umask 027.
