# System notification plugins - 2026-08-30

## Scope and design
- Calendar connectors remain USD 12/year and explicitly require Eduvixo Calendar.
- Telegram/WhatsApp become independent system channels, USD 48/year each; Calendar is optional.
- Reuse existing provider adapters and encrypted configuration. Add core channel settings, a durable event journal, per-recipient delivery ledger and a bounded CLI worker.
- Cover chat, form inbox, completed surveys, editorial/user notifications, package updates, license expiry and Calendar changes/cancellations/reminders. Transient UI success/error toasts are not outbound business notifications.
- Recheck active users, demo status, permissions, campus and conversation/event visibility before delivery. Only consented private destinations; no student message bodies in automatic chat/form/survey alerts.
- Deduplicate events, never automatically resend ambiguous provider calls. Credentials and live destinations are not test data. No outbound live tests without configured accounts.

## Deployment / rollback
Targets: demo CMS web121 and product website web123, client9. Additive database migration only, no existing rows deleted. Keep old Calendar integration settings for rollback; migrate encrypted messenger settings without enabling unconfigured providers. Back up both deployments and demo database before applying changes. On rollback stop the new worker, restore backed-up files/DB together; retain new data separately if created since deployment. Preserve direct-access restrictions.

## Delivered
- Deployed to both target sites on 2026-08-30, approximately 10:22 UTC. Six extensions installed/active at `1.0.2-beta.1`. Signed packages and CMS installation distribution `1.0.1` published; product IDs and license download controls preserved.
- Google/Apple/Microsoft connectors explicitly require installed, active Eduvixo Calendar in all seven Marketplace locales and runtime/signed manifests. Annual prices remain 12 USD; Calendar remains 120 USD.
- Telegram/WhatsApp: 48 USD/year each in signed manifests and seven locale files; no Calendar dependency; configuration at `/system/notifications`, gated by `system.manage`, CSRF and demo read-only protection.
- Existing external licensing product names/model/version identifiers were retained for compatibility; the legacy internal messenger model string is not public marketing copy.
- Messenger and updated Calendar package migrations check for the required core notification tables. Install the new CMS distribution/core patch before installing these packages on another installation.
- Additive core migration `024_system_notifications.sql`: four tables (`notification_channel_settings`, `notification_events`, `notification_deliveries`, `notification_cursors`) and four source lookup indexes. No production data removed.
- Scheduler `/etc/cron.d/eduvixo-notifications`, every minute, `web121`, batch 50, umask 027. Logs: `storage/logs/notification-worker.log`; rotation `/etc/logrotate.d/eduvixo-notifications` weekly, four compressed copies. Existing Calendar worker retained.
- On fresh installations an operator must configure the scheduler to invoke `php /path/to/cms/scripts/notification-worker.php 50` once per minute. No cron is automatically installed by the web installer.

## Important fixes / compatibility
- Existing `Secrets::decrypt()` passed nonce and ciphertext in reversed order. Fixed the argument order; encryption format/key unchanged. Round-trip tests now pass. This also restores existing consumers of this helper.
- Native CMS tables use DB-local `NOW()`; this server is UTC+08. Calendar and the new journal use UTC. The collector normalizes source timestamps and uses completion time plus ID for surveys, so previously started responses are not lost when completed later.
- An initial licensing preflight explicitly rejected engine version `1.0.1` (`Product version not licensed`). No production engine version was changed. The licensed engine remains `1.0`; installation archive version `1.0.1` identifies the patch distribution. A subsequent direct license validation for engine `1.0` passed. Do not change licensed engine/product identifiers without coordinating a proper entitlement update.
- Five-minute source replay window plus per-user/per-plugin deduplication covers ordinary out-of-order transaction commits; durable published events use a pending journal, not a high-water mark. Very long transactions beyond the replay window need operational review.
- Ambiguous outbound responses and interrupted sends become `unknown`, never automatic resend. The settings screen shows delivery state totals. Manual provider investigation is required before any explicit retry.
- Pending sends recheck active/non-demo users, current channel configuration, conversation ownership/team, role and campus visibility. Calendar reminders are suppressed after cancellation, rescheduling or expiration.
- Calendar internal and messenger reminders share a stable event key; they do not duplicate transport delivery. Internal notices fan out to configured system channels; explicitly selected messenger channels narrow channel-specific reminders. Calendar changes/cancellations use the same journal.
- `SystemNotifications::record()` is the durable bridge for modules. `user_id=0` means broadcast to configured recipients, filtered by supported source permissions. User-targeted notices in existing `user_notifications` also flow to channels. Unknown custom journal source types fail closed; new modules can use user-targeted notices or add an explicit audience rule.
- Existing encrypted messenger Calendar settings are preserved and copied, disabled, if present. Wider notification consent must be confirmed before enabling. No provider credentials/recipients were configured in this deployment.

## Files changed
Private CMS source: `app/Core/{AccessControl,AiRepository,Secrets,SystemNotifications,NotificationChannels,NotificationAudience,NotificationDispatcher}.php`; `app/Http/{DashboardController,NotificationController}.php`; `app/Views/{console,console-notification-settings}.php`; `public/index.php`; `scripts/notification-worker.php`; `database/migrations/024_system_notifications.sql`.

Private extension source: six addon/plugin manifests; Calendar repository/dispatcher/controller; required-core migration checks for Calendar and both messengers. Provider HTTP adapters remain unchanged.

Public repository: `config/marketplace.php`, all seven `lang/*.json`, `.gitignore` staging exclusion, and the deployment/test/recovery artifacts in `.wrk`. Private source, archives, credentials and signing keys remain ignored and were not force-added to Git.

## Verified
- 31 isolated MariaDB assertions for notification configuration, encryption, consent, demo/group restrictions, source delivery, late survey completion, both messengers without Calendar, update alerts, deduplication, ambiguous failures, revoked access and Calendar privacy/stale reminders. Temporary test databases were removed; no external messages sent.
- 30 Calendar regression assertions, including conflicts, recurrence/DST, concurrency, reminder ledger and rollback preservation.
- 9 provider security tests, zero outbound calls.
- PHP lint, Git whitespace check, signed manifest/payload/hash verification: 11 products, 12 downloadable files, 7 locales.
- Live audit `notification-production-audit.php`: six active correct versions/prices/dependencies, 7 actual HTTPS language pages contain expected descriptions/badges/prices, zero configured channels.
- HTTPS checks without disabling certificate verification: product Marketplace and demo GET `/login` 200; unauthenticated notification settings 302; direct private package/plugin/addon source requests 403. Apache, php8.4-fpm and cron active. Cron executions observed every minute at 18:22-18:25 server time; notification/Calendar workers return `ok:true`. No new PHP errors in demo error log.
- Actual settings template rendered and visually inspected locally using Browser skill and `notification-ui-preview.php`, with no POST operations. In-app Browser rejected production navigation with `ERR_CERT_AUTHORITY_INVALID`; external Windows curl and server curl both successfully validated HTTPS. No certificate bypass was used; production authenticated UI/provider-send E2E remains unverified.

## Backups and recovery
Pre-deployment server backup: `/root/eduvixo-backups/notifications-pre-20260830-101956/` (archives list/read checks passed).
- `demo.sql`: SHA256 `f11a12ef0d37dbea19b73afaa55f7994ad93d448ec96c6697587ee6d40b3d11c`.
- `demo.tar.gz`: SHA256 `7088cc6f02d2932b767f91b65e7022e2e42d18d5c220ecde326c3add124fa25e`.
- `website.tar.gz`: SHA256 `2f69305ce58b5610e4e6600768c8fbd175f14edb70a7aa2c9e59098dd9ef70f6`.

Full private local source snapshot: `F:\Git\ChivaleGroup\.backups\eduvixo-notifications-local-20260830-1020.tar.gz`, 481227944 bytes, SHA256 `9c8a59ff94e33eb1f36256c24c911276ee8535d46867288bb98d9e40cd62e13f`. Includes credentials/private source/signing material: never publish. Excludes temporary staging directories. Small subsequent verification-note edits are in Git.

Rollback: first disable only the new notification cron entry (move it into the private backup directory), preserve a fresh copy of any new data, restore the demo/website files to the same explicitly verified web121/web123 roots and correct ownership, then restore matching package registry/database state. A full SQL restore reverts all writes since the snapshot, so assess and preserve intervening user activity first. Additive notification tables/indexes may be retained on a code rollback; do not drop them merely to clean up. Recheck licensed engine `1.0`, private-path 403 responses, login and both site health checks. Restore existing Calendar cron unchanged.

## Remaining activation steps
- Configure a Telegram bot/private opted-in chat IDs and/or WhatsApp Cloud credentials, an approved two-body-parameter utility template, and opted-in international recipient numbers.
- Verify actual provider delivery with designated recipients after configuration. Provider messaging charges are separate from the plugin price. Not tested with live provider credentials in this turn.
- Persistent journal/delivery retention and an operator-assisted retry UI can be added later; no automatic deletion or unsafe ambiguous retries were introduced.
