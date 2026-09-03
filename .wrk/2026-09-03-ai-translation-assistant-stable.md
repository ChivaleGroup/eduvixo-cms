# AI Translation Assistant 1.0.1 Stable

## Outcome

- Released the free, license-gated `AI Translation Assistant` package as `1.0.1 Stable`.
- Published the signed package and signed 13-product catalogue to `www.eduvixo.com`.
- Installed and activated the plugin only on `demo.eduvixo.com`; `shoudu.lrn.asia` remains without it.
- Configured demo for the OpenAI-compatible endpoint and `gpt-5-mini`. The credential is encrypted with the installation-specific Base CMS secret and is never returned to the browser.

## Implementation and security

- Each Base CMS installation owns its provider, endpoint, model and encrypted credential.
- Supported providers are local Ollama loopback and remote OpenAI-compatible HTTPS endpoints.
- Remote private, loopback, link-local and metadata destinations are rejected to reduce SSRF risk.
- Translation results must preserve HTML structure, placeholders, URLs, e-mail addresses and numbers.
- Source boundary whitespace is preserved and no translated content is saved or published automatically.
- Translation and connection tests use session and persistent activity-log rate limits.
- The settings modal is keyboard accessible, traps focus, restores focus and closes with `Esc`.

## Interrupted-deployment recovery

The first install attempt loaded the demo configuration while inherited `CMS_*` variables still referenced Shoudu. Package files were placed in the intended demo directory, but the database transaction registered the package in Shoudu. The accidental Shoudu database rows were backed up and removed; no Shoudu plugin files existed or were deleted. The installer now loads Shoudu first, clears the CMS environment, then loads demo, and verifies the actual stored encrypted value instead of relying on PDO `rowCount()`.

Recovery backup:

- `/root/eduvixo-backups/ai-translation-routing-repair-20260903-100631`

Deployment backups:

- `/root/eduvixo-backups/ai-translation-pre-20260903-104713`
- `/root/eduvixo-backups/ai-translation-stable-pre-20260903-104727`

## Release identity

- Package: `ai-translation-assistant-1.0.1.zip`
- SHA-256: `bbc4340e8fcf883c727bbcc8474f0a2b96bef573b67002ed865c7065a9ed7f83`
- Publisher key: `chivale-eduvixo-2026`
- Package signature: verified
- Public package path: denied with a non-disclosing response; downloads remain behind the Marketplace license endpoint.

## Validation

- Service suite: 12/12 passed against the mock Ollama-compatible provider.
- Marketplace package, payload and catalogue signatures: passed.
- Full Stable release verification: 106 assertions passed.
- Marketplace pages: 13 products and localized AI Translation copy verified in all 7 languages.
- Demo routes: anonymous workspace redirects to login; anonymous API returns `401`.
- Extension CSS and JavaScript: HTTP `200` with correct MIME types.
- Authenticated desktop visual review: workspace and provider modal render correctly.
- Demo: Base CMS `1.0.25`, AI Translation Assistant `1.0.1`, active and signature verified.
- Shoudu: Base CMS `1.0.25`, AI Translation Assistant absent from files and database.
- Apache and PHP-FPM active; no warning-level service log entries after deployment.

## Current external limitation

The OpenAI credential is valid enough to reach the provider, but the provider currently returns a rate or usage limit response. Keep `verified_at` empty until API billing/quota is available and `Test saved connection` succeeds. This does not affect package integrity, installation isolation or local Ollama support.

## Rollback

Use Package Manager rollback for the demo package update. For website catalogue rollback, restore the targeted files from the Marketplace deployment backup, restore ownership to `web123:client9`, reload PHP-FPM and repeat the production audit. Never copy the encrypted provider credential between installations.
