# Demo notification recipient setup - 2026-09-01

## Scope

- Audited the Telegram and WhatsApp notification channels on `demo.eduvixo.com` for the active, non-demo Owner user.
- Confirmed Telegram remains enabled, verified, encrypted and operational, with one confirmed prior delivery.
- The requested Telegram account has not yet opened the bot conversation. Telegram does not permit delivery to a phone number or username; the user must first send `/start` to `@EduvixoNotificationsBot`, after which its private numeric chat ID can replace the existing recipient mapping.
- Added the consented WhatsApp destination to the Owner user's encrypted recipient map.
- Kept WhatsApp disabled and unverified because Meta Cloud API credentials, phone-number identity and an approved utility template are not yet available. The stored recipient draft is visible in `System -> Notification channels` and does not enqueue deliveries while disabled.
- No provider token, recipient identifier, phone number or username is stored in Git or this work note.

## Backup and rollback

Pre-change production backup: `/root/eduvixo-backups/notification-recipients-pre-20260901-161100`.

- `demo.sql`: `1e721e6032531eda6248a5eb0a801e01c5e92f9bc3d155cc6aaabc600dd2c23d`
- `ROLLBACK.txt`: `a525859c8741967b8ee4fd96e284d36cd8966749df26c309edc2f462a52534dd`

Preserve newer data before any SQL rollback.

## Verification

- Recipient configuration remains encrypted at rest.
- WhatsApp recipient map contains exactly one active, non-demo user and matches the consented destination.
- WhatsApp remains disabled, with no verification timestamp, credentials or approved template configured.
- Telegram provider credentials are encrypted; the channel remains enabled and verified, and its current destination is a private chat.
- The notification settings page shows the stored WhatsApp recipient draft and does not reveal provider secrets.
