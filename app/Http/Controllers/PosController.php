<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\CartReservation;
use App\Models\PosClosure;
use App\Models\SimulatedOrderPayment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PosController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'string', 'max:40'],
        ]);
        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

        $products = Product::query()->where('is_active', true)->where('stock', '>', 0)
                ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(function ($search) use ($term, $operator) {
                    $search->where('name', $operator, "%{$term}%")->orWhere('sku', $operator, "%{$term}%");
                }))
                ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
                ->orderBy('name')->limit(100)->get()
                ->each(function (Product $product) {
                    $reserved = $product->cartReservations()->where('expires_at', '>', now())->sum('quantity');
                    $product->setAttribute('available_stock', max(0, $product->stock - $reserved));
                })->filter(fn (Product $product) => $product->available_stock > 0)->take(80)->values();

        return Inertia::render('POS/Index', [
            'products' => $products,
            'filters' => $filters,
            'categories' => Product::query()->where('is_active', true)->where('stock', '>', 0)
                ->distinct()->orderBy('category')->pluck('category'),
            'recentOrders' => Order::query()->with(['payment:id,order_id,method,status', 'cashier:id,name'])
                ->latest()->limit(6)->get(),
            'closedForToday' => PosClosure::query()->where('cashier_id', $request->user()->id)->whereDate('business_date', today())->exists(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:120'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'payment_method' => ['required', Rule::in(['tarjeta_simulada', 'transferencia_simulada', 'efectivo_simulado'])],
            'cash_tendered' => ['nullable', 'numeric', 'min:0', 'max:99999999.99'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*.product_id' => ['required', 'integer', 'distinct', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
        ]);

        $receipt = DB::transaction(function () use ($request, $data) {
            if (PosClosure::query()->where('cashier_id', $request->user()->id)->whereDate('business_date', today())->exists()) {
                throw ValidationException::withMessages(['payment_method' => 'La caja ya tiene cierre Z para hoy. No se permiten más ventas en esta sesión.']);
            }
            $requested = collect($data['items'])->keyBy(fn ($item) => (int) $item['product_id']);
            $products = Product::query()->whereIn('id', $requested->keys())->lockForUpdate()->get()->keyBy('id');
            if ($products->count() !== $requested->count()) {
                throw ValidationException::withMessages(['items' => 'Uno o más productos ya no están disponibles.']);
            }

            $subtotalCents = 0;
            foreach ($requested as $productId => $item) {
                $product = $products->get($productId);
                if (! $product->is_active) {
                    throw ValidationException::withMessages(['items' => "{$product->name} ya no está disponible."]);
                }
                $reserved = CartReservation::query()->where('product_id', $product->id)->where('expires_at', '>', now())->sum('quantity');
                $available = max(0, $product->stock - $reserved);
                if ($item['quantity'] > $available) {
                    throw ValidationException::withMessages(['items' => "Stock insuficiente para {$product->name}; disponibles después de apartados: {$available}."]);
                }
                $unitPriceCents = (int) round(((float) $product->price) * 100);
                $subtotalCents += $unitPriceCents * (int) $item['quantity'];
            }

            $taxCents = (int) round($subtotalCents * 0.16);
            $totalCents = $subtotalCents + $taxCents;
            $cashTenderedCents = isset($data['cash_tendered']) ? (int) round((float) $data['cash_tendered'] * 100) : null;
            if ($data['payment_method'] === 'efectivo_simulado' && ($cashTenderedCents === null || $cashTenderedCents < $totalCents)) {
                throw ValidationException::withMessages(['cash_tendered' => 'El efectivo simulado debe cubrir el total de la venta.']);
            }
            $client = isset($data['customer_email'])
                ? User::query()->where('email', $data['customer_email'])->where('role', User::ROLE_CLIENTE)->first()
                : null;
            $order = Order::create([
                'order_number' => 'AQ-'.now()->format('ymd').'-'.Str::upper(Str::random(6)),
                'client_id' => $client?->id,
                'cashier_id' => $request->user()->id,
                'customer_name' => $data['customer_name'] ?? $client?->name,
                'customer_email' => $data['customer_email'] ?? null,
                'channel' => 'pos',
                'status' => 'pagada_simulada',
                'subtotal' => $this->decimal($subtotalCents),
                'tax_rate' => 16,
                'tax' => $this->decimal($taxCents),
                'total' => $this->decimal($totalCents),
            ]);

            foreach ($requested as $productId => $item) {
                $product = $products->get($productId);
                $before = $product->stock;
                $quantity = (int) $item['quantity'];
                $unitPriceCents = (int) round(((float) $product->price) * 100);
                $lineCents = $unitPriceCents * $quantity;
                $product->decrement('stock', $quantity);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'product_name' => $product->name,
                    'unit_price' => $this->decimal($unitPriceCents),
                    'quantity' => $quantity,
                    'line_total' => $this->decimal($lineCents),
                ]);

                InventoryMovement::create([
                    'product_id' => $product->id,
                    'recorded_by' => $request->user()->id,
                    'type' => 'venta_pos',
                    'quantity_delta' => -$quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $product->fresh()->stock,
                    'reason' => "Venta POS {$order->order_number}",
                    'notes' => "Método de pago: {$data['payment_method']} (simulación local)",
                ]);
            }

            $payment = SimulatedOrderPayment::create([
                'order_id' => $order->id,
                'reference' => 'SIM-POS-'.Str::upper(Str::random(12)),
                'method' => $data['payment_method'],
                'status' => 'cargo_simulado',
                'amount' => $this->decimal($totalCents),
                'amount_tendered' => $data['payment_method'] === 'efectivo_simulado' ? $this->decimal($cashTenderedCents) : null,
                'change_amount' => $data['payment_method'] === 'efectivo_simulado' ? $this->decimal($cashTenderedCents - $totalCents) : null,
            ]);

            return [
                'order_number' => $order->order_number,
                'subtotal' => $order->subtotal,
                'tax' => $order->tax,
                'total' => $order->total,
                'payment_reference' => $payment->reference,
                'payment_method' => $payment->method,
                'amount_tendered' => $payment->amount_tendered,
                'change_amount' => $payment->change_amount,
            ];
        });

        return redirect()->route('pos.index')
            ->with('success', 'Venta registrada y descontada del inventario mediante una operación local simulada.')
            ->with('receipt', $receipt);
    }

    public function close(Request $request): Response|RedirectResponse
    {
        $existing = PosClosure::query()->where('cashier_id', $request->user()->id)
            ->whereDate('business_date', today())->first();
        if ($existing) {
            return Inertia::render('POS/Close', ['closure' => $existing, 'summary' => null]);
        }

        $summary = $this->dailySummary($request->user()->id);
        if ($request->isMethod('get')) {
            return Inertia::render('POS/Close', ['closure' => null, 'summary' => $summary]);
        }

        $data = $request->validate([
            'opening_float' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'counted_cash' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        DB::transaction(function () use ($request, $data) {
            User::query()->lockForUpdate()->findOrFail($request->user()->id);
            if (PosClosure::query()->where('cashier_id', $request->user()->id)->whereDate('business_date', today())->exists()) {
                throw ValidationException::withMessages(['closure' => 'La caja ya se cerró hoy.']);
            }
            $summary = $this->dailySummary($request->user()->id);
            $openingCents = (int) round((float) $data['opening_float'] * 100);
            $countedCents = (int) round((float) $data['counted_cash'] * 100);
            $expectedCents = $openingCents + (int) round((float) $summary['cash_sales'] * 100);

            PosClosure::create([
                'cashier_id' => $request->user()->id,
                'business_date' => today(),
                'opening_float' => $this->decimal($openingCents),
                'expected_cash' => $this->decimal($expectedCents),
                'counted_cash' => $this->decimal($countedCents),
                'difference' => $this->decimal($countedCents - $expectedCents),
                'sales_count' => $summary['sales_count'],
                'cash_sales' => $summary['cash_sales'],
                'card_sales' => $summary['card_sales'],
                'transfer_sales' => $summary['transfer_sales'],
                'closed_at' => now(),
            ]);
        });

        return redirect()->route('pos.close')->with('success', 'Cierre Z local generado. La caja quedó bloqueada para nuevas ventas hasta el siguiente día.');
    }

    private function dailySummary(int $cashierId): array
    {
        $payments = SimulatedOrderPayment::query()->whereHas('order', fn ($query) => $query
            ->where('cashier_id', $cashierId)->whereDate('created_at', today())->where('channel', 'pos'));

        return [
            'sales_count' => (clone $payments)->count(),
            'cash_sales' => (clone $payments)->where('method', 'efectivo_simulado')->sum('amount'),
            'card_sales' => (clone $payments)->where('method', 'tarjeta_simulada')->sum('amount'),
            'transfer_sales' => (clone $payments)->where('method', 'transferencia_simulada')->sum('amount'),
        ];
    }

    private function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
