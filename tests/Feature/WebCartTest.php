<?php

namespace Tests\Feature;

use App\Models\CartReservation;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\Product;
use App\Models\StripePaymentAttempt;
use App\Models\User;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class WebCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_add_to_public_cart_but_must_sign_in_before_creating_a_payment(): void
    {
        $product = $this->product('AQ-WEB-001', 5);

        $this->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 2])->assertRedirect();
        $this->get('/carrito')->assertOk()->assertInertia(fn (Assert $page) => $page->component('Cart/Index')
            ->has('lines', 1)->where('lines.0.quantity', 2)->where('cart.itemsCount', 2));

        $this->post('/carrito/pago/stripe/intento', ['fulfillment_type' => 'click_collect'])
            ->assertRedirect('/acceso');
    }

    public function test_order_is_pending_until_stripe_webhook_confirms_payment_and_then_decrements_stock(): void
    {
        $client = $this->customer();
        $product = $this->product('AQ-WEB-002', 5);
        $this->actingAs($client)->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 2]);
        $event = $this->paymentSucceededEvent('pi_order_1', 23200);
        $this->mockStripe($event, intentCount: 1);

        $response = $this->actingAs($client)->postJson('/carrito/pago/stripe/intento', [
            'request_key' => '1a1fbe4b-16ee-4f6f-915d-cb5fe004d108',
            'fulfillment_type' => 'click_collect',
        ])->assertOk()->assertJsonPath('client_secret', 'pi_order_1_secret_test')->assertJsonPath('currency', 'MXN');

        $order = Order::with(['items', 'stripePaymentAttempt'])->firstOrFail();
        $this->assertSame('ecommerce', $order->channel);
        $this->assertSame('click_collect', $order->fulfillment_type);
        $this->assertSame('pago_pendiente', $order->status);
        $this->assertSame('pago_pendiente', $order->fulfillment_status);
        $this->assertNotEmpty($order->pickup_code);
        $this->assertSame('200.00', $order->subtotal);
        $this->assertSame('32.00', $order->tax);
        $this->assertSame('232.00', $order->total);
        $this->assertSame('requires_payment_method', $order->stripePaymentAttempt->status);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(0, InventoryMovement::count());

        $this->postJson('/webhooks/stripe', [], ['Stripe-Signature' => 'test-signature'])->assertOk();
        $order->refresh();
        $this->assertSame('pagada_stripe_test', $order->status);
        $this->assertSame('pendiente_recoleccion', $order->fulfillment_status);
        $this->assertSame(3, $product->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'recorded_by' => $client->id,
            'type' => 'venta_web',
            'quantity_delta' => -2,
            'quantity_before' => 5,
            'quantity_after' => 3,
        ]);
        $this->assertDatabaseHas('stripe_payment_attempts', ['order_id' => $order->id, 'status' => 'succeeded']);
        $this->assertDatabaseHas('stripe_webhook_events', ['event_id' => 'evt_order_paid']);
        $this->assertNotNull(\App\Models\StripeWebhookEvent::where('event_id', 'evt_order_paid')->value('processed_at'));
        $this->assertDatabaseMissing('cart_reservations', ['product_id' => $product->id]);
        $this->actingAs($client)->get('/cuenta/pedidos')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Account/Orders')
                ->has('orders.data', 1)->where('orders.data.0.order_number', $order->order_number));
    }

    public function test_insufficient_inventory_does_not_create_a_payment_or_order(): void
    {
        $client = $this->customer();
        $product = $this->product('AQ-WEB-003', 1);
        $this->mockStripe();

        $this->actingAs($client)->from('/carrito')->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 2])
            ->assertRedirect('/carrito')->assertSessionHasErrors('product');

        $this->actingAs($client)->postJson('/carrito/pago/stripe/intento', [
            'request_key' => 'c0a1e39d-7ddb-48de-9a32-5cd22030c473',
            'fulfillment_type' => 'click_collect',
        ])->assertUnprocessable()->assertJsonValidationErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('stripe_payment_attempts', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_checkout_requires_a_valid_fulfillment_type_and_nonempty_cart(): void
    {
        $client = $this->customer();
        $this->mockStripe();

        $this->actingAs($client)->postJson('/carrito/pago/stripe/intento', [
            'request_key' => 'c0a1e39d-7ddb-48de-9a32-5cd22030c474',
            'fulfillment_type' => 'delivery',
        ])->assertUnprocessable()->assertJsonValidationErrors('fulfillment_type');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_shipping_checkout_validates_and_saves_the_delivery_address_snapshot(): void
    {
        $client = $this->customer();
        $product = $this->product('AQ-WEB-005', 2);
        $this->actingAs($client)->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 1]);
        $this->mockStripe(intentCount: 1);
        $base = [
            'request_key' => 'c0a1e39d-7ddb-48de-9a32-5cd22030c475',
            'fulfillment_type' => 'envio',
        ];

        $this->actingAs($client)->postJson('/carrito/pago/stripe/intento', $base)
            ->assertUnprocessable()->assertJsonValidationErrors('shipping_address.recipient');
        $this->assertDatabaseCount('orders', 0);

        $address = [
            'recipient' => 'Cliente de envío',
            'phone' => '5551234567',
            'street' => 'Avenida del Acuario',
            'exterior_number' => '123',
            'interior_number' => '4B',
            'neighborhood' => 'Centro',
            'borough' => 'Cuauhtémoc',
            'postal_code' => '06000',
            'city' => 'Ciudad de México',
            'state' => 'Ciudad de México',
            'instructions' => 'Tocar el timbre azul',
        ];
        $this->actingAs($client)->postJson('/carrito/pago/stripe/intento', array_merge($base, [
            'request_key' => 'c0a1e39d-7ddb-48de-9a32-5cd22030c476',
            'shipping_address' => [...$address, 'postal_code' => 'ABC'],
        ]))->assertUnprocessable()->assertJsonValidationErrors('shipping_address.postal_code');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($client)->postJson('/carrito/pago/stripe/intento', array_merge($base, [
            'request_key' => 'c0a1e39d-7ddb-48de-9a32-5cd22030c477',
            'shipping_address' => $address,
        ]))->assertOk();
        $order = Order::firstOrFail();
        $this->assertSame('pago_pendiente', $order->fulfillment_status);
        $this->assertSame($address, $order->shipping_address);
        $this->assertSame('pago_pendiente', $order->status);
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

    public function test_biological_conflicts_must_be_acknowledged_before_creating_a_payment_intent(): void
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
        $this->mockStripe(intentCount: 1);

        $this->actingAs($client)->get('/carrito')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->has('compatibilityWarnings', 1)
                ->where('compatibilityWarnings.0.products', ['Pez Disco', 'Cíclido']));

        $base = [
            'fulfillment_type' => 'click_collect',
            'request_key' => 'c0a1e39d-7ddb-48de-9a32-5cd22030c478',
        ];
        $this->actingAs($client)->postJson('/carrito/pago/stripe/intento', $base)
            ->assertUnprocessable()->assertJsonValidationErrors('compatibility');
        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($client)->postJson('/carrito/pago/stripe/intento', array_merge($base, [
            'request_key' => 'c0a1e39d-7ddb-48de-9a32-5cd22030c479',
            'compatibility_confirmed' => true,
        ]))->assertOk();

        $order = Order::firstOrFail();
        $this->assertNotNull($order->care_responsibility_accepted_at);
        $this->assertNotNull($order->compatibility_acknowledged_at);
        $this->assertSame(1, count($order->compatibility_warnings));
    }

    private function mockStripe(?array $event = null, int $intentCount = 0): void
    {
        $stripe = Mockery::mock(StripePaymentService::class);
        $stripe->shouldReceive('isConfigured')->andReturn(true);
        $stripe->shouldReceive('mode')->andReturn('test');
        $stripe->shouldReceive('isLiveMode')->andReturn(false);
        if ($intentCount > 0) {
            $stripe->shouldReceive('createPaymentIntent')->times($intentCount)->andReturn(
                ['id' => 'pi_order_1', 'status' => 'requires_payment_method', 'client_secret' => 'pi_order_1_secret_test'],
            );
        }
        if ($event !== null) {
            $stripe->shouldReceive('verifyWebhook')->once()->andReturn($event);
        }
        $stripe->shouldReceive('refundPaymentIntent')->andReturn('succeeded');
        $this->app->instance(StripePaymentService::class, $stripe);
    }

    private function paymentSucceededEvent(string $intentId, int $amountCents): array
    {
        return [
            'id' => 'evt_order_paid',
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => [
                'id' => $intentId,
                'amount_received' => $amountCents,
                'currency' => 'mxn',
                'livemode' => false,
            ]],
        ];
    }

    private function product(string $sku, int $stock): Product
    {
        return Product::create([
            'sku' => $sku,
            'name' => 'Producto de carrito',
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
            'name' => 'Cliente AQUARIUM',
            'email' => 'cliente@example.test',
            'password' => 'AcuarioSeguro123',
            'role' => User::ROLE_CLIENTE,
        ]);
    }
}
