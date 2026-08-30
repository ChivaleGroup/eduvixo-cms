# AI Translation Assistant and Marketplace release - 2026-08-30

## Scope and product decision

- Product: `AI Translation Assistant`.
- Release: `1.0.0-beta.1`, Beta channel.
- Price: Free.
- Browser download remains protected by product-license verification and the existing three-failure lockout. Product identity: `AI Translation Assistant` / `AI-Assisted Multilingual Content` / `1.0.0`.
- No publisher-side Microsoft, Google, OpenAI or other external account is required. Each institution controls its own local Ollama service or OpenAI-compatible HTTPS provider and bears any provider cost.
- The plugin does not depend on Eduvixo Calendar or another add-on.

## Plugin implementation

Private source: `.plugins/EduvixoAITranslationAssistant/source`.

The plugin adds a responsive Eduvixo administration workspace at `/ai/translation-assistant` for users with `ai.manage`. It supports English, German, Chinese, Vietnamese, Thai, Lao and Polish, automatic source-language detection, plain text and HTML, draft comparison, copying and source/target swapping. Results are review-only: the plugin never writes or publishes CMS content automatically.

Provider configuration supports:

- local Ollama through a loopback-only endpoint, with `http://127.0.0.1:11434` as the default;
- a customer-owned OpenAI-compatible public HTTPS endpoint with an encrypted API key.

The provider test and translation actions use JSON/AJAX without page reloads. Translation events and setting changes are recorded in the activity log without source text, translated text or credentials.

## Security controls

- Remote endpoints require HTTPS, resolve to a public address and are pinned to the validated address for the request. Private, reserved, loopback and metadata destinations are rejected for remote providers.
- Local Ollama explicitly permits only `localhost`, `127.0.0.1` or `::1`.
- Redirects and non-approved protocols are disabled; connection, response-size and request-time limits are enforced.
- Provider API keys use Eduvixo `Secrets` encryption and are never returned to the browser.
- CSRF, authentication, `ai.manage`, session request limits and Demo User read-only enforcement apply.
- Prompts treat source content as untrusted translation input and explicitly reject embedded instructions.
- Placeholders are compared before accepting a translation. HTML mode additionally requires every tag and attribute to remain byte-for-byte unchanged.
- Input is limited to 20,000 characters and output to 60,000 characters.

## Signed distribution

- Package: `storage/marketplace/packages/ai-translation-assistant-1.0.0-beta.1.zip`.
- Size: 15,628 bytes.
- SHA-256: `d82f312c24037509814323371ce63dd52cd9037e4ed2a347d67f9a98c4ca7c72`.
- Publisher key: `chivale-eduvixo-2026`.
- The signed manifest and all seven payload files verify with the Eduvixo Ed25519 publisher key.
- Private source and signed artifacts were copied to `F:\Git\ChivaleGroup\.backups\eduvixo-ai-translation-assistant-20260830-194200` because `.plugins/` and `storage/marketplace/` are intentionally ignored by Git.

## Website and catalog

- AI Translation Assistant is the thirteenth Marketplace product and the fourteenth downloadable control because Windows has two variants.
- Localized description and the existing localized Free label are available for all seven website languages.
- The product uses the unified blue Marketplace icon treatment, a new `languages` symbol and the existing product-detail modal.
- The official signed catalog was rebuilt and contains 13 products in seven languages.
- Current updater audits in `.wrk/system-update-tests.php` and `.wrk/core-update-production-audit.php` now expect 13 products.

## Deployment

Production website root: `/var/www/clients/client9/web123/web`, owner `web123:client9`.

Final pre-deployment backup: `/root/eduvixo-backups/ai-translation-pre-20260830-124039`.

- `website-files.tar.gz`: 105,877 bytes, mode `0600`.
- `ROLLBACK.txt` documents exact restore behavior and recognizes whether an earlier package version existed.
- Deployment used protected staging and atomic file replacement.
- Private package/catalog/config/language files use mode `0640`; the public icon sprite uses `0644`.
- PHP 8.4 FPM was reloaded; Apache and PHP 8.4 FPM remained active.
- No database schema or data changes were made.

The internal core updater fixture was synchronized with the current signed 13-product catalog. Its previous catalog is retained as `/root/eduvixo-deploy/core-candidate/official-catalog.json.pre-ai-translation-20260830`.

Rollback: extract the final backup into the verified web123 root, retain its restored package version, restore `web123:client9` ownership and recorded modes, reload PHP 8.4 FPM, restore the previous core-candidate catalog when testing the older fixture, and repeat the production audit. No database rollback is required.

## Validation

- PHP syntax, JSON parsing, JavaScript syntax and Git whitespace checks passed.
- Service tests passed for local-provider configuration, placeholder preservation, HTML preservation, provider connection and rejection of metadata, private remote, non-HTTPS and placeholder-changing responses.
- Package signature, manifest identity, Free license and every payload hash passed locally and in production.
- Every configured Marketplace package is present; the new package matches its size and SHA-256.
- Official catalog signature, 13-product count and all seven localized descriptions passed.
- All seven production Marketplace pages return HTTP 200 with 13 cards, 14 licensed controls, the AI Translation card, lock and localized description.
- Direct package access returns HTTP 403.
- The complete 40-assertion core update, data-preservation, authentication, CAPTCHA and rollback suite passed after fixture synchronization.
- Visual local-build validation confirmed a 381 px square-style card, blue icon, Free label, license lock and a working Beta/Free detail modal with no browser console warnings.
- PHP 8.4 FPM reported no warning-level entries after deployment. Apache contained only unrelated external probes for nonexistent PHP scripts.

## TLS observation

The Codex in-app Chromium surface reported `ERR_CERT_AUTHORITY_INVALID` for the production visual check. Independent Windows Schannel and OpenSSL checks both validate the live Let's Encrypt certificate and complete chain with `Verify return code: 0 (ok)`; production HTTP/TLS audits also pass. This appears isolated to that embedded browser trust store, not the deployed certificate. No security interstitial was bypassed.

## Operational limitation

Free describes the plugin license, not AI inference. Local Ollama requires an installed local model and adequate resources. A remote provider requires institution-owned credentials and may charge for usage. Beta deliberately provides a review-first translation workspace; direct adapters that populate page/post translation fields can be added later without changing the provider security boundary.
