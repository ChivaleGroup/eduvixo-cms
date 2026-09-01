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
- No Meta OAuth callback or Embedded Signup handler exists in the current Eduvixo source.
