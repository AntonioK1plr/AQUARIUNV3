<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use LogicException;

class WarehouseLocationSeeder extends Seeder
{
    private const AISLES_BY_GROUP = [
        'peces' => ['A', 'B', 'C'],
        'alimento' => ['D'],
        'tanques' => ['E'],
        'equipo' => ['F', 'G'],
    ];

    private const GROUP_ORDER = [
        'pez' => 0,
        'invertebrado' => 0,
        'coral' => 0,
        'alimento' => 1,
        'acuario' => 2,
        'accesorio' => 3,
        'filtracion' => 3,
        'medicina' => 3,
        'sustrato' => 3,
        'decoracion' => 3,
    ];

    /** Fill missing locations in category order, preserving saved locations. */
    public function run(): void
    {
        $products = Product::query()->get()
            ->sortBy(fn (Product $product) => sprintf(
                '%d|%s',
                self::GROUP_ORDER[$product->category] ?? 99,
                mb_strtolower($product->name),
            ));

        $usedLocations = $products
            ->pluck('warehouse_location')
            ->filter()
            ->map(fn (string $location) => mb_strtoupper($location))
            ->flip();

        foreach ($products as $product) {
            if (filled($product->warehouse_location)) {
                continue;
            }

            $group = $this->groupFor($product);
            $location = $this->nextAvailableLocation($group, $usedLocations);
            $product->forceFill(['warehouse_location' => $location])->save();
            $usedLocations[mb_strtoupper($location)] = true;
        }
    }

    private function groupFor(Product $product): string
    {
        return match ($product->category) {
            'pez', 'invertebrado', 'coral' => 'peces',
            'alimento' => 'alimento',
            'acuario' => 'tanques',
            default => 'equipo',
        };
    }

    private function nextAvailableLocation(string $group, $usedLocations): string
    {
        foreach (self::AISLES_BY_GROUP[$group] as $aisle) {
            for ($slot = 1; $slot <= 25; $slot++) {
                $shelf = intdiv($slot - 1, 5) + 1;
                $level = (($slot - 1) % 5) + 1;
                $location = sprintf('Pasillo %s-%02d-%02d', $aisle, $shelf, $level);

                if (! isset($usedLocations[mb_strtoupper($location)])) {
                    return $location;
                }
            }
        }

        throw new LogicException("No quedan ubicaciones en los pasillos asignados al grupo {$group}; cada pasillo admite 25 ubicaciones.");
    }
}
