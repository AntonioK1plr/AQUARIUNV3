<?php

namespace App\Http\Controllers;

use App\Models\CartReservation;
use App\Models\Appointment;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StripePaymentAttempt;
use App\Models\StripeWebhookEvent;
use App\Services\BiologicalCompatibilityService;
use App\Services\StripePaymentService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class StripeCheckoutController extends Controller
{
    public function createIntent(Request $request, BiologicalCompatibilityService $compatibility, StripePaymentService $stripe): JsonResponse
    {
        if (! $stripe->isConfigured()) {
            return response()->json(['message' => 'Stripe no está habilitado para el modo configurado en este entorno.'], 503);
        }

        $data = $request->validate([
            'request_key' => ['required', 'uuid'],
            'fulfillment_type' => ['required', Rule::in(['envio', 'click_collect'])],
            'compatibility_confirmed' => ['nullable', 'boolean'],
            'shipping_address' => ['required_if:fulfillment_type,envio', 'array'],
            'shipping_address.recipient' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:120'],
            'shipping_address.phone' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'regex:/^[0-9+() .-]{10,20}$/'],
            'shipping_address.street' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:160'],
            'shipping_address.exterior_number' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:20'],
            'shipping_address.interior_number' => ['nullable', 'string', 'max:20'],
            'shipping_address.neighborhood' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:120'],
            'shipping_address.borough' => ['nullable', 'string', 'max:120'],
            'shipping_address.postal_code' => ['required_if:fulfillment_type,envio', 'nullable', 'regex:/^[0-9]{5}$/'],
            'shipping_address.city' => ['required_if:fulfillment_type,envio', 'nullable', Rule::in(['Ciudad de México'])],
            'shipping_address.state' => ['required_if:fulfillment_type,envio', 'nullable', Rule::in(['Ciudad de México'])],
            'shipping_address.instructions' => ['nullable', 'string', 'max:500'],
        ]);

        $cart = $request->session()->get('cart', []);
        if (! count($cart)) {
            throw ValidationException::withMessages(['cart' => 'Agrega al menos un producto antes de pagar.']);
        }

        $cartProducts = Product::query()->whereIn('id', array_keys($cart))->get()->keyBy('id');
        $liveProductIds = $cartProducts->filter(fn (Product $product) => $product->isLive())->keys()->map(fn ($id) => (string) $id)->all();
        $acceptedIds = array_map('strval', array_keys($request->session()->get('responsible_care_acceptances', [])));
        if (array_diff($liveProductIds, $acceptedIds)) {
            throw ValidationException::withMessages(['care' => 'Falta aceptar el cuidado responsable de los ejemplares vivos.']);
        }

        $warnings = $compatibility->warnings($cartProducts->values());
        if ($warnings && ! ($data['compatibility_confirmed'] ?? false)) {
            throw ValidationException::withMessages(['compatibility' => 'Confirma que revisaste las advertencias de compatibilidad.']);
        }

        $sessionHash = $this->sessionHash($request);
        $attempt = StripePaymentAttempt::query()->where('client_request_key', $data['request_key'])->with('order')->first();
        if ($attempt && (int) $attempt->order->client_id !== (int) $request->user()->id) {
            abort(404);
        }

        if (! $attempt) {
            $attempt = DB::transaction(function () use ($request, $data, $cart, $warnings, $sessionHash, $stripe) {
                $products = Product::query()->whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');
                if ($products->count() !== count($cart)) {
                    throw ValidationException::withMessages(['cart' => 'Un producto ya no está disponible. Actualiza el carrito.']);
                }

                $subtotalCents = 0;
                foreach ($cart as $productId => $quantity) {
                    $product = $products->get((int) $productId);
                    $reservation = CartReservation::query()->where('session_hash', $sessionHash)->where('product_id', $product->id)
                        ->where('expires_at', '>', now())->lockForUpdate()->first();
                    $heldByOthers = CartReservation::query()->where('product_id', $product->id)->where('expires_at', '>', now())
                        ->where('session_hash', '!=', $sessionHash)->sum('quantity');
                    if (! $product->is_active || ! $reservation || $reservation->quantity < (int) $quantity || (int) $quantity > $product->stock - $heldByOthers) {
                        throw ValidationException::withMessages(['cart' => "La reserva de {$product->name} venció o el inventario cambió. Actualiza el carrito."]);
                    }
                    $subtotalCents += (int) round((float) $product->price * 100) * (int) $quantity;
                }

                $taxCents = (int) round($subtotalCents * 0.16);
                $totalCents = $subtotalCents + $taxCents;
                $orderNumber = 'AQ-WEB-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
                $pickupCode = $data['fulfillment_type'] === 'click_collect' ? 'AQ-'.Str::upper(Str::random(8)) : null;
                $order = Order::create([
                    'order_number' => $orderNumber,
                    'client_id' => $request->user()->id,
                    'cashier_id' => null,
                    'customer_name' => $request->user()->name,
                    'customer_email' => $request->user()->email,
                    'channel' => 'ecommerce',
                    'status' => 'pago_pendiente',
                    'subtotal' => $this->decimal($subtotalCents),
                    'tax_rate' => 16,
                    'tax' => $this->decimal($taxCents),
                    'total' => $this->decimal($totalCents),
                    'fulfillment_type' => $data['fulfillment_type'],
                    'fulfillment_status' => 'pago_pendiente',
                    'pickup_code' => $pickupCode,
                    'shipping_address' => $data['fulfillment_type'] === 'envio' ? $data['shipping_address'] : null,
                    'care_responsibility_accepted_at' => $request->session()->get('responsible_care_acceptances') ? now() : null,
                    'compatibility_acknowledged_at' => $warnings ? now() : null,
                    'compatibility_warnings' => $warnings ?: null,
                ]);

                foreach ($cart as $productId => $quantity) {
                    $product = $products->get((int) $productId);
                    $unitCents = (int) round((float) $product->price * 100);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'product_name' => $product->name,
                        'unit_price' => $this->decimal($unitCents),
                        'quantity' => (int) $quantity,
                        'line_total' => $this->decimal($unitCents * (int) $quantity),
                    ]);
                    CartReservation::query()->where('session_hash', $sessionHash)->where('product_id', $product->id)
                        ->update(['expires_at' => now()->addMinutes(60)]);
                }

                return StripePaymentAttempt::create([
                    'order_id' => $order->id,
                    'client_request_key' => $data['request_key'],
                    'amount' => $this->decimal($totalCents),
                    'currency' => 'mxn',
                    'status' => 'creating',
                    'stripe_mode' => $stripe->mode(),
                    'session_hash' => $sessionHash,
                ]);
            });
        }

        try {
            $intent = $attempt->payment_intent_id
                ? $stripe->retrievePaymentIntent($attempt->payment_intent_id)
                : $stripe->createPaymentIntent(
                    (int) round((float) $attempt->amount * 100),
                    (string) $attempt->order->customer_email,
                    (int) $attempt->order_id,
                    (string) $attempt->order->order_number,
                );
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => $exception->getMessage()], 502);
        }

        $attempt->update([
            'payment_intent_id' => $intent['id'],
            'status' => $intent['status'] ?? 'requires_payment_method',
        ]);

        return response()->json([
            'client_secret' => $intent['client_secret'],
            'order_number' => $attempt->order->order_number,
            'amount' => $attempt->amount,
            'currency' => 'MXN',
            'mode' => $stripe->mode(),
        ]);
    }

    public function webhook(Request $request, StripePaymentService $stripe): JsonResponse
    {
        $event = $stripe->verifyWebhook($request->getContent(), $request->header('Stripe-Signature'));
        if (! $event || empty($event['id']) || empty($event['type'])) {
            return response()->json(['message' => 'Firma de webhook no válida.'], 400);
        }

        try {
            DB::transaction(function () use ($event, $stripe) {
                $receipt = StripeWebhookEvent::query()->firstOrCreate(
                    ['event_id' => $event['id']],
                    ['event_type' => $event['type']],
                );
                if ($receipt->processed_at) {
                    return;
                }

                $intent = $event['data']['object'] ?? [];
                $paymentIntentId = $event['type'] === 'refund.updated' ? ($intent['payment_intent'] ?? null) : ($intent['id'] ?? null);
                $payment = StripePaymentAttempt::query()->where('payment_intent_id', $paymentIntentId)
                    ->lockForUpdate()->first();
                if ($payment) {
                    $eventLiveMode = (bool) ($intent['livemode'] ?? false);
                    if (($payment->stripe_mode === 'live') !== $eventLiveMode) {
                        $receipt->update(['processed_at' => now()]);
                        return;
                    }
                    if ($event['type'] === 'refund.updated') {
                        $refundSucceeded = ($intent['status'] ?? null) === 'succeeded';
                        $payment->update([
                            'status' => $refundSucceeded ? 'refunded' : (($intent['status'] ?? null) === 'failed' ? 'refund_failed' : 'refund_pending'),
                            'refunded_at' => $refundSucceeded ? now() : null,
                        ]);
                    } elseif ($payment->order_id) {
                        $order = Order::query()->whereKey($payment->order_id)->lockForUpdate()->firstOrFail();
                        if ($event['type'] === 'payment_intent.succeeded') {
                            $this->completeSuccessfulPayment($payment, $order, $intent, $stripe);
                        } elseif ($event['type'] === 'payment_intent.payment_failed') {
                            $payment->update([
                                'status' => 'requires_payment_method',
                                'failure_code' => $intent['last_payment_error']['code'] ?? 'payment_failed',
                            ]);
                        } elseif ($event['type'] === 'payment_intent.canceled') {
                            $payment->update(['status' => 'canceled']);
                            $order->update(['status' => 'pago_cancelado', 'fulfillment_status' => 'pago_cancelado']);
                            CartReservation::query()->where('session_hash', $payment->session_hash)->delete();
                        }
                    } elseif ($payment->appointment_id) {
                        $appointment = Appointment::query()->whereKey($payment->appointment_id)->lockForUpdate()->firstOrFail();
                        if ($event['type'] === 'payment_intent.succeeded') {
                            $this->completeSuccessfulAppointmentPayment($payment, $appointment, $intent, $stripe);
                        } elseif ($event['type'] === 'payment_intent.payment_failed') {
                            $payment->update([
                                'status' => 'requires_payment_method',
                                'failure_code' => $intent['last_payment_error']['code'] ?? 'payment_failed',
                            ]);
                        } elseif ($event['type'] === 'payment_intent.canceled') {
                            $payment->update(['status' => 'canceled']);
                            if ($appointment->status === Appointment::STATUS_PAYMENT_PENDING) {
                                $appointment->update(['status' => Appointment::STATUS_PAYMENT_EXPIRED]);
                            }
                        }
                    }
                }

                $receipt->update(['event_type' => $event['type'], 'processed_at' => now()]);
            });
        } catch (QueryException $exception) {
            // Stripe retries deliveries; a duplicate event is an acknowledged no-op.
            if (! StripeWebhookEvent::query()->where('event_id', $event['id'])->exists()) {
                throw $exception;
            }
        } catch (Throwable $exception) {
            report($exception);
            return response()->json(['message' => 'No se pudo conciliar el pago; Stripe puede reintentar el evento.'], 500);
        }

        return response()->json(['received' => true]);
    }

    private function completeSuccessfulPayment(StripePaymentAttempt $payment, Order $order, array $intent, StripePaymentService $stripe): void
    {
        if ((bool) ($intent['livemode'] ?? false) !== $stripe->isLiveMode()
            || strtolower((string) ($intent['currency'] ?? '')) !== 'mxn'
            || (int) ($intent['amount_received'] ?? 0) !== (int) round((float) $payment->amount * 100)) {
            throw new RuntimeException('El importe o el modo de Stripe no coincide con el pago local.');
        }

        $paidStatus = $stripe->isLiveMode() ? 'pagada_stripe' : 'pagada_stripe_test';
        if ($order->status === $paidStatus) {
            return;
        }
        if ($order->status !== 'pago_pendiente') {
            return;
        }

        $items = $order->items()->get();
        $products = Product::query()->whereIn('id', $items->pluck('product_id'))->lockForUpdate()->get()->keyBy('id');
        $stockAvailable = true;
        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            $reservation = CartReservation::query()->where('session_hash', $payment->session_hash)
                ->where('product_id', $item->product_id)->where('expires_at', '>', now())->lockForUpdate()->first();
            $heldByOthers = CartReservation::query()->where('product_id', $item->product_id)->where('expires_at', '>', now())
                ->where('session_hash', '!=', $payment->session_hash)->sum('quantity');
            if (! $product || ! $product->is_active || ! $reservation || $reservation->quantity < $item->quantity
                || $item->quantity > $product->stock - $heldByOthers) {
                $stockAvailable = false;
                break;
            }
        }

        if (! $stockAvailable) {
            $refundStatus = $stripe->refundPaymentIntent((string) $payment->payment_intent_id, 'order', (int) $order->id);
            $payment->update([
                'status' => $refundStatus === 'succeeded' ? 'refunded_stock_issue' : 'refund_pending',
                'paid_at' => now(),
                'refunded_at' => $refundStatus === 'succeeded' ? now() : null,
                'refund_reason' => 'El inventario ya no tenía existencias disponibles al conciliar el pago.',
            ]);
            $order->update(['status' => $refundStatus === 'succeeded' ? 'reembolso_stock' : 'reembolso_stock_pendiente', 'fulfillment_status' => 'pago_reembolsado']);
            CartReservation::query()->where('session_hash', $payment->session_hash)->delete();
            return;
        }

        foreach ($items as $item) {
            $product = $products->get($item->product_id);
            $before = $product->stock;
            $product->decrement('stock', $item->quantity);
            InventoryMovement::create([
                'product_id' => $product->id,
                'recorded_by' => $order->client_id,
                'type' => 'venta_web',
                'quantity_delta' => -$item->quantity,
                'quantity_before' => $before,
                'quantity_after' => $product->fresh()->stock,
                'reason' => "Pedido web {$order->order_number}",
                'notes' => $order->fulfillment_type === 'click_collect'
                    ? "Click & Collect; pago Stripe {$stripe->mode()}; código {$order->pickup_code}"
                    : "Envío local; pago Stripe {$stripe->mode()}.",
            ]);
        }

        $order->update([
            'status' => $paidStatus,
            'fulfillment_status' => $order->fulfillment_type === 'click_collect' ? 'pendiente_recoleccion' : 'preparando_envio',
        ]);
        $payment->update(['status' => 'succeeded', 'paid_at' => now(), 'failure_code' => null]);
        CartReservation::query()->where('session_hash', $payment->session_hash)->delete();
    }

    private function completeSuccessfulAppointmentPayment(StripePaymentAttempt $payment, Appointment $appointment, array $intent, StripePaymentService $stripe): void
    {
        $validTestPayment = (bool) ($intent['livemode'] ?? false) === $stripe->isLiveMode()
            && strtolower((string) ($intent['currency'] ?? '')) === 'mxn'
            && (int) ($intent['amount_received'] ?? 0) === (int) round((float) $payment->amount * 100);
        if (! $validTestPayment) {
            throw new RuntimeException('El importe o el modo de Stripe no coincide con el pago de la cita.');
        }

        if ($payment->status === 'succeeded' || $payment->status === 'refunded') {
            return;
        }

        if ($appointment->status !== Appointment::STATUS_PAYMENT_PENDING || ! $payment->expires_at || $payment->expires_at->isPast()) {
            $refundStatus = $stripe->refundPaymentIntent((string) $payment->payment_intent_id, 'appointment', (int) $appointment->id);
            $payment->update(['status' => $refundStatus === 'succeeded' ? 'refunded_inactive_appointment' : 'refund_pending', 'paid_at' => now(), 'refunded_at' => $refundStatus === 'succeeded' ? now() : null, 'refund_reason' => 'La reserva de horario ya no estaba activa.']);
            if ($appointment->status === Appointment::STATUS_PAYMENT_PENDING) {
                $appointment->update(['status' => Appointment::STATUS_PAYMENT_EXPIRED]);
            }
            return;
        }

        $appointment->update(['status' => Appointment::STATUS_SCHEDULED]);
        $payment->update(['status' => 'succeeded', 'paid_at' => now(), 'failure_code' => null]);
    }

    private function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function sessionHash(Request $request): string
    {
        $owner = $request->session()->get('aquarium_cart_owner');
        if (! is_string($owner) || $owner === '') {
            $owner = (string) Str::uuid();
            $request->session()->put('aquarium_cart_owner', $owner);
        }

        return hash('sha256', $owner);
    }
}
