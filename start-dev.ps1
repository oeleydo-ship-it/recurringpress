# Starts the local dev stack on Windows: web server, queue worker, scheduler and Vite (or the SSR server with -Build).
#
# `php artisan horizon` cannot run on native Windows (PHP for Windows has no pcntl extension),
# so locally a plain queue worker processes the same Redis queues Horizon would. Jobs still
# show up in the Horizon dashboard at /horizon (superadmin login required).
# In production (Linux) run `php artisan horizon` under Supervisor/systemd instead — see INSTALL.md.
#
# Usage:  powershell -ExecutionPolicy Bypass -File .\start-dev.ps1           (Vite dev server with hot reload)
#         powershell -ExecutionPolicy Bypass -File .\start-dev.ps1 -Build    (production build + SSR server)

param([switch]$Build)

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

# Laravel 13 needs PHP 8.3+ (AMPPS ships 8.2), so prefer the standalone PHP 8.4 install.
$php = if (Test-Path 'C:\php84\php.exe') { 'C:\php84\php.exe' } else { (Get-Command php -ErrorAction Stop).Source }

# MySQL (AMPPS) must be up or every request stalls on DB connection retries.
if (-not (Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -InformationLevel Quiet -WarningAction SilentlyContinue)) {
    Write-Warning 'MySQL is not listening on 127.0.0.1:3306 - start it from the AMPPS control panel first.'
}
if (-not (Test-NetConnection -ComputerName 127.0.0.1 -Port 6379 -InformationLevel Quiet -WarningAction SilentlyContinue)) {
    Write-Warning 'Redis is not listening on 127.0.0.1:6379 - start the Redis service first (cache, sessions and queues use it).'
}

Start-Process -FilePath $php -ArgumentList 'artisan', 'serve', '--host=127.0.0.1', '--port=8002' -WorkingDirectory $PSScriptRoot
Start-Process -FilePath $php -ArgumentList 'artisan', 'queue:work', 'redis', '--queue=high,default,mail', '--tries=3', '--timeout=75', '--sleep=2' -WorkingDirectory $PSScriptRoot
Start-Process -FilePath $php -ArgumentList 'artisan', 'schedule:work' -WorkingDirectory $PSScriptRoot

if ($Build) {
    # Production-like: compiled assets (public/build + bootstrap/ssr) and the Inertia SSR server.
    & npm.cmd run build
    if ($LASTEXITCODE -ne 0) { throw 'npm run build failed' }
    if (Test-Path 'public\hot') { Remove-Item 'public\hot' }
    Start-Process -FilePath $php -ArgumentList 'artisan', 'inertia:start-ssr' -WorkingDirectory $PSScriptRoot
} else {
    # Hot reload; Laravel serves assets from the Vite dev server while public/hot exists.
    Start-Process -FilePath 'npm.cmd' -ArgumentList 'run', 'dev' -WorkingDirectory $PSScriptRoot
}

Write-Host 'App:     http://localhost:8002'
Write-Host 'Horizon: http://localhost:8002/horizon (log in at /superadmin/login first)'
