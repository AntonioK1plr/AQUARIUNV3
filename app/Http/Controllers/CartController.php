<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\CartReservation;
use App\Models\SimulatedOrderPayment;
use App\Services\BiologicalCompatibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CartController extends Controller
{
    public function index(Request $request, BiologicalCompatibilityService $compatibility): Response
    {
        $cart = $request->session()->get('cart', []);
        $sessionHash = $this->sessionHash($request);
        $expiredIds = CartReservation::query()->where('session_hash', $sessionHash)->where('expires_at', '<=', now())->pluck('product_id')->all();
        if ($expiredIds) {
            CartReservation::query()->where('session_hash', $sessionHash)->whereIn('product_id', $expiredIds)->delete();
            foreach ($expiredIds as $id) {
                unset($cart[$id]);
            }
            $request->session()->put('cart', $cart);
            $accepted = $request->session()->get('responsible_care_acceptances', []);
            foreach ($expiredIds as $id) {
                unset($accepted[$id]);
            }
            $request->session()->put('responsible_care_acceptances', $accepted);
        }
        $products = Product::query()->whereIn('id', array_keys($cart))->where('is_active', true)->get()->keyBy('id');
        $heldByOthers = CartReservation::query()->whereIn('product_id', $products->keys())->where('expires_at', '>', now())
            ->where('session_hash', '!=', $sessionHash)->get()->groupBy('product_id')->map(fn ($rows) => $rows->sum('quantity'));
        $products->each(function (Product $product) use ($heldByOthers) {
            $product->setAttribute('available_stock', max(0, $product->stock - (int) $heldByOthers->get($product->id, 0)));
        });
        $lines = collect($cart)->map(function ($quantity, $id) use ($products) {
            $product = $products->get((int) $id);
            return $product ? [
                'product' => $product,
                'quantity' => min((int) $quantity, $product->stock),
            ] : null;
        })->filter(fn ($line) => $line && $line['quantity'] > 0)->values();
        $subtotalCents = $lines->sum(fn ($line) => (int) round((float) $line['product']->price * 100) * $line['quantity']);
        $taxCents = (int) round($subtotalCents * 0.16);
        $warnings = $compatibility->warnings($products->values()->filter(fn (Product $product) => isset($cart[$product->id])));
        $acceptedCare = $request->session()->get('responsible_care_acceptances', []);
        $reservedUntil = CartReservation::query()->where('session_hash', $sessionHash)->whereIn('product_id', array_keys($cart))
            ->where('expires_at', '>', now())->pluck('expires_at', 'product_id');

        if ($expiredIds) {
            $request->session()->flash('warning', 'El apartado de 30 minutos venció para algunos productos; revisa nuevamente las existencias.');
        }

        return Inertia::render('Cart/Index', [
            'lines' => $lines,
            'totals' => [
                'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
                'tax' => number_format($taxCents / 100, 2, '.', ''),
                'total' => number_format(($subtotalCents + $taxCents) / 100, 2, '.', ''),
            ],
            'authUser' => $request->user()?->only('name', 'email', 'role'),
            'compatibilityWarnings' => $warnings,
            'careAcceptedProductIds' => array_map('intval', array_keys($acceptedCare)),
            'reservedUntil' => $reservedUntil,
        ]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:100'],
            'care_responsibility_accepted' => ['nullable', 'boolean'],
        ]);
        $sessionHash = $this->sessionHash($request);
        $product = Product::query()->whereKey($data['product_id'])->where('is_active', true)->firstOrFail();
        if ($product->isLive() && ! ($data['care_responsibility_accepted'] ?? false)) {
            throw ValidationException::withMessages(['care' => 'Antes de agregar un ejemplar vivo, confirma la carta de cuidado responsable.']);
        }
        $cart = $request->session()->get('cart', []);
        $quantity = ($cart[$product->id] ?? 0) + ($data['quantity'] ?? 1);
        DB::transaction(function () use ($product, $quantity, $sessionHash) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            CartReservation::query()->where('expires_at', '<=', now())->delete();
            $heldByOthers = CartReservation::query()->where('product_id', $locked->id)->where('expires_at', '>', now())
                ->where('session_hash', '!=', $sessionHash)->sum('quantity');
            if (! $locked->is_active || $quantity > max(0, $locked->stock - $heldByOthers)) {
                throw ValidationException::withMessages(['product' => "No hay unidades disponibles suficientes de {$locked->name}; otras personas pueden tenerlas reservadas."]);
            }
            CartReservation::query()->updateOrCreate(
                ['session_hash' => $sessionHash, 'product_id' => $locked->id],
                ['quantity' => $quantity, 'expires_at' => now()->addMinutes(30)],
            );
        });
        $cart[$product->id] = $quantity;
        $request->session()->put('cart', $cart);
        if ($product->isLive()) {
            $acceptances = $request->session()->get('responsible_care_acceptances', []);
            $acceptances[$product->id] = now()->toIso8601String();
            $request->session()->put('responsible_care_acceptances', $acceptances);
        }

        return back()->with('success', 'Producto agregado al carrito.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:1000']]);
        $sessionHash = $this->sessionHash($request);
        DB::transaction(function () use ($product, $data, $sessionHash) {
            $locked = Product::query()->lockForUpdate()->findOrFail($product->id);
            $heldByOthers = CartReservation::query()->where('product_id', $locked->id)->where('expires_at', '>', now())
                ->where('session_hash', '!=', $sessionHash)->sum('quantity');
            if (! $locked->is_active || $data['quantity'] > max(0, $locked->stock - $heldByOthers)) {
                throw ValidationException::withMessages(['quantity' => 'La cantidad supera las existencias disponibles una vez consideradas las reservas activas.']);
            }
            CartReservation::query()->updateOrCreate(
                ['session_hash' => $sessionHash, 'product_id' => $locked->id],
                ['quantity' => $data['quantity'], 'expires_at' => now()->addMinutes(30)],
            );
        });
        $cart = $request->session()->get('cart', []);
        $cart[$product->id] = $data['quantity'];
        $request->session()->put('cart', $cart);

        return back();
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        $cart = $request->session()->get('cart', []);
        unset($cart[$product->id]);
        $request->session()->put('cart', $cart);
        CartReservation::query()->where('session_hash', $this->sessionHash($request))->where('product_id', $product->id)->delete();
        $acceptances = $request->session()->get('responsible_care_acceptances', []);
        unset($acceptances[$product->id]);
        $request->session()->put('responsible_care_acceptances', $acceptances);

        return back()->with('success', 'Producto eliminado del carrito.');
    }

    public function checkout(Request $request, BiologicalCompatibilityService $compatibility): RedirectResponse
    {
        $data = $request->validate([
            'fulfillment_type' => ['required', Rule::in(['envio', 'click_collect'])],
            'payment_method' => ['required', Rule::in(['tarjeta_simulada', 'transferencia_simulada'])],
            'compatibility_confirmed' => ['nullable', 'boolean'],
            'shipping_address' => ['required_if:fulfillment_type,envio', 'array'],
            'shipping_address.recipient' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:120'],
            'shipping_address.phone' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'regex:/^[0-9+() .-]{10,20}$/'],
            'shipping_address.street' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:160'],
            'shipping_address.exterior_number' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:20'],
            'shipping_address.interior_number' => ['nullable', 'string', 'max:20'],
            'shipping_address.neighborhood' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:120'],
            'shipping_address.postal_code' => ['required_if:fulfillment_type,envio', 'nullable', 'regex:/^[0-9]{5}$/'],
            'shipping_address.city' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:120'],
            'shipping_address.state' => ['required_if:fulfillment_type,envio', 'nullable', 'string', 'max:120'],
            'shipping_address.instructions' => ['nullable', 'string', 'max:500'],
        ]);
        $cart = $request->session()->get('cart', []);
        if (! count($cart)) {
            throw ValidationException::withMessages(['cart' => 'Agrega al menos un producto antes de confirmar la compra.']);
        }

        $cartProducts = Product::query()->whereIn('id', array_keys($cart))->get()->keyBy('id');
        $liveProductIds = $cartProducts->filter(fn (Product $product) => $product->isLive())->keys()->map(fn ($id) => (string) $id)->all();
        $acceptedIds = array_map('strval', array_keys($request->session()->get('responsible_care_acceptances', [])));
        if (array_diff($liveProductIds, $acceptedIds)) {
            throw ValidationException::withMessages(['care' => 'Falta aceptar la carta de cuidado responsable de los ejemplares vivos. Retira y vuelve a agregar esos productos.']);
        }
        $warnings = $compatibility->warnings($cartProducts->values());
        if ($warnings && ! ($data['compatibility_confirmed'] ?? false)) {
            throw ValidationException::withMessages(['compatibility' => 'Confirma que revisaste la advertencia biológica antes de continuar.']);
        }

        $sessionHash = $this->sessionHash($request);
        $receipt = DB::transaction(function () use ($request, $data, $cart, $warnings, $sessionHash) {
            $products = Product::query()->whereIn('id', array_keys($cart))->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== count($cart)) {
                throw ValidationException::withMessages(['cart' => 'Un producto del carrito ya no está disponible. Actualiza tu selección.']);
            }
            $subtotalCents = 0;
            foreach ($cart as $productId => $quantity) {
                $product = $products->get((int) $productId);
                $reservation = CartReservation::query()->where('session_hash', $sessionHash)->where('product_id', $product->id)
                    ->where('expires_at', '>', now())->lockForUpdate()->first();
                $heldByOthers = CartReservation::query()->where('product_id', $product->id)->where('expires_at', '>', now())
                    ->where('session_hash', '!=', $sessionHash)->sum('quantity');
                $availableToThisReservation = $product->stock - $heldByOthers;
                if (! $product->is_active || ! $reservation || $reservation->quantity < (int) $quantity || (int) $quantity > $availableToThisReservation) {
                    throw ValidationException::withMessages(['cart' => "Stock insuficiente para {$product->name}. Actualiza tu carrito."]);
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
                'status' => 'pagada_simulada',
                'subtotal' => $this->decimal($subtotalCents),
                'tax_rate' => 16,
                'tax' => $this->decimal($taxCents),
                'total' => $this->decimal($totalCents),
                'fulfillment_type' => $data['fulfillment_type'],
                'fulfillment_status' => $data['fulfillment_type'] === 'click_collect' ? 'pendiente_recoleccion' : 'preparando_envio',
                'pickup_code' => $pickupCode,
                'shipping_address' => $data['fulfillment_type'] === 'envio' ? $data['shipping_address'] : null,
                'care_responsibility_accepted_at' => $request->session()->get('responsible_care_acceptances') ? now() : null,
                'compatibility_acknowledged_at' => $warnings ? now() : null,
                'compatibility_warnings' => $warnings ?: null,
            ]);

            foreach ($cart as $productId => $quantity) {
                $product = $products->get((int) $productId);
                $before = $product->stock;
                $unitCents = (int) round((float) $product->price * 100);
                $product->decrement('stock', (int) $quantity);
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'unit_price' => $this->decimal($unitCents),
                    'quantity' => (int) $quantity,
                    'line_total' => $this->decimal($unitCents * (int) $quantity),
                ]);
                InventoryMovement::create([
                    'product_id' => $product->id,
                    'recorded_by' => $request->user()->id,
                    'type' => 'venta_web',
                    'quantity_delta' => -(int) $quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $product->fresh()->stock,
                    'reason' => "Pedido web {$order->order_number}",
                    'notes' => $data['fulfillment_type'] === 'click_collect' ? "Click & Collect; código {$pickupCode}" : 'Envío; pago simulado local',
                ]);
            }

            $payment = SimulatedOrderPayment::create([
                'order_id' => $order->id,
                'reference' => 'SIM-WEB-'.Str::upper(Str::random(12)),
                'method' => $data['payment_method'],
                'status' => 'cargo_simulado',
                'amount' => $this->decimal($totalCents),
            ]);

            CartReservation::query()->where('session_hash', $sessionHash)->delete();

            return [
                'order_number' => $order->order_number,
                'pickup_code' => $pickupCode,
                'fulfillment_type' => $data['fulfillment_type'],
                'shipping_address' => $order->shipping_address,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'total' => $order->total,
                'payment_reference' => $payment->reference,
            ];
        });

        $request->session()->forget('cart');
        $request->session()->forget('responsible_care_acceptances');

        return redirect()->route('orders.index')
            ->with('success', 'Pedido confirmado con pago simulado. No se realizó ningún cobro real.')
            ->with('receipt', $receipt);
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
