# Telegram Notifications - production activation

Date: 2026-09-01

## Scope

- Created the official Telegram bot `@EduvixoNotificationsBot` with the display name `Eduvixo Notifications` through Telegram BotFather.
- Activated Telegram Notifications on `demo.eduvixo.com` for the active non-demo Eduvixo user `1 - Mario`.
- Kept the channel independent from Eduvixo Calendar; it receives authorized system notifications and Calendar notifications when the add-on is active.
- Did not configure the separate `shoudu.lrn.asia` installation.

## Security and privacy

- The Bot API token was never written to `.cfg`, source files, Git, documentation, command output, or the system notification payload.
- Eduvixo stores the token and private recipient mapping in `notification_channel_settings.encrypted_settings` through the existing `Secrets` service.
- Only the opted-in private Telegram destination was mapped. Group and channel destinations remain rejected by core validation.
- The configured user is active and is not a demo user. Authorization is rechecked before each queued delivery.
- Automatic chat, form, and survey alerts contain no submitted visitor content.
- Ambiguous provider results remain `unknown` and are never retried automatically.

## Verification

- Telegram `getMe` verified the bot username.
- Telegram Bot API accepted the private `/start` subscription and returned a private recipient update.
- Eduvixo configuration returned `Enabled` after provider verification.
- A controlled `user_notifications` event traversed the production `NotificationDispatcher` and background channel implementation.
- Worker result: one Telegram channel, one confirmed `sent`, zero `unknown`, zero `skipped`.
- The Telegram Web chat displayed the test title, message, and secure `demo.eduvixo.com/system/notifications` link.
- The audit confirmed encrypted settings and at least one confirmed production delivery.

## Operations and rollback

- Configuration: `System -> Notification channels -> Telegram Notifications for Eduvixo`.
- Disable the channel there to stop new outbound deliveries without deleting audit history.
- If the token is ever suspected of exposure, revoke it in BotFather immediately, create a replacement token, save it in Eduvixo, and verify a new controlled delivery.
- Do not copy this installation's encrypted settings to another Eduvixo installation because encryption keys and recipient authorization are installation-specific.
