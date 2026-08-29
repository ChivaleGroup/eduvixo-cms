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

Final commit identifiers, bundle location, push verification, secret-scan results and credential-rotation outcomes are recorded below after execution.
