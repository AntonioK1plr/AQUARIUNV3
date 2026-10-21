<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\CartReservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Validation\ValidationException;

class CatalogController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'in:pez,invertebrado,coral,acuario,filtracion,sustrato,decoracion,alimento,medicina,accesorio'],
            'water_type' => ['nullable', 'in:dulce,salada'],
            'brand' => ['nullable', 'string', 'max:100'],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        if (isset($filters['q'])) {
            $filters['q'] = trim($filters['q']);
            if ($filters['q'] === '') {
                unset($filters['q']);
            }
        }

        if (
            isset($filters['min_price'], $filters['max_price'])
            && (float) $filters['max_price'] < (float) $filters['min_price']
        ) {
            throw ValidationException::withMessages([
                'max_price' => 'El precio máximo debe ser igual o mayor al precio mínimo.',
            ]);
        }

        $products = Product::query()
            ->where('is_active', true)
            ->when($filters['q'] ?? null, function (Builder $query, string $search) {
                $search = trim($search);

                if (DB::connection()->getDriverName() === 'pgsql') {
                    $document = "to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(scientific_name, '') || ' ' || coalesce(description, '') || ' ' || coalesce(brand, '') || ' ' || coalesce(sku, ''))";

                    $query->whereRaw("{$document} @@ plainto_tsquery('simple', ?)", [$search])
                        ->orderByRaw("ts_rank({$document}, plainto_tsquery('simple', ?)) DESC", [$search]);

                    return;
                }

                $term = mb_strtolower($search);
                $query->where(function (Builder $matches) use ($term) {
                    foreach (['name', 'scientific_name', 'description', 'brand', 'sku'] as $column) {
                        $matches->orWhereRaw("LOWER({$column}) LIKE ?", ["%{$term}%"]);
                    }
                });
            })
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['water_type'] ?? null, fn ($query, $waterType) => $query->where('water_type', $waterType))
            ->when($filters['brand'] ?? null, fn ($query, $brand) => $query->where('brand', $brand))
            ->when(isset($filters['min_price']), fn ($query) => $query->where('price', '>=', $filters['min_price']))
            ->when(isset($filters['max_price']), fn ($query) => $query->where('price', '<=', $filters['max_price']))
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $reservedStock = CartReservation::query()
            ->whereIn('product_id', $products->getCollection()->pluck('id'))
            ->where('expires_at', '>', now())
            ->selectRaw('product_id, SUM(quantity) as quantity')
            ->groupBy('product_id')
            ->pluck('quantity', 'product_id');

        $creditContents = @file_get_contents(public_path('images/catalog-image-credits.json')) ?: '[]';
        $creditRecords = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $creditContents), true);
        $imageCredits = collect(is_array($creditRecords) ? $creditRecords : [])->keyBy('localFile');

        $products->getCollection()->transform(function (Product $product) use ($reservedStock, $imageCredits) {
            $product->setAttribute('available_stock', max(0, $product->stock - (int) ($reservedStock[$product->id] ?? 0)));
            $imageCredit = $imageCredits->get($product->image_path);
            $product->setAttribute('image_credit', $imageCredit);
            $localImage = public_path(ltrim((string) $product->image_path, '/'));
            $product->setAttribute(
                'image_src',
                is_file($localImage) ? $product->image_path : ($imageCredit['imageUrl'] ?? $product->image_path),
            );

            return $product;
        });

        return Inertia::render('Catalog/Index', [
            'products' => $products,
            'filters' => $filters,
            'brands' => Product::query()
                ->where('is_active', true)
                ->whereNotNull('brand')
                ->where('brand', '<>', '')
                ->distinct()
                ->orderBy('brand')
                ->pluck('brand'),
        ]);
    }
}
