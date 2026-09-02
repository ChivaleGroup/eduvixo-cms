# My Calendar 1.1.3 - Telegram reminder format

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

- Signed package: `eduvixo-calendar-1.1.3.zip`.
- Size: 66,978 bytes.
- SHA-256: `b63ea96791becb74b654d09829d0f1a055049a5ec908f610c66671efef0f0ead`.
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
