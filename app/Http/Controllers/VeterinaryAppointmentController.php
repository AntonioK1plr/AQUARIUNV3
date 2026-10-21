<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VeterinaryAppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $appointments = Appointment::query()
            ->with(['client:id,name,email,phone', 'payment:id,appointment_id,reference,amount,status'])
            ->where('veterinarian_id', $request->user()->id)
            ->whereIn('status', [Appointment::STATUS_SCHEDULED, Appointment::STATUS_COMPLETED, Appointment::STATUS_NO_SHOW])
            ->orderByRaw("CASE WHEN status = 'programada' THEN 0 ELSE 1 END")
            ->orderBy('scheduled_at')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Appointments/VeterinaryIndex', [
            'appointments' => $appointments,
            'summary' => [
                'pending' => Appointment::where('veterinarian_id', $request->user()->id)->where('status', Appointment::STATUS_SCHEDULED)->count(),
                'completed' => Appointment::where('veterinarian_id', $request->user()->id)->where('status', Appointment::STATUS_COMPLETED)->count(),
                'noShows' => Appointment::where('veterinarian_id', $request->user()->id)->where('status', Appointment::STATUS_NO_SHOW)->count(),
            ],
        ]);
    }

    public function complete(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->veterinarian_id === $request->user()->id, 404);
        abort_unless($appointment->status === Appointment::STATUS_SCHEDULED, 409, 'La cita ya no está pendiente.');

        $data = $request->validate([
            'diagnosis' => ['required', 'string', 'max:5000'],
            'prescription' => ['nullable', 'string', 'max:5000'],
            'veterinarian_notes' => ['nullable', 'string', 'max:5000'],
            'clinical_data' => ['nullable', 'array'],
            'clinical_data.temperature_c' => ['nullable', 'numeric', 'min:0', 'max:60'],
            'clinical_data.ph' => ['nullable', 'numeric', 'min:0', 'max:14'],
            'clinical_data.ammonia_mg_l' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'clinical_data.nitrite_mg_l' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'clinical_data.nitrate_mg_l' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'clinical_data.weight_g' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'clinical_data.medication' => ['nullable', 'string', 'max:160'],
            'clinical_data.dose' => ['nullable', 'string', 'max:120'],
            'clinical_data.route' => ['nullable', 'string', 'max:120'],
            'clinical_data.frequency' => ['nullable', 'string', 'max:120'],
            'clinical_data.duration_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);

        $clinicalData = collect($data['clinical_data'] ?? [])->filter(fn ($value) => $value !== null && $value !== '')->all();
        $recordedAt = now();
        $recordPayload = [
            'appointment_id' => $appointment->id,
            'veterinarian_id' => $request->user()->id,
            'diagnosis' => $data['diagnosis'],
            'prescription' => $data['prescription'] ?? null,
            'clinical_data' => $clinicalData,
            'recorded_at' => $recordedAt->toIso8601String(),
        ];

        $appointment->update([
            'diagnosis' => $data['diagnosis'],
            'prescription' => $data['prescription'] ?? null,
            'veterinarian_notes' => $data['veterinarian_notes'] ?? null,
            'clinical_data' => $clinicalData ?: null,
            'clinical_record_hash' => hash('sha256', json_encode($recordPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
            'clinical_recorded_at' => $recordedAt,
            'status' => Appointment::STATUS_COMPLETED,
        ]);

        return back()->with('success', 'Consulta guardada en el expediente de la cita.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->veterinarian_id === $request->user()->id, 404);
        abort_unless($appointment->status === Appointment::STATUS_SCHEDULED, 409, 'La cita ya no está pendiente.');

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:180'],
        ]);

        DB::transaction(function () use ($appointment, $data) {
            $lockedAppointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            if ($lockedAppointment->status !== Appointment::STATUS_SCHEDULED) {
                throw ValidationException::withMessages(['appointment' => 'La cita ya no está disponible para cancelación.']);
            }
            $lockedAppointment->update([
                'status' => Appointment::STATUS_CANCELLED_VET,
                'cancellation_reason' => $data['cancellation_reason'],
            ]);
            $lockedAppointment->payment()->update([
                'status' => 'reembolsado_simulado',
                'refund_reason' => 'Cancelación solicitada por veterinario: '.$data['cancellation_reason'],
                'refunded_at' => now(),
            ]);
        });

        return back()->with('success', 'Cita cancelada y reembolso simulado registrado.');
    }

    public function markNoShow(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->veterinarian_id === $request->user()->id, 404);
        if ($appointment->status !== Appointment::STATUS_SCHEDULED || $appointment->scheduled_at->isFuture()) {
            throw ValidationException::withMessages(['appointment' => 'Solo puedes marcar como inasistencia una cita programada cuya hora ya pasó.']);
        }

        $appointment->update([
            'status' => Appointment::STATUS_NO_SHOW,
            'cancellation_reason' => 'El paciente no se presentó; no aplica reembolso simulado.',
        ]);

        return back()->with('success', 'Inasistencia registrada. El cargo simulado se conserva conforme a la regla del proyecto.');
    }
}
