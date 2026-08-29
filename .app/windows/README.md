# Eduvixo for Windows

Native Windows shell for the online Eduvixo workspace.

## Current scope

- Branded work-mode launcher with online and intentionally disabled offline cards.
- Maximized startup with standard Windows minimize, restore and close controls.
- Seven interface languages: Chinese, English, German, Lao, Polish, Thai and Vietnamese.
- Automatic Windows-language detection with English fallback.
- Optional persistence of the selected language and online mode.
- Embedded Microsoft Edge WebView2 browser opening `https://demo.eduvixo.com/dashboard`.
- Native, CAPTCHA-protected sign-in that transfers only the authenticated session to WebView2 and never displays the web login page.
- Demo credentials supplied at runtime by the server configuration; no password is embedded in source or in the portable executable.
- Application-owned WebView2 profile isolated from external browsers; native sign-in refreshes the authenticated session before the dashboard opens.
- Navigation controls, branded connection and runtime error screens, and a safe return to the launcher.
- Top-level in-app navigation restricted to HTTPS addresses under `eduvixo.com`; other HTTPS links open in the default system browser.

The application intentionally does not import cookies from Chrome, Edge or another browser. The first sign-in takes place inside Eduvixo and is then retained in `%LOCALAPPDATA%\Eduvixo\WebView2`.

## Requirements

- Windows 10 or Windows 11, x86 or x64.
- Internet access for online work.
- Microsoft Edge WebView2 Evergreen Runtime. Most supported Windows installations already include it; the application provides an official download path when it is missing.

## Build

The project is pinned to the stable .NET SDK declared in `global.json`.

```powershell
.\scripts\verify.ps1
.\scripts\build.ps1 -Version 0.2.1
```

Self-contained outputs are written to:

- `dist\0.2.1\win-x86\eduvixo.exe`
- `dist\0.2.1\win-x64\eduvixo.exe`

`SHA256SUMS.txt` contains the release hashes. Distribution output is excluded from Git; source, localization, build configuration and documentation are tracked.

## Local data

- Settings: `%LOCALAPPDATA%\Eduvixo\settings.json`
- Web session: `%LOCALAPPDATA%\Eduvixo\WebView2`
- Unexpected-error log: `%LOCALAPPDATA%\Eduvixo\Logs\application.log`

No credentials are stored in the settings file or application binary. Password autosave and general form autofill are disabled; the authenticated session remains isolated within the WebView2 profile managed by the Microsoft runtime.
