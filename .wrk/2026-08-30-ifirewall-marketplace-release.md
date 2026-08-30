# iFirewall - Marketplace release

Date: 2026-08-30
Version: 1.0.0
Channel: Stable
Commercial model: USD 120 per year
Environment: production (`www.eduvixo.com`)
Status: deployed and verified

## Outcome

iFirewall is available as a new premium plugin tile in the Eduvixo Marketplace. The package can be requested only through the existing AJAX license modal. Browser download remains disabled until the Chivale license service validates a key against the iFirewall product identity.

The source manifest already declares version 1.0.0, so the fallback version 3.8.6 was not applied. The original signed archive was preserved without modification.

## Product and package

- Product: `iFirewall`
- Product model used for license validation: `PHP Security & IP Firewall`
- Version: `1.0.0`
- Release channel: `Stable`
- Price displayed in all languages: USD 120 per year
- Requirement displayed in all languages: PHP 8.5+
- Private release file: `ifirewall-php-1.0.0.zip`
- Size: 37,193 bytes
- SHA-256: `b70d4aee3303dbd55afa07112e650320fe5aac2bccda58d4daeb18385044c1a1`

The source's Ed25519 detached signature, all 21 declared file hashes, archive sidecar checksum and 24 ZIP paths were validated. All 16 PHP files passed PHP 8.5 syntax validation. No unsafe archive path was found.

iFirewall synchronizes centrally managed IPv4, IPv6 and CIDR ranges and blocks matching requests before application code. It is not network-level DDoS protection or a complete WAF. Operation requires PHP 8.5+, curl, sodium, pdo_sqlite, session, a valid TLS CA store, private writable storage, a dedicated sync API token and the separately supplied setup code.

## Implementation

- Added a premium plugin product without exposing its storage path or filename in HTML.
- Added per-package license identities while preserving the existing Eduvixo identity fallback for CMS and Shoudu.
- Preserved the 128-character license field, AJAX/JSON validation, three-failure IP lock, one-use opaque download token and package integrity checks.
- Added localized plugin type, product description, annual price, PHP requirement and generic product-license modal copy in English, German, Chinese, Vietnamese, Thai, Lao and Polish.
- Added localized Marketplace descriptions and keywords for iFirewall SEO.
- Kept the established square card proportions. On desktop the iFirewall card is centered above the full-width Windows panel; tablet and mobile layouts return it to the responsive grid flow.
- Added `.plugins/` to `.gitignore` so vendor source, setup material and release archives cannot be committed accidentally.

No database schema change was required. No public file contains the release archive.

## Deployment and rollback

The active Apache vhost now resolves `www.eduvixo.com` to:

`/var/www/clients/client9/web123/web`

This supersedes the previous `web120` path recorded in older deployment notes. The target was verified from `/etc/apache2/sites-enabled/100-eduvixo.com.vhost` before deployment.

Pre-deployment backup:

`/root/eduvixo-backups/ifirewall-marketplace-pre-20260830-092530.tar.gz`

Backup SHA-256:

`3cb5dbc9b4a9edaade6d0987d0e9f0a4d3559b9fd2ed5597f18362484364e330`

Deployment archive:

`/root/eduvixo-ifirewall-20260830-092530.tar.gz`

Deployment SHA-256:

`0b121c45d3669bd156e99272350266d888c8095771b6010edcb5c32a395c296a`

The private package is owned by `web123:client9` with mode `0640`. Apache and PHP 8.4 FPM remained active after deployment. PHP 8.4 runs the Marketplace only; the downloaded plugin itself declares PHP 8.5+ for its destination environment.

Rollback: extract the pre-deployment backup into `/var/www/clients/client9/web123/web`, remove only `/var/www/clients/client9/web123/web/storage/marketplace/packages/ifirewall-php-1.0.0.zip`, restore `web123:client9` ownership, lint the restored PHP files and reload PHP 8.4 FPM.

## Validation

- Local and production PHP syntax checks passed.
- All seven translation files decode correctly and expose the localized product, price and SEO metadata.
- All seven localized Marketplace routes return HTTP 200 and contain iFirewall, the localized annual price and the 128-character license input.
- The production package byte count and SHA-256 match the verified source archive.
- Direct archive guesses are blocked with HTTP 403 or 404; the actual filename is absent from Marketplace HTML.
- The production page sends CSP, `X-Content-Type-Options: nosniff` and `X-Frame-Options: SAMEORIGIN`.
- Visual QA confirmed a 381 x 430 px desktop card, five Marketplace products, no horizontal overflow and no browser console warnings or errors.
- The modal opens for iFirewall and displays the localized product-license copy.
- Local service validation confirmed invalid-license JSON behavior and attempt decrementing without contacting the license service for malformed input.

A successful end-to-end licensed download was not executed because no valid iFirewall customer license was present in the project configuration. The first issued iFirewall license should be used for that final commercial-flow acceptance test; the package remains inaccessible until such a license validates successfully.
