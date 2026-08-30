# Hosting credit and product ecosystem

Date: 2026-08-30

## Outcome

- The public product website no longer displays the hosting provider credit.
- `SYSTEM -> License` now contains a default-enabled `Show hosting credit on the public website` control.
- The control affects the Eduvixo public theme footer and renders `Hosting provided by Chivale.` with the existing Chivale link when enabled.
- The product website now presents a clear commercial model on Home, Product and Services in all seven supported languages.
- Eduvixo CMS 1.0.5 and Eduvixo Theme 1.1.7 were built, signed, published and installed on both managed CMS instances.

## Product presentation

The website separates the offer into three explicit tiers:

1. Base platform: Eduvixo CMS, USD 360 per year, with the built-in platform capabilities.
2. Free official extensions: Eduvixo Theme, Shoudu Theme, Eduvixo for Windows, Google Analytics and AI Translation Assistant.
3. Paid official extensions: Eduvixo Calendar at USD 120 per year, calendar connectors at USD 12 per year each, Telegram and WhatsApp Notifications at USD 48 per year each, and iFirewall at USD 120 per year.

The Home page uses a compact three-card preview. Product contains the complete scope. Services explains the boundary between the platform license, official extensions and professional services.

## Releases

- Core package: `eduvixo-core-1.0.5.zip`
  - Size: 737884 bytes
  - SHA-256: `38703ff69d705cd79ac4f4d56ed8561c2a242daf87d77132fa2cf02f0391170d`
- Theme package: `eduvixo-theme-1.1.7.zip`
  - Size: 1565719 bytes
  - SHA-256: `8d24652b64c70e21386a675b9670d5c7a6a9d21b3a039d49c2377437ddd54bb6`
- Final local deployment archive: `tmp/release/hosting-credit-ecosystem-1.0.5-r1.tar.gz`
  - Size: 2409910 bytes
  - SHA-256: `2AF6B39BA3D5D815EAA2AC6AFC1E39D144A603026829C72709CA3C589D3CBBA1`

The signed official catalog contains 13 products, including 10 system-installable extensions. Direct core and theme package requests without installation authorization return HTTP 401.

## Deployment

Targets:

- Product website: `/var/www/clients/client9/web123/web`, owner `web123:client9`
- Demo CMS: `/var/www/clients/client9/web121/web`, owner `web121:client9`
- Shoudu CMS: `/var/www/clients/client59/web119/web`, owner `web119:client59`

Verified final state:

- Demo: core 1.0.5, Eduvixo Theme package 1.1.7, active theme `eduvixo`.
- Shoudu: core 1.0.5, Eduvixo Theme package 1.1.7, active theme `shoudu`.
- Both installations enforce valid licenses, trust the official `chivale-eduvixo-2026` publisher key, report a verified theme signature and are outside maintenance mode.
- The hosting credit setting resolves to `true` on both installations by default.
- Apache and PHP 8.3 FPM are active.

Core recovery identifiers created by the updater:

- Demo: `recovery-3d6940d501d1b75de825a7a8`
- Shoudu: `recovery-dfbed84d99e486bf9ad52bb0`

## Backup and rollback

Full pre-deployment backup:

`/root/eduvixo-backups/hosting-credit-ecosystem-pre-20260830-143946`

Contents:

- `website.tar.gz`: `ad373315706f9238159879ceb99dccc073789f3a836a4d36551c59b18b1a1bcb`
- `demo.tar.gz`: `aec9d1b1d129f4742ec1e2146cee606012821b8053d6d3e2924e55a18be42798`
- `demo.sql`: `bc8678ab6735c601a258080ace208d361ce4dc30e34a6546f2575bb53da2c27a`
- `shoudu.tar.gz`: `24c90c602c7b0057484862009d09483a6efd1a1fa745a2a4694cc1d30537ef65`
- `shoudu.sql`: `987cda5040a677589d0132c722cc2bd23b08a5ede41f47a4cb1085c85dbe1ebe`
- `ROLLBACK.txt`: `88be96907a16f206da253b8da4b32540581ec194d836af877a99a91204e97723`

Prefer the updater recovery package for a core rollback and the extension release archive for a theme rollback. The full tar and SQL files are disaster-recovery fallbacks. Preserve writes made after the backup before any full restore.

## Database impact

- No schema migration was required for the hosting control.
- A `settings.show_hosting_credit` row is written only when an authorized administrator saves the control. Missing values intentionally resolve to `true`.
- Deployment updated package release and audit metadata and ensured the official publisher trust record exists.
- No customer content or user data was deleted or mass-updated.

## Deployment findings

1. The first core update attempt received a transient domain-authorization response. Direct license enforcement passed on both installations and no authorization control was bypassed.
2. Demo package backup directories had stale root ownership. Ownership was corrected only within the resolved `storage/packages` boundary to restore the signed package rollback lifecycle.
3. Shoudu did not yet trust the official publisher. The existing Ed25519 public key was registered through `PackageManager::trustPublisher`, after which signature verification passed.
4. The Shoudu `themes` parent directory prevented the atomic rename. Ownership was corrected only on that resolved directory.
5. The new product website `app/views/partials` directory was initially created with root ownership, which caused incomplete HTTP 200 documents. The directory was corrected to `web123:client9` mode `0750`, and the deployment helper now applies owner, group and mode whenever it creates a new target directory.
6. Four orphaned staging/work objects from failed theme attempts were removed after exact path validation. Release packages, backups and rollback archives were not touched.

## Validation

- PHP syntax validation passed for all changed PHP sources, both deployed CMS entry points and both deployed theme views.
- JSON decoding and ecosystem schema checks passed for `de`, `en`, `lo`, `pl`, `th`, `vi` and `zh`.
- CSS assets were rebuilt from source.
- Local route validation passed for 84 localized website routes.
- Production validation passed for the same 84 routes, including a complete closing HTML document and correct language marker.
- Desktop and mobile visual QA passed at 1440 px and 390 px widths without horizontal overflow.
- The public Eduvixo website omits the hosting credit, while the Demo Eduvixo Theme renders it when the default-enabled setting is active.
- Official catalog signature, core and theme versions, package protection, active themes, license enforcement, publisher trust, maintenance state and service health all passed.
- No new Apache or PHP service warnings appeared after the ownership correction and final audit.

## Main tracked files

- `app/views/partials/ecosystem.php`
- `app/views/pages/home.php`
- `app/views/pages/product.php`
- `app/views/pages/services.php`
- `app/views/layout.php`
- `resources/pages.css`
- `public/assets/css/site.min.css`
- `lang/*.json`
- `config/marketplace.php`
- `scripts/build-core-release.php`

The CMS source remains in the ignored private `.cms/source` tree and is distributed only through the signed core and theme packages.
