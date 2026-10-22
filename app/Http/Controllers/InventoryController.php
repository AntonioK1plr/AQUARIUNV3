<?php

namespace App\Http\Controllers;

use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\CartReservation;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class InventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'stock' => ['nullable', Rule::in(['disponible', 'bajo', 'agotado'])],
        ]);

        $operator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $productsQuery = Product::query()->where('is_active', true)
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where(function ($search) use ($term, $operator) {
                $search->where('name', $operator, "%{$term}%")->orWhere('sku', $operator, "%{$term}%");
            }))
            ->when(($filters['stock'] ?? null) === 'disponible', fn ($query) => $query->where('stock', '>', 5))
            ->when(($filters['stock'] ?? null) === 'bajo', fn ($query) => $query->whereBetween('stock', [1, 5]))
            ->when(($filters['stock'] ?? null) === 'agotado', fn ($query) => $query->where('stock', 0));

        return Inertia::render('Warehouse/Index', [
            'products' => $productsQuery->orderBy('warehouse_location')->orderBy('name')->paginate(10)->withQueryString(),
            'filters' => $filters,
            'summary' => [
                'products' => Product::where('is_active', true)->count(),
                'units' => Product::where('is_active', true)->sum('stock'),
                'lowStock' => Product::where('is_active', true)->whereBetween('stock', [1, 5])->count(),
                'outOfStock' => Product::where('is_active', true)->where('stock', 0)->count(),
            ],
            'recentMovements' => InventoryMovement::with(['product:id,name,sku', 'actor:id,name', 'lot:id,lot_code'])
                ->latest()->limit(8)->get(),
            'productOptions' => Product::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'stock']),
            'lots' => InventoryLot::query()->with('product:id,name,sku')->where('quantity_remaining', '>', 0)
                ->orderBy('received_at')->get(),
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(['pez', 'invertebrado', 'coral', 'acuario', 'filtracion', 'sustrato', 'decoracion', 'alimento', 'medicina', 'accesorio'])],
            'water_type' => ['nullable', Rule::in(['dulce', 'salada'])],
            'brand' => ['nullable', 'string', 'max:100'],
            'warehouse_location' => ['nullable', 'string', 'max:24', 'regex:/^(?:Pasillo\s+)?[A-Za-z]-\d{2}-\d{2}$/i'],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        $data['sku'] = strtoupper($data['sku']);
        $data['warehouse_location'] = $this->normalizeWarehouseLocation($data['warehouse_location'] ?? null);
        $data['stock'] = 0;
        $data['is_active'] = true;
        $data['is_featured'] = false;
        Product::create($data);

        return back()->with('success', 'Producto creado con existencias en cero. Registra una recepción de lote para ingresar unidades.');
    }

    public function updateLocation(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_location' => ['nullable', 'string', 'max:24', 'regex:/^(?:Pasillo\s+)?[A-Za-z]-\d{2}-\d{2}$/i'],
        ]);

        $product->update(['warehouse_location' => $this->normalizeWarehouseLocation($data['warehouse_location'] ?? null)]);

        return back()->with('success', 'Ubicación de almacén actualizada.');
    }

    public function receive(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'lot_code' => ['required', 'string', 'max:80', Rule::unique('inventory_lots', 'lot_code')->where(fn ($query) => $query->where('product_id', $product->id))],
            'supplier' => ['required', 'string', 'max:160'],
            'received_at' => ['required', 'date', 'before_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unit_cost' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        DB::transaction(function () use ($request, $product, $data) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            if (! $lockedProduct->is_active) {
                throw ValidationException::withMessages(['product' => 'No se pueden recibir lotes para un producto retirado.']);
            }

            $before = $lockedProduct->stock;
            $lot = InventoryLot::create([
                'product_id' => $lockedProduct->id,
                'lot_code' => $data['lot_code'],
                'supplier' => $data['supplier'],
                'received_at' => $data['received_at'],
                'quantity_received' => $data['quantity'],
                'quantity_remaining' => $data['quantity'],
                'unit_cost' => $data['unit_cost'],
                'recorded_by' => $request->user()->id,
            ]);

            $lockedProduct->stock += $data['quantity'];
            $lockedProduct->cost_price = $data['unit_cost'];
            $lockedProduct->save();

            InventoryMovement::create([
                'product_id' => $lockedProduct->id,
                'inventory_lot_id' => $lot->id,
                'recorded_by' => $request->user()->id,
                'type' => 'recepcion_lote',
                'quantity_delta' => $data['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $lockedProduct->stock,
                'reason' => 'Recepción de lote '.$lot->lot_code,
            ]);
        });

        return back()->with('success', 'Lote recibido, existencias actualizadas y movimiento auditado.');
    }

    public function decrease(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['merma', 'mortalidad_biologica', 'ajuste_salida'])],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'reason' => ['required', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'inventory_lot_id' => ['nullable', 'integer', Rule::exists('inventory_lots', 'id')->where('product_id', $product->id)],
        ]);

        DB::transaction(function () use ($request, $product, $data) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            if ($data['quantity'] > $lockedProduct->stock) {
                throw ValidationException::withMessages(['quantity' => 'La salida no puede superar las existencias actuales.']);
            }
            $reserved = CartReservation::query()->where('product_id', $lockedProduct->id)->where('expires_at', '>', now())->sum('quantity');
            if ($lockedProduct->stock - $data['quantity'] < $reserved) {
                throw ValidationException::withMessages(['quantity' => 'La salida dejaría sin cubrir productos apartados en carritos activos.']);
            }

            if (empty($data['inventory_lot_id']) && InventoryLot::where('product_id', $lockedProduct->id)->where('quantity_remaining', '>', 0)->exists()) {
                throw ValidationException::withMessages(['inventory_lot_id' => 'Selecciona el lote que corresponde a esta salida.']);
            }

            $lot = null;
            if (! empty($data['inventory_lot_id'])) {
                $lot = InventoryLot::query()->lockForUpdate()->findOrFail($data['inventory_lot_id']);
                if ($data['quantity'] > $lot->quantity_remaining) {
                    throw ValidationException::withMessages(['quantity' => 'La salida supera las unidades disponibles del lote seleccionado.']);
                }
                $lot->decrement('quantity_remaining', $data['quantity']);
            }

            $before = $lockedProduct->stock;
            $lockedProduct->decrement('stock', $data['quantity']);
            InventoryMovement::create([
                'product_id' => $lockedProduct->id,
                'inventory_lot_id' => $lot?->id,
                'recorded_by' => $request->user()->id,
                'type' => $data['type'],
                'quantity_delta' => -$data['quantity'],
                'quantity_before' => $before,
                'quantity_after' => $lockedProduct->fresh()->stock,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return back()->with('success', 'Salida registrada y existencias actualizadas.');
    }

    public function retire(Product $product): RedirectResponse
    {
        if ($product->stock > 0 || CartReservation::query()->where('product_id', $product->id)->where('expires_at', '>', now())->exists()) {
            throw ValidationException::withMessages(['product' => 'Retira o ajusta las existencias antes de ocultar este producto.']);
        }

        $product->update(['is_active' => false]);

        return back()->with('success', 'Producto retirado del catálogo. Se conservaron sus movimientos para auditoría.');
    }

    private function normalizeWarehouseLocation(?string $location): ?string
    {
        if (blank($location)) {
            return null;
        }

        preg_match('/(?:Pasillo\s+)?([A-Za-z])-(\d{2})-(\d{2})/i', trim($location), $matches);

        return sprintf('Pasillo %s-%s-%s', strtoupper($matches[1]), $matches[2], $matches[3]);
    }
}
