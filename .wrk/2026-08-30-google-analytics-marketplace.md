# Google Analytics plugin and licensed Marketplace release - 2026-08-30

## Scope and decisions

- Product website and Marketplace target: `/var/www/clients/client9/web123/web`, owner `web123:client9`.
- No changes were made to the demo or Shoudu installations and no database schema or data changes were required.
- Eduvixo CMS is priced at USD 360 per year.
- The default Eduvixo theme and both Windows portable variants now require product-license verification before a browser download token can be issued.
- Google Analytics is a free stable plugin, but its package remains protected by product-license verification. The Marketplace product identity is `Google Analytics for Eduvixo` / `Web Analytics Integration` / `1.0.0`.
- Free Marketplace products are explicitly labelled in both the website cards and the signed catalog consumed inside Eduvixo.

## Google Analytics plugin

Private source: `.plugins/EduvixoGoogleAnalytics/source`.

The signed package is `storage/marketplace/packages/google-analytics-1.0.0.zip`, 8,845 bytes, SHA-256 `68ddbc291b03e87afbaeb4ac2fef1d966b1ca982edc40ff9d5d38e15b9ad4c1f`. It contains a six-file payload and a valid Ed25519 publisher signature.

The plugin stores only one configuration value: the Google Analytics 4 Measurement ID suffix. It accepts either the suffix or a `G-`-prefixed value, normalizes it and stores the suffix only. It does not contain the `www.eduvixo.com` property ID. The public integration loads Google only after explicit visitor consent and provides a persistent settings control to withdraw or change the decision. Copy is included for all Eduvixo frontend languages plus the existing Khmer fallback used by the CMS.

Because `.plugins/` and `storage/marketplace/` are private ignored paths, the source and signed ZIP were additionally copied to `F:\Git\ChivaleGroup\.backups\eduvixo-google-analytics-plugin-20260830-120000`.

## Product website Analytics

The website property is configured through `SITE_GOOGLE_ANALYTICS_ID`, with `G-CCZKQZHM4S` as the current website default. Google Tag Manager is not embedded in the HTML and is not requested before consent. The consent dialog, persistent analytics-settings control, privacy notice and Marketplace copy are localized in all seven website languages. The Content Security Policy permits only the Google endpoints required for this optional analytics flow.

## Licensed Windows variants

License requests now carry a validated `variant` value through the AJAX request, package resolver and one-use download token. A license can therefore unlock only the exact selected x64 or x86 file. Direct browser downloads remain disabled, an empty or unknown variant is rejected, and successful tokens remain IP/user-agent bound and single-use.

## Backup and deployment

Production backup: `/root/eduvixo-backups/marketplace-google-analytics-pre-20260830-115617`.

- `website-files.tar.gz`: 116,250 bytes, mode `0600`.
- `ROLLBACK.txt` records the targeted restore procedure.
- Deployment used protected staging `/root/eduvixo-deploy/marketplace-google-analytics-20260830-115617` and atomic file replacement.
- New private package mode is `0640`; public assets are `0644`; ownership is `web123:client9`.
- PHP 8.4 FPM was reloaded after publication. Apache and PHP 8.4 FPM remained active.

Rollback: extract `website-files.tar.gz` into the verified web123 root, restore `web123:client9` ownership, remove only the newly added Google Analytics ZIP when a full Marketplace rollback is intended, reload PHP 8.4 FPM and repeat the production audit. The change has no database rollback step.

## Validation

- PHP syntax checks passed for all changed PHP, views, configuration and deployment/audit scripts.
- JavaScript syntax checks passed for the website and plugin assets; the production CSS/JS bundle was rebuilt.
- All seven JSON language files parse successfully and contain the CMS price, Google Analytics description, consent UI and privacy notice.
- All 13 Marketplace files match configured sizes and SHA-256 checksums.
- The Google Analytics ZIP signature, manifest identity and every payload hash were verified locally and in production.
- The signed official catalog verifies and contains 12 products in seven languages, including localized `Free` metadata and a licensed Google Analytics entry.
- All seven production Marketplace pages return HTTP 200, show 12 product cards and 13 license-gated controls. The Eduvixo theme, Google Analytics and both Windows variants are locked.
- Direct access to the Google Analytics package returns HTTP 403.
- Production HTML contains the consent component and expected website Measurement ID, but no Google loader URL before consent. CSP includes only the required Google script/collection endpoints.
- Local browser review verified the consent dialog, absence of Google network loading before consent, the product-detail modal, both locked Windows controls and no browser console warnings/errors.
- PHP 8.4 FPM journal contained no warning-level entries after deployment. Apache's shared error log showed unrelated external probes for nonexistent PHP scripts (`Primary script unknown`); production health and application audits remained clean.

## Remaining operational note

The Windows binaries remain unsigned, as previously agreed; Authenticode signing is deferred until the final Windows build. Marketplace license enforcement is already active independently of code signing.
