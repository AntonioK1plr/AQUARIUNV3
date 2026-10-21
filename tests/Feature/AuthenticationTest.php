<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_register_as_a_customer_with_a_hashed_strong_password(): void
    {
        $response = $this->post('/registro', [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.test',
            'password' => 'AcuarioSeguro123',
            'password_confirmation' => 'AcuarioSeguro123',
            'role' => User::ROLE_ADMINISTRADOR,
        ]);

        $user = User::where('email', 'ana@example.test')->firstOrFail();

        $response->assertRedirect('/cuenta');
        $this->assertAuthenticatedAs($user);
        $this->assertSame(User::ROLE_CLIENTE, $user->role);
        $this->assertTrue(Hash::check('AcuarioSeguro123', $user->password));
        $this->assertNotSame('AcuarioSeguro123', $user->password);
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $this->from('/registro')->post('/registro', [
            'name' => 'Ana Pérez',
            'email' => 'ana@example.test',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertRedirect('/registro')->assertSessionHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_guest_can_log_in_and_log_out(): void
    {
        $user = User::create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.test',
            'password' => 'AcuarioSeguro123',
            'role' => User::ROLE_CLIENTE,
        ]);

        $this->post('/acceso', [
            'email' => 'ana@example.test',
            'password' => 'AcuarioSeguro123',
        ])->assertRedirect('/cuenta');

        $this->assertAuthenticatedAs($user);
        $this->get('/cuenta')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Account/Show')
            ->where('user.email', 'ana@example.test')
            ->where('user.role', User::ROLE_CLIENTE));

        $this->post('/salir')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_role_middleware_restricts_staff_routes(): void
    {
        Route::get('/prueba-administrador', fn () => response('ok'))
            ->middleware(['auth', 'role:administrador']);

        $customer = User::create([
            'name' => 'Ana Pérez',
            'email' => 'ana@example.test',
            'password' => 'AcuarioSeguro123',
            'role' => User::ROLE_CLIENTE,
        ]);

        $this->actingAs($customer)->get('/prueba-administrador')->assertForbidden();

        $administrator = User::create([
            'name' => 'Admin AQUARIUM',
            'email' => 'admin@example.test',
            'password' => 'AcuarioSeguro123',
            'role' => User::ROLE_ADMINISTRADOR,
        ]);

        $this->actingAs($administrator)->get('/prueba-administrador')->assertOk()->assertSee('ok');
    }

    public function test_authenticated_write_actions_are_logged_without_request_payloads(): void
    {
        $client = User::create([
            'name' => 'Ana Pérez', 'email' => 'ana@example.test',
            'password' => 'AcuarioSeguro123', 'role' => User::ROLE_CLIENTE,
        ]);

        $auditLogger = new class
        {
            public ?array $context = null;

            public function info(string $message, array $context): void
            {
                $this->context = $context;
            }
        };
        Log::shouldReceive('channel')->once()->with('daily')->andReturn($auditLogger);

        $this->actingAs($client)->post('/salir')->assertRedirect('/');
        $this->assertSame($client->id, $auditLogger->context['uid']);
        $this->assertSame('POST salir', $auditLogger->context['action']);
        $this->assertSame('127.0.0.1', $auditLogger->context['ip']);
        $this->assertArrayHasKey('occurred_at', $auditLogger->context);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $auditLogger->context['sha256']);
        $this->assertArrayNotHasKey('password', $auditLogger->context);
    }

    public function test_user_can_update_profile_contact_details(): void
    {
        $client = User::create([
            'name' => 'Ana Pérez', 'email' => 'ana@example.test', 'password' => 'AcuarioSeguro123', 'role' => User::ROLE_CLIENTE,
        ]);

        $this->actingAs($client)->patch('/cuenta', [
            'name' => 'Ana Acuario', 'phone' => '5551234567', 'address' => 'Calle de prueba 123, CDMX',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $client->id, 'name' => 'Ana Acuario', 'phone' => '5551234567', 'address' => 'Calle de prueba 123, CDMX',
        ]);
    }

    public function test_user_can_request_and_apply_a_password_reset_token(): void
    {
        Notification::fake();
        $client = User::create([
            'name' => 'Ana Pérez', 'email' => 'ana@example.test', 'password' => 'AcuarioSeguro123', 'role' => User::ROLE_CLIENTE,
        ]);

        $this->get('/recuperar-contrasena')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/ForgotPassword'));
        $this->post('/recuperar-contrasena', ['email' => $client->email])->assertRedirect();
        Notification::assertSentTo($client, ResetPassword::class);
        $notification = Notification::sent($client, ResetPassword::class)->first();

        $this->get('/restablecer-contrasena/'.$notification->token.'?email='.urlencode($client->email))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/ResetPassword')->where('email', $client->email));
        $this->post('/restablecer-contrasena', [
            'token' => $notification->token,
            'email' => $client->email,
            'password' => 'NuevaAcuarioSeguro456',
            'password_confirmation' => 'NuevaAcuarioSeguro456',
        ])->assertRedirect('/acceso');

        $this->assertTrue(Hash::check('NuevaAcuarioSeguro456', $client->fresh()->password));
    }
}
