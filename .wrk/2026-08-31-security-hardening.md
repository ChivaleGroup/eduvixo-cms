# Security hardening - 2026-08-31

## Scope and outcome

Point 2, further security hardening, was completed for the public Eduvixo website and its shared-host security boundary. No visual content, product data, database schema or customer data was changed.

The implementation adds:

- strict HTTP method handling: public pages accept GET/HEAD, Contact additionally accepts POST, and unused methods return 405;
- a 64 KiB request-body boundary at both supported document roots, with an early Content-Length rejection and Apache's body limiter retained for layered enforcement;
- HSTS for one year without `includeSubDomains`, preserving independent subdomain rollout;
- Cross-Origin-Opener-Policy, X-Permitted-Cross-Domain-Policies, X-DNS-Prefetch-Control and X-Download-Options headers;
- removal of X-Powered-By where the hosting stack could expose it;
- a hardened nonce-based CSP with `object-src 'none'`, explicit base/form/frame/media/manifest/worker policies and HTTPS upgrade;
- strict cookie-only PHP sessions with strict session-ID acceptance and URL session identifiers disabled;
- a persistent cryptographically random 256-bit runtime key stored outside the public tree, replacing the predictable path-derived fallback;
- exclusive file locking and fail-closed behavior for Contact, Marketplace and license-attempt rate-limit state;
- concealed 404 responses for private repository/application paths instead of distinguishable 403 responses;
- a standard `/.well-known/security.txt` disclosure contact;
- a dedicated MariaDB Fail2ban jail using the existing ITTSP blocking action.

## Infrastructure audit and decision

MariaDB listens publicly on TCP 3306 and UFW currently permits that port. The server has legitimate remotely scoped database accounts, including one account restricted to a specific address and two accounts configured with a wildcard host. Closing the port or changing account hosts without a dependency inventory could interrupt unrelated production services on this shared server, so neither the listener, UFW rule nor database grants were changed.

The journal contained confirmed remote probes and rejected root logins. The new `eduvixo-mariadb-auth` jail matches the actual MariaDB systemd journal format and applies:

- 3 failures;
- within 10 minutes;
- 1-hour ban;
- existing `ittsp-api` action;
- MariaDB journal only.

Fail2ban configuration testing succeeded and the active jail count increased from four to five. Fail2ban, MariaDB, Apache and PHP 8.4 FPM remained active. No firewall rule, database account, grant, configuration or data was changed.

## Application deployment

- Target: `/var/www/clients/client9/web123/web`.
- Files published: 7.
- Runtime key: generated directly on the server as `storage/.site-rate-key`, owner `web123:client9`, mode `0640`; its value was not printed, uploaded or committed.
- Published files use `web123:client9`, mode `0640`.
- Database changes: none.
- Service restart: none required for the website layer.

The first complete pre-hardening backup is:

- `/root/eduvixo-backups/security-hardening-pre-20260830-234552`
- `website-files.tar.gz`: SHA-256 `11a499d5757de1246d1d46641be16d937fec5dd9f4d37543b64502567958cafb`
- `ROLLBACK.txt`: SHA-256 `33231841ef06b2384ad6dc4fac4baaac3abee9dd58745754fb7f4e67bd55e42b`

Two incremental backups were created while verifying the body-size boundary. The final pre-rule-adjustment backup is `/root/eduvixo-backups/security-hardening-pre-20260830-234922` with archive SHA-256 `cf03f94e281e80bad7635680626ad4750de7f70efcc55f4087f7b5432b765438`.

Full rollback uses the first complete backup, removes the newly created `public/.well-known/security.txt` and `storage/.site-rate-key`, restores ownership/modes and repeats the security, route, cache and Marketplace audits.

## Fail2ban deployment and rollback

- Filter: `/etc/fail2ban/filter.d/eduvixo-mariadb-auth.conf`.
- Jail: `/etc/fail2ban/jail.d/eduvixo-mariadb-auth.conf`.
- Backup: `/root/eduvixo-backups/security-infrastructure-pre-20260830-235446`.
- `ROLLBACK.txt`: SHA-256 `c46b631ff3d7efac60604d9cb84c94de34b66644bd30685602e2178b72802317`.
- `state.json`: SHA-256 `9b60aed1b4f11acf4e00661228014ab589e89c6139bbef36c8874e9da5fc605c`.

Both files were new. Rollback removes them, runs `fail2ban-client -t`, reloads Fail2ban and confirms the previous four-jail list.

## Validation

- PHP syntax passed for all changed application, configuration, deployment and audit files locally and on production.
- Atomic-limiter tests passed: Contact minimum interval, Marketplace request ceiling and corrupt license state fail-closed behavior.
- Secure runtime-key tests confirmed a random 64-hex key different from the former path-derived fallback.
- Security audit passed in all seven languages.
- TRACE, OPTIONS, PUT, DELETE, PATCH and POST on read-only pages are rejected with 405.
- Requests over 65,536 bytes return 413.
- Six representative private paths return concealed 404 responses.
- HSTS, hardened CSP, COOP and auxiliary security headers are present on dynamic pages; common headers are also present on static assets and API errors.
- `security.txt` returns HTTP 200 with `text/plain`.
- Route and SEO audit passed 84/84 localized URLs.
- Cache/session regression audit passed 84/84 routes: ordinary pages create no PHP session, while Contact and Marketplace retain no-store, session and CSRF controls.
- Marketplace regression audit passed for 13 products, seven languages, filters and production assets.
- Unauthenticated package API access remains 401/no-store; license endpoint rejects GET with 405/no-store.
- Apache configuration reports `Syntax OK`; the pre-existing `NameVirtualHost has no effect` notice is unrelated.
- Production application error log showed no matching PHP fatal, warning or parse errors after verification traffic.
- Git continues to track only `.cfg/README.md` and `storage/.gitkeep` from protected local areas. The random runtime secret remains ignored.
- The repository Secret scan was found to be failing on two historical false positives for the public publisher identifier `chivale-eduvixo-2026`. Exact historical Gitleaks fingerprints are allowlisted in `.gitleaksignore`; no rule or directory is excluded, and all other findings remain blocking.

## Residual recommendation

The remaining infrastructure risk is intentional public MariaDB reachability for remote consumers. A later, separately coordinated migration should inventory every successful remote client, replace wildcard database hosts with exact addresses or a private VPN/tunnel, update ISPConfig/UFW consistently, and only then close public TCP 3306. This was not mixed into the website hardening because it has a materially wider operational blast radius.
