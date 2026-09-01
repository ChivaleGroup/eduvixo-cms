# Neutral product naming release - 2026-09-01

## Decision

- Public core name: `Base CMS`.
- Scheduling add-on: `My Calendar`.
- Windows application: `Desktop Client for Windows`.
- School theme: `Eduvixo` with an explicit school/education description.
- Independent school theme: `Shoudu Custom Theme`.
- Default software publisher/operator: `QUANT Software House`.
- Service/hosting identity where required: `Chivale`; group identity: `Chivale Group`; formal legal and certificate identity: `Chivale Group LTD`.
- The Eduvixo distribution website and its public brand remain unchanged.

Technical slugs, package archive internals, executable name, local application-data paths, signing key ID, HTTP headers and the validated Chivale license contract identity remain unchanged for backward compatibility. In particular, the legacy `Eduvixo` product identity and legacy extension entitlement names are non-public API identifiers required by existing licenses.

## Releases

- Base CMS `1.0.11` Stable.
- Eduvixo theme `1.1.10` Stable.
- Shoudu Custom Theme `1.1.4` Stable.
- My Calendar `1.1.2` Stable.
- Google Calendar, Apple Calendar and Microsoft 365 Calendar `1.1.1` Stable.
- Telegram Notifications `1.0.2-beta.2`.
- WhatsApp Notifications `1.1.0-beta.2`.
- Google Analytics `1.0.1` Stable.
- AI Translation Assistant `1.0.0-beta.2`.
- Desktop Client for Windows `0.2.5` portable x86/x64. Version `0.2.4` was not overwritten after the final multilingual naming correction.

## Deployment

- Website: `/var/www/clients/client9/web123/web` (`web123:client9`).
- Demo CMS: `/var/www/clients/client9/web121/web` (`web121:client9`).
- Shoudu CMS: `/var/www/clients/client59/web119/web` (`web119:client59`).
- Recovery backup: `/root/eduvixo-backups/base-cms-1.0.11-pre-20260901-151914`.
- Demo retained active Eduvixo theme and all installed active integrations.
- Shoudu retained active Shoudu Custom Theme; unrelated integrations were not installed.

## Verification

- Release verifier: 106 assertions, including catalog/core signatures, every installable package signature and payload, artifact hashes, installer boundary and seven language structures.
- Clean installer production test: 12 assertions, including all migrations, owner role, bundled theme, schema and live license boundary.
- PHP lint: 215 files; JSON parse: 44 files; JavaScript syntax: 37 files.
- Windows: seven languages with 47 keys, signing-script syntax, x86/x64 portable builds and warning-free Release build.
- Web Push cryptography and isolated MySQL integration tests passed; WhatsApp onboarding security tests passed.
- Production audit: 34 assertions for artifact integrity, seven public languages, both CMS releases, theme continuity, registry names and maintenance state.
- English and Polish Marketplace plus English Product returned HTTP 200 with HSTS and CSP, contained all new public names and no legacy public CMS/Calendar names.
- Recent website, demo and Shoudu error logs contained no new PHP fatal, parse, uncaught or warning entries.

## Next product work

Build the provider-neutral `Social Publishing` plugin after this naming baseline. The plugin should keep provider adapters and credentials outside the core, initially support Facebook Pages through Meta OAuth/webhooks, and use QUANT Software House as publisher while allowing white-label distribution.
