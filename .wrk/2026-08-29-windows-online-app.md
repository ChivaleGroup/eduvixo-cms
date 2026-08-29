# Eduvixo for Windows - initial online client

Date: 2026-08-29
Version: 0.1.0
Status: implemented and locally verified

## Scope

Created the initial Windows client in `.app/windows/` for both 32-bit and 64-bit Windows. The first release provides a branded work-mode launcher and an online Eduvixo workspace. The offline option is visible but intentionally disabled.

The online workspace opens `https://demo.eduvixo.com/dashboard` in an application-owned Microsoft Edge WebView2 profile. Authentication is performed by the Eduvixo web application; the launcher does not collect or store credentials.

## Architecture

- WPF application targeting `net10.0-windows` with the .NET SDK pinned by `global.json`.
- Microsoft Edge WebView2 package `1.0.4191.47`, pinned in `packages.lock.json`.
- Self-contained, single-file ReadyToRun outputs for `win-x86` and `win-x64`.
- Application settings stored in `%LOCALAPPDATA%\Eduvixo\settings.json`.
- Persistent WebView2 profile stored in `%LOCALAPPDATA%\Eduvixo\WebView2`.
- Unexpected application errors stored in `%LOCALAPPDATA%\Eduvixo\Logs\application.log` without credentials.

## User experience

- Professional Eduvixo launcher using the established navy, blue and white visual identity.
- Language selector at the top and optional "remember my choice" behavior.
- Languages: Chinese, English, German, Lao, Polish, Thai and Vietnamese.
- Automatic Windows language detection with English fallback.
- Active online card on the left and disabled offline card on the right.
- Integrated browser toolbar with launcher, back, forward, refresh, verified host display and official website action.
- Branded loading, missing-runtime and navigation-error states.
- Keyboard focus, accessible control names and disabled-state semantics verified through Windows UI Automation.

## Security controls

- Top-level in-app navigation is limited to HTTPS and `eduvixo.com` or its subdomains.
- External HTTPS links open in the system browser.
- Non-HTTPS and unsupported navigation targets are blocked.
- Browser cookies are isolated from Chrome and Edge profiles.
- WebView2 password autosave and general form autofill are disabled.
- No passwords, access tokens or infrastructure credentials are embedded in the application or settings.
- Single-instance mutex prevents parallel clients from sharing the same WebView2 profile.

## Build and verification

Commands:

```powershell
cd .app\windows
.\scripts\verify.ps1
.\scripts\build.ps1 -Version 0.1.0
```

Verification completed:

- 26 localization keys matched across all seven language files.
- XAML parsed successfully.
- Release build completed with zero warnings and zero errors.
- Real Windows UI test completed for the launcher, Polish localization, remembered-choice setting, launcher return action and online navigation.
- Online navigation reached the Eduvixo sign-in page at `demo.eduvixo.com`.
- Final `win-x86` executable launched successfully on 64-bit Windows.
- A second `win-x64` launch was rejected with the expected single-instance message while the first instance remained active.
- PE headers verified: x86 `0x014C`, x64 `0x8664`.
- Outputs contain one executable per architecture and a SHA-256 manifest only.

Release hashes:

- win-x86: `c397c4b823a38e9fc4e0115699a5d19f5dc990416629512ec2089a382b228c49`
- win-x64: `85c385498538fdf6cdedf90903c697cceb76177b3b83c75af7c250720a9789d2`

## Distribution and outstanding release work

Generated binaries are in `.app/windows/dist/0.1.0/` and are excluded from Git. They currently have no Authenticode signature. Before public distribution, obtain an organization code-signing certificate, sign both executables and build a signed installer. Offline work remains a future product phase.

No database, server, production deployment or infrastructure changes were required.

## Rollback

This addition is isolated under `.app/windows/`, plus this work record and one repository README section. Reverting the corresponding Git commit removes the source integration. Generated binaries can be regenerated from the tagged source and lock file.
