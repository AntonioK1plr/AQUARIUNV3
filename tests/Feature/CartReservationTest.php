<?php

namespace Tests\Feature;

use App\Models\CartReservation;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartReservationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cart_reserves_units_for_thirty_minutes_and_limits_other_sessions(): void
    {
        $product = Product::create([
            'sku' => 'AQ-RESERVE-001', 'name' => 'Accesorio de prueba', 'category' => 'accesorio',
            'price' => 100, 'stock' => 5, 'is_active' => true,
        ]);

        $this->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 3])->assertRedirect();
        $reservation = CartReservation::firstOrFail();
        $this->assertSame(3, $reservation->quantity);
        $this->assertTrue($reservation->expires_at->between(now()->addMinutes(29), now()->addMinutes(30)));

        $this->withSession(['aquarium_cart_owner' => 'other-browser-session'])->from('/catalogo')
            ->post('/carrito/items', ['product_id' => $product->id, 'quantity' => 3])
            ->assertRedirect('/catalogo')->assertSessionHasErrors('product');
        $this->assertDatabaseCount('cart_reservations', 1);
    }

    public function test_expired_cart_reservations_are_removed_by_the_local_command(): void
    {
        $product = Product::create([
            'sku' => 'AQ-RESERVE-002', 'name' => 'Accesorio de prueba', 'category' => 'accesorio',
            'price' => 100, 'stock' => 2, 'is_active' => true,
        ]);
        CartReservation::create([
            'session_hash' => str_repeat('a', 64), 'product_id' => $product->id,
            'quantity' => 1, 'expires_at' => now()->subSecond(),
        ]);

        $this->artisan('aquarium:carts:expire-reservations --dry-run')->expectsOutputToContain('1 reservas de carrito vencieron')->assertSuccessful();
        $this->assertDatabaseCount('cart_reservations', 1);
        $this->artisan('aquarium:carts:expire-reservations')->expectsOutputToContain('1')->assertSuccessful();
        $this->assertDatabaseCount('cart_reservations', 0);
    }
}
