@echo off
setlocal
set "ROOT=%~dp0"
set "CLI=%ROOT%.tools\symfony-cli\symfony.exe"
if not exist "%CLI%" (
    echo Symfony CLI is missing. Install it with:
    echo   powershell -ExecutionPolicy Bypass -File "%ROOT%scripts\install-symfony-cli.ps1"
    exit /b 1
)
"%CLI%" %*
