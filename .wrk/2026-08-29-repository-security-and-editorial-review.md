# Repository security and editorial review

Date: 2026-08-29  
Environment: public GitHub repository, local working copy and production website

## Scope

- remove credentials and private release archives from the public Git history;
- prevent credentials, CMS distribution sources, private packages and deployment artifacts from being committed again;
- preserve a verifiable rollback path before rewriting history;
- edit the English website copy first, then perform a full Polish editorial review;
- deploy and validate the revised English and Polish content;
- publish a clean, reviewed Git state.

## Security finding

The public repository tracked production and demo configuration files under `.cfg/`. A value-based history scan confirmed committed SMTP, FTP, MySQL, PostgreSQL, SFTP, SSH and product-license values. Historical CMS and theme ZIP archives were also reachable from Git history. Generic secret scanners did not detect these project-specific formats, so validation uses both Gitleaks and exact-value matching without printing the values.

The `.cms/` directory contains the licensed CMS source and installation archive. It is intentionally local-only: committing it to a public repository would bypass the Marketplace access controls. Working documents under `.doc/`, runtime storage and release output are also local-only.

## Remediation design

1. Add deny-by-default ignore rules for `.cfg/`, `.cms/`, `.doc/`, runtime storage, releases, archives, database dumps, license files and private-key formats.
2. Retain only a sanitized `.cfg/README.md` in version control.
3. Remove all `.cfg/` content and historical public ZIP artifacts from every Git revision with `git-filter-repo`.
4. Create a complete local Git bundle before rewriting history. It is a temporary security rollback artifact and must never be published because it contains the compromised history.
5. Force-update `main` only against the previously verified remote head, then verify the remote object graph and repository settings.
6. Rotate still-active exposed credentials separately from history removal, updating dependent production configuration and testing each service before continuing.

## Editorial changes

English was reviewed as the reference language. Awkward product terminology and several overly technical expressions were replaced with concise, natural institutional language.

Polish received a full editorial rewrite. Machine-translated wording, incorrect grammatical gender, literal technical calques and unclear calls to action were corrected across navigation, home, product, services, Marketplace, support, documentation, FAQ, knowledge base, updates, contact, privacy and terms.

## Production deployment

Only `lang/en.json`, `lang/pl.json` and the regenerated `public/sitemap.xml` were deployed.

- backup: `/root/eduvixo-backups/editorial-en-pl-pre-20260829-1702.tar.gz`
- release: `/root/eduvixo-editorial-en-pl-20260829-1702.tar.gz`
- SHA-256: `53AECEAD8C742A519FD14B35239E01BD22E25859525A042C1D344D98291FA126`

Rollback: extract the backup archive into `/var/www/clients/client9/web120/web`, restore ownership to `web120:client9`, validate both JSON files and run `apache2ctl configtest` before any service reload.

## Validation completed before Git publication

- 43 PHP files passed syntax validation;
- JavaScript syntax and `git diff --check` passed;
- all seven translation files decode and share the English schema;
- sitemap generation produced 84 localized URLs;
- all 84 local page/language combinations returned HTTP 200 with the expected language, canonical URL, eight alternate links and parseable JSON-LD;
- all 24 English and Polish production pages returned HTTP 200 with the revised copy and SEO alternates;
- the production sitemap contains 84 URLs;
- Apache and PHP-FPM are active and the recent website error log contains no matching PHP or Apache application errors.

## Git history rewrite and credential rotation

The pre-rewrite repository, including every local ref, was saved as:

- bundle: `F:\Git\ChivaleGroup\eduvixo-cms-security-rollback-20260829-1706.bundle`
- SHA-256: `90D3D0771EBD4D7851C13AC3B725480A38CE863CDEC383A2991D1F3C815CADA9`
- previous remote `main`: `dd5379e913257b6a86caf077495a837c07130cab`
- pre-rewrite website commit: `b167dd8ba5e907e4f3665a751cd9b0184378175c`

The bundle passed `git bundle verify`. It contains compromised historical material and is for local emergency rollback only; it must not be uploaded or shared.

`git-filter-repo` removed `.cfg/`, the historical CMS installation archive and all historical Marketplace ZIP/checksum artifacts from the complete `main` history. The first sanitized website commit is `74959e1b0b0f45e801559c7eec493c5b17ece393`.

Post-rewrite checks on `main`:

- nine reachable commits;
- zero `.cfg/` or historical public ZIP paths;
- zero matches for all known exposed credential and license values;
- zero Gitleaks findings;
- no ignored local credentials, `.cms/`, `.doc/`, private package storage or release artifacts in the Git index.

## Remote publication

The rewritten `main` branch was force-updated with an exact lease against the previously verified remote commit `dd5379e913257b6a86caf077495a837c07130cab`. The first published security-documentation commit was `f48cdc5c571fb67eeaa92e7e2182d71e57d7e793`. The local and remote heads matched after publication.

GitHub verification confirmed:

- only `refs/heads/main` exists;
- zero forks and zero pull requests;
- `.cfg/README.md` is present;
- credential files and historical public ZIP paths return HTTP 404 through the repository Contents API;
- the old commit is unreachable from every repository ref, although GitHub may continue to serve unreachable objects by exact SHA until server-side garbage collection.

GitHub documents Support-assisted cache removal for sensitive-data rewrites. Because all exposed credentials were revoked or rotated and the repository has no forks or pull requests, the cached object no longer grants active access. A Support request can still cite repository `ChivaleGroup/eduvixo-cms`, zero affected pull requests, and first changed commit mapping `d2f00a65fcf380069616f09a4267664248212b4d` → `85a05b4226df9f7d78a8f23a86c9a964dd590f81` if immediate cache collection is required.

## Credential rotation

All active secrets found in the former public history were rotated without changing public service identities:

- root SSH password;
- two independent SFTP account passwords;
- two independent FTP account passwords;
- MySQL application password, updated atomically in the demo `.env`;
- PostgreSQL role password;
- SMTP/IMAP mailbox password on the dedicated mail server;
- Eduvixo product license key in the Chivale license service and encrypted demo installation.

The local ignored `.cfg/` files were updated with the new values. No secret value is recorded in Git, documentation, command output or logs.

Rollback material:

- primary server account/database backup: `/root/eduvixo-backups/credential-rotation-20260829-1715/`;
- mailbox database row: `/root/eduvixo-credential-backup-20260829-1725/mail-user.sql` on the mail server;
- license-service database row: `/root/eduvixo-license-backup-20260829-1740.sql` on the Chivale server;
- demo encrypted license state: `/root/eduvixo-backups/license-rotation-pre-20260829-1740.tar.gz`.

Each credential was tested through its real protocol after rotation. An additional value-based audit against the pre-rewrite bundle confirmed that the eight former infrastructure/mail passwords no longer match the active server hashes or authenticate to their database services. The old product license returns an invalid-license response, while the new key validates and the demo returns HTTP 200.

## Recurrence prevention

- `.gitignore` denies local configuration, CMS sources, working documents, private packages, releases, dumps, licenses and common private-key formats;
- only `.cfg/README.md` can be tracked from the configuration directory;
- `.github/workflows/security.yml` runs Gitleaks on every `main` push and pull request;
- the workflow downloads a fixed Gitleaks version, verifies its published SHA-256 and pins `actions/checkout` to an exact commit;
- the final local scan and exact-value scan must remain clean before publication.
