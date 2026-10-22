$ErrorActionPreference = 'Stop'
Set-Location (Split-Path -Parent $PSScriptRoot)

$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = 'database/aquarium-local.sqlite'
$env:CACHE_STORE = 'file'
$env:QUEUE_CONNECTION = 'sync'
$env:SESSION_DRIVER = 'file'

if (-not (Test-Path -LiteralPath $env:DB_DATABASE)) {
    New-Item -ItemType File -Path $env:DB_DATABASE | Out-Null
}

Write-Host 'SQLite queda como respaldo aislado. PostgreSQL en Docker es la base única recomendada para las pruebas funcionales.' -ForegroundColor Yellow
& (Join-Path $PSScriptRoot 'php-local.ps1') artisan migrate --seed --force
exit $LASTEXITCODE
