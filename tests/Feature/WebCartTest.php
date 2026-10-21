<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_to_public_cart_and_is_asked_to_sign_in_before_checkout(): void
    {
        $product = $this->product('AQ-WEB-001', 5);

        $this->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect();
        $this->get('/carrito')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Cart/Index')
                ->has('lines', 1)->where('lines.0.quantity', 2)->where('cart.itemsCount', 2));

        $this->post('/carrito/checkout', [
            'fulfillment_type' => 'click_collect',
            'payment_method' => 'tarjeta_simulada',
        ])->assertRedirect('/acceso');
    }

    public function test_customer_can_checkout_click_collect_with_simulated_payment_and_stock_audit(): void
    {
        $client = $this->customer();
        $product = $this->product('AQ-WEB-002', 5);

        $this->actingAs($client)->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 2]);
        $this->actingAs($client)->post('/carrito/checkout', [
            'fulfillment_type' => 'click_collect',
            'payment_method' => 'tarjeta_simulada',
        ])->assertRedirect('/cuenta/pedidos')->assertSessionHas('receipt');

        $order = Order::with(['items', 'payment'])->firstOrFail();
        $this->assertSame('ecommerce', $order->channel);
        $this->assertSame('click_collect', $order->fulfillment_type);
        $this->assertSame('pendiente_recoleccion', $order->fulfillment_status);
        $this->assertNotEmpty($order->pickup_code);
        $this->assertSame('200.00', $order->subtotal);
        $this->assertSame('32.00', $order->tax);
        $this->assertSame('232.00', $order->total);
        $this->assertSame('tarjeta_simulada', $order->payment->method);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'recorded_by' => $client->id,
            'type' => 'venta_web',
            'quantity_delta' => -2,
            'quantity_before' => 5,
            'quantity_after' => 3,
        ]);
        $this->get('/cuenta/pedidos')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Orders')
                ->has('orders.data', 1)->where('orders.data.0.order_number', $order->order_number));
    }

    public function test_customer_checkout_rejects_insufficient_stock_without_partial_sale(): void
    {
        $client = $this->customer();
        $product = $this->product('AQ-WEB-003', 1);

        $this->actingAs($client)->from('/carrito')->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect('/carrito')->assertSessionHasErrors('product');
        $this->actingAs($client)->post('/carrito/checkout', [
                'fulfillment_type' => 'click_collect',
                'payment_method' => 'transferencia_simulada',
        ])->assertRedirect('/carrito')->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('simulated_order_payments', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_checkout_does_not_accept_cash_or_external_payment_methods(): void
    {
        $client = $this->customer();
        $product = $this->product('AQ-WEB-004', 2);

        $this->actingAs($client)->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->actingAs($client)->from('/carrito')->post('/carrito/checkout', [
                'fulfillment_type' => 'envio',
                'payment_method' => 'efectivo',
            ])->assertRedirect('/carrito')->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_shipping_checkout_requires_and_stores_a_delivery_address_snapshot(): void
    {
        $client = $this->customer();
        $product = $this->product('AQ-WEB-005', 2);
        $address = [
            'recipient' => 'Cliente prueba',
            'phone' => '5551234567',
            'street' => 'Avenida del Acuario',
            'exterior_number' => '123',
            'interior_number' => '4B',
            'neighborhood' => 'Centro',
            'postal_code' => '06000',
            'city' => 'Cuauhtémoc',
            'state' => 'Ciudad de México',
            'instructions' => 'Tocar el timbre azul',
        ];

        $this->actingAs($client)->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs($client)
            ->from('/carrito')->post('/carrito/checkout', [
                'fulfillment_type' => 'envio',
                'payment_method' => 'transferencia_simulada',
            ])->assertRedirect('/carrito')->assertSessionHasErrors('shipping_address.recipient');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($client)
            ->post('/carrito/checkout', [
                'fulfillment_type' => 'envio',
                'payment_method' => 'transferencia_simulada',
                'shipping_address' => [...$address, 'postal_code' => 'ABC'],
            ])->assertRedirect('/carrito')->assertSessionHasErrors('shipping_address.postal_code');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($client)
            ->post('/carrito/checkout', [
                'fulfillment_type' => 'envio',
                'payment_method' => 'transferencia_simulada',
                'shipping_address' => $address,
            ])->assertRedirect('/cuenta/pedidos')->assertSessionHas('receipt');

        $order = Order::firstOrFail();
        $this->assertSame('preparando_envio', $order->fulfillment_status);
        $this->assertSame($address, $order->shipping_address);
    }

    public function test_live_species_require_responsible_care_acceptance_before_adding_to_cart(): void
    {
        $fish = $this->liveFish('AQ-LIVE-001', 'Pez de prueba');

        $this->from('/catalogo')->post('/carrito/items', ['product_id' => $fish->id, 'quantity' => 1])
            ->assertRedirect('/catalogo')->assertSessionHasErrors('care');
        $this->assertSame([], session('cart', []));

        $this->post('/carrito/items', ['product_id' => $fish->id, 'quantity' => 1, 'care_responsibility_accepted' => true])
            ->assertRedirect();
        $this->assertArrayHasKey($fish->id, session('responsible_care_acceptances'));
    }

    public function test_biological_conflicts_are_shown_and_must_be_acknowledged_at_checkout(): void
    {
        $client = $this->customer();
        $discus = $this->liveFish('AQ-LIVE-002', 'Pez Disco', [
            'water_type' => 'dulce', 'min_ph' => 6.0, 'max_ph' => 6.8, 'min_temp_c' => 28, 'max_temp_c' => 30, 'temperament' => 'pacifico',
        ]);
        $cichlid = $this->liveFish('AQ-LIVE-003', 'Cíclido', [
            'water_type' => 'dulce', 'min_ph' => 7.8, 'max_ph' => 8.4, 'min_temp_c' => 24, 'max_temp_c' => 27, 'temperament' => 'agresivo',
        ]);
        $this->actingAs($client)->post('/carrito/items', ['product_id' => $discus->id, 'quantity' => 1, 'care_responsibility_accepted' => true]);
        $this->actingAs($client)->post('/carrito/items', ['product_id' => $cichlid->id, 'quantity' => 1, 'care_responsibility_accepted' => true]);

        $this->actingAs($client)->get('/carrito')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('compatibilityWarnings', 1)
                ->where('compatibilityWarnings.0.products', ['Pez Disco', 'Cíclido']));

        $this->actingAs($client)->from('/carrito')->post('/carrito/checkout', [
            'fulfillment_type' => 'click_collect', 'payment_method' => 'tarjeta_simulada',
        ])->assertRedirect('/carrito')->assertSessionHasErrors('compatibility');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($client)->post('/carrito/checkout', [
            'fulfillment_type' => 'click_collect', 'payment_method' => 'tarjeta_simulada', 'compatibility_confirmed' => true,
        ])->assertRedirect('/cuenta/pedidos')->assertSessionHas('receipt');

        $order = Order::firstOrFail();
        $this->assertNotNull($order->care_responsibility_accepted_at);
        $this->assertNotNull($order->compatibility_acknowledged_at);
        $this->assertSame(1, count($order->compatibility_warnings));
    }

    private function product(string $sku, int $stock): Product
    {
        return Product::create([
            'sku' => $sku,
            'name' => 'Pez de prueba',
            'category' => 'accesorio',
            'price' => 100,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function liveFish(string $sku, string $name, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'sku' => $sku,
            'name' => $name,
            'category' => 'pez',
            'water_type' => 'salada',
            'min_ph' => 8.0,
            'max_ph' => 8.4,
            'min_temp_c' => 24,
            'max_temp_c' => 28,
            'temperament' => 'pacifico',
            'price' => 100,
            'stock' => 5,
            'is_active' => true,
        ], $attributes));
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Cliente prueba',
            'email' => 'cliente@example.test',
            'password' => 'AcuarioSeguro123',
            'role' => User::ROLE_CLIENTE,
        ]);
    }
}
