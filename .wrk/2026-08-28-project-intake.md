# Eduvixo CMS project intake

Date: 2026-08-28  
Environment reviewed: local repository and production host for `eduvixo.com`

## Objective and agreed delivery order

1. Receive a clean, installation-ready Eduvixo CMS distribution derived from the system developed in `F:\Git\quant-software-house\shoudu-web`.
2. Expose it at `/demo` through `public/demo`, while keeping application code and writable runtime data outside the public tree, without the Shoudu runtime theme, Shoudu school content, customer data, deployment state, local secrets, generated files, logs, backups or caches.
3. Configure the public boundary so that the preferred server document root is `web/public`, while a root `.htaccess` remains a secure fallback when a hosting platform fixes the document root at `web`.
4. Install, migrate and validate the demo system on the Eduvixo host.
5. Design and implement the multilingual Eduvixo product and services website after its business and functional scope is supplied.

## Current repository state

- The repository is an initial asset/deployment skeleton rather than an application.
- `public/demo` currently contains only favicon assets.
- `public/marketplace` contains the Eduvixo 1.0.0 and Shoudu 1.1.0 signed theme artifacts and checksums.
- `.src/images` contains the supplied Eduvixo brand assets.
- `.info` identifies the product as **Eduvixo — Education Digital Experience & Communication Platform**.
- No application runtime, installer, migrations, tests or web entry point are present yet.

## Source-system findings

- The source system is a PHP 8 / PDO MySQL application with a front controller in `public/index.php`.
- It contains 18 additive migrations, a CLI installer, licensing, multilingual content, themes, plugins, Page Builder, Marketplace, forms, surveys, media, access workflows and live chat/AI functionality.
- Fresh installations currently seed the independent `eduvixo` theme; Shoudu 1.1.0 is already separated as a signed Marketplace package.
- Platform views are under `app/Views`; public presentation belongs to independent theme packages.
- The installer and configuration still contain legacy Shoudu-compatible license naming and product identity that must be reviewed as part of the clean-distribution task.

## Hosting findings

- DNS for `eduvixo.com` resolves to the configured production server.
- Apache currently serves `eduvixo.com` from `/var/www/clients/client9/web120/web`, not from `web/public`.
- `AllowOverride All` is enabled for the current website root.
- The deployed `web/public` directory exists, but there is no application entry point or root routing `.htaccess`; the root URL consequently returns HTTP 403.
- The server provides PHP 8.4 with PDO MySQL, sodium, cURL, fileinfo, GD and mbstring.
- Ownership for the website account is `web120:client9`.

## Public-boundary design

Preferred deployment:

- Apache/Nginx document root: `/home/eduvixo.com/web/public` (canonical target; the underlying ISPConfig path is equivalent).
- `public/.htaccess`: serve existing files/directories directly and route all other requests to `index.php` with the query string preserved.
- Nginx equivalent: `try_files $uri $uri/ /index.php?$query_string`.

Fallback deployment for a fixed `web` document root:

- Root `.htaccess` denies `.env`, `.cfg`, `.git`, `.src`, `.wrk`, application sources, configuration, migrations, scripts, storage and package sources.
- It internally routes public requests to `public/` and rejects direct `/public/...` URLs so only one canonical public URL exists.
- `/.well-known/acme-challenge/` remains reachable for certificate renewal.

The application must work in both modes without generating `/public` in links, redirects, assets, canonical URLs or API endpoints.

For the demo sub-application, `public/demo` must contain only its public entry point and browser assets. PHP engine code, configuration, migrations, package sources, logs, uploads not intended for direct delivery and other writable state must remain outside `public`; the deployment may use a controlled public-directory mapping or a split application/public layout.

## Clean installer acceptance criteria

- No Shoudu theme runtime or Shoudu school content is bundled or activated.
- No `.env`, `.cfg`, license material, signing private keys, administrator passwords, customer uploads, database dumps, logs, caches, backups or production identifiers are included.
- Installation is repeatable and fails safely before partial data creation when requirements, database access or licensing are invalid.
- Migrations are ordered and idempotent; the installer records applied versions.
- A fresh administrator credential is supplied securely and is never printed into public output or committed.
- Writable runtime paths and ownership are documented and verified after installation.
- The demo can be hosted below `/demo` without hard-coded root paths.
- PHP syntax, JSON manifests, migrations, installer flow, protected-path checks, HTTP routes and clean-install smoke tests pass before production deployment.

## Security finding requiring remediation

All credential-bearing `.cfg` files are currently tracked in Git and have been pushed to the configured GitHub remote. No secret values are reproduced in this document. Before public launch:

1. Rotate SSH, SFTP, FTP, MySQL, PostgreSQL and mail credentials.
2. Remove secret files from tracking and add a deny-by-default `.gitignore` rule for `.cfg/*`, retaining only sanitized examples if needed.
3. Purge the committed secret blobs from repository history and coordinate the required force update.
4. Verify that no old credentials remain valid and no application artifact contains them.

History rewriting and credential rotation were not performed during this read-only intake because they are materially destructive security operations outside the requested project assessment.

## Outstanding business scope

The product/services website information architecture, languages, content, conversions, forms, CRM/analytics integrations, pricing and legal pages remain intentionally open until the separate website brief is supplied.
