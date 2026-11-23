<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\StripePaymentAttempt;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class ClinicAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_pays_for_consultation_before_it_is_confirmed(): void
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');
        $event = $this->paymentSucceededEvent('pi_appointment_1', (int) config('aquarium.consultation_fee') * 100);
        $this->mockStripe($event, intentCount: 1);

        $this->actingAs($client)->postJson('/citas/pago/stripe/intento', $this->booking($veterinarian, 'a2a755ab-436c-44e7-8a23-c8f28553c0e9'))
            ->assertOk()->assertJsonPath('appointment_id', 1)->assertJsonPath('mode', 'test');

        $appointment = Appointment::firstOrFail();
        $attempt = StripePaymentAttempt::firstOrFail();
        $this->assertSame(Appointment::STATUS_PAYMENT_PENDING, $appointment->status);
        $this->assertSame('requires_payment_method', $attempt->status);
        $this->assertSame('pi_appointment_1', $attempt->payment_intent_id);

        $this->postJson('/webhooks/stripe', [], ['Stripe-Signature' => 'test-signature'])->assertOk();
        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_SCHEDULED]);
        $this->assertDatabaseHas('stripe_payment_attempts', ['id' => $attempt->id, 'status' => 'succeeded']);

        $this->actingAs($client)->get('/citas')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Appointments/ClientIndex')
            ->has('appointments.data', 1)
            ->where('appointments.data.0.patient_name', 'Nemo'));
    }

    public function test_veterinarian_cannot_be_reserved_twice_for_the_same_time(): void
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');
        $this->mockStripe(intentCount: 1);
        $payload = $this->booking($veterinarian, 'a2a755ab-436c-44e7-8a23-c8f28553c0e9');

        $this->actingAs($client)->postJson('/citas/pago/stripe/intento', $payload)->assertOk();
        $payload['request_key'] = '78a20125-328c-4ae0-829f-3d4fa068410d';
        $this->actingAs($client)->postJson('/citas/pago/stripe/intento', $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_booking_is_limited_to_the_daily_appointment_window(): void
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');
        $this->mockStripe();

        foreach (['08:30', '19:01', '21:00'] as $index => $time) {
            $payload = $this->booking($veterinarian, sprintf('a2a755ab-436c-44e7-8a23-c8f28553c0%02d', $index + 10));
            $payload['scheduled_at'] = now()->addDays(3)->setTimeFromTimeString($time)->toDateTimeString();
            $this->actingAs($client)->postJson('/citas/pago/stripe/intento', $payload)
                ->assertUnprocessable()->assertJsonValidationErrors('scheduled_at');
        }

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_window_includes_nine_am_and_seven_pm_when_available(): void
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');
        $this->mockStripe(intentCount: 2);

        foreach (['09:00', '19:00'] as $index => $time) {
            $payload = $this->booking($veterinarian, sprintf('a2a755ab-436c-44e7-8a23-c8f28553c1%02d', $index + 10));
            $payload['scheduled_at'] = now()->addDays(3 + $index)->setTimeFromTimeString($time)->toDateTimeString();
            $this->actingAs($client)->postJson('/citas/pago/stripe/intento', $payload)->assertOk();
        }

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_client_cancellation_requests_a_stripe_refund(): void
    {
        [$client, , $appointment] = $this->makeAppointment();
        $this->mockStripe(refundStatus: 'succeeded');

        $this->actingAs($client)->patch("/citas/{$appointment->id}/cancelar", [
            'cancellation_reason' => 'Cambio de horario',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_CANCELLED_CLIENT]);
        $this->assertDatabaseHas('stripe_payment_attempts', [
            'appointment_id' => $appointment->id,
            'status' => 'refunded',
            'refund_reason' => 'Cancelación solicitada por cliente: Cambio de horario',
        ]);
    }

    public function test_veterinarian_sees_patient_data_and_can_save_diagnosis_and_prescription(): void
    {
        [$client, $veterinarian, $appointment] = $this->makeAppointment();

        $this->actingAs($veterinarian)->get('/veterinaria/citas')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointments/VeterinaryIndex')
                ->where('appointments.data.0.client.email', $client->email)
                ->where('appointments.data.0.patient_name', 'Nemo'));

        $this->actingAs($veterinarian)->patch("/veterinaria/citas/{$appointment->id}/consulta", [
            'diagnosis' => 'Infección leve; requiere vigilancia.',
            'prescription' => 'Tratamiento durante cinco días.',
            'veterinarian_notes' => 'Revisar calidad del agua en seguimiento.',
            'clinical_data' => [
                'temperature_c' => '25.5', 'ph' => '8.1', 'ammonia_mg_l' => '0.1',
                'medication' => 'Medicamento indicado', 'dose' => '1 ml', 'route' => 'oral',
                'frequency' => 'cada 24 horas', 'duration_days' => '5',
            ],
        ])->assertRedirect();

        $appointment->refresh();
        $this->assertSame(Appointment::STATUS_COMPLETED, $appointment->status);
        $this->assertSame('25.5', $appointment->clinical_data['temperature_c']);
        $this->assertSame('1 ml', $appointment->clinical_data['dose']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $appointment->clinical_record_hash);
        $this->assertNotNull($appointment->clinical_recorded_at);
    }

    public function test_completed_clinical_record_pdf_is_available_to_client_and_assigned_veterinarian(): void
    {
        [$client, $veterinarian, $appointment] = $this->makeAppointment();
        $appointment->update([
            'status' => Appointment::STATUS_COMPLETED,
            'diagnosis' => 'Revisión clínica del expediente.',
            'prescription' => 'Observar durante siete días.',
            'clinical_record_hash' => str_repeat('a', 64),
            'clinical_recorded_at' => now(),
        ]);

        foreach ([$client, $veterinarian] as $authorizedUser) {
            $response = $this->actingAs($authorizedUser)->get("/expedientes/clinicos/{$appointment->id}.pdf");
            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF-', $response->getContent());
        }
    }

    public function test_clinical_record_pdf_is_private_and_only_available_after_completion(): void
    {
        [$client, , $appointment] = $this->makeAppointment();
        $otherClient = $this->makeUser(User::ROLE_CLIENTE, 'other@example.test');
        $admin = $this->makeUser(User::ROLE_ADMINISTRADOR, 'admin@example.test');

        $this->actingAs($otherClient)->get("/expedientes/clinicos/{$appointment->id}.pdf")->assertForbidden();
        $this->actingAs($admin)->get("/expedientes/clinicos/{$appointment->id}.pdf")->assertForbidden();
        $this->actingAs($client)->get("/expedientes/clinicos/{$appointment->id}.pdf")->assertNotFound();
    }

    public function test_no_show_keeps_the_confirmed_stripe_payment_without_refund(): void
    {
        [, $veterinarian, $appointment] = $this->makeAppointment(now()->subHour());
        $this->mockStripe();

        $this->actingAs($veterinarian)->patch("/veterinaria/citas/{$appointment->id}/inasistencia")
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_NO_SHOW]);
        $this->assertDatabaseHas('stripe_payment_attempts', ['appointment_id' => $appointment->id, 'status' => 'succeeded', 'refunded_at' => null]);
    }

    public function test_veterinarian_cancellation_requests_a_stripe_refund(): void
    {
        [, $veterinarian, $appointment] = $this->makeAppointment();
        $this->mockStripe(refundStatus: 'succeeded');

        $this->actingAs($veterinarian)->patch("/veterinaria/citas/{$appointment->id}/cancelar", [
            'cancellation_reason' => 'Emergencia clínica',
        ])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_CANCELLED_VET]);
        $this->assertDatabaseHas('stripe_payment_attempts', ['appointment_id' => $appointment->id, 'status' => 'refunded']);
    }

    private function makeAppointment(?\Illuminate\Support\Carbon $scheduledAt = null): array
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');
        $appointment = Appointment::create([
            'client_id' => $client->id,
            'veterinarian_id' => $veterinarian->id,
            'patient_name' => 'Nemo',
            'species' => 'Pez payaso Ocellaris',
            'symptoms' => 'Presenta menor actividad.',
            'scheduled_at' => $scheduledAt ?? now()->addDays(2),
            'status' => Appointment::STATUS_SCHEDULED,
            'consultation_fee' => config('aquarium.consultation_fee'),
        ]);
        StripePaymentAttempt::create([
            'appointment_id' => $appointment->id,
            'client_request_key' => (string) \Illuminate\Support\Str::uuid(),
            'payment_intent_id' => 'pi_paid_appointment_'.$appointment->id,
            'amount' => $appointment->consultation_fee,
            'currency' => 'mxn',
            'status' => 'succeeded',
            'session_hash' => hash('sha256', 'appointment-client-'.$client->id),
            'stripe_mode' => 'test',
            'paid_at' => now(),
            'expires_at' => now()->addMinutes(30),
        ]);

        return [$client, $veterinarian, $appointment];
    }

    private function booking(User $veterinarian, string $requestKey): array
    {
        return [
            'request_key' => $requestKey,
            'veterinarian_id' => $veterinarian->id,
            'patient_name' => 'Nemo',
            'species' => 'Pez payaso Ocellaris',
            'symptoms' => 'Menor actividad y apetito desde ayer.',
            'scheduled_at' => now()->addDays(3)->setTime(12, 0)->toDateTimeString(),
        ];
    }

    private function mockStripe(?array $event = null, int $intentCount = 0, string $refundStatus = 'succeeded'): void
    {
        $stripe = Mockery::mock(StripePaymentService::class);
        $stripe->shouldReceive('isConfigured')->andReturn(true);
        $stripe->shouldReceive('mode')->andReturn('test');
        $stripe->shouldReceive('isLiveMode')->andReturn(false);
        if ($intentCount > 0) {
            $stripe->shouldReceive('createPaymentIntent')->times($intentCount)->andReturn(
                ['id' => 'pi_appointment_1', 'status' => 'requires_payment_method', 'client_secret' => 'pi_appointment_1_secret_test'],
                ...array_fill(0, max(0, $intentCount - 1), ['id' => 'pi_appointment_next', 'status' => 'requires_payment_method', 'client_secret' => 'pi_appointment_next_secret_test']),
            );
        }
        if ($event !== null) {
            $stripe->shouldReceive('verifyWebhook')->once()->andReturn($event);
        }
        $stripe->shouldReceive('refundPaymentIntent')->andReturn($refundStatus);
        $this->app->instance(StripePaymentService::class, $stripe);
    }

    private function paymentSucceededEvent(string $intentId, int $amountCents): array
    {
        return [
            'id' => 'evt_appointment_succeeded',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => [
                'id' => $intentId,
                'amount_received' => $amountCents,
                'currency' => 'mxn',
                'livemode' => false,
            ]],
        ];
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'phone' => '5551234567',
            'password' => 'AcuarioSeguro123',
            'role' => $role,
        ]);
    }
}
