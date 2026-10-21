$ErrorActionPreference = 'Stop'
$projectRoot = (Resolve-Path (Split-Path -Parent $PSScriptRoot)).Path
$contextPath = Join-Path $projectRoot '.docker-context'

if (Test-Path -LiteralPath $contextPath) {
    $resolvedContext = (Resolve-Path -LiteralPath $contextPath).Path
    if ($resolvedContext -ne $contextPath -or (Split-Path -Leaf $resolvedContext) -ne '.docker-context') {
        throw "Se canceló la limpieza: la ruta del contexto no coincide con la carpeta esperada del proyecto ($contextPath)."
    }
    Remove-Item -LiteralPath $resolvedContext -Recurse -Force
}
New-Item -ItemType Directory -Path $contextPath | Out-Null

$directories = @('app', 'bootstrap', 'config', 'database', 'docker', 'public', 'resources', 'routes', 'vendor')
foreach ($directory in $directories) {
    $source = Join-Path $projectRoot $directory
    $destination = Join-Path $contextPath $directory
    & robocopy.exe $source $destination /E /XD .git /NFL /NDL /NJH /NJS /NC /NS | Out-Null
    if ($LASTEXITCODE -ge 8) {
        throw "Robocopy no pudo preparar $directory (código $LASTEXITCODE)."
    }
}

foreach ($file in @('artisan', 'composer.json', 'composer.lock')) {
    Copy-Item -LiteralPath (Join-Path $projectRoot $file) -Destination $contextPath
}

$publicStorage = Join-Path $projectRoot 'storage/app/public'
if (Test-Path -LiteralPath $publicStorage) {
    $destination = Join-Path $contextPath 'storage/app/public'
    New-Item -ItemType Directory -Path $destination -Force | Out-Null
    & robocopy.exe $publicStorage $destination /E /XD .git /NFL /NDL /NJH /NJS /NC /NS | Out-Null
    if ($LASTEXITCODE -ge 8) {
        throw "Robocopy no pudo preparar las imágenes locales (código $LASTEXITCODE)."
    }
}

$totalBytes = (Get-ChildItem -LiteralPath $contextPath -File -Recurse -Force | Measure-Object -Property Length -Sum).Sum
Write-Output "Contexto de imagen listo: $([Math]::Round($totalBytes / 1MB, 1)) MB (sin herramientas, pruebas, node_modules ni metadatos Git)."
