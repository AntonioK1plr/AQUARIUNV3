<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Notifications\OrderStatusChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OrderFulfillmentController extends Controller
{
    public function picking(Request $request): Response
    {
        $orders = Order::query()->with(['items.product:id,warehouse_location', 'client:id,name,email'])
            ->where(function ($query) {
                $query->where(function ($pickup) {
                    $pickup->where('fulfillment_type', 'click_collect')
                        ->whereIn('fulfillment_status', ['pendiente_recoleccion', 'listo_recoleccion']);
                })->orWhere(function ($shipment) {
                    $shipment->where('fulfillment_type', 'envio')
                        ->whereIn('fulfillment_status', ['preparando_envio', 'listo_envio', 'enviado']);
                });
            })
            ->when($request->query('q'), function ($query, $term) {
                $query->where(function ($search) use ($term) {
                    $search->where('order_number', 'like', "%{$term}%")
                        ->orWhere('pickup_code', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%");
                });
            })
            ->orderByRaw("CASE WHEN fulfillment_status IN ('pendiente_recoleccion', 'preparando_envio') THEN 0 ELSE 1 END")
            ->orderBy('created_at')->paginate(12)->withQueryString();

        return Inertia::render('Warehouse/Picking', [
            'orders' => $orders,
            'filters' => ['q' => $request->query('q', '')],
        ]);
    }

    public function markReady(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $initialStatus = $locked->fulfillment_type === 'click_collect' ? 'pendiente_recoleccion' : 'preparando_envio';
            $readyStatus = $locked->fulfillment_type === 'click_collect' ? 'listo_recoleccion' : 'listo_envio';
            if (! in_array($locked->fulfillment_type, ['click_collect', 'envio'], true) || $locked->fulfillment_status !== $initialStatus) {
                throw ValidationException::withMessages(['order' => 'Este pedido ya no está pendiente de preparación.']);
            }

            $locked->update(['fulfillment_status' => $readyStatus]);
            OrderFulfillmentEvent::create([
                'order_id' => $locked->id,
                'performed_by' => $request->user()->id,
                'event_type' => $locked->fulfillment_type === 'click_collect' ? 'pedido_preparado' : 'envio_preparado',
                'previous_status' => $initialStatus,
                'new_status' => $readyStatus,
                'notes' => $locked->fulfillment_type === 'click_collect'
                    ? 'Pedido verificado y marcado listo para entrega en mostrador.'
                    : 'Pedido embalado y marcado listo para salida local simulada.',
            ]);
        });

        $order->refresh();
        $order->client?->notify(new OrderStatusChanged(
            $order,
            $order->fulfillment_type === 'envio' ? 'Pedido listo para despacho' : 'Pedido listo para recoger',
            $order->fulfillment_type === 'envio'
                ? 'Tu pedido está listo para su salida local simulada.'
                : 'Tu pedido ya está preparado. Presenta el código o pase QR en el mostrador.',
        ));

        $readyMessage = $order->fulfillment_type === 'envio' ? 'listo para despacho' : 'listo para recolección';

        return back()->with('success', "El pedido {$order->order_number} quedó {$readyMessage}.");
    }

    public function dispatchShipment(Request $request, Order $order): RedirectResponse
    {
        DB::transaction(function () use ($request, $order) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->fulfillment_type !== 'envio' || $locked->fulfillment_status !== 'listo_envio') {
                throw ValidationException::withMessages(['order' => 'Solo se pueden registrar salidas para pedidos de envío listos.']);
            }

            $reference = 'AQ-ENV-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
            $locked->update([
                'fulfillment_status' => 'enviado',
                'shipping_tracking_reference' => $reference,
            ]);
            OrderFulfillmentEvent::create([
                'order_id' => $locked->id,
                'performed_by' => $request->user()->id,
                'event_type' => 'envio_despachado',
                'previous_status' => 'listo_envio',
                'new_status' => 'enviado',
                'notes' => "Salida local simulada; referencia {$reference}. No se contactó una paquetería.",
            ]);
        });

        $order->refresh();
        $order->client?->notify(new OrderStatusChanged(
            $order,
            'Pedido despachado',
            "Se registró una salida local simulada. Referencia: {$order->shipping_tracking_reference}.",
        ));

        return back()->with('success', "Se registró la salida local del pedido {$order->order_number}.");
    }

    public function pickups(Request $request): Response
    {
        $orders = Order::query()->with(['items.product:id,warehouse_location', 'client:id,name,email'])
            ->where('fulfillment_type', 'click_collect')
            ->where('fulfillment_status', 'listo_recoleccion')
            ->when($request->query('q'), function ($query, $term) {
                $query->where(function ($search) use ($term) {
                    $search->where('order_number', 'like', "%{$term}%")
                        ->orWhere('pickup_code', 'like', "%{$term}%")
                        ->orWhere('customer_name', 'like', "%{$term}%");
                });
            })
            ->orderBy('created_at')->paginate(12)->withQueryString();

        return Inertia::render('POS/Pickups', [
            'orders' => $orders,
            'filters' => ['q' => $request->query('q', '')],
        ]);
    }

    public function deliver(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'pickup_code' => ['required', 'string', 'max:24'],
        ]);

        DB::transaction(function () use ($request, $order, $data) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            if ($locked->fulfillment_type !== 'click_collect' || $locked->fulfillment_status !== 'listo_recoleccion') {
                throw ValidationException::withMessages(['order' => 'Este pedido no está listo para entregarse o ya fue entregado.']);
            }
            if (! hash_equals((string) $locked->pickup_code, trim($data['pickup_code']))) {
                throw ValidationException::withMessages(['pickup_code' => 'El código no coincide con el pedido seleccionado.']);
            }

            $locked->update(['fulfillment_status' => 'entregado']);
            OrderFulfillmentEvent::create([
                'order_id' => $locked->id,
                'performed_by' => $request->user()->id,
                'event_type' => 'pedido_entregado',
                'previous_status' => 'listo_recoleccion',
                'new_status' => 'entregado',
                'notes' => 'Código de recolección validado en POS.',
            ]);
        });

        $order->refresh();
        $order->client?->notify(new OrderStatusChanged(
            $order,
            'Pedido entregado',
            'La entrega de tu pedido quedó confirmada en el mostrador.',
        ));

        return back()->with('success', "Se confirmó la entrega del pedido {$order->order_number}.");
    }
}
