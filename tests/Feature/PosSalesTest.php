<?php

namespace Tests\Feature;

use App\Models\InventoryMovement;
use App\Models\CartReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PosSalesTest extends TestCase
{
    use RefreshDatabase;

    public function test_cashier_can_view_the_pos_but_customer_cannot(): void
    {
        $this->get('/pos')->assertRedirect('/acceso');
        $client = $this->makeUser(User::ROLE_CLIENTE, 'client@example.test');
        $this->actingAs($client)->get('/pos')->assertForbidden();

        $cashier = $this->makeUser(User::ROLE_CAJERO_POS, 'cashier@example.test');
        $this->actingAs($cashier)->get('/pos')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('POS/Index'));
    }

    public function test_pos_sale_simulates_payment_calculates_tax_and_decrements_stock_with_audit(): void
    {
        $cashier = $this->makeUser(User::ROLE_CAJERO_POS, 'cashier@example.test');
        $product = $this->product('AQ-POS-001', 'Filtro de muestra', 100, 10);

        $this->actingAs($cashier)->post('/pos/ventas', [
            'customer_name' => 'Cliente de prueba',
            'customer_email' => '',
            'payment_method' => 'tarjeta_simulada',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertRedirect('/pos')->assertSessionHas('receipt');

        $order = Order::with(['items', 'payment'])->firstOrFail();
        $this->assertSame('200.00', $order->subtotal);
        $this->assertSame('32.00', $order->tax);
        $this->assertSame('232.00', $order->total);
        $this->assertSame('cargo_simulado', $order->payment->status);
        $this->assertSame('tarjeta_simulada', $order->payment->method);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 8]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'recorded_by' => $cashier->id,
            'type' => 'venta_pos',
            'quantity_delta' => -2,
            'quantity_before' => 10,
            'quantity_after' => 8,
        ]);
    }

    public function test_sale_is_rejected_atomically_when_stock_is_insufficient(): void
    {
        $cashier = $this->makeUser(User::ROLE_CAJERO_POS, 'cashier@example.test');
        $product = $this->product('AQ-POS-002', 'Stock limitado', 100, 1);

        $this->actingAs($cashier)->from('/pos')->post('/pos/ventas', [
            'payment_method' => 'transferencia_simulada',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertRedirect('/pos')->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('simulated_order_payments', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 1]);
        $this->assertSame(0, InventoryMovement::count());
    }

    public function test_real_cash_method_is_not_accepted_in_the_local_pos(): void
    {
        $cashier = $this->makeUser(User::ROLE_CAJERO_POS, 'cashier@example.test');
        $product = $this->product('AQ-POS-003', 'Producto de prueba', 20, 2);

        $this->actingAs($cashier)->from('/pos')->post('/pos/ventas', [
            'payment_method' => 'efectivo',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect('/pos')->assertSessionHasErrors('payment_method');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_simulated_cash_calculates_change_and_z_closure_blocks_more_sales(): void
    {
        $cashier = $this->makeUser(User::ROLE_CAJERO_POS, 'cash-z@example.test');
        $product = $this->product('AQ-POS-CASH', 'Producto en efectivo simulado', 100, 4);

        $this->actingAs($cashier)->post('/pos/ventas', [
            'payment_method' => 'efectivo_simulado',
            'cash_tendered' => 200,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect('/pos')->assertSessionHas('receipt');

        $this->assertDatabaseHas('simulated_order_payments', [
            'method' => 'efectivo_simulado', 'amount' => '116.00',
            'amount_tendered' => '200.00', 'change_amount' => '84.00',
        ]);

        $this->get('/pos/cierre-z')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('POS/Close')
                ->where('summary.sales_count', 1)
                ->where('summary.cash_sales', fn ($value) => (float) $value === 116.0));

        $this->post('/pos/cierre-z', ['opening_float' => 50, 'counted_cash' => 166])
            ->assertRedirect('/pos/cierre-z')->assertSessionHas('success');
        $this->assertDatabaseHas('pos_closures', [
            'cashier_id' => $cashier->id, 'expected_cash' => '166.00',
            'counted_cash' => '166.00', 'difference' => '0.00', 'sales_count' => 1,
        ]);

        $this->get('/pos')->assertInertia(fn (Assert $page) => $page->where('closedForToday', true));
        $this->from('/pos')->post('/pos/ventas', [
            'payment_method' => 'tarjeta_simulada',
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect('/pos')->assertSessionHasErrors('payment_method');
        $this->assertSame(1, Order::count());
    }

    public function test_simulated_cash_rejects_an_insufficient_tender_before_creating_a_sale(): void
    {
        $cashier = $this->makeUser(User::ROLE_CAJERO_POS, 'cash-short@example.test');
        $product = $this->product('AQ-POS-CASH-SHORT', 'Producto en efectivo incompleto', 100, 4);

        $this->actingAs($cashier)->from('/pos')->post('/pos/ventas', [
            'payment_method' => 'efectivo_simulado', 'cash_tendered' => 100,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertRedirect('/pos')->assertSessionHasErrors('cash_tendered');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 4]);
    }

    public function test_pos_cannot_sell_units_reserved_in_customer_carts(): void
    {
        $cashier = $this->makeUser(User::ROLE_CAJERO_POS, 'cashier@example.test');
        $product = $this->product('AQ-POS-004', 'Apartado en carrito', 100, 5);
        CartReservation::create([
            'session_hash' => str_repeat('b', 64), 'product_id' => $product->id,
            'quantity' => 3, 'expires_at' => now()->addMinutes(15),
        ]);

        $this->actingAs($cashier)->from('/pos')->post('/pos/ventas', [
            'payment_method' => 'tarjeta_simulada',
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
        ])->assertRedirect('/pos')->assertSessionHasErrors('items');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
    }

    private function product(string $sku, string $name, int $price, int $stock): Product
    {
        return Product::create([
            'sku' => $sku,
            'name' => $name,
            'category' => 'accesorio',
            'price' => $price,
            'stock' => $stock,
            'is_active' => true,
        ]);
    }

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name' => ucfirst($role),
            'email' => $email,
            'password' => 'AcuarioSeguro123',
            'role' => $role,
        ]);
    }
}
