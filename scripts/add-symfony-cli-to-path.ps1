# Adds .tools/symfony-cli to the current user's PATH (once) so `symfony` works in new terminals.
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
$dir = (Resolve-Path (Join-Path (Join-Path $root '.tools') 'symfony-cli')).Path
if (-not (Test-Path (Join-Path $dir 'symfony.exe'))) {
    Write-Error "symfony.exe not found. Run install-symfony-cli.ps1 first."
}
$userPath = [Environment]::GetEnvironmentVariable('Path', 'User')
$parts = $userPath -split ';' | Where-Object { $_ -and ($_ -ne $dir) }
$newPath = ($dir + ';' + ($parts -join ';')).TrimEnd(';')
[Environment]::SetEnvironmentVariable('Path', $newPath, 'User')
$env:Path = $dir + ';' + $env:Path
Write-Host "Added to user PATH: $dir"
Write-Host "Open a new PowerShell window and run: symfony version"
