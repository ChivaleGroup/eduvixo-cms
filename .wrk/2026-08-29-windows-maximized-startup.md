# Eduvixo for Windows - maximized startup

Date: 2026-08-29
Version: 0.2.1
Environment: local Windows portable release build
Status: implemented and verified

## Objective

Open the standalone Eduvixo Windows application maximized by default.

## Implementation

- `MainWindow` now declares `WindowState="Maximized"`.
- The standard Windows frame remains available, including minimize, restore and close controls.
- The configured 1120 x 720 dimensions remain the restore size when the user leaves the maximized state.
- The behaviour applies to the launcher, native login and embedded online workspace because they share the same top-level window.
- Release metadata and build defaults were advanced from 0.2.0 to 0.2.1.
- Build verification now fails if maximized startup is removed accidentally.

## Files changed

- `.app/windows/src/Eduvixo.Windows/MainWindow.xaml`
- `.app/windows/src/Eduvixo.Windows/Eduvixo.Windows.csproj`
- `.app/windows/scripts/build.ps1`
- `.app/windows/scripts/verify.ps1`
- `.app/windows/README.md`

No backend, website, database, installer, WebView2 session, credential or Windows system-setting changes were required.

## Verification

- XAML parsing and the explicit maximized-startup assertion passed.
- All 47 localization keys matched across seven languages.
- Release build completed with zero warnings and zero errors.
- Portable self-contained x86 and x64 publication completed successfully.
- PE architectures: x86 `0x014C`, x64 `0x8664`.
- File version: `0.2.1.0` for both executables.
- The generated checksum manifest matches both executables.

## Portable release

- win-x86 SHA-256: `65009bf2395659e1c2addbdbe5fd569c452888d7aeb80a24491dbd60f7c45a4b`
- win-x64 SHA-256: `d3a0664af8294c82d690c49d60409f10dbd06b6f4dcbc6dc104bcb85cab448bf`

Artifacts:

- `.app/windows/dist/0.2.1/win-x86/eduvixo.exe`
- `.app/windows/dist/0.2.1/win-x64/eduvixo.exe`

Release binaries remain excluded from Git. They are portable and unsigned.
