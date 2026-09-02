# Shared Telegram broker and private user connections

Date: 2026-09-02

## Result

- One centrally operated Telegram bot now serves multiple licensed Base CMS installations.
- Every Telegram binding is scoped to the canonical CMS domain and CMS user ID, encrypted at rest, and invisible to other installations or users.
- Users manage their own connection in **My settings**. A ten-minute, one-use deep link is rendered locally as a QR code; Telegram confirms the link through the authenticated webhook.
- Existing demo delivery was migrated to the broker without exposing or retaining the shared bot token in the CMS database.
- Core authorization still decides every recipient. User, team, role, campus, resource and installation-wide events can only reach connected users who are permitted to receive the source event. “All” means all authorized users in one installation, never all broker tenants.
- Provider failure is isolated: Telegram recipient discovery cannot stop Web Push or other notification channels.

## Release and deployment

- Base CMS: `1.0.16` Stable on `demo.eduvixo.com` and `shoudu.lrn.asia`.
- Telegram Notifications: `1.2.0-beta.1`, installed and active only on demo; Shoudu remains unchanged until an administrator installs the Marketplace package.
- Marketplace and clean installer were rebuilt and signed. Direct package paths remain protected.
- Central endpoints are hosted under `https://www.eduvixo.com/api/integrations/telegram`.
- The broker validates the installation license with the product identity supplied by the licensed CMS, allowing differently branded Base CMS deployments without weakening tenant boundaries.
- Production recovery point: `/root/eduvixo-backups/telegram-shared-broker-pre-20260902-104132`.
- Targeted broker hotfix backup: `/root/eduvixo-backups/telegram-broker-license-fix-20260902-105115.php`.

## Verification

- Local signed-release verification: 106 assertions.
- Local broker security suite: 14 assertions.
- WhatsApp onboarding regression suite: passed.
- Production broker/Marketplace/two-CMS audit: 51 assertions.
- Clean installer production-host test: 12 assertions.
- Browser QA: connected account state rendered correctly in My settings; no console warnings or errors.
- Apache configuration and `apache2`, `php8.4-fpm`, `mariadb`, and `cron`: healthy.

## Operational notes

- Changing the shared bot profile is a platform-wide operator action, not a per-school action.
- Each recipient connects their own private Telegram account once. Team/role membership changes require no Telegram reconnection because authorization is recalculated by the CMS.
- Rollback should restore the corresponding recovery archive and the previous bot delivery mode before removing the webhook or broker files.
