<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\SimulatedPayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClinicAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_book_a_consultation_with_local_simulation_and_view_it(): void
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');

        $this->actingAs($client)->post('/citas', $this->booking($veterinarian))
            ->assertRedirect('/citas');

        $appointment = Appointment::firstOrFail();
        $payment = SimulatedPayment::firstOrFail();
        $this->assertSame(Appointment::STATUS_SCHEDULED, $appointment->status);
        $this->assertSame('simulado_local', $payment->method);
        $this->assertSame('cargo_simulado', $payment->status);
        $this->assertSame((float) config('aquarium.simulated_consultation_fee'), (float) $payment->amount);

        $this->get('/citas')->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Appointments/ClientIndex')
            ->has('appointments.data', 1)
            ->where('appointments.data.0.patient_name', 'Nemo'));
    }

    public function test_a_veterinarian_cannot_be_booked_twice_for_the_same_time(): void
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');
        $this->actingAs($client)->post('/citas', $this->booking($veterinarian))->assertRedirect('/citas');
        $this->from('/citas')->post('/citas', $this->booking($veterinarian))
            ->assertRedirect('/citas')->assertSessionHasErrors('scheduled_at');

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_booking_is_limited_to_the_daily_appointment_window(): void
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');

        foreach (['08:30', '19:01', '21:00'] as $time) {
            $payload = $this->booking($veterinarian);
            $payload['scheduled_at'] = now()->addDays(3)->setTimeFromTimeString($time)->toDateTimeString();

            $this->actingAs($client)->from('/citas')->post('/citas', $payload)
                ->assertRedirect('/citas')->assertSessionHasErrors('scheduled_at');
        }

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_booking_window_includes_nine_am_and_seven_pm_when_available(): void
    {
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $veterinarian = $this->makeUser(User::ROLE_VETERINARIO, 'vet@example.test');

        foreach (['09:00', '19:00'] as $index => $time) {
            $payload = $this->booking($veterinarian);
            $payload['scheduled_at'] = now()->addDays(3 + $index)->setTimeFromTimeString($time)->toDateTimeString();

            $this->actingAs($client)->post('/citas', $payload)->assertRedirect('/citas');
        }

        $this->assertDatabaseCount('appointments', 2);
    }

    public function test_client_cancellation_registers_a_simulated_refund(): void
    {
        [$client, $veterinarian, $appointment] = $this->makeAppointment();

        $this->actingAs($client)->patch("/citas/{$appointment->id}/cancelar", [
            'cancellation_reason' => 'Cambio de horario',
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_CANCELLED_CLIENT]);
        $this->assertDatabaseHas('simulated_payments', [
            'appointment_id' => $appointment->id,
            'status' => 'reembolsado_simulado',
            'refund_reason' => 'Cancelación solicitada por cliente: Cambio de horario',
        ]);
    }

    public function test_veterinarian_sees_patient_data_and_can_save_diagnosis_and_prescription(): void
    {
        [$client, $veterinarian, $appointment] = $this->makeAppointment();

        $this->actingAs($veterinarian)->get('/veterinaria/citas')->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Appointments/VeterinaryIndex')
                ->where('appointments.data.0.client.email', 'client@example.test')
                ->where('appointments.data.0.patient_name', 'Nemo'));

        $this->patch("/veterinaria/citas/{$appointment->id}/consulta", [
            'diagnosis' => 'Infección leve; requiere vigilancia.',
            'prescription' => 'Tratamiento de prueba durante cinco días.',
            'veterinarian_notes' => 'Revisar calidad del agua en seguimiento.',
            'clinical_data' => [
                'temperature_c' => '25.5', 'ph' => '8.1', 'ammonia_mg_l' => '0.1',
                'medication' => 'Medicamento de prueba', 'dose' => '1 ml', 'route' => 'oral',
                'frequency' => 'cada 24 horas', 'duration_days' => '5',
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => Appointment::STATUS_COMPLETED,
            'diagnosis' => 'Infección leve; requiere vigilancia.',
            'prescription' => 'Tratamiento de prueba durante cinco días.',
        ]);

        $appointment->refresh();
        $this->assertSame('25.5', $appointment->clinical_data['temperature_c']);
        $this->assertSame('1 ml', $appointment->clinical_data['dose']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $appointment->clinical_record_hash);
        $this->assertNotNull($appointment->clinical_recorded_at);
    }

    public function test_completed_clinical_record_pdf_is_available_to_the_client_and_assigned_veterinarian(): void
    {
        [$client, $veterinarian, $appointment] = $this->makeAppointment();
        $appointment->update([
            'status' => Appointment::STATUS_COMPLETED,
            'diagnosis' => 'Prueba clínica del expediente.',
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
        [$client, $veterinarian, $appointment] = $this->makeAppointment();
        $otherClient = $this->makeUser(User::ROLE_CLIENTE, 'other@example.test');
        $admin = $this->makeUser(User::ROLE_ADMINISTRADOR, 'admin@example.test');

        $this->actingAs($otherClient)->get("/expedientes/clinicos/{$appointment->id}.pdf")->assertForbidden();
        $this->actingAs($admin)->get("/expedientes/clinicos/{$appointment->id}.pdf")->assertForbidden();
        $this->actingAs($client)->get("/expedientes/clinicos/{$appointment->id}.pdf")->assertNotFound();
    }

    public function test_no_show_keeps_the_simulated_charge_and_creates_no_refund(): void
    {
        [$client, $veterinarian, $appointment] = $this->makeAppointment(now()->subHour());

        $this->actingAs($veterinarian)->patch("/veterinaria/citas/{$appointment->id}/inasistencia")
            ->assertRedirect();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_NO_SHOW]);
        $this->assertDatabaseHas('simulated_payments', ['appointment_id' => $appointment->id, 'status' => 'cargo_simulado', 'refunded_at' => null]);
    }

    public function test_veterinarian_cancellation_registers_a_simulated_refund(): void
    {
        [$client, $veterinarian, $appointment] = $this->makeAppointment();

        $this->actingAs($veterinarian)->patch("/veterinaria/citas/{$appointment->id}/cancelar", [
            'cancellation_reason' => 'Emergencia clínica',
        ])->assertRedirect();

        $this->assertDatabaseHas('appointments', ['id' => $appointment->id, 'status' => Appointment::STATUS_CANCELLED_VET]);
        $this->assertDatabaseHas('simulated_payments', ['appointment_id' => $appointment->id, 'status' => 'reembolsado_simulado']);
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
            'consultation_fee' => config('aquarium.simulated_consultation_fee'),
        ]);
        SimulatedPayment::create([
            'appointment_id' => $appointment->id,
            'reference' => 'SIM-TEST-'.$appointment->id,
            'amount' => $appointment->consultation_fee,
            'method' => 'simulado_local',
            'status' => 'cargo_simulado',
        ]);

        return [$client, $veterinarian, $appointment];
    }

    private function booking(User $veterinarian): array
    {
        return [
            'veterinarian_id' => $veterinarian->id,
            'patient_name' => 'Nemo',
            'species' => 'Pez payaso Ocellaris',
            'symptoms' => 'Menor actividad y apetito desde ayer.',
            'scheduled_at' => now()->addDays(3)->setTime(12, 0)->toDateTimeString(),
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
