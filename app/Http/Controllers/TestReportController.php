<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TestReportController extends Controller
{
    public function index(): Response
    {
        $disk = Storage::disk('local');
        $report = $disk->exists('testing/latest.json') ? json_decode($disk->get('testing/latest.json'), true) : null;
        $history = collect($disk->allFiles('testing'))
            ->filter(fn ($path) => str_starts_with(basename($path), 'AQUARIUM_TEST_REPORT_') && pathinfo($path, PATHINFO_EXTENSION) === 'json')
            ->sortByDesc(fn ($path) => basename($path))
            ->take(12)
            ->map(fn ($path) => [
                'filename' => basename($path),
                'generated_at' => date(DATE_ATOM, $disk->lastModified($path)),
            ])->values();
        $smokeFiles = collect($disk->allFiles('testing'))
            ->filter(fn ($path) => str_starts_with(basename($path), 'AQUARIUM_HTTP_SMOKE_') && pathinfo($path, PATHINFO_EXTENSION) === 'json')
            ->sortByDesc(fn ($path) => basename($path));
        $performanceReport = $smokeFiles->first()
            ? json_decode($disk->get($smokeFiles->first()), true)
            : null;
        $performanceHistory = $smokeFiles->take(10)->map(fn ($path) => [
            'filename' => basename($path),
            'generated_at' => date(DATE_ATOM, $disk->lastModified($path)),
        ])->values();
        $postgresReport = $disk->exists('testing/postgres-latest.json')
            ? json_decode($disk->get('testing/postgres-latest.json'), true)
            : null;
        $postgresHistory = collect($disk->allFiles('testing'))
            ->filter(fn ($path) => str_starts_with(basename($path), 'AQUARIUM_POSTGRES_TEST_REPORT_') && pathinfo($path, PATHINFO_EXTENSION) === 'json')
            ->sortByDesc(fn ($path) => basename($path))
            ->take(10)
            ->map(fn ($path) => [
                'filename' => basename($path),
                'generated_at' => date(DATE_ATOM, $disk->lastModified($path)),
            ])->values();
        $seleniumReport = $disk->exists('testing/selenium/latest.json')
            ? json_decode($disk->get('testing/selenium/latest.json'), true)
            : null;
        $seleniumHistory = collect($disk->allFiles('testing/selenium'))
            ->filter(fn ($path) => str_starts_with(basename($path), 'selenium-') && pathinfo($path, PATHINFO_EXTENSION) === 'json')
            ->sortByDesc(fn ($path) => basename($path))
            ->take(10)
            ->map(fn ($path) => [
                'filename' => basename($path),
                'generated_at' => date(DATE_ATOM, $disk->lastModified($path)),
            ])->values();
        $jmeterReport = $disk->exists('testing/jmeter/latest.json')
            ? json_decode($disk->get('testing/jmeter/latest.json'), true)
            : null;
        $jmeterHistory = collect($disk->allFiles('testing/jmeter'))
            ->filter(fn ($path) => str_starts_with(basename($path), 'AQUARIUM_JMETER_REPORT_') && pathinfo($path, PATHINFO_EXTENSION) === 'json')
            ->sortByDesc(fn ($path) => basename($path))
            ->take(10)
            ->map(fn ($path) => [
                'filename' => basename($path),
                'generated_at' => date(DATE_ATOM, $disk->lastModified($path)),
            ])->values();

        return Inertia::render('Admin/Testing/Index', [
            'report' => $report,
            'history' => $history,
            'performanceReport' => $performanceReport,
            'performanceHistory' => $performanceHistory,
            'postgresReport' => $postgresReport,
            'postgresHistory' => $postgresHistory,
            'seleniumReport' => $seleniumReport,
            'seleniumHistory' => $seleniumHistory,
            'jmeterReport' => $jmeterReport,
            'jmeterHistory' => $jmeterHistory,
            'command' => 'scripts/php-local.ps1 artisan aquarium:tests:report',
            'smokeCommand' => 'scripts/http-smoke-local.ps1',
            'seleniumCommand' => 'npm run test:e2e',
            'jmeterCommand' => 'scripts/run-jmeter-local.ps1',
        ]);
    }
}
