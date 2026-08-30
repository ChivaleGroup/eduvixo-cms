$ErrorActionPreference = 'Stop'
$root = [System.IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$languageDirectory = Join-Path $root 'src\Eduvixo.Windows\lang'
$english = Get-Content -LiteralPath (Join-Path $languageDirectory 'en.json') -Raw | ConvertFrom-Json -AsHashtable
$expectedKeys = @($english.Keys | Sort-Object)

foreach ($file in Get-ChildItem -LiteralPath $languageDirectory -Filter '*.json') {
    $dictionary = Get-Content -LiteralPath $file.FullName -Raw | ConvertFrom-Json -AsHashtable
    $actualKeys = @($dictionary.Keys | Sort-Object)
    if (Compare-Object $expectedKeys $actualKeys) {
        throw "Localization schema mismatch: $($file.Name)"
    }
}

[xml](Get-Content -LiteralPath (Join-Path $root 'src\Eduvixo.Windows\App.xaml') -Raw) | Out-Null
$mainWindow = [xml](Get-Content -LiteralPath (Join-Path $root 'src\Eduvixo.Windows\MainWindow.xaml') -Raw)
if ($mainWindow.Window.WindowState -ne 'Maximized') {
    throw 'The application must open maximized.'
}

$tokens = $null
$parseErrors = $null
[System.Management.Automation.Language.Parser]::ParseFile(
    (Join-Path $root 'scripts\sign-release.ps1'),
    [ref]$tokens,
    [ref]$parseErrors
) | Out-Null
if ($parseErrors.Count -ne 0) {
    throw "Signing script syntax validation failed: $($parseErrors.Message -join '; ')"
}

$project = Join-Path $root 'src\Eduvixo.Windows\Eduvixo.Windows.csproj'
Push-Location $root
try {
    dotnet restore $project --locked-mode
    if ($LASTEXITCODE -ne 0) { throw 'Restore verification failed.' }

    dotnet build (Join-Path $root 'Eduvixo.Windows.slnx') --configuration Release --no-restore -warnaserror
    if ($LASTEXITCODE -ne 0) { throw 'Build verification failed.' }
} finally {
    Pop-Location
}

Write-Output "Verified maximized startup, $($expectedKeys.Count) localization keys across 7 languages, signing script syntax and a warning-free Release build."
