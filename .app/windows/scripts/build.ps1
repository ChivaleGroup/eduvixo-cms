param(
    [ValidatePattern('^\d+\.\d+\.\d+([.-][0-9A-Za-z.-]+)?$')]
    [string]$Version = '0.2.1',
    [ValidateSet('Release', 'Debug')]
    [string]$Configuration = 'Release'
)

$ErrorActionPreference = 'Stop'
$root = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$project = Join-Path $root 'src\Eduvixo.Windows\Eduvixo.Windows.csproj'
$distRoot = [System.IO.Path]::GetFullPath((Join-Path $root "dist\$Version"))

if (-not $distRoot.StartsWith((Join-Path $root 'dist'), [System.StringComparison]::OrdinalIgnoreCase)) {
    throw 'Unsafe distribution path.'
}

if (Test-Path -LiteralPath $distRoot) {
    Remove-Item -LiteralPath $distRoot -Recurse -Force
}

New-Item -ItemType Directory -Path $distRoot -Force | Out-Null
dotnet restore $project --locked-mode
if ($LASTEXITCODE -ne 0) { throw 'Restore failed.' }

$hashes = @()
foreach ($runtime in @('win-x86', 'win-x64')) {
    $output = Join-Path $distRoot $runtime
    dotnet publish $project --configuration $Configuration --runtime $runtime --self-contained true --no-restore --disable-build-servers -p:Version=$Version -p:PublishDir="$output\"
    if ($LASTEXITCODE -ne 0) { throw "Publish failed for $runtime." }

    $executable = Join-Path $output 'eduvixo.exe'
    if (-not (Test-Path -LiteralPath $executable)) { throw "Missing executable for $runtime." }
    Get-ChildItem -LiteralPath $output -Filter '*.xml' -File | Remove-Item -Force
    $unexpectedFiles = @(Get-ChildItem -LiteralPath $output -File | Where-Object Name -ne 'eduvixo.exe')
    if ($unexpectedFiles.Count -ne 0) { throw "Unexpected publish files for ${runtime}: $($unexpectedFiles.Name -join ', ')" }
    $hash = Get-FileHash -LiteralPath $executable -Algorithm SHA256
    $hashes += "$($hash.Hash.ToLowerInvariant())  $runtime/eduvixo.exe"
}

$hashes | Set-Content -LiteralPath (Join-Path $distRoot 'SHA256SUMS.txt') -Encoding utf8
Write-Output "Eduvixo $Version published to $distRoot"
