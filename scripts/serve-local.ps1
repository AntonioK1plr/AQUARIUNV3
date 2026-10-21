param(
    [int] $Port = 8001
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$phpExe = (Get-Command php -ErrorAction Stop).Source
$frontController = Join-Path $projectRoot 'public/index.php'
$phpRoot = Split-Path -Parent $phpExe
$localIniDirectory = Join-Path $projectRoot 'storage/framework/php-local-ini'
New-Item -ItemType Directory -Force -Path $localIniDirectory | Out-Null
$extensionDirectory = (Join-Path $phpRoot 'ext').Replace('\', '/')
$iniContents = @"
extension_dir = "$extensionDirectory"
extension=php_pdo_pgsql.dll
extension=php_pgsql.dll
extension=php_gd.dll
extension=php_zip.dll
extension=php_intl.dll
"@
[System.IO.File]::WriteAllText((Join-Path $localIniDirectory 'pgsql.ini'), $iniContents)

$previousScanDirectory = $env:PHP_INI_SCAN_DIR
try {
    $env:PHP_INI_SCAN_DIR = $localIniDirectory
    & $phpExe -S "127.0.0.1:$Port" -t public $frontController
    exit $LASTEXITCODE
}
finally {
    $env:PHP_INI_SCAN_DIR = $previousScanDirectory
}
