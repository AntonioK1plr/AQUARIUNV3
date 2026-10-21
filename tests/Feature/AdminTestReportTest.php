<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminTestReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrator_can_view_local_test_reports(): void
    {
        Storage::fake('local');

        $this->get('/admin/pruebas')->assertRedirect('/acceso');

        $warehouse = $this->user(User::ROLE_ALMACENISTA, 'warehouse-report@example.test');
        $this->actingAs($warehouse)->get('/admin/pruebas')->assertForbidden();

        $administrator = $this->user(User::ROLE_ADMINISTRADOR, 'admin-report@example.test');
        $this->actingAs($administrator)->get('/admin/pruebas')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Testing/Index')
                ->where('report', null)
                ->where('command', 'scripts/php-local.ps1 artisan aquarium:tests:report'));
    }

    public function test_administrator_can_view_the_latest_local_http_smoke_report(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('testing/AQUARIUM_HTTP_SMOKE_20261019_160833.json', json_encode([
            'generated_at' => '2026-10-19T16:08:33-06:00',
            'base_url' => 'http://127.0.0.1:8001',
            'total_requests' => 6,
            'all_successful' => true,
            'latency_ms' => ['min' => 10, 'median' => 12, 'p95' => 15, 'max' => 18],
            'samples' => [['success' => true], ['success' => true]],
        ]));

        $administrator = $this->user(User::ROLE_ADMINISTRADOR, 'admin-performance@example.test');
        $this->actingAs($administrator)->get('/admin/pruebas')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Testing/Index')
                ->where('performanceReport.total_requests', 6)
                ->where('performanceReport.latency_ms.p95', 15)
                ->has('performanceHistory', 1)
                ->where('smokeCommand', 'scripts/http-smoke-local.ps1'));
    }

    public function test_administrator_can_view_the_separate_postgresql_test_report(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('testing/postgres-latest.json', json_encode([
            'generated_at' => '2026-10-19T16:18:00-06:00',
            'database' => 'aquarium_codex_test_20261019',
            'database_version' => 'PostgreSQL 16.15',
            'totals' => ['total' => 50, 'passed' => 50, 'failed' => 0, 'errors' => 0],
            'assertions' => 568,
            'duration_seconds' => 6.75,
            'junit_file' => 'AQUARIUM_POSTGRES_TEST_REPORT_20261019_161800.xml',
        ]));

        $administrator = $this->user(User::ROLE_ADMINISTRADOR, 'admin-postgres-report@example.test');
        $this->actingAs($administrator)->get('/admin/pruebas')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Testing/Index')
                ->where('postgresReport.database', 'aquarium_codex_test_20261019')
                ->where('postgresReport.totals.passed', 50)
                ->where('postgresReport.assertions', 568)
                ->has('postgresHistory', 0));
    }

    public function test_administrator_can_view_selenium_and_jmeter_evidence(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('testing/selenium/latest.json', json_encode([
            'suite' => 'selenium-public-local',
            'base_url' => 'http://127.0.0.1:8001',
            'finished_at' => '2026-10-19T22:47:00Z',
            'status' => 'passed',
            'scenarios' => [['name' => 'Inicio público', 'status' => 'passed', 'duration_ms' => 550]],
        ]));
        Storage::disk('local')->put('testing/jmeter/latest.json', json_encode([
            'suite' => 'jmeter-public-local',
            'generated_at' => '2026-10-19T16:49:00-06:00',
            'base_url' => 'http://127.0.0.1:8001',
            'configuration' => ['virtual_users' => 5, 'duration_seconds' => 10],
            'status' => 'passed',
            'totals' => ['requests' => 36, 'succeeded' => 36, 'failed' => 0],
            'elapsed_ms' => ['min' => 319, 'average' => 1212, 'p95' => 1729, 'max' => 1729],
            'jtl_file' => 'aquarium.jtl',
            'dashboard' => 'dashboard/index.html',
            'limitation' => 'Muestra local de desarrollo.',
        ]));

        $administrator = $this->user(User::ROLE_ADMINISTRADOR, 'admin-browser-report@example.test');
        $this->actingAs($administrator)->get('/admin/pruebas')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Testing/Index')
                ->where('seleniumReport.status', 'passed')
                ->where('seleniumReport.scenarios.0.name', 'Inicio público')
                ->where('jmeterReport.totals.requests', 36)
                ->where('jmeterReport.elapsed_ms.p95', 1729)
                ->where('seleniumCommand', 'npm run test:e2e')
                ->where('jmeterCommand', 'scripts/run-jmeter-local.ps1'));
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'AcuarioSeguro123',
            'role' => $role,
        ]);
    }
}
