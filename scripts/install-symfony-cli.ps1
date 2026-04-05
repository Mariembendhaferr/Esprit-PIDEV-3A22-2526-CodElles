# Downloads the official Symfony CLI (Windows amd64) into .tools/symfony-cli/
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$destDir = Join-Path (Join-Path $root '.tools') 'symfony-cli'
$exe = Join-Path $destDir 'symfony.exe'

if (Test-Path $exe) {
    Write-Host "Symfony CLI already present: $exe"
    exit 0
}

New-Item -ItemType Directory -Force -Path $destDir | Out-Null
$release = Invoke-RestMethod -Uri 'https://api.github.com/repos/symfony-cli/symfony-cli/releases/latest'
$asset = $release.assets | Where-Object { $_.name -eq 'symfony-cli_windows_amd64.zip' } | Select-Object -First 1
if (-not $asset) { throw 'Could not find symfony-cli_windows_amd64.zip in latest release.' }

$zip = Join-Path $env:TEMP ("symfony-cli-" + $release.tag_name + '.zip')
Write-Host "Downloading $($asset.browser_download_url) ..."
Invoke-WebRequest -Uri $asset.browser_download_url -OutFile $zip
Expand-Archive -Path $zip -DestinationPath $destDir -Force
Remove-Item $zip -Force

if (-not (Test-Path $exe)) { throw "Expected symfony.exe not found under $destDir" }
Write-Host "Installed: $exe"
Write-Host "Add to your PATH (User) for 'symfony' everywhere, or run: .\symfony.bat server:start"
