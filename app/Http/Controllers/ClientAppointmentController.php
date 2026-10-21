<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\SimulatedPayment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientAppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $appointments = Appointment::query()->with(['veterinarian:id,name,email', 'payment:id,appointment_id,reference,amount,status,refunded_at'])
            ->where('client_id', $request->user()->id)
            ->orderByDesc('scheduled_at')
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Appointments/ClientIndex', [
            'appointments' => $appointments,
            'veterinarians' => User::query()->where('role', User::ROLE_VETERINARIO)->orderBy('name')->get(['id', 'name']),
            'simulatedFee' => config('aquarium.simulated_consultation_fee'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'veterinarian_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_VETERINARIO))],
            'patient_name' => ['required', 'string', 'max:120'],
            'species' => ['required', 'string', 'max:120'],
            'symptoms' => ['required', 'string', 'max:3000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $requestedTime = Carbon::parse($data['scheduled_at'])->format('H:i');
        if ($requestedTime < '09:00' || $requestedTime > '19:00') {
            throw ValidationException::withMessages([
                'scheduled_at' => 'La agenda permite reservar únicamente entre las 09:00 y las 19:00, sujeto a disponibilidad del veterinario.',
            ]);
        }

        DB::transaction(function () use ($request, $data) {
            User::query()->whereKey($data['veterinarian_id'])->lockForUpdate()->firstOrFail();
            $scheduledAt = Carbon::parse($data['scheduled_at']);
            $alreadyReserved = Appointment::query()
                ->where('veterinarian_id', $data['veterinarian_id'])
                ->where('scheduled_at', $scheduledAt)
                ->where('status', Appointment::STATUS_SCHEDULED)
                ->lockForUpdate()
                ->exists();

            if ($alreadyReserved) {
                throw ValidationException::withMessages(['scheduled_at' => 'Ese horario ya está reservado. Elige otra fecha u hora.']);
            }

            $fee = config('aquarium.simulated_consultation_fee');
            $appointment = Appointment::create([
                ...$data,
                'client_id' => $request->user()->id,
                'scheduled_at' => $scheduledAt,
                'status' => Appointment::STATUS_SCHEDULED,
                'consultation_fee' => $fee,
            ]);

            SimulatedPayment::create([
                'appointment_id' => $appointment->id,
                'reference' => 'SIM-'.Str::upper(Str::random(14)),
                'amount' => $fee,
                'method' => 'simulado_local',
                'status' => 'cargo_simulado',
            ]);
        });

        return redirect()->route('appointments.index')->with('success', 'Cita apartada. El cargo mostrado es solo una simulación local.');
    }

    public function cancel(Request $request, Appointment $appointment): RedirectResponse
    {
        abort_unless($appointment->client_id === $request->user()->id, 404);
        abort_unless($appointment->status === Appointment::STATUS_SCHEDULED, 409, 'Solo se pueden cancelar citas programadas.');

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:180'],
        ]);

        DB::transaction(function () use ($appointment, $data) {
            $lockedAppointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            if ($lockedAppointment->status !== Appointment::STATUS_SCHEDULED) {
                throw ValidationException::withMessages(['appointment' => 'La cita ya no está disponible para cancelación.']);
            }

            $lockedAppointment->update([
                'status' => Appointment::STATUS_CANCELLED_CLIENT,
                'cancellation_reason' => $data['cancellation_reason'],
            ]);
            $lockedAppointment->payment()->update([
                'status' => 'reembolsado_simulado',
                'refund_reason' => 'Cancelación solicitada por cliente: '.$data['cancellation_reason'],
                'refunded_at' => now(),
            ]);
        });

        return back()->with('success', 'Cita cancelada. El reembolso quedó registrado como simulación local.');
    }
}
