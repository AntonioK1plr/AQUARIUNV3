param(
    [int] $Users = 500,
    [int] $RampSeconds = 60,
    [int] $DurationSeconds = 60,
    [string] $BaseUrl = 'http://127.0.0.1:8001'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot
$planPath = Join-Path $projectRoot 'tests/performance/aquarium-public-flow.jmx'
$outputRoot = Join-Path $projectRoot 'storage/app/testing/jmeter'
$jmeter = Get-Command jmeter -ErrorAction SilentlyContinue
$localJmeter = Join-Path $projectRoot 'tools/jmeter/apache-jmeter-5.6.3/bin/jmeter.bat'
$localJava = Join-Path $projectRoot 'tools/java/jdk-21.0.12.1+1'

$jmeterCommand = if ($jmeter) { $jmeter.Source } elseif (Test-Path -LiteralPath $localJmeter) { $localJmeter } else { $null }
if (-not $jmeterCommand) {
    throw 'No se encontró JMeter. Instala la distribución local indicada en README.md o agrega jmeter a PATH.'
}
if (Test-Path -LiteralPath (Join-Path $localJava 'bin/java.exe')) {
    $env:JAVA_HOME = $localJava
    $env:Path = "$($env:JAVA_HOME)\bin;$($env:Path)"
} elseif (-not (Get-Command java -ErrorAction SilentlyContinue)) {
    throw 'JMeter necesita Java 8 o posterior. No se encontró el JDK portable del proyecto ni Java en PATH.'
}
if (-not (Test-Path -LiteralPath $planPath)) {
    throw "No existe el plan JMeter: $planPath"
}
$probe = Invoke-WebRequest -Uri $BaseUrl -Method Get -TimeoutSec 10 -UseBasicParsing
if ($probe.StatusCode -ne 200) {
    throw "La página local respondió HTTP $($probe.StatusCode). No se inicia la carga."
}

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$resultPath = Join-Path $outputRoot "aquarium_$stamp.jtl"
$dashboardPath = Join-Path $outputRoot "dashboard_$stamp"
New-Item -ItemType Directory -Path $outputRoot -Force | Out-Null
$uri = [Uri] $BaseUrl
$commandLine = 'call "{0}" -n -t "{1}" -l "{2}" -e -o "{3}" -Jhost={4} -Jport={5} -Jusers={6} -Jramp={7} -Jduration={8}' -f `
    $jmeterCommand, $planPath, $resultPath, $dashboardPath, $uri.Host, $uri.Port, $Users, $RampSeconds, $DurationSeconds
& $env:ComSpec /d /c $commandLine
$exitCode = $LASTEXITCODE
if ($exitCode -ne 0 -or -not (Test-Path -LiteralPath $resultPath) -or -not (Test-Path -LiteralPath (Join-Path $dashboardPath 'index.html'))) {
    throw "JMeter no generó una ejecución completa (código $exitCode). Revisa la salida anterior y los archivos bajo $outputRoot."
}
$samples = @(Import-Csv -LiteralPath $resultPath)
if (-not $samples.Count) {
    throw "JMeter no registró muestras en $resultPath."
}
$elapsed = @($samples | ForEach-Object { [double] $_.elapsed } | Sort-Object)
$successCount = @($samples | Where-Object { $_.success -eq 'true' }).Count
$p95Index = [Math]::Max(0, [Math]::Ceiling($elapsed.Count * 0.95) - 1)
$serverProfile = if ($uri.Port -eq 8080) { 'Docker · Nginx + PHP-FPM' } else { 'PHP local de desarrollo' }
$limitation = if ($uri.Port -eq 8080 -and $Users -ge 500) {
    'Carga local de 500 o más usuarios virtuales sobre Docker/Nginx/PHP-FPM; los resultados no equivalen a una validación de producción ni de un SLA externo.'
} elseif ($uri.Port -eq 8080) {
    'Etapa gradual de carga local sobre Docker/Nginx/PHP-FPM; aún no representa la prueba objetivo de 500 usuarios.'
} else {
    'Medición local con el servidor PHP de desarrollo; no representa capacidad de producción ni permite proyectar carga de 500 usuarios.'
}
$summary = [ordered]@{
    suite = 'jmeter-public-local'
    generated_at = (Get-Date).ToString('o')
    base_url = $BaseUrl
    server_profile = $serverProfile
    configuration = [ordered]@{ virtual_users = $Users; ramp_seconds = $RampSeconds; duration_seconds = $DurationSeconds }
    status = if ($successCount -eq $samples.Count) { 'passed' } else { 'failed' }
    totals = [ordered]@{ requests = $samples.Count; succeeded = $successCount; failed = $samples.Count - $successCount }
    elapsed_ms = [ordered]@{
        min = [Math]::Round($elapsed[0], 2)
        average = [Math]::Round(($elapsed | Measure-Object -Average).Average, 2)
        p95 = [Math]::Round($elapsed[$p95Index], 2)
        max = [Math]::Round($elapsed[-1], 2)
    }
    jtl_file = Split-Path -Leaf $resultPath
    dashboard = "$(Split-Path -Leaf $dashboardPath)/index.html"
    limitation = $limitation
}
$reportJson = $summary | ConvertTo-Json -Depth 5
$reportName = "AQUARIUM_JMETER_REPORT_$stamp.json"
[System.IO.File]::WriteAllText((Join-Path $outputRoot $reportName), $reportJson, [System.Text.UTF8Encoding]::new($false))
[System.IO.File]::WriteAllText((Join-Path $outputRoot 'latest.json'), $reportJson, [System.Text.UTF8Encoding]::new($false))
Write-Output "Resultados JTL: $resultPath"
Write-Output "Dashboard HTML: $dashboardPath/index.html"
Write-Output "Resumen JSON: $(Join-Path $outputRoot $reportName)"
