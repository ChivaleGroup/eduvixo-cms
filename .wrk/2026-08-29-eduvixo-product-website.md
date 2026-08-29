# Eduvixo product website — implementation and deployment

Date: 2026-08-29  
Environment: production (`www.eduvixo.com`) and existing demo (`demo.eduvixo.com`)

## Outcome

Implemented and deployed a dependency-free, multilingual PHP product website for Eduvixo. The visual direction is based on `.src/images/eduvixo-cms.png` and the product positioning from `.doc/Eduvixo-Opis.pdf` and `.doc/Eduvixo-Hero.pdf`.

Public routes are physical directories under `public/`:

- `/`, `/product/`, `/services/`, `/marketplace/`, `/updates/`, `/contact/`
- `/support/`, `/support/docs/`, `/support/faq/`, `/support/knowledge-base/`
- `/privacy/`, `/terms/`, `/demo/`

The `/demo/` route redirects to the external demo in a new browser context. The site navigation also contains a direct demo login link.

## Architecture

- PHP 8.4 server rendering with a small application layer in `app/`
- JSON translations in `lang/{code}.json`
- source CSS and JavaScript in `resources/`
- minified production assets in `public/assets/`
- no runtime frontend dependencies, external fonts or third-party JavaScript
- Apache front-controller rules in `public/.htaccess`
- root `.htaccess` provides a safe fallback when a host temporarily points at the repository root

Production CSS: 28,045 bytes.  
Production JavaScript: 896 bytes.

## Localization

Languages, displayed alphabetically by English language name:

1. Chinese (`zh`)
2. English (`en`)
3. German (`de`)
4. Lao (`lo`)
5. Polish (`pl`)
6. Thai (`th`)
7. Vietnamese (`vi`)

Selection priority:

1. explicit language path (`/{code}/...`), legacy query or saved visitor selection
2. browser `Accept-Language`
3. operating-system locale discovered in the browser and saved as a cookie
4. country/host signal (`CF-IPCountry`, country headers, GeoIP extension or client host TLD)
5. English fallback

Language-prefixed routes validate accepted two-letter codes; the legacy language endpoint still validates its return URL. All seven JSON files share the same schema. Localized copies should receive a native-speaker editorial review before a major paid campaign.

## Nearest-campus product capability

The home page includes a dedicated nearest-campus section explaining the privacy-aware flow: visitor permission, comparison with active campus coordinates, and recommendation of the closest facility. The website markets the implemented CMS capability; live matching remains driven by campus data in the Eduvixo system.

## Contact form

The production contact form includes:

- CSRF protection
- honeypot and minimum completion time
- server-side validation and length limits
- per-IP HMAC rate limiting
- header-safe reply address handling
- SMTP support when configured, with native server mail transport fallback
- no database persistence of contact messages

A production validation submission returned `303 See Other`, displayed the success state and was accepted by the native mail transport. The mail queue subsequently drained. Inbox delivery was not inspected.

## Demo login prefill

The existing demo CMS was updated so demo prefill is enabled only by environment configuration:

- `CMS_DEMO_MODE`
- `CMS_DEMO_EMAIL`
- `CMS_DEMO_PASSWORD`

No demo password was added to tracked source. Production checks confirmed `/login` returns HTTP 200 and both fields are populated. Authentication will work after the matching demo account exists in the CMS, as planned by the owner.

## Deployment

Website files were deployed to:

`/var/www/clients/client9/web120/web`

Apache `DocumentRoot` is configured as:

`/var/www/clients/client9/web120/web/public`

Both `DocumentRoot` occurrences in `/etc/apache2/sites-available/eduvixo.com.vhost` were updated. Apache configuration passed syntax validation and the service reloaded successfully. The site remains functional through the repository-root `.htaccess` fallback if ISPConfig regenerates the virtual host, but the `DocumentRoot` modification should be checked after future ISPConfig domain changes.

Final deployment archive:

- local: `tmp/release/eduvixo-website-20260829-final.tar.gz`
- server: `/root/eduvixo-website-20260829-final.tar.gz`
- SHA-256: `0BEACAFC87385D15946071B1A94D2D6C8978D9B9FFF78C035337DDF292B131FD`

## Backups and rollback

Backups created before production changes:

- website: `/root/eduvixo-backups/web-pre-20260829-132127.tar.gz`
- Apache vhost: `/root/eduvixo-backups/eduvixo.com.vhost-pre-20260829-132127`
- demo login files and `.env`: `/root/eduvixo-backups/demo-login-prefill-pre-20260829-143014.tar.gz`

Website rollback:

1. Restore the web archive into `/var/www/clients/client9/web120/web`.
2. Restore the saved vhost file to `/etc/apache2/sites-available/eduvixo.com.vhost`.
3. Run `apache2ctl configtest`.
4. Reload Apache only if the syntax test succeeds.

Demo rollback:

1. Extract the demo backup into `/var/www/clients/client9/web121/web`.
2. Restore ownership to `web121:client9`.
3. Run PHP syntax checks and `php-fpm8.4 -t`.
4. Reload PHP-FPM only if validation succeeds.

## Validation performed

- local PHP syntax checks passed for 39 website and modified CMS files
- all seven translation JSON files decoded and matched the English schema
- 84 production route/language combinations returned HTTP 200 with the correct `<html lang>`
- main routes tested externally over HTTPS
- bare domain redirects permanently to `www`
- `/demo/` redirects to `https://demo.eduvixo.com/`
- demo root and login both resolve to HTTP 200 after redirects
- CSS asset returns HTTP 200 with immutable cache headers
- security headers include CSP, frame, MIME, referrer and permissions policies
- `.env` returns 403; technical-looking `/app`, `/lang` and `/.cfg` paths return 404
- Apache and PHP-FPM are active
- no new website or demo errors appeared in application error logs after deployment
- `git diff --check` passed

## Database and infrastructure impact

- no database schema or data changes
- Apache vhost `DocumentRoot` changed to the `public` directory
- PHP-FPM reloaded after demo login configuration validation
- no DNS, firewall, SSL or container changes

## Security follow-up

Credential files under `.cfg/` are currently already tracked by Git. Ignoring `.cfg/*` prevents newly untracked files from being added, but does not remove existing secrets from Git history. Rotate exposed credentials and purge them from repository history in a separately planned security change; do not perform this as an incidental website commit.
