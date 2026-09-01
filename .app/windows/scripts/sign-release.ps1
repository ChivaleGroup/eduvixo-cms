param(
    [ValidatePattern('^\d+\.\d+\.\d+([.-][0-9A-Za-z.-]+)?$')]
    [string]$Version = '0.2.5',
    [ValidatePattern('^https://[a-z0-9.-]+/$')]
    [string]$Endpoint = 'https://cus.codesigning.azure.net/',
    [ValidatePattern('^[A-Za-z0-9-]{3,100}$')]
    [string]$AccountName = 'Chivale',
    [ValidatePattern('^[A-Za-z][A-Za-z0-9-]{3,98}[A-Za-z0-9]$')]
    [string]$ProfileName = 'EduvixoPublicTrust',
    [ValidatePattern('^[^\r\n]{2,200}$')]
    [string]$ExpectedPublisher = 'Chivale Group LTD',
    [switch]$InteractiveBrowser,
    [switch]$SkipBuild
)

$ErrorActionPreference = 'Stop'
$root = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$distBase = [System.IO.Path]::GetFullPath((Join-Path $root 'dist'))
$distRoot = [System.IO.Path]::GetFullPath((Join-Path $distBase $Version))

if (-not $distRoot.StartsWith($distBase + [System.IO.Path]::DirectorySeparatorChar, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'Unsafe distribution path.'
}

if (-not $SkipBuild) {
    & (Join-Path $PSScriptRoot 'build.ps1') -Version $Version -Configuration Release
    if ($LASTEXITCODE -ne 0) { throw 'Release build failed.' }
}

$executables = @('win-x86', 'win-x64') | ForEach-Object {
    $path = Join-Path $distRoot "$_\eduvixo.exe"
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw "Missing release executable: $path" }
    Get-Item -LiteralPath $path
}

$clientRoot = Join-Path $env:LOCALAPPDATA 'Microsoft\MicrosoftArtifactSigningClientTools'
$dlib = Join-Path $clientRoot 'Azure.CodeSigning.Dlib.dll'
if (-not (Test-Path -LiteralPath $dlib -PathType Leaf)) {
    throw 'Microsoft Artifact Signing Client Tools are not installed for the current user.'
}

$sdkRoot = Join-Path ${env:ProgramFiles(x86)} 'Windows Kits\10\bin'
$signTool = Get-ChildItem -LiteralPath $sdkRoot -Directory -ErrorAction Stop |
    Where-Object { $_.Name -match '^\d+\.\d+\.\d+\.\d+$' -and [version]$_.Name -ge [version]'10.0.22621.0' } |
    Sort-Object { [version]$_.Name } -Descending |
    ForEach-Object { Join-Path $_.FullName 'x64\signtool.exe' } |
    Where-Object { Test-Path -LiteralPath $_ -PathType Leaf } |
    Select-Object -First 1

if (-not $signTool) { throw 'A supported x64 SignTool 10.0.22621.0 or newer was not found.' }

$metadata = [ordered]@{
    Endpoint               = $Endpoint
    CodeSigningAccountName = $AccountName
    CertificateProfileName = $ProfileName
    CorrelationId          = [guid]::NewGuid().ToString()
}

if ($InteractiveBrowser) {
    $metadata.ExcludeCredentials = @(
        'EnvironmentCredential',
        'WorkloadIdentityCredential',
        'ManagedIdentityCredential',
        'SharedTokenCacheCredential',
        'VisualStudioCredential',
        'VisualStudioCodeCredential',
        'AzureCliCredential',
        'AzurePowerShellCredential',
        'AzureDeveloperCliCredential'
    )
}

$tempBase = [System.IO.Path]::GetFullPath([System.IO.Path]::GetTempPath())
$tempDirectory = [System.IO.Path]::GetFullPath((Join-Path $tempBase ("eduvixo-sign-" + [guid]::NewGuid().ToString('N'))))
if (-not $tempDirectory.StartsWith($tempBase, [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'Unsafe temporary path.'
}

New-Item -ItemType Directory -Path $tempDirectory | Out-Null
$metadataPath = Join-Path $tempDirectory 'metadata.json'

try {
    $json = $metadata | ConvertTo-Json -Depth 3
    [System.IO.File]::WriteAllText($metadataPath, $json, [System.Text.UTF8Encoding]::new($false))

    foreach ($executable in $executables) {
        $existing = Get-AuthenticodeSignature -LiteralPath $executable.FullName
        if ($existing.Status -ne [System.Management.Automation.SignatureStatus]::NotSigned) {
            throw "Refusing to sign an executable that already has a signature: $($executable.FullName)"
        }

        & $signTool sign /v /fd SHA256 /tr 'http://timestamp.acs.microsoft.com' /td SHA256 /dlib $dlib /dmdf $metadataPath $executable.FullName
        if ($LASTEXITCODE -ne 0) { throw "Artifact Signing failed for $($executable.FullName)." }

        & $signTool verify /pa /all /v $executable.FullName
        if ($LASTEXITCODE -ne 0) { throw "Authenticode verification failed for $($executable.FullName)." }

        $signature = Get-AuthenticodeSignature -LiteralPath $executable.FullName
        if ($signature.Status -ne [System.Management.Automation.SignatureStatus]::Valid) {
            throw "Windows reports an invalid signature for $($executable.FullName): $($signature.StatusMessage)"
        }
        if ($signature.SignerCertificate.Subject -notmatch [regex]::Escape($ExpectedPublisher)) {
            throw "Unexpected publisher in the signing certificate: $($signature.SignerCertificate.Subject)"
        }
        if (-not $signature.TimeStamperCertificate) {
            throw "The signature has no trusted timestamp: $($executable.FullName)"
        }
    }

    $hashes = foreach ($runtime in @('win-x86', 'win-x64')) {
        $executable = Join-Path $distRoot "$runtime\eduvixo.exe"
        $hash = Get-FileHash -LiteralPath $executable -Algorithm SHA256
        "$($hash.Hash.ToLowerInvariant())  $runtime/eduvixo.exe"
    }
    $hashes | Set-Content -LiteralPath (Join-Path $distRoot 'SHA256SUMS.txt') -Encoding utf8
} finally {
    if (Test-Path -LiteralPath $tempDirectory) {
        $resolved = [System.IO.Path]::GetFullPath($tempDirectory)
        if ($resolved.StartsWith($tempBase, [System.StringComparison]::OrdinalIgnoreCase)) {
            Remove-Item -LiteralPath $resolved -Recurse -Force
        }
    }
}

Write-Output "Desktop Client for Windows $Version signed and verified with publisher '$ExpectedPublisher'."
