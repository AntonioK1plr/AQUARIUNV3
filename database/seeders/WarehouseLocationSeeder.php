<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class WarehouseLocationSeeder extends Seeder
{
    /**
     * Fill missing warehouse locations in the catalog's requested aisle order.
     * Existing locations are retained so manual warehouse adjustments survive
     * subsequent database seeding.
     */
    public function run(): void
    {
        $aisles = [
            'pez' => 'A',
            'alimento' => 'B',
            'acuario' => 'C',
            'filtracion' => 'D',
            'accesorio' => 'D',
            'medicina' => 'D',
            'sustrato' => 'D',
            'decoracion' => 'D',
        ];

        $products = Product::query()->orderBy('name')->get();
        $usedLocations = $products
            ->pluck('warehouse_location')
            ->filter()
            ->map(fn (string $location) => mb_strtoupper($location))
            ->flip();
        $nextSlotByAisle = [];

        $products
            ->sortBy(fn (Product $product) => sprintf('%s|%s', $aisles[$product->category] ?? 'H', mb_strtolower($product->name)))
            ->each(function (Product $product) use ($aisles, $usedLocations, &$nextSlotByAisle): void {
                if (filled($product->warehouse_location)) {
                    return;
                }

                $aisle = $aisles[$product->category] ?? 'H';
                $slot = $nextSlotByAisle[$aisle] ?? 1;

                do {
                    $shelf = intdiv($slot - 1, 9) + 1;
                    $level = (($slot - 1) % 9) + 1;
                    $location = sprintf('Pasillo %s-%02d-%02d', $aisle, $shelf, $level);
                    $slot++;
                } while (isset($usedLocations[mb_strtoupper($location)]));

                $product->forceFill(['warehouse_location' => $location])->save();
                $usedLocations[mb_strtoupper($location)] = true;
                $nextSlotByAisle[$aisle] = $slot;
            });
    }
}
