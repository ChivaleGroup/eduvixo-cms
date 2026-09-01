# WhatsApp Coexistence onboarding - 2026-09-01

## Objective

Keep the existing WhatsApp Business mobile application active while connecting the same business number to Eduvixo through the official WhatsApp Cloud API Coexistence flow.

## Meta state

- The `Chivale Messages & Content` Meta application is enrolled as an Independent Tech Provider.
- Chivale Group LTD business verification was resubmitted and is `In review`; Meta estimates about two business days.
- Tech Provider App Review documentation is `In review`.
- The existing WhatsApp Business number was added to the Chivale WABA but remains unverified. Standard number registration correctly refused it because the number is active in the WhatsApp Business application.
- The number must not be disconnected or migrated through standard registration. Continue only through Meta-hosted Embedded Signup configured for WhatsApp Business App Coexistence.
- Meta currently offers creation of a hosted Embedded Signup configuration and requires an OAuth redirect URI.

## Safety and continuation

- Do not store the phone number, verification codes, app secret, OAuth codes, access tokens, or system-user tokens in this repository or work notes.
- Do not remove the number from the WhatsApp Business application.
- Wait for both Meta reviews to complete before treating the integration as production-ready.
- Next external action: create the persistent Meta-hosted Embedded Signup configuration with an HTTPS callback controlled by Eduvixo, then run the QR-based Coexistence onboarding from the WhatsApp Business mobile application.
- After onboarding: create a least-privilege system-user token, configure the approved utility template, payment method, encrypted Eduvixo channel settings and consented recipient map, then run a controlled delivery test.

## Existing Eduvixo implementation

- Plugin source: `.plugins/EduvixoWhatsApp/source`.
- Provider uses WhatsApp Cloud API template delivery with exactly two body text parameters.
- Runtime settings are encrypted by the existing notification-channel configuration path.
- Central production endpoint: `https://www.eduvixo.com/system/notifications/whatsapp`.
- Server API: license-authenticated start and claim endpoints plus a signed Meta webhook endpoint under `/api/integrations/whatsapp/`.
- One-time state and claim tokens are hashed at rest; Meta access credentials are encrypted and never placed in browser URLs or page content.
- Every CMS authenticates with its existing Eduvixo license, claims only its own result server-to-server, and encrypts credentials with the existing `Secrets` store.
- Meta Embedded Signup uses Business App Coexistence and Graph API `v26.0`; phone registration is intentionally skipped.
- Demo and Shoudu run Eduvixo `1.0.10` Stable. Demo has signed WhatsApp Notifications `1.1.0-beta.1`; Shoudu retains its prior state without the plugin.

## Release and deployment

- Signed core package: `eduvixo-core-1.0.10.zip`, SHA-256 `68472915ba0c129a5287f55826b2ad89043a49523a09b52ba2b33a7d34a2f451`.
- Clean installer: `eduvixo-install-1.0.10.zip`, SHA-256 `63f37e42bdd0159c3f0df98921d401a20bf7b42f90f75cf3c34581f74d9d8570`.
- Signed plugin: `whatsapp-notifications-1.1.0-beta.1.zip`, SHA-256 `554190f6df5dd61a181b9984ac4fd5ac53e36e0b6b6bdc22bde3de34e6e4d210`.
- Main pre-deployment recovery point: `/root/eduvixo-backups/whatsapp-onboarding-pre-20260901-095635`.
- Request-limit hotfix backup: `/root/eduvixo-backups/whatsapp-webhook-limit-pre-20260901-100435.tar.gz`.
- A deployment-process environment leak briefly wrote the demo plugin package record to the Shoudu database while placing files in demo. The exact phantom row was preserved in the recovery directory and removed transactionally; demo was then reinstalled with a clean environment. No user content or notification settings were deleted. The deploy script now clears every `CMS_*` variable before each installation-specific child process.

## Verification

- WhatsApp onboarding security suite passed: one-time states/claims, encrypted credential storage, no token in return URL, tenant-bound claim, safe installation URL validation, signed webhook verification and single OAuth exchange.
- Signed release verification passed 66 assertions.
- Isolated clean-install verification passed 12 assertions.
- Both production CMS installations report `1.0.10`; Apache configuration is valid and public website, Marketplace, demo and Shoudu return HTTP 200.
- Public request limits remain 64 KiB generally, with an isolated 1 MiB allowance for signed Meta webhook payloads. A 70 KiB ordinary request returns 413, a 70 KiB unsigned webhook reaches signature validation and returns 403, and a webhook over 1 MiB returns 413.
- The central broker key exists with mode `0640` and the website owner.

## Remaining external activation

- Business Verification and Tech Provider App Review remain `In review` in Meta.
- The Meta-hosted Embedded Signup configuration, app secret and webhook verification token are intentionally not stored in Git.
- Immediately before creating the persistent Meta configuration and submitting its redirect/webhook settings, obtain direct user confirmation under the browser safety policy.
