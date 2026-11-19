<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartReservation;
use App\Services\BiologicalCompatibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            'stripe' => [
                'enabled' => app(\App\Services\StripePaymentService::class)->isConfigured(),
                'publishableKey' => app(\App\Services\StripePaymentService::class)->isConfigured() ? config('services.stripe.key') : null,
                'mode' => app(\App\Services\StripePaymentService::class)->mode(),
            ],
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
