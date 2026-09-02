# My Calendar 1.1.4 - Telegram reminder format

## Scope

- Calendar reminders sent through Telegram now use a channel-specific layout with the local event date, time and full IANA time-zone identifier.
- The event title and description are included as separately labelled sections with blank lines between them.
- The existing secure same-origin Calendar link remains the final line. Other notification sources and Calendar delivery channels keep their existing payloads.
- An empty event description is represented by a short `-`; Telegram receives plain text with no HTML or Markdown parsing.

Expected shape:

```text
Reminder: 2026-09-02 14:30 Asia/Bangkok

Title: Event title

Description: Event description

https://demo.eduvixo.com/calendar?event=123
```

## Release

- Signed package: `eduvixo-calendar-1.1.4.zip`.
- Size: 67,012 bytes.
- SHA-256: `9ed90035ab72d5b5568bee03ce736714f3d6278374bce60f6c21b80bac49802c`.
- Release channel: Stable.
- Private source snapshot: `F:\Git\ChivaleGroup\.backups\my-calendar-1.1.3-telegram-reminder-source-20260902.tar.gz`.
- Source snapshot SHA-256: `024b6f4394121d05f1f74b65ef89309983ffc0df606cc79dd34a0cdde5c0d542`.

## Deployment and recovery

- The first deployment attempt was stopped by its post-install invariant: the demo files had been replaced while the child process inherited Shoudu database environment values. The Marketplace publication had not started.
- Recovery point `/root/eduvixo-backups/calendar-telegram-reminder-pre-20260902-002018` was verified before use. Demo files were restored to 1.1.2, and the exact Shoudu phantom package was removed through the package lifecycle manager, including its runtime state and any newly applied package migrations. No user content was deleted.
- The deployment script was corrected to clear every `CMS_*` environment value immediately before each installation-specific child process.
- Successful deployment recovery point: `/root/eduvixo-backups/calendar-telegram-reminder-pre-20260902-002403`.
- Demo was updated from active, signature-verified 1.1.2 to active, signature-verified 1.1.3. Shoudu does not have My Calendar installed and remained unchanged.
- The public Marketplace configuration, signed official catalogue and immutable 1.1.3 package were published atomically under the website owner.

## Validation

- PHP syntax and JSON parsing passed locally and on production.
- The isolated MariaDB Calendar integration suite passed 45 checks, including the exact Telegram title/body spacing, description, IANA time zone and selected provider channel.
- The complete signed release verifier passed 106 assertions across all Marketplace artifacts and seven languages.
- Production postcheck passed nine assertions: package/config/catalog identity, demo release state, deployed formatter, clean Shoudu state and three HTTP routes.
- Apache configuration is valid; Apache and PHP 8.4 FPM are active; recent website and demo logs contain zero critical PHP errors.
- No live test notification was generated, so the next naturally scheduled Telegram reminder will provide the final end-to-end presentation check without adding artificial Calendar data.

## Internal plus Telegram correction

- A real reminder revealed that 1.1.3 still used the legacy layout when `Internal` and `Telegram` were selected together. The internal reminder inserted an unrestricted system event first; Telegram then reused its deduplication key and retained the earlier generic payload.
- Version 1.1.4 keeps internal Calendar notifications in `calendar_notifications` only and creates the external event exclusively for the explicitly selected Telegram channel. The channel-specific payload can no longer be overwritten by the internal path.
- The isolated integration test now selects `Internal` and `Telegram` together and verifies the exact Telegram title, description, blank lines, IANA time zone and channel restriction.
- Production recovery point: `/root/eduvixo-backups/calendar-telegram-reminder-pre-20260902-035613`.
- Demo is active on signature-verified My Calendar 1.1.4; Shoudu remains without the add-on. The signed Marketplace catalogue and package identify the same 1.1.4 checksum.
- Private 1.1.4 source snapshot: `F:\Git\ChivaleGroup\.backups\my-calendar-1.1.4-telegram-reminder-source-20260902.tar.gz`, SHA-256 `f998e993b00d65b415f00b4b3919e70b3cf6fc481463c890c76683eb4dec02c6`.
- After deployment there were zero unprocessed Calendar events, zero pending Calendar provider deliveries and zero overdue reminder jobs, so no legacy queued payload remains to be delivered.

## Browser plus Telegram correction and chat payload

- A second production case showed that selecting `Browser` and `Telegram` still reused the same reminder deduplication key. The browser payload was inserted first; the durable notification layer correctly retained its original content while merging the Telegram channel, so Telegram received the generic date/location text.
- My Calendar `1.1.5` includes the normalized provider channel in the reminder key. Internal, Web Push and Telegram reminder records are now independent, and the Telegram payload retains the requested time zone, title, description and blank-line layout even when all three channels are selected.
- Base CMS `1.0.13` sends live-chat Telegram alerts as `Sender: <Name>` and `Message: <text>`. Content is converted to bounded plain text and control characters are removed. The separate generic handoff event was removed because the same visitor message already enters the durable chat collector; this prevents the two alerts visible in the reported screenshot.
- Isolated production-host validation passed 47 Calendar assertions and 34 notification assertions. The tests reproduce `Internal + Browser + Telegram`, require separate channel keys, verify the exact description layout, verify sender/message content and ensure a chat handoff is delivered once per authorized recipient.
- Signed artifacts: `eduvixo-calendar-1.1.5.zip` (67,091 bytes, SHA-256 `283de002ea521f221fbfa8cb900601c4950029028a86a5a708cc958402ea024e`), `eduvixo-core-1.0.13.zip` (761,402 bytes, SHA-256 `721a3f26abe8c2c12c16dd23ec757926fec1a3d5fbb2a29b2dfa46ee908967dc`) and `eduvixo-install-1.0.13.zip` (10,407,500 bytes, SHA-256 `2149fb5d9d4a2fb3a2b63d9aa95b14b0906d0f47ac268cc3cce65032e4c8bca0`). The complete signed Marketplace verifier passed 106 assertions.
- Deployed through the signed update/package lifecycle to both CMS installations for the core and to demo only for Calendar. Shoudu remains without the paid Calendar add-on. Recovery point: `/root/eduvixo-backups/telegram-calendar-chat-pre-20260902-051500`.
- A reminder at `05:15:00 UTC` was processed by Calendar `1.1.4` eight seconds before `1.1.5` completed at `05:15:11 UTC`; its old payload is therefore a deployment-boundary record rather than evidence from the corrected release. The next naturally scheduled reminder is used for the final live queue/provider verification.
- The next natural reminder was created by `1.1.5` at `05:20:02 UTC` as two independent events: generic `web-push` and Telegram-only. Telegram received `Reminder: 2026-09-02 12:30 Asia/Bangkok`, then the separately labelled title and `Description: Kalendarz (Description)` with the requested blank lines. The provider-confirmed delivery was recorded as `sent` at `05:21:04 UTC`; no retry or duplicate was created.
- Private source snapshot: `F:\Git\ChivaleGroup\.backups\telegram-calendar-chat-1.0.13-1.1.5-source-20260902.tar.gz`, 10,336,214 bytes, SHA-256 `22913dde140d124dfb3f61731f798e39ef58a645d5de821bbb3999ef5daa3c17`.
