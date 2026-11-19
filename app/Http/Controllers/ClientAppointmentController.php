<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\StripePaymentAttempt;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Support\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientAppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        Appointment::query()->where('status', Appointment::STATUS_PAYMENT_PENDING)
            ->whereHas('stripePaymentAttempt', fn ($query) => $query->where('expires_at', '<=', now()))
            ->update(['status' => Appointment::STATUS_PAYMENT_EXPIRED]);

        $appointments = Appointment::query()->with(['veterinarian:id,name,email', 'payment:id,appointment_id,reference,amount,status,refunded_at', 'stripePaymentAttempt:id,appointment_id,amount,status,paid_at,refunded_at,refund_reason,expires_at,stripe_mode'])
            ->where('client_id', $request->user()->id)
            ->orderByDesc('scheduled_at')
            ->paginate(8)
            ->withQueryString();

        return Inertia::render('Appointments/ClientIndex', [
            'appointments' => $appointments,
            'veterinarians' => User::query()->where('role', User::ROLE_VETERINARIO)->orderBy('name')->get(['id', 'name']),
            'consultationFee' => config('aquarium.consultation_fee'),
            'stripe' => [
                'enabled' => app(StripePaymentService::class)->isConfigured(),
                'publishableKey' => app(StripePaymentService::class)->isConfigured() ? config('services.stripe.key') : null,
                'mode' => app(StripePaymentService::class)->mode(),
            ],
        ]);
    }

    public function createIntent(Request $request, StripePaymentService $stripe): JsonResponse
    {
        if (! $stripe->isConfigured()) {
            return response()->json(['message' => 'Stripe no está habilitado para el modo configurado en este entorno.'], 503);
        }

        $data = $request->validate([
            'request_key' => ['required', 'uuid'],
            'veterinarian_id' => ['required', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', User::ROLE_VETERINARIO))],
            'patient_name' => ['required', 'string', 'max:120'],
            'species' => ['required', 'string', 'max:120'],
            'symptoms' => ['required', 'string', 'max:3000'],
            'scheduled_at' => ['required', 'date', 'after:now'],
        ]);

        $scheduledAt = Carbon::parse($data['scheduled_at']);
        if ($scheduledAt->format('H:i') < '09:00' || $scheduledAt->format('H:i') > '19:00') {
            throw ValidationException::withMessages(['scheduled_at' => 'La agenda permite reservar únicamente entre las 09:00 y las 19:00, sujeto a disponibilidad del veterinario.']);
        }

        $attempt = StripePaymentAttempt::query()->with('appointment')->where('client_request_key', $data['request_key'])->first();
        if ($attempt) {
            abort_unless($attempt->appointment && (int) $attempt->appointment->client_id === (int) $request->user()->id, 404);
            if (! $attempt->payment_intent_id || $attempt->expires_at?->isPast()) {
                return response()->json(['message' => 'La reserva temporal venció. Elige de nuevo el horario e intenta otra vez.'], 409);
            }
            try {
                $intent = $stripe->retrievePaymentIntent($attempt->payment_intent_id);
            } catch (\Throwable $exception) {
                report($exception);
                return response()->json(['message' => 'No se pudo recuperar el pago de prueba.'], 502);
            }

            return response()->json($this->intentPayload($attempt, $intent));
        }

        $feeCents = (int) round((float) config('aquarium.consultation_fee') * 100);
        $attempt = DB::transaction(function () use ($request, $data, $scheduledAt, $feeCents, $stripe) {
            User::query()->whereKey($data['veterinarian_id'])->lockForUpdate()->firstOrFail();
            $alreadyReserved = Appointment::query()
                ->where('veterinarian_id', $data['veterinarian_id'])
                ->where('scheduled_at', $scheduledAt)
                ->where(function ($query) {
                    $query->where('status', Appointment::STATUS_SCHEDULED)
                        ->orWhere(function ($pending) {
                            $pending->where('status', Appointment::STATUS_PAYMENT_PENDING)
                                ->whereHas('stripePaymentAttempt', fn ($attemptQuery) => $attemptQuery->where('expires_at', '>', now()));
                        });
                })
                ->lockForUpdate()
                ->exists();

            if ($alreadyReserved) {
                throw ValidationException::withMessages(['scheduled_at' => 'Ese horario ya está reservado o temporalmente apartado para completar un pago. Elige otra fecha u hora.']);
            }

            $appointment = Appointment::create([
                'veterinarian_id' => $data['veterinarian_id'],
                'patient_name' => $data['patient_name'],
                'species' => $data['species'],
                'symptoms' => $data['symptoms'],
                'client_id' => $request->user()->id,
                'scheduled_at' => $scheduledAt,
                'status' => Appointment::STATUS_PAYMENT_PENDING,
                'consultation_fee' => number_format($feeCents / 100, 2, '.', ''),
            ]);

            return StripePaymentAttempt::create([
                'appointment_id' => $appointment->id,
                'client_request_key' => $data['request_key'],
                'amount' => number_format($feeCents / 100, 2, '.', ''),
                'currency' => 'mxn',
                'status' => 'creating',
                'stripe_mode' => $stripe->mode(),
                'session_hash' => hash('sha256', 'appointment-client-'.$request->user()->id),
                'expires_at' => now()->addMinutes(30),
            ])->load('appointment');
        });

        try {
            $intent = $stripe->createPaymentIntent(
                $feeCents,
                (string) $request->user()->email,
                (int) $attempt->appointment_id,
                'AQ-CITA-'.$attempt->appointment_id,
                'appointment',
            );
        } catch (\Throwable $exception) {
            report($exception);
            $attempt->update(['status' => 'creation_failed', 'expires_at' => now()]);
            $attempt->appointment->update(['status' => Appointment::STATUS_PAYMENT_EXPIRED]);
            return response()->json(['message' => 'Stripe no pudo preparar el pago. Revisa claves y Stripe CLI.'], 502);
        }

        $attempt->update(['payment_intent_id' => $intent['id'], 'status' => $intent['status'] ?? 'requires_payment_method']);

        return response()->json($this->intentPayload($attempt->fresh('appointment'), $intent));
    }

    private function intentPayload(StripePaymentAttempt $attempt, array $intent): array
    {
        return [
            'client_secret' => $intent['client_secret'],
            'order_number' => 'Cita AQ-'.$attempt->appointment_id,
            'amount' => $attempt->amount,
            'currency' => 'MXN',
            'return_url' => url('/citas?stripe_appointment='.$attempt->appointment_id),
            'mode' => $attempt->stripe_mode ?? 'test',
            'appointment_id' => $attempt->appointment_id,
        ];
    }
    public function cancel(Request $request, Appointment $appointment, StripePaymentService $stripe): RedirectResponse
    {
        abort_unless($appointment->client_id === $request->user()->id, 404);
        abort_unless($appointment->status === Appointment::STATUS_SCHEDULED, 409, 'Solo se pueden cancelar citas programadas.');

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:180'],
        ]);

        $stripePayment = $appointment->stripePaymentAttempt()->where('status', 'succeeded')->first();

        DB::transaction(function () use ($appointment, $data) {
            $lockedAppointment = Appointment::query()->lockForUpdate()->findOrFail($appointment->id);
            if ($lockedAppointment->status !== Appointment::STATUS_SCHEDULED) {
                throw ValidationException::withMessages(['appointment' => 'La cita ya no está disponible para cancelación.']);
            }

            $lockedAppointment->update([
                'status' => Appointment::STATUS_CANCELLED_CLIENT,
                'cancellation_reason' => $data['cancellation_reason'],
            ]);
        });

        if ($stripePayment) {
            $reason = 'Cancelación solicitada por cliente: '.$data['cancellation_reason'];
            $stripePayment->update(['status' => 'refund_pending', 'refund_reason' => $reason]);
            try {
                $refundStatus = $stripe->refundPaymentIntent((string) $stripePayment->payment_intent_id, 'appointment', (int) $appointment->id);
                $stripePayment->update(['status' => $refundStatus === 'succeeded' ? 'refunded' : 'refund_pending', 'refunded_at' => $refundStatus === 'succeeded' ? now() : null]);
                return back()->with($refundStatus === 'succeeded' ? 'success' : 'warning', $refundStatus === 'succeeded'
                    ? 'Cita cancelada y reembolso de prueba confirmado por Stripe.'
                    : 'Cita cancelada. Stripe recibió la solicitud de reembolso y aún la está procesando.');
            } catch (\Throwable $exception) {
                report($exception);
                return back()->with('warning', 'La cita se canceló, pero Stripe no confirmó el reembolso. Revisa el log local y el Dashboard de pruebas.');
            }
        }

        $appointment->payment()->update([
            'status' => 'legacy_cancelled',
            'refund_reason' => 'Registro histórico simulado; no existió cargo en Stripe.',
        ]);
        return back()->with('success', 'Cita cancelada. Este registro histórico no tuvo un cobro de Stripe que reembolsar.');
    }
}
