<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\SimulatedOrderPayment;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_warehouse_prepares_a_click_collect_order_and_pos_confirms_delivery_once(): void
    {
        $warehouse = $this->user(User::ROLE_ALMACENISTA, 'warehouse@example.test');
        $cashier = $this->user(User::ROLE_CAJERO_POS, 'cashier@example.test');
        $order = $this->order();
        $productId = $this->productId();
        Product::whereKey($productId)->update(['warehouse_location' => 'A-02-01']);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productId,
            'sku' => 'AQ-PICK-001',
            'product_name' => 'Pez de prueba',
            'unit_price' => '100.00',
            'quantity' => 1,
            'line_total' => '100.00',
        ]);

        $this->actingAs($warehouse)->get('/almacen/picking')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Warehouse/Picking')
                ->has('orders.data', 1)->where('orders.data.0.order_number', $order->order_number)
                ->where('orders.data.0.items.0.product.warehouse_location', 'A-02-01'));

        $this->actingAs($cashier)->get('/pos/recolecciones')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('POS/Pickups')->has('orders.data', 0));

        $this->actingAs($warehouse)->patch("/almacen/picking/{$order->id}/listo")
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'fulfillment_status' => 'listo_recoleccion']);
        $this->assertDatabaseHas('order_fulfillment_events', [
            'order_id' => $order->id,
            'performed_by' => $warehouse->id,
            'event_type' => 'pedido_preparado',
            'previous_status' => 'pendiente_recoleccion',
            'new_status' => 'listo_recoleccion',
        ]);

        $this->actingAs($cashier)->from('/pos/recolecciones')->patch("/pos/recolecciones/{$order->id}/entregar", [
            'pickup_code' => 'AQ-INCORRECTO',
        ])->assertRedirect('/pos/recolecciones')->assertSessionHasErrors('pickup_code');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'fulfillment_status' => 'listo_recoleccion']);

        $this->actingAs($cashier)->patch("/pos/recolecciones/{$order->id}/entregar", [
            'pickup_code' => $order->pickup_code,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'fulfillment_status' => 'entregado']);
        $this->assertDatabaseHas('order_fulfillment_events', [
            'order_id' => $order->id,
            'performed_by' => $cashier->id,
            'event_type' => 'pedido_entregado',
            'previous_status' => 'listo_recoleccion',
            'new_status' => 'entregado',
        ]);

        $this->actingAs($cashier)->from('/pos/recolecciones')->patch("/pos/recolecciones/{$order->id}/entregar", [
            'pickup_code' => $order->pickup_code,
        ])->assertRedirect('/pos/recolecciones')->assertSessionHasErrors('order');
        $this->assertSame(2, OrderFulfillmentEvent::where('order_id', $order->id)->count());
    }

    public function test_customer_cannot_access_staff_pickup_workflows(): void
    {
        $client = $this->user(User::ROLE_CLIENTE, 'client@example.test');

        $this->actingAs($client)->get('/almacen/picking')->assertForbidden();
        $this->actingAs($client)->get('/pos/recolecciones')->assertForbidden();
    }

    public function test_client_receives_local_order_status_notification(): void
    {
        $client = $this->user(User::ROLE_CLIENTE, 'pickup-notice@example.test');
        $warehouse = $this->user(User::ROLE_ALMACENISTA, 'pickup-notice-warehouse@example.test');
        $order = $this->order();
        $order->update(['client_id' => $client->id]);

        $this->actingAs($warehouse)->patch("/almacen/picking/{$order->id}/listo")->assertRedirect();

        $notification = $client->notifications()->firstOrFail();
        $this->assertSame('Pedido listo para recoger', $notification->data['title']);
        $this->assertSame($order->order_number, $notification->data['order_number']);

        $this->actingAs($client)->get('/cuenta/pedidos')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Orders')
                ->has('notifications', 1)
                ->where('notifications.0.data.title', 'Pedido listo para recoger'));
    }

    public function test_warehouse_can_prepare_and_record_local_shipment_departure(): void
    {
        $warehouse = $this->user(User::ROLE_ALMACENISTA, 'warehouse-shipping@example.test');
        $order = Order::create([
            'order_number' => 'AQ-WEB-SHIP-0001',
            'customer_name' => 'Cliente de envío',
            'customer_email' => 'shipping@example.test',
            'channel' => 'ecommerce',
            'status' => 'pagada_simulada',
            'subtotal' => '100.00',
            'tax_rate' => '16.00',
            'tax' => '16.00',
            'total' => '116.00',
            'fulfillment_type' => 'envio',
            'fulfillment_status' => 'preparando_envio',
            'shipping_address' => [
                'recipient' => 'Cliente de envío', 'phone' => '5551234567', 'street' => 'Calle Coral',
                'exterior_number' => '8', 'neighborhood' => 'Centro', 'postal_code' => '06000',
                'city' => 'Cuauhtémoc', 'state' => 'Ciudad de México',
            ],
        ]);

        $this->actingAs($warehouse)->get('/almacen/picking')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Warehouse/Picking')
                ->has('orders.data', 1)->where('orders.data.0.fulfillment_type', 'envio'));

        $this->actingAs($warehouse)->patch("/almacen/picking/{$order->id}/listo")
            ->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'fulfillment_status' => 'listo_envio']);

        $this->actingAs($warehouse)->patch("/almacen/picking/{$order->id}/despachar")
            ->assertRedirect()->assertSessionHas('success');
        $order->refresh();
        $this->assertSame('enviado', $order->fulfillment_status);
        $this->assertMatchesRegularExpression('/^AQ-ENV-\d{6}-[A-Z0-9]{6}$/', $order->shipping_tracking_reference);
        $this->assertDatabaseHas('order_fulfillment_events', [
            'order_id' => $order->id,
            'performed_by' => $warehouse->id,
            'event_type' => 'envio_preparado',
            'previous_status' => 'preparando_envio',
            'new_status' => 'listo_envio',
        ]);
        $this->assertDatabaseHas('order_fulfillment_events', [
            'order_id' => $order->id,
            'performed_by' => $warehouse->id,
            'event_type' => 'envio_despachado',
            'previous_status' => 'listo_envio',
            'new_status' => 'enviado',
        ]);

        $this->actingAs($warehouse)->from('/almacen/picking')->patch("/almacen/picking/{$order->id}/despachar")
            ->assertRedirect('/almacen/picking')->assertSessionHasErrors('order');
        $this->assertSame(2, OrderFulfillmentEvent::where('order_id', $order->id)->count());
    }

    public function test_expired_uncollected_pickup_can_be_previewed_then_refunded_and_returned_to_stock(): void
    {
        $product = Product::create([
            'sku' => 'AQ-EXP-001', 'name' => 'Producto expirado', 'category' => 'accesorio',
            'price' => 100, 'stock' => 0, 'is_active' => true,
        ]);
        $order = Order::create([
            'order_number' => 'AQ-WEB-EXPIRE-001',
            'customer_name' => 'Cliente sin recoger',
            'channel' => 'ecommerce',
            'status' => 'pagada_simulada',
            'subtotal' => '200.00', 'tax_rate' => '16.00', 'tax' => '32.00', 'total' => '232.00',
            'fulfillment_type' => 'click_collect',
            'fulfillment_status' => 'listo_recoleccion',
            'pickup_code' => 'AQ-EXPIRE01',
        ]);
        $order->created_at = now()->subHours(49);
        $order->save();
        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'sku' => $product->sku,
            'product_name' => $product->name, 'unit_price' => '100.00', 'quantity' => 2, 'line_total' => '200.00',
        ]);
        SimulatedOrderPayment::create([
            'order_id' => $order->id, 'reference' => 'SIM-EXPIRE-001', 'method' => 'tarjeta_simulada',
            'status' => 'cargo_simulado', 'amount' => '232.00',
        ]);

        $this->assertSame(0, Artisan::call('aquarium:orders:expire-pickups', ['--dry-run' => true]));
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'fulfillment_status' => 'listo_recoleccion']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 0]);
        $this->assertDatabaseHas('simulated_order_payments', ['order_id' => $order->id, 'status' => 'cargo_simulado']);

        $this->assertSame(0, Artisan::call('aquarium:orders:expire-pickups'));
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'cancelada_expiracion',
            'fulfillment_status' => 'cancelado_expiracion',
        ]);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 2]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'type' => 'reingreso_vencimiento',
            'quantity_delta' => 2,
            'quantity_before' => 0,
            'quantity_after' => 2,
        ]);
        $this->assertDatabaseHas('simulated_order_payments', ['order_id' => $order->id, 'status' => 'reembolso_simulado']);
        $this->assertDatabaseHas('order_fulfillment_events', [
            'order_id' => $order->id,
            'event_type' => 'recoleccion_vencida',
            'new_status' => 'cancelado_expiracion',
        ]);
    }

    private function order(): Order
    {
        return Order::create([
            'order_number' => 'AQ-WEB-PICK-0001',
            'customer_name' => 'Cliente Click Collect',
            'customer_email' => 'client@example.test',
            'channel' => 'ecommerce',
            'status' => 'pagada_simulada',
            'subtotal' => '100.00',
            'tax_rate' => '16.00',
            'tax' => '16.00',
            'total' => '116.00',
            'fulfillment_type' => 'click_collect',
            'fulfillment_status' => 'pendiente_recoleccion',
            'pickup_code' => 'AQ-PICKUP01',
        ]);
    }

    private function productId(): int
    {
        return \App\Models\Product::create([
            'sku' => 'AQ-PICK-001',
            'name' => 'Pez de prueba',
            'category' => 'pez',
            'price' => 100,
            'stock' => 0,
            'is_active' => true,
        ])->id;
    }

    private function user(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'AcuarioSeguro123',
            'role' => $role,
        ]);
    }
}
