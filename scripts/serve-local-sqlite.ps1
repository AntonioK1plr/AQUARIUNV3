param(
    [int] $Port = 8001
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

$phpExe = (Get-Command php -ErrorAction Stop).Source
$prependFile = Join-Path $PSScriptRoot 'local-sqlite-server-env.php'
$frontController = Join-Path $projectRoot 'public/index.php'

& $phpExe -d "auto_prepend_file=$prependFile" -S "127.0.0.1:$Port" -t public $frontController
exit $LASTEXITCODE
