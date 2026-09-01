# Demo notification recipient setup - 2026-09-01

## Scope

- Audited the Telegram and WhatsApp notification channels on `demo.eduvixo.com` for the active, non-demo Owner user.
- Confirmed Telegram remains enabled, verified and encrypted.
- The requested Telegram account opened `@EduvixoNotificationsBot` with `/start`. Its username and private chat type were verified through the Bot API, and the Owner recipient mapping was replaced with the stable numeric private chat ID. Telegram does not permit delivery to a phone number or username alone.
- Added the consented WhatsApp destination to the Owner user's encrypted recipient map.
- Kept WhatsApp disabled and unverified because Meta Cloud API credentials, phone-number identity and an approved utility template are not yet available. The stored recipient draft is visible in `System -> Notification channels` and does not enqueue deliveries while disabled.
- No provider token, recipient identifier, phone number or username is stored in Git or this work note.

## Backup and rollback

Pre-change production backup: `/root/eduvixo-backups/notification-recipients-pre-20260901-161100`.

- `demo.sql`: `1e721e6032531eda6248a5eb0a801e01c5e92f9bc3d155cc6aaabc600dd2c23d`
- `ROLLBACK.txt`: `a525859c8741967b8ee4fd96e284d36cd8966749df26c309edc2f462a52534dd`

Preserve newer data before any SQL rollback.

Telegram remapping backup: `/root/eduvixo-backups/telegram-recipient-pre-20260901-161647`.

- `demo.sql`: `9f2e0c99cc29a50c706d839b1ccbd8b0c8a1591d76b32b2334f2656fcbc2fcc0`
- `ROLLBACK.txt`: `3c5c4552a3fc97a0dd7a43cf4eb22056971bf59074cebd701d6666d47203c899`

## Verification

- Recipient configuration remains encrypted at rest.
- WhatsApp recipient map contains exactly one active, non-demo user and matches the consented destination.
- WhatsApp remains disabled, with no verification timestamp, credentials or approved template configured.
- Telegram provider credentials and recipient mapping are encrypted; the channel remains enabled and verified, and the requested destination is a verified private chat.
- A controlled Telegram notification completed with one confirmed `sent`, zero `unknown`, zero `skipped` and no Web Push fallback.
- The first controlled notice was created in the same second as the channel revision and was intentionally outside the strict post-opt-in cursor. It produced no delivery row and no outbound request. A separate post-activation notice verified delivery without duplication.
- The notification settings page shows the stored WhatsApp recipient draft and does not reveal provider secrets.
