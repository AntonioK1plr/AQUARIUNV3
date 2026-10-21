param(
    [string] $Database = 'aquarium_codex_test_20261019'
)

$ErrorActionPreference = 'Stop'
if ($Database -notmatch '^aquarium_codex_test_[0-9]{8}$') {
    throw 'Por seguridad, el nombre debe tener el formato aquarium_codex_test_YYYYMMDD. No uses la base de trabajo aquarium.'
}

$projectRoot = Split-Path -Parent $PSScriptRoot
$reportDirectory = Join-Path $projectRoot 'storage/app/testing'
$scanDirectory = Join-Path $projectRoot 'storage/framework/php-local-ini'
$php = (Get-Command php -ErrorAction Stop).Source
$envNames = @('DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'PHP_INI_SCAN_DIR')
$previous = @{}
foreach ($name in $envNames) { $previous[$name] = [Environment]::GetEnvironmentVariable($name, 'Process') }

try {
    $ready = & docker exec aquarium-postgres pg_isready 2>&1
    if ($LASTEXITCODE -ne 0) { throw "PostgreSQL local no está disponible: $ready" }

    $exists = & docker exec aquarium-postgres psql -U aquarium -d postgres -tAc "select datname from pg_database where datname = '$Database';"
    if ($LASTEXITCODE -ne 0) { throw 'No se pudo consultar el contenedor aquarium-postgres.' }
    if (($exists | Out-String).Trim() -ne $Database) {
        & docker exec aquarium-postgres createdb -U aquarium $Database
        if ($LASTEXITCODE -ne 0) { throw "No se pudo crear la base aislada $Database." }
    }

    $env:DB_CONNECTION = 'pgsql'
    $env:DB_HOST = '127.0.0.1'
    $env:DB_PORT = '5432'
    $env:DB_DATABASE = $Database
    $env:DB_USERNAME = 'aquarium'
    $env:PHP_INI_SCAN_DIR = $scanDirectory

    $connectionInfo = (& $php artisan db:show --counts 2>&1 | Out-String)
    if ($LASTEXITCODE -ne 0 -or $connectionInfo -notmatch [regex]::Escape($Database) -or $connectionInfo -notmatch 'Connection\s+.*pgsql') {
        throw "La verificación previa de Laravel no confirmó la base PostgreSQL aislada $Database. Se cancela antes de PHPUnit."
    }

    New-Item -ItemType Directory -Path $reportDirectory -Force | Out-Null
    $stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
    $junitName = "AQUARIUM_POSTGRES_TEST_REPORT_$stamp.xml"
    $jsonName = "AQUARIUM_POSTGRES_TEST_REPORT_$stamp.json"
    $junitPath = Join-Path $reportDirectory $junitName
    $jsonPath = Join-Path $reportDirectory $jsonName

    & $php vendor/bin/phpunit "--log-junit=$junitPath"
    $testExitCode = $LASTEXITCODE
    if (-not (Test-Path -LiteralPath $junitPath)) {
        throw "PHPUnit no produjo el reporte JUnit. Código: $testExitCode"
    }

    [xml] $junit = Get-Content -LiteralPath $junitPath -Raw
    $suite = $junit.testsuites.testsuite
    $total = [int] $suite.tests
    $failures = [int] $suite.failures
    $errors = [int] $suite.errors
    $skipped = [int] $suite.skipped
    $report = [ordered]@{
        generated_at = (Get-Date).ToString('o')
        environment = 'PostgreSQL local'
        database = $Database
        database_version = 'PostgreSQL 16.15'
        totals = [ordered]@{
            total = $total
            passed = $total - $failures - $errors - $skipped
            failed = $failures
            errors = $errors
            skipped = $skipped
        }
        assertions = [int] $suite.assertions
        duration_seconds = [double] $suite.time
        junit_file = $junitName
    }
    $json = $report | ConvertTo-Json -Depth 5
    Set-Content -LiteralPath $jsonPath -Value $json -Encoding utf8
    Set-Content -LiteralPath (Join-Path $reportDirectory 'postgres-latest.json') -Value $json -Encoding utf8
    Write-Output $json
    Write-Output "JUnit: $junitPath"
    Write-Output "JSON: $jsonPath"
    if ($testExitCode -ne 0) { exit $testExitCode }
}
finally {
    foreach ($name in $envNames) {
        [Environment]::SetEnvironmentVariable($name, $previous[$name], 'Process')
    }
}
