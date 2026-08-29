# Eduvixo for Windows - Marketplace release

Date: 2026-08-29
Version: 0.2.1
Environment: production (`www.eduvixo.com`)
Status: deployed and verified

## Outcome

The portable Windows application is available as one new `Eduvixo for Windows` Marketplace tile. The tile provides separate x64 and x86 downloads without license-key verification. x64 is identified as the recommended build.

The application files remain outside the public document root. Browser downloads use the existing CSRF-protected request flow and an opaque, IP/user-agent-bound, single-use token. The private storage path and executable filenames are not present in public Marketplace markup.

## Release files

- x64: `eduvixo-windows-0.2.1-x64.exe`, 157,796,032 bytes, SHA-256 `d3a0664af8294c82d690c49d60409f10dbd06b6f4dcbc6dc104bcb85cab448bf`
- x86: `eduvixo-windows-0.2.1-x86.exe`, 148,780,736 bytes, SHA-256 `65009bf2395659e1c2addbdbe5fd569c452888d7aeb80a24491dbd60f7c45a4b`

Production storage:

`/var/www/clients/client9/web120/web/storage/marketplace/packages`

Both files are owned by `web120:client9` with mode `0640`. No executable was placed in `public`.

## Implementation

- Added Marketplace products with multiple architecture variants while retaining one public tile.
- Extended one-use token payloads with a validated variant identifier.
- Preserved existing direct, licensed and updater package behavior.
- Added executable MIME type and safe configured download names.
- Disabled PHP execution-time limits and cleared output buffers immediately before streaming large verified files.
- Added explicit public-route blocking for EXE, MSI, APPX and MSIX files.
- Added localized Windows product copy, architecture labels, compatibility, portable state, recommendation and unsigned-release notice in all seven languages.
- Expanded the desktop Marketplace grid from three to four columns and added a responsive two-button variant layout.

The release is portable and unsigned. Windows may display a SmartScreen/security warning on first launch; the Marketplace states this clearly in every language.

## Modified files

- `.htaccess`
- `app/MarketplaceService.php`
- `app/Site.php`
- `app/views/pages/marketplace.php`
- `config/marketplace.php`
- `lang/{de,en,lo,pl,th,vi,zh}.json`
- `public/.htaccess`
- `public/assets/css/site.min.css`
- `resources/pages.css`

No database or CMS/demo backend change was required.

## Deployment and rollback

Pre-deployment production backup:

`/root/eduvixo-backups/windows-marketplace-pre-20260829-2241.tar.gz`

Backup SHA-256:

`5b2a69511a1ba3c9380e136dc4ae22e842bc6a375ad6f93a3a6c1cc83a6365e0`

Deployment archive:

`/root/eduvixo-windows-marketplace-20260829-2241.tar.gz`

Deployment archive SHA-256:

`9ae1273df003f392cc8e66143da0668676ff8d67d28dac76cb10d25c4a9ada13`

Rollback: extract the pre-deployment backup into `/var/www/clients/client9/web120/web`, restore `web120:client9` ownership, remove only the two Windows 0.2.1 executables from private Marketplace storage, lint PHP, validate Apache, reload PHP 8.4 FPM and repeat the route checks.

## Validation

- Local PHP syntax checks passed for all changed PHP files.
- All seven translation JSON files decode and contain the new keys.
- Production PHP syntax, Apache configuration and translation checks passed.
- Apache and PHP 8.4 FPM remained active after reload.
- All seven localized Marketplace pages returned HTTP 200 and displayed the Windows tile with both variants.
- x64 and x86 were downloaded locally and again through the production HTTPS flow; byte counts and SHA-256 checksums matched.
- Both production tokens were rejected with HTTP 404 after their first use.
- Direct public executable and private-storage URL probes returned HTTP 404.
- Visual production QA found four equally sized desktop cards, no horizontal overflow and no browser console warnings or errors.
- Existing CMS and theme package behavior remains unchanged by the variant implementation.

## Remaining risk and recommendation

Code-sign future Windows releases with an Extended Validation or suitable organization code-signing certificate. This will reduce SmartScreen friction and provide publisher identity; it is the principal remaining release-quality improvement.
