# Eduvixo system Marketplace lifecycle

Date: 2026-08-30
Status: deployed and verified
Core release: 1.0.4 beta

## Outcome

The authenticated `EXPERIENCE -> Marketplace` workspace now uses one canonical component catalog instead of rendering the official web catalog, installed extensions and governance data as competing sections. Matching official and installed identities are merged, so components do not repeat.

Discovery is dynamic and does not reload the console. It includes text search, category (`All`, `Theme`, `Add-on`, `Plugin`), pricing (`All`, `Free`, `Paid`), status and pagination. Category and pricing chips remain synchronized with their select controls and the current filter state is written to the URL.

Lifecycle actions follow the requested contract:

- available or updated official component: green `Install` / `Update`;
- installed removable package: red `Uninstall` on the left;
- installed configurable package: warm-yellow `Configure` on the right;
- new plugins and add-ons become active after verified installation so their configuration route is usable; themes remain inactive until deliberately selected;
- active themes and bundled components cannot be uninstalled;
- official installation requires a license key of at most 128 characters in a modal and forwards it only over HTTPS to the distribution service;
- the entered key is not stored or logged;
- publisher signature, signed catalog identity, SHA-256, compatibility, dependency and package inventory checks run before installation;
- uninstall creates a private recovery archive, checks reverse dependencies, reverses package migrations and restores files/migrations if the transactional state removal fails.

The interface uses consistent blue icon covers, balanced three-column cards, explicit Free/Paid badges, justified descriptions, a details drawer, responsive breakpoints and accessible dialog/form labels. Legacy Marketplace moderation/governance assets are no longer loaded in this workspace.

## Catalog contract

The signed official catalog now carries `slug`, `installable`, `pricing`, protected package URL/checksum and bounded product-specific license identity. Ten of thirteen public products are installable inside Eduvixo:

- Eduvixo and Shoudu themes;
- Eduvixo Calendar;
- Google, Apple and Microsoft 365 Calendar integrations;
- Telegram and WhatsApp notification integrations;
- Google Analytics;
- AI Translation Assistant.

CMS and Windows artifacts are distribution products, not extensions installable into an existing CMS. iFirewall remains a public distribution product but is not advertised as an in-system installation because its current generic PHP artifact does not contain the Eduvixo extension manifest/runtime lifecycle required by `PackageManager`.

Four legacy catalog identifiers were 30-31 characters long. They were normalized to the existing 32-character package contract, the catalog was re-signed and both installations refreshed it. No filename or storage path is disclosed by the UI.

## Files changed

Core source:

- `.cms/source/app/Core/MarketplaceCatalog.php`
- `.cms/source/app/Core/PackageManager.php`
- `.cms/source/app/Http/DashboardController.php`
- `.cms/source/app/Views/console-marketplace.php`
- `.cms/source/app/Views/console.php`
- `.cms/source/public/index.php`
- `.cms/source/public/theme/eduvixo-marketplace.js`
- `.cms/source/public/theme/eduvixo-marketplace.css`
- `.cms/source/app/release.json`

Distribution/build:

- `config/marketplace.php`
- `scripts/build-official-catalog.php`
- `scripts/build-core-release.php`
- private generated `storage/marketplace/official-catalog.json`
- private signed `storage/marketplace/packages/eduvixo-core-1.0.4.zip`

Operational verification and deployment helpers are retained in `.wrk/`.

## Deployment

Targets:

- product/distribution site: `/var/www/clients/client9/web123/web` (`web123:client9`);
- demo CMS: `/var/www/clients/client9/web121/web` (`web121:client9`);
- Shoudu CMS: `/var/www/clients/client59/web119/web` (`web119:client59`).

Both CMS installations were updated through the signed `SystemUpdate` lifecycle and now report core 1.0.4 with a completed job and no maintenance marker.

Recovery identifiers:

- demo: `recovery-e4a1dc402be9cf56669f66f5`;
- Shoudu: `recovery-97b0f2e898ea3c97da9bd8fa`.

Full pre-deployment backup:

- `/root/eduvixo-backups/marketplace-system-pre-20260830-134331`
- contains complete website/CMS tar archives, both database dumps, hashes and rollback instructions.

Catalog identifier repair backup:

- `/root/eduvixo-backups/marketplace-catalog-id-pre-20260830-135620`
- contains the previous website Marketplace configuration and signed catalog plus rollback instructions.

The first demo update attempt received a transient `Domain not authorized` response before core files changed. File ownership, domain identity, encrypted license state and grace-period checks were validated as the site user; direct enforcement then passed on both installations. The signed update was resumed from the backup checkpoint and completed normally. No license control was bypassed.

## Database and infrastructure impact

No schema migration or package installation/uninstallation was executed during this deployment. The normal core updater added its standard `system.updated` audit record and created private database/file recovery snapshots. No customer content, theme selection, extension configuration or account permissions were changed.

Apache, PHP 8.4 FPM and cron remained active. No Apache/PHP error journal entries occurred during the deployment window. Both per-site update workers report 1.0.4, completed state and no catalog error.

## Verification

- PHP lint passed for every changed PHP file and both deployed entrypoints/controllers.
- JavaScript syntax validation passed.
- signed official catalog verification passed: 13 products, 10 system-installable products, unique identities and 32-character IDs;
- signed core verification passed: 163-file inventory and Ed25519 signature;
- canonical merge, update state and pricing filter unit fixture passed;
- existing Marketplace package/signature/language audit passed: 13 products, 14 artifacts, seven languages;
- all seven public production Marketplace languages still return 13 dynamically filterable products with complete price classification;
- 26 live production assertions passed after deployment;
- all ten package endpoints return 401 without authorization;
- direct core archive and private source/catalog paths return 403;
- both sites expose the protected login and redirect unauthenticated Marketplace requests;
- isolated browser review used the real signed catalog and actual Google Analytics manifest: 10 balanced cards, nine Install actions, one Uninstall action, one Configure action, working dynamic category/price filters, details drawer, 128-character license modal and no browser console errors.

## Rollback

Prefer the per-installation recovery directory for a core-only rollback because it preserves later customer writes. Verify the recovery inventory and hashes, enable maintenance, restore recorded core files, invalidate OPcache, validate PHP and remove maintenance only after health checks pass.

Use the full tar/SQL backup only for disaster recovery and restore a database dump into an isolated recovery database first; a direct SQL restore would discard post-backup writes. Restore the website backup to revert distribution/catalog changes, then refresh the signed catalog on both installations.

## Outstanding operational check

No real extension license was submitted and no production component was installed merely for testing. The authorization, rejection, staging and lifecycle paths were verified structurally and through protected endpoints; the first customer-issued product license should receive a normal smoke test when it is used for an intended installation.
