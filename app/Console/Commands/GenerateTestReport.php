<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

class GenerateTestReport extends Command
{
    protected $signature = 'aquarium:tests:report';

    protected $description = 'Ejecuta PHPUnit aislado en SQLite en memoria y guarda reportes locales JSON y JUnit.';

    public function handle(): int
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory('testing');
        $directory = $disk->path('testing');
        $timestamp = now()->format('Ymd_His');
        $xmlPath = $directory.DIRECTORY_SEPARATOR."AQUARIUM_TEST_REPORT_{$timestamp}.xml";
        $jsonPath = $directory.DIRECTORY_SEPARATOR."AQUARIUM_TEST_REPORT_{$timestamp}.json";
        $phpunit = base_path('vendor/bin/phpunit');

        if (! is_file($phpunit)) {
            $this->error('No se encontró PHPUnit en vendor/bin/phpunit.');

            return self::FAILURE;
        }

        $process = new Process([
            PHP_BINARY,
            $phpunit,
            '--configuration', base_path('phpunit.xml'),
            '--log-junit', $xmlPath,
        ], base_path(), [
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'CACHE_STORE' => 'array',
            'SESSION_DRIVER' => 'array',
        ]);
        $process->setTimeout(900);
        $process->run(function (string $type, string $buffer) {
            $this->output->write($buffer);
        });

        if (! is_file($xmlPath)) {
            $this->error('PHPUnit terminó sin crear el reporte JUnit.');

            return self::FAILURE;
        }

        $xml = simplexml_load_file($xmlPath);
        if (! $xml) {
            $this->error('No se pudo leer el reporte JUnit de PHPUnit.');

            return self::FAILURE;
        }

        $cases = [];
        foreach ($xml->xpath('//testcase') ?: [] as $testCase) {
            $status = 'passed';
            $detail = null;
            foreach (['failure' => 'failed', 'error' => 'error', 'skipped' => 'skipped'] as $element => $caseStatus) {
                if (isset($testCase->{$element}[0])) {
                    $status = $caseStatus;
                    $detail = trim((string) ($testCase->{$element}[0]['message'] ?? $testCase->{$element}[0]));
                    break;
                }
            }
            $className = (string) ($testCase['classname'] ?? '');
            $cases[] = [
                'name' => (string) ($testCase['name'] ?? 'Prueba'),
                'class' => $className,
                'module' => $this->moduleName($className),
                'status' => $status,
                'duration_seconds' => (float) ($testCase['time'] ?? 0),
                'detail' => $detail ? mb_substr($detail, 0, 600) : null,
            ];
        }

        $totals = ['total' => count($cases), 'passed' => 0, 'failed' => 0, 'errors' => 0, 'skipped' => 0];
        foreach ($cases as $case) {
            match ($case['status']) {
                'passed' => $totals['passed']++,
                'failed' => $totals['failed']++,
                'error' => $totals['errors']++,
                'skipped' => $totals['skipped']++,
            };
        }

        $aggregateSuite = $xml->testsuite[0] ?? null;
        $assertions = (int) ($aggregateSuite['assertions'] ?? 0);

        $report = [
            'generated_at' => now()->toIso8601String(),
            'environment' => 'Local · PHPUnit sobre SQLite en memoria',
            'duration_seconds' => (float) ($aggregateSuite['time'] ?? 0),
            'assertions' => $assertions,
            'totals' => $totals,
            'cases' => $cases,
            'junit_report' => basename($xmlPath),
        ];
        $encoded = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $disk->put("testing/AQUARIUM_TEST_REPORT_{$timestamp}.json", $encoded.PHP_EOL);
        $disk->put('testing/latest.json', $encoded.PHP_EOL);

        $this->newLine();
        $this->info("Resultado: {$totals['passed']}/{$totals['total']} pruebas aprobadas; {$assertions} aserciones.");
        $this->line("Reporte: {$jsonPath}");
        $this->line("JUnit: {$xmlPath}");

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }

    private function moduleName(string $className): string
    {
        return match (true) {
            str_contains($className, 'AuthenticationTest') => 'Acceso y roles',
            str_contains($className, 'PublicCatalogTest') => 'Catálogo público',
            str_contains($className, 'OperationsModulesTest') => 'Personal e inventario',
            str_contains($className, 'ClinicAppointmentsTest') => 'Clínica veterinaria',
            str_contains($className, 'PosSalesTest') => 'Punto de venta',
            str_contains($className, 'WebCartTest') => 'Carrito y checkout',
            str_contains($className, 'OrderFulfillmentTest') => 'Preparación y entrega',
            str_contains($className, 'AdminTestReportTest') => 'Reportes de pruebas',
            default => 'Base del sistema',
        };
    }
}
