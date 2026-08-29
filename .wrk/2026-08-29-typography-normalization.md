# Website typography normalization

Date: 2026-08-29

Environment: local working copy and production (`www.eduvixo.com`)

## Outcome

- Replaced every em dash in public-facing website sources with a standard hyphen across all seven languages, social metadata fallbacks, the web manifest and contact-message formatting.
- Added responsive justification for substantial prose blocks while retaining left alignment on screens up to 760 px to protect mobile readability.
- Enabled automatic language-aware hyphenation, balanced heading wrapping, improved paragraph wrapping and safe word breaking.
- Corrected the installable web app manifest start URL from the legacy `/?lang=en` format to the canonical `/en/` route.
- Rebuilt compressed production assets and the multilingual sitemap.

## Implementation files

- `app/ContactService.php`
- `app/views/layout.php`
- `lang/{zh,en,de,lo,pl,th,vi}.json`
- `resources/pages.css`
- `public/assets/css/site.min.css`
- `public/site.webmanifest`
- `public/sitemap.xml`

## Deployment

Production root: `/var/www/clients/client9/web120/web`

Release: `/root/eduvixo-typography-20260829-180337.tar.gz`

SHA-256: `A252DF4D9FB7E32E9E5DF5565064FED052F2754684093F8293F108B0234B94ED`

Pre-deployment backup: `/root/eduvixo-backups/typography-pre-20260829-180337.tar.gz`

Rollback: extract the backup into the production root, restore ownership to `web120:client9`, validate the changed PHP and JSON files, run `apache2ctl configtest`, and reload PHP-FPM only after validation passes.

## Validation

- 43 PHP files passed syntax validation.
- All seven translation files decode and share the English schema.
- No em dash remains in public-facing application, translation, resource, public, configuration or script sources.
- JavaScript syntax and `git diff --check` passed.
- Compressed CSS contains justification, automatic hyphenation and balanced heading wrapping.
- The regenerated sitemap contains 84 localized URLs.
- All 84 local page/language combinations returned HTTP 200 with the expected language, canonical URL, eight alternate links and parseable JSON-LD; none rendered an em dash.
- Representative production routes across all seven languages returned HTTP 200 without an em dash.
- Production CSS and manifest contain the new typography rules and `/en/` start URL.
- Apache and PHP-FPM are active, Apache configuration is valid, and the recent website error log contains no matching application errors.

## Impact and risks

- No database, DNS, SSL, firewall or web-server document-root changes.
- No service interruption was required; PHP-FPM was reloaded after validation.
- Full justification is deliberately limited to wider layouts because narrow justified columns reduce readability, especially in long translated words.
