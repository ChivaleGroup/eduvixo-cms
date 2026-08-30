# Eduvixo for Windows - Azure Artifact Signing preparation

Date: 2026-08-30
Environment: local Windows workstation and Microsoft Azure
Status: identity validation in progress

## Azure state

- Artifact Signing account `Chivale` exists in the `Chivale` resource group with the Basic SKU and Central US endpoint.
- Updated Microsoft Artifact Signing terms dated 2026-05-04 were accepted by the authorized account holder.
- The current identity has the `Artifact Signing Identity Verifier` role at the Artifact Signing account scope.
- A Public organization identity validation for Chivale Group LTD was submitted and is currently `In Progress`.
- No certificate profile can be created until Microsoft changes the identity validation status to `Completed`.

No tax identifiers, business identifiers, personal data, access tokens, credentials or certificate material are recorded in this document or committed to Git.

## Local signing preparation

- Installed the official Microsoft Artifact Signing Client Tools package 1.0.0 through Windows Package Manager.
- Confirmed .NET 8 runtime and a supported Windows SDK SignTool are available.
- Added `scripts/sign-release.ps1` for clean x86/x64 build, Azure digest signing, RFC 3161 timestamping, publisher validation, Authenticode verification and post-signing SHA-256 generation.
- Advanced the next Windows release version to `0.2.2`; existing unsigned `0.2.1` marketplace artifacts remain unchanged.
- Signing metadata is generated in a uniquely named operating-system temporary directory and removed after every attempt.

## Validation performed

- Corrected the build and verification scripts to execute from the Windows project directory, ensuring the pinned stable .NET SDK 10.0.202 is used instead of an unrelated preview SDK installed on the workstation.
- `scripts/verify.ps1` completed successfully with all 47 localization keys across seven languages, valid XAML, signing-script syntax validation and a warning-free Release build.
- A clean `0.2.2` self-contained build completed for win-x86 and win-x64.
- Both release executables report product version `0.2.2` and `NotSigned`, which is the expected state before the Public Trust profile becomes available.

## Remaining steps

1. Wait for Public identity validation status `Completed`.
2. Create one `Public Trust` certificate profile named `EduvixoPublicTrust`.
3. Assign `Artifact Signing Certificate Profile Signer` to the release identity at the narrowest practical scope.
4. Run `scripts/sign-release.ps1 -Version 0.2.2 -ProfileName EduvixoPublicTrust -InteractiveBrowser`.
5. Verify both executables, publish them through the protected Marketplace flow, and retain the unsigned `0.2.1` artifacts for rollback until the signed release is confirmed.
