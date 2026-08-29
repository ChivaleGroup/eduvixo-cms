# Eduvixo for Windows - native demo login

Date: 2026-08-29
Version: 0.2.0
Environment: local Windows release build and production demo authentication at `demo.eduvixo.com`
Status: implemented, deployed configuration synchronized and verified

## Objective

Replace the web login page in the portable Windows application with a native Eduvixo login screen. The demo account must be prepared automatically, CAPTCHA must remain mandatory, and successful authentication must open the dashboard directly.

## Implementation

- Added a branded native login view between the work-mode launcher and the embedded dashboard.
- Added localized login copy in Chinese, English, German, Lao, Polish, Thai and Vietnamese.
- The email and password are obtained at runtime from the server-controlled demo configuration over HTTPS. Neither value is compiled into the executable or committed to Git.
- The password is displayed only as a masked native password field.
- The client obtains a fresh PHP session, CSRF token and CAPTCHA image from the existing CMS login flow.
- Login is submitted to the existing `/login` endpoint with JSON response negotiation.
- On success, only the authenticated session cookie is transferred into the isolated WebView2 profile before opening `/dashboard`.
- A redirect to `/login` caused by an expired dashboard session is intercepted and replaced with the native login view.
- Invalid CAPTCHA, expired CSRF, invalid credentials and connectivity problems receive localized application messages.
- CAPTCHA can be refreshed without reloading the view.
- WebView2 password autosave and general autofill remain disabled.

## Production configuration change

The demo account password had already been changed in the CMS database, while `CMS_DEMO_PASSWORD` still contained the previous prefill value. The environment value was synchronized from the authoritative local `.cfg/demo.eduvixo.com/login+password.demo.txt` configuration.

Changed file:

`/var/www/clients/client9/web121/web/.env`

Only `CMS_DEMO_PASSWORD` changed. The file retained mode `0640` and ownership `web121:client9`. No PHP-FPM reload was required because the application reads `.env` for each request.

Backup:

`/root/eduvixo-backups/demo-env-pre-windows-login-20260829-141821`

Rollback:

```bash
cp --preserve=mode,ownership,timestamps /root/eduvixo-backups/demo-env-pre-windows-login-20260829-141821 /var/www/clients/client9/web121/web/.env
```

No database, schema, DNS, SSL, firewall, web-server or PHP-FPM changes were made.

## Verification

- Production `/login` returned HTTP 200.
- Server prefill email and password matched the authoritative local demo configuration without printing either secret.
- CAPTCHA was enabled and returned within the same server session.
- Real native login completed successfully and opened `Overview · Eduvixo` directly.
- The web login page was not displayed.
- The server security audit recorded valid CSRF, CAPTCHA and credentials for the test login.
- The final portable x86 and x64 builds both displayed the native login screen, CAPTCHA and a 16-character masked password.
- PE architectures: x86 `0x014C`, x64 `0x8664`.
- File version: `0.2.0.0`.
- 47 localization keys matched across all seven language files.
- Release build completed with zero warnings and zero errors.

## Portable release

- win-x86 SHA-256: `9d8e3a29643fe2382ed8fa63a87721ef206ae812d4c4b407cb6b2640cb3ee9f2`
- win-x64 SHA-256: `c67bc9185babba3c375518e9b766dbc96912fb4afde594a6dd9c98d1abeb90d6`

Artifacts:

- `.app/windows/dist/0.2.0/win-x86/eduvixo.exe`
- `.app/windows/dist/0.2.0/win-x64/eduvixo.exe`

Release binaries remain excluded from Git. They are portable and currently unsigned, as agreed for this phase.
