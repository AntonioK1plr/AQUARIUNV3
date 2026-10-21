<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\Product;
use App\Notifications\OrderStatusChanged;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireUncollectedOrders extends Command
{
    protected $signature = 'aquarium:orders:expire-pickups {--hours=48 : Horas que puede esperar un pedido sin recogerse} {--dry-run : Solo mostrar cuántos pedidos vencerían}';

    protected $description = 'Cancela Click & Collect vencidos, reembolsa de forma simulada y devuelve las unidades al inventario.';

    public function handle(): int
    {
        $hours = filter_var($this->option('hours'), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 720]]);
        if ($hours === false) {
            $this->error('El parámetro --hours debe ser un entero entre 1 y 720.');

            return self::FAILURE;
        }

        $cutoff = now()->subHours($hours);
        $eligible = Order::query()
            ->where('fulfillment_type', 'click_collect')
            ->whereIn('fulfillment_status', ['pendiente_recoleccion', 'listo_recoleccion'])
            ->where('created_at', '<=', $cutoff);

        if ($this->option('dry-run')) {
            $count = $eligible->count();
            $this->info("Simulación: {$count} pedidos vencerían con antigüedad de {$hours} horas.");

            return self::SUCCESS;
        }

        $expired = 0;
        $eligible->orderBy('id')->chunkById(50, function ($orders) use (&$expired) {
            foreach ($orders as $order) {
                $wasExpired = DB::transaction(function () use ($order) {
                    $locked = Order::query()->lockForUpdate()->with('items')->find($order->id);
                    if (! $locked || $locked->fulfillment_type !== 'click_collect'
                        || ! in_array($locked->fulfillment_status, ['pendiente_recoleccion', 'listo_recoleccion'], true)
                        || $locked->created_at->gt(now()->subHours((int) $this->option('hours')))) {
                        return false;
                    }

                    foreach ($locked->items as $item) {
                        $product = Product::query()->lockForUpdate()->find($item->product_id);
                        if (! $product) {
                            continue;
                        }
                        $before = $product->stock;
                        $product->increment('stock', $item->quantity);
                        InventoryMovement::create([
                            'product_id' => $product->id,
                            'recorded_by' => null,
                            'type' => 'reingreso_vencimiento',
                            'quantity_delta' => $item->quantity,
                            'quantity_before' => $before,
                            'quantity_after' => $product->fresh()->stock,
                            'reason' => "Reingreso de pedido vencido {$locked->order_number}",
                            'notes' => 'Pedido Click & Collect no recogido dentro del plazo configurado.',
                        ]);
                    }

                    $previousStatus = $locked->fulfillment_status;
                    $locked->update([
                        'status' => 'cancelada_expiracion',
                        'fulfillment_status' => 'cancelado_expiracion',
                    ]);
                    $locked->payment()->update(['status' => 'reembolso_simulado']);
                    OrderFulfillmentEvent::create([
                        'order_id' => $locked->id,
                        'performed_by' => null,
                        'event_type' => 'recoleccion_vencida',
                        'previous_status' => $previousStatus,
                        'new_status' => 'cancelado_expiracion',
                        'notes' => 'Venció el plazo de recolección; se registró devolución de inventario y reembolso simulado.',
                    ]);

                    return true;
                });

                $expired += $wasExpired ? 1 : 0;
                if ($wasExpired) {
                    $order->refresh();
                    $order->client?->notify(new OrderStatusChanged(
                        $order,
                        'Pedido cancelado por vencimiento',
                        'Venció el plazo de 48 horas para recoger el pedido. Se registró un reembolso simulado.',
                    ));
                }
            }
        });

        $this->info("Pedidos vencidos y procesados: {$expired}. Reembolsos solo simulados; no se movió dinero real.");

        return self::SUCCESS;
    }
}
