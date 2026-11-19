<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class IncreaseInventoryStock extends Command
{
    protected $signature = 'aquarium:increase-stock {quantity=25 : Unidades a sumar por producto}';

    protected $description = 'Incrementa una sola vez el stock de todos los productos y registra cada movimiento.';

    public function handle(): int
    {
        $quantity = filter_var($this->argument('quantity'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! $quantity) {
            $this->error('La cantidad debe ser un entero mayor que cero.');

            return self::FAILURE;
        }

        try {
            $count = DB::transaction(function () use ($quantity) {
                $reason = 'Ajuste inicial de inventario para migración a Render';
                if (InventoryMovement::query()->where('reason', $reason)->exists()) {
                    throw new RuntimeException('Este ajuste ya fue aplicado; se cancela para evitar duplicar existencias.');
                }

                $products = Product::query()->orderBy('id')->lockForUpdate()->get();
                foreach ($products as $product) {
                    $before = (int) $product->stock;
                    $product->increment('stock', $quantity);
                    InventoryMovement::query()->create([
                        'product_id' => $product->id,
                        'recorded_by' => null,
                        'type' => 'ajuste',
                        'quantity_delta' => $quantity,
                        'quantity_before' => $before,
                        'quantity_after' => $before + $quantity,
                        'reason' => $reason,
                        'notes' => "Ajuste inicial de +{$quantity} unidades solicitado para la migración de AQUARIUM a Render Postgres.",
                    ]);
                }

                return $products->count();
            });
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Se sumaron {$quantity} unidades a {$count} productos. Se registró un movimiento por producto.");

        return self::SUCCESS;
    }
}
