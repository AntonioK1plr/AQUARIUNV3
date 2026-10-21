param(
    [string] $BaseUrl = 'http://127.0.0.1:8001',
    [int] $RequestsPerRoute = 2
)

$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Net.Http
$client = [System.Net.Http.HttpClient]::new()
$client.Timeout = [TimeSpan]::FromSeconds(15)
$routes = @('/', '/catalogo', '/catalogo?water_type=salada')
$samples = [System.Collections.Generic.List[object]]::new()
$pending = [System.Collections.Generic.List[object]]::new()

foreach ($route in $routes) {
    for ($index = 1; $index -le $RequestsPerRoute; $index++) {
        $url = $BaseUrl.TrimEnd('/') + $route
        $timer = [System.Diagnostics.Stopwatch]::StartNew()
        $pending.Add([pscustomobject]@{ Route = $route; Url = $url; Timer = $timer; Task = $client.GetAsync($url) })
    }
}

[System.Threading.Tasks.Task]::WaitAll([System.Threading.Tasks.Task[]] @($pending | ForEach-Object { $_.Task }))
foreach ($item in $pending) {
    $item.Timer.Stop()
    $response = $item.Task.Result
    $samples.Add([pscustomobject]@{
        route = $item.Route
        status = [int] $response.StatusCode
        elapsed_ms = [Math]::Round($item.Timer.Elapsed.TotalMilliseconds, 2)
        success = $response.IsSuccessStatusCode
    })
    $response.Dispose()
}
$client.Dispose()

$latencies = @($samples | ForEach-Object elapsed_ms | Sort-Object)
$p95Index = [Math]::Max(0, [Math]::Ceiling($latencies.Count * 0.95) - 1)
$report = [ordered]@{
    generated_at = (Get-Date).ToString('o')
    base_url = $BaseUrl
    scenario = 'Lectura local de inicio, catálogo y filtro por agua salada; sin escrituras ni pagos.'
    total_requests = $samples.Count
    parallel_requests = $samples.Count
    all_successful = [bool] (@($samples | Where-Object { -not $_.success }).Count -eq 0)
    latency_ms = [ordered]@{
        min = [Math]::Round($latencies[0], 2)
        median = [Math]::Round($latencies[[Math]::Floor(($latencies.Count - 1) / 2)], 2)
        p95 = [Math]::Round($latencies[$p95Index], 2)
        max = [Math]::Round($latencies[-1], 2)
    }
    samples = @($samples)
    limitation = 'Muestra de humo local; no sustituye la carga sostenida de 500 usuarios con JMeter.'
}

$outputRoot = Join-Path (Split-Path -Parent $PSScriptRoot) 'storage/app/testing'
New-Item -ItemType Directory -Path $outputRoot -Force | Out-Null
$reportPath = Join-Path $outputRoot ('AQUARIUM_HTTP_SMOKE_' + (Get-Date -Format 'yyyyMMdd_HHmmss') + '.json')
$report | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $reportPath -Encoding utf8
$report | ConvertTo-Json -Depth 5
Write-Output "Reporte: $reportPath"
if (-not $report.all_successful) { exit 1 }
