# Eduvixo for Windows - toolbar-free workspace

Date: 2026-08-30
Version: 0.2.3
Environment: local Windows portable release build
Status: implemented and verified

## Objective

Remove the application-owned browser toolbar above the embedded Eduvixo workspace and open the CMS `Visit Site` action in the user's default Windows browser.

## Implementation

- Removed the complete 62-pixel WebView2 toolbar, including the application logo, launcher, back, forward, refresh, host indicator and website button.
- The embedded workspace now fills the entire application client area below the standard Windows title bar.
- Removed the unused WPF toolbar style and all toolbar-specific event handlers, localization assignments and navigation-state updates.
- Changed `CoreWebView2.NewWindowRequested` handling so every HTTPS link requesting a new window is passed to Windows Shell with `UseShellExecute = true`.
- The CMS `View site`/`Visit Site` link already uses `target="_blank"`, so it now opens in the user's configured default browser instead of navigating the embedded WebView2 instance.
- Same-window navigation remains restricted to HTTPS addresses under `eduvixo.com`; the existing navigation security boundary was preserved.
- Advanced the portable release and signing defaults to `0.2.3`.
- Disabled automatic source-revision suffixes in the product version so Windows displays the clean product version `0.2.3`.
- Extended build verification to reject:
  - restoration of any removed browser-toolbar control;
  - a WebView2 layout that does not directly fill `BrowserView`;
  - internal WebView2 navigation from the new-window handler.

## Files changed

- `.app/windows/src/Eduvixo.Windows/MainWindow.xaml`
- `.app/windows/src/Eduvixo.Windows/MainWindow.xaml.cs`
- `.app/windows/src/Eduvixo.Windows/App.xaml`
- `.app/windows/src/Eduvixo.Windows/Eduvixo.Windows.csproj`
- `.app/windows/scripts/build.ps1`
- `.app/windows/scripts/sign-release.ps1`
- `.app/windows/scripts/verify.ps1`
- `.app/windows/README.md`

## Verification

- XAML parsed successfully.
- All 47 localization keys remain consistent across seven languages.
- Release build completed with zero warnings and zero errors.
- Regression checks confirmed the toolbar is absent and new-window HTTPS navigation uses the default Windows browser.
- Portable self-contained publication completed for x86 and x64.
- PE architectures:
  - x86: `0x014C`;
  - x64: `0x8664`.
- File and product versions are `0.2.3.0` and `0.2.3` respectively.
- The checksum manifest matches both executables.
- Automated live-window screenshot capture was unavailable because the current Windows desktop session denied UI capture access. No security restriction was bypassed; structural XAML checks and compiled regression checks cover the requested layout and routing behavior.

## Portable release

- win-x86 SHA-256: `bef08fe7b71f3473e366fd33fba3cc6193781f17ae2ed71fcf9f2c47328986ef`
- win-x64 SHA-256: `0633a2025d12c6fab266bb233bb258f6b7ba10dc3220c1644edd5dbf6738656d`

Artifacts:

- `.app/windows/dist/0.2.3/win-x86/eduvixo.exe`
- `.app/windows/dist/0.2.3/win-x64/eduvixo.exe`

Both executables remain unsigned as planned for this development stage. No web server, database, marketplace, credential, infrastructure or Windows system-setting change was required.

## Rollback

Revert the source commit and rebuild version `0.2.2`. Existing `0.2.2` and marketplace release artifacts are not modified by this change.
