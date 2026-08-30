# Marketplace UI - 2026-08-30

## Scope

- Unified all public Marketplace product icons with the blue Eduvixo premium treatment.
- Converted each icon into an accessible product-details trigger.
- Added a responsive native dialog showing localized product type, description, version, release channel, price, and compatibility or requirements.
- Added an explicit localized free-price label for products without a configured annual price.
- Preserved the existing licensed, direct, and variant download flows.

## Localization

Added the product-details interface and free-price label to all supported locales: `de`, `en`, `lo`, `pl`, `th`, `vi`, and `zh`.

## Production deployment

- Target: `/var/www/clients/client9/web123/web`
- Owner/group preserved: `web123:client9`
- Static asset mode: `0644`
- PHP, locale, and source asset mode: `0640`

Pre-deployment backup:

- `/root/eduvixo-backups/marketplace-ui-pre-20260830-182417.tar.gz`
- SHA-256: `0ddb1f1a347bc4a71b0c84c97815fa0af3727dd6ca99bdff1907c98b994834fa`

Rollback:

1. Extract the backup with `/var/www/clients/client9/web123/web` as the target directory.
2. Restore ownership to `web123:client9`.
3. Revalidate PHP syntax, locale JSON, the public Marketplace route, and asset responses.

## Validation

- PHP syntax checks passed.
- JavaScript syntax check passed.
- Asset build completed and generated assets are synchronized.
- All seven localized Marketplace routes returned HTTP 200 locally.
- Each locale rendered 11 product-detail triggers and four explicitly free products.
- Browser validation confirmed the Polish card layout, paid and free modal variants, keyboard focus restoration, and no console warnings or errors.
- Production returned HTTP 200 with 11 detail triggers and four free labels.
- Production CSS and JavaScript returned HTTP 200 with the expected security headers.
- The signed official Marketplace endpoint remained available after deployment.
- No new website application errors appeared in the domain error log after deployment.

The in-app browser could not visually open the production hostname because its embedded trust store rejected the current certificate chain. Production was therefore validated through HTTPS requests on the deployment host; local browser validation covered the complete interactive behavior and visual presentation.
