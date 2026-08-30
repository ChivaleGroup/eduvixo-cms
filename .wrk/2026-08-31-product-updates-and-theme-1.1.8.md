# Product ecosystem, Updates and Eduvixo Theme 1.1.8

Date: 2026-08-31 (Asia/Bangkok)

## Scope delivered

- Home now presents AI Translation Assistant, Google Analytics and Eduvixo for Windows in the compact free-extension card. Themes remain in the complete Product catalog.
- Product ecosystem lists start directly below their descriptions instead of being pinned to the bottom of unequal cards.
- Product includes a responsive horizontal custom-solutions section for commissioned add-ons, plugins, integrations, workflows and presentation work.
- Updates was rebuilt into a release-focused page with the supported core version, official theme versions, verified Marketplace catalog count, controlled update lifecycle and operational update policy.
- All new copy is available in `de`, `en`, `lo`, `pl`, `th`, `vi` and `zh`.
- The generated sitemap contains all 84 localized canonical routes with current modification dates.
- Eduvixo Theme 1.1.8 renders the hosting credit link as `Hosting provided by Chivale`, without a terminal period.
- Remaining em dashes were removed from the signed Eduvixo Theme payload.

## Release artifacts

- Theme package: `storage/marketplace/packages/eduvixo-theme-1.1.8.zip`
  - Size: 1,565,727 bytes
  - SHA-256: `1e43d238720db9d8692f688acd4e281aeaba56cc09e02de7ab8f49eaf723bc21`
  - Publisher key: `chivale-eduvixo-2026`
  - Signature: Ed25519 verified
- Final deployment archive: `tmp/release/product-updates-theme-1.1.8.tar.gz`
  - Size: 1,698,944 bytes
  - SHA-256: `9ab375d1b780fe9a58fe3d06dc8545563739828f1bcc392fcc56c5431cb6884f`
- Sitemap SHA-256: `d27b0650a86671a6e27da5232d107f618afb0d50599d5785ea0f6269a87574f6`
- Signed official catalog: 13 products, 7 language payloads, Eduvixo Theme 1.1.8.

## Production deployment

- Product website: `/var/www/clients/client9/web123/web`, owner `web123:client9`.
- Demo CMS: `/var/www/clients/client9/web121/web`, owner `web121:client9`.
- Shoudu CMS: `/var/www/clients/client59/web119/web`, owner `web119:client59`.
- Both CMS installations now report Eduvixo Theme 1.1.8 in the filesystem and package database.
- Demo retains active theme `eduvixo`; Shoudu retains active theme `shoudu`.
- Both installations trust the official publisher, report a verified package signature and are outside maintenance mode.
- Direct unauthenticated theme-package access returns HTTP 401.

## Backup and rollback

Backup directory: `/root/eduvixo-backups/product-updates-theme-1.1.8-pre-20260830-180950`

It contains:

- targeted website archive and the previous sitemap;
- full transactional SQL dumps for Demo and Shoudu;
- the previous Eduvixo Theme directory for each CMS;
- `ROLLBACK.txt` with restoration order and post-restore validation.

All backup files use mode `0600`. Preserve writes made after the backup before restoring a full SQL dump. Prefer the Package Manager release archive for an ordinary theme rollback; use the tar and SQL files for disaster recovery.

## Deployment incident and permanent correction

The first theme pass inherited `CMS_*` variables from the previously loaded Shoudu configuration. This placed the Demo filesystem update and Shoudu package metadata out of alignment. The release stopped before the second replacement because Package Manager correctly rejected an equal-version install.

Recovery was performed without discarding data:

1. The misaligned Demo theme was preserved inside the backup directory and Demo 1.1.7 was restored from the verified pre-deployment archive.
2. The Package Manager rollback archive created under the Demo root was copied, checksum-verified and assigned to the path referenced by Shoudu metadata.
3. Shoudu was rolled back through `PackageManager::rollback()`.
4. Both sites were upgraded again through `PackageManager::install()` with `CMS_*` variables cleared before each site configuration is loaded.
5. The one abandoned, checksum-matched staging ZIP from the rejected install was removed. The signed release package and all recovery archives remain available.

The reusable theme bootstrap now clears inherited `CMS_*` variables before loading site configuration, preventing cross-installation environment leakage in future multi-site deployments.

## Database impact

- No schema migration was added or executed.
- Package metadata and rollback history were updated by the existing signed Package Manager lifecycle.
- No content, user, role, license or application settings were deleted or mass-updated.

## Validation

- PHP syntax passed for all changed PHP files and both installed theme views.
- All seven language JSON files decode and satisfy the new Updates/custom-solutions schema.
- Signed catalog verification passed and matches the theme package checksum.
- Every file in the theme package matches the signed manifest hash.
- Local and production checks passed for all 84 localized routes, correct `lang` markers and complete HTML documents.
- Desktop QA at 1440 px and mobile QA at 390 px passed without horizontal overflow.
- Production Home, Product and Updates content checks passed in all seven languages.
- Demo public output contains exactly `Hosting provided by Chivale` and not the period-suffixed variant.
- Apache, PHP 8.3 FPM and MariaDB are active; no warning-or-higher Apache/PHP journal entries appeared after deployment.

## Main tracked files

- `app/views/pages/updates.php`
- `app/views/partials/ecosystem.php`
- `config/marketplace.php`
- `lang/{de,en,lo,pl,th,vi,zh}.json`
- `resources/pages.css`
- `public/assets/css/site.min.css`
- `public/sitemap.xml`

The private CMS source and signed marketplace artifacts remain in ignored distribution paths and are deployed through the controlled release archive rather than Git.
