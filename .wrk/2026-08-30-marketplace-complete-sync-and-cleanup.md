# Marketplace complete sync and cleanup

Date: 2026-08-30
Environment: production (`www.eduvixo.com`)
Status: deployed and verified

## Outcome

The complete active Eduvixo Marketplace catalogue is synchronized with production. The active Apache site is `/var/www/clients/client9/web123/web`; this supersedes all historical `web120` paths. Ownership follows `.cfg/chown`: `web123:client9`.

Active releases:

- Eduvixo CMS `1.0.0`
- Eduvixo Theme `1.1.6`
- Shoudu Theme `1.1.1`
- iFirewall `1.0.0` Stable, USD 120/year
- Eduvixo for Windows Portable `0.2.3`, x64 and x86

The CMS archive was refreshed from `.cms/eduvixo-install-1.0.0.zip`. The refreshed archive contains 365 entries, is 10,334,744 bytes, and has SHA-256 `48ae0869807ae5556edf4b45b55c5683560e6025ed01b3bec936c451c80b332d`.

Windows release hashes:

- x64: `0633a2025d12c6fab266bb233bb258f6b7ba10dc3220c1644edd5dbf6738656d`
- x86: `bef08fe7b71f3473e366fd33fba3cc6193781f17ae2ed71fcf9f2c47328986ef`

## Production cleanup

Removed the obsolete physical route directories after backup:

- `demo`
- `marketplace`
- `support`
- `updates`

Requests now reach their canonical implementations under `public/` through the root rewrite rules. `error`, `stats`, `scripts`, source directories, private runtime state, and all active application directories were retained.

Removed obsolete Marketplace artifacts:

- Eduvixo Theme `1.0.0`
- Shoudu Theme `1.1.0`
- Eduvixo for Windows `0.2.1` x64 and x86
- obsolete checksum sidecars

Private Marketplace storage now contains exactly the six files required by the five active products. No package was placed under `public/`.

## Permissions

The project ownership command from `.cfg/chown` was applied. Insecure legacy `0777` permissions were normalized:

- application, configuration, language, resource, script, and storage directories: `0750`
- private files and Marketplace packages: `0640`
- public directories and files: `0755` and `0644`
- web root: owner `web123:client9`, mode `0755`

## Local cleanup

Removed regenerable and obsolete local data:

- the complete ignored `tmp/` workspace
- Windows build outputs `bin/` and `obj/`
- Windows releases `0.1.0`, `0.2.0`, `0.2.1`, `0.2.2`, and `manual-x86`
- obsolete local Marketplace packages and checksum sidecars

The current Windows `0.2.3` release and all six active Marketplace package files were retained. Removed build artifacts can be regenerated from source; the production pre-change state is recoverable from the server backup below.

## Backup and rollback

Pre-deployment backup:

`/root/eduvixo-backups/marketplace-complete-pre-20260830-142607.tar.gz`

Size: 180,312,104 bytes

SHA-256:

`a7245d4c1a784906a3b8be8ecda6b88a047c0dab5b56b5c48445b8406fff911a`

Rollback procedure:

1. Extract the archive with its stored paths into `/var/www/clients/client9/web123/web`.
2. Remove only the newly deployed Windows `0.2.3` executables if the previous catalogue is required exactly.
3. Restore ownership with `chown -R web123:client9 /home/eduvixo.com/web/*` and normalize the web root owner separately.
4. Validate package checksums, PHP syntax, JSON translations, and `apache2ctl configtest` before returning traffic to the restored state.

## Validation

- All 43 local PHP files pass syntax validation.
- All seven language JSON files decode successfully.
- Local and production package byte counts and SHA-256 values match the Marketplace configuration.
- All seven localized Marketplace routes return HTTP 200 and list all five products with Windows version `0.2.3`.
- The opaque-token Windows download flow returns HTTP 303 followed by HTTP 200, the `0.2.3-x64` filename, and a valid `MZ` executable stream.
- Direct private-storage access returns HTTP 403 and direct filename guessing returns HTTP 404.
- Canonical `/marketplace/`, `/support/`, `/updates/`, and `/demo/` redirects remain operational after legacy-directory removal.
- Apache configuration syntax is valid; Apache and PHP 8.4 FPM are active.
- Active Apache and PHP-FPM configuration contains no `web120` reference.
- No database schema or data was changed.

The known ISPConfig `NameVirtualHost has no effect` warning remains unrelated and does not invalidate Apache syntax.
