<?php

namespace App\Console\Commands;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\Product;
use App\Models\StripePaymentAttempt;
use App\Notifications\OrderStatusChanged;
use App\Services\StripePaymentService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpireUncollectedOrders extends Command
{
    protected $signature = 'aquarium:orders:expire-pickups {--hours=48 : Horas que puede esperar un pedido sin recogerse} {--dry-run : Solo mostrar cuántos pedidos vencerían}';

    protected $description = 'Cancela Click & Collect vencidos, solicita el reembolso del pago en línea y devuelve las unidades al inventario.';

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
                $refundMessage = null;
                try {
                    $wasExpired = DB::transaction(function () use ($order, &$refundMessage) {
                    $locked = Order::query()->lockForUpdate()->with('items')->find($order->id);
                    if (! $locked || $locked->fulfillment_type !== 'click_collect'
                        || ! in_array($locked->fulfillment_status, ['pendiente_recoleccion', 'listo_recoleccion'], true)
                        || $locked->created_at->gt(now()->subHours((int) $this->option('hours')))) {
                        return false;
                    }

                    $stripePayment = StripePaymentAttempt::query()->where('order_id', $locked->id)->lockForUpdate()->first();
                    if ($stripePayment) {
                        if ($stripePayment->status !== 'refunded') {
                            $reason = 'Pedido Click & Collect vencido sin recolección.';
                            $stripePayment->update(['status' => 'refund_pending', 'refund_reason' => $reason]);
                            $refundStatus = app(StripePaymentService::class)->refundPaymentIntent(
                                (string) $stripePayment->payment_intent_id,
                                'order',
                                (int) $locked->id,
                            );
                            $stripePayment->update([
                                'status' => $refundStatus === 'succeeded' ? 'refunded' : 'refund_pending',
                                'refunded_at' => $refundStatus === 'succeeded' ? now() : null,
                            ]);
                            $refundMessage = $refundStatus === 'succeeded'
                                ? 'Stripe confirmó el reembolso de prueba.'
                                : 'Stripe recibió el reembolso; estado pendiente de confirmación.';
                        } else {
                            $refundMessage = 'Stripe ya había confirmado el reembolso.';
                        }
                    } else {
                        $locked->payment()->update(['status' => 'legacy_cancelled_no_charge']);
                        $refundMessage = 'Registro anterior al checkout Stripe; no hubo cargo en línea que reembolsar.';
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
                    OrderFulfillmentEvent::create([
                        'order_id' => $locked->id,
                        'performed_by' => null,
                        'event_type' => 'recoleccion_vencida',
                        'previous_status' => $previousStatus,
                        'new_status' => 'cancelado_expiracion',
                        'notes' => 'Venció el plazo de recolección; se devolvió inventario. '.$refundMessage,
                    ]);

                    return true;
                    });
                } catch (Throwable $exception) {
                    report($exception);
                    $this->warn("No se pudo procesar el pedido {$order->order_number}; se reintentará en la siguiente ejecución.");
                    $wasExpired = false;
                }

                $expired += $wasExpired ? 1 : 0;
                if ($wasExpired) {
                    $order->refresh();
                    $order->client?->notify(new OrderStatusChanged(
                        $order,
                        'Pedido cancelado por vencimiento',
                        'Venció el plazo de 48 horas para recoger el pedido. '.$refundMessage,
                    ));
                }
            }
        });

        $this->info("Pedidos vencidos y procesados: {$expired}. Los pedidos cobrados en línea solicitan reembolso Stripe; los registros antiguos sin cargo no generan reembolso.");

        return self::SUCCESS;
    }
}
