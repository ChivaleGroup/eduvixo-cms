param([Parameter(Mandatory=$true)][string]$Command, [string[]]$Upload)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path $PSScriptRoot -Parent
$connection = @{}
Get-Content -LiteralPath (Join-Path $projectRoot '.cfg/SSH.txt') | ForEach-Object {
    if ($_ -match '^\s*([^#:=]+)\s*[:=]\s*(.*?)\s*$') { $connection[$matches[1].Trim().ToLower()] = $matches[2].Trim() }
}
$destination = "$($connection['user'])@$($connection['server ipv4'])"
if ($Upload) {
    & 'D:\Program Files\PuTTY\pscp.exe' -batch -P $connection['port'] -pw $connection['pass'] @Upload "${destination}:/root/eduvixo-deploy/"
    if ($LASTEXITCODE -ne 0) { throw 'Upload failed' }
}
& 'D:\Program Files\PuTTY\plink.exe' -batch -P $connection['port'] -pw $connection['pass'] $destination $Command
if ($LASTEXITCODE -ne 0) { throw "Remote command failed ($LASTEXITCODE)" }
