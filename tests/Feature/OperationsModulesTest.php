<?php

namespace Tests\Feature;

use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OperationsModulesTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_view_and_create_employee_accounts(): void
    {
        $customer = $this->makeUser(User::ROLE_CLIENTE, 'cliente@example.test');
        $this->actingAs($customer)->get('/admin/empleados')->assertForbidden();

        $administrator = $this->makeUser(User::ROLE_ADMINISTRADOR, 'admin@example.test');
        $this->actingAs($administrator)->get('/admin/empleados')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Employees/Index')->has('employees.data'));

        $this->post('/admin/empleados', [
            'name' => 'Luis Almacén',
            'email' => 'luis@example.test',
            'phone' => '5555555555',
            'role' => User::ROLE_ALMACENISTA,
            'password' => 'AcuarioSeguro123',
            'password_confirmation' => 'AcuarioSeguro123',
        ])->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'luis@example.test',
            'phone' => '5555555555',
            'role' => User::ROLE_ALMACENISTA,
        ]);
    }

    public function test_only_warehouse_and_admin_roles_can_open_inventory(): void
    {
        $this->get('/almacen')->assertRedirect('/acceso');

        $customer = $this->makeUser(User::ROLE_CLIENTE, 'cliente@example.test');
        $this->actingAs($customer)->get('/almacen')->assertForbidden();

        $warehouse = $this->makeUser(User::ROLE_ALMACENISTA, 'warehouse@example.test');
        $this->actingAs($warehouse)->get('/almacen')->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Warehouse/Index')->where('summary.products', 0));
    }

    public function test_warehouse_can_create_a_product_and_receive_a_traced_lot(): void
    {
        $warehouse = $this->makeUser(User::ROLE_ALMACENISTA, 'warehouse@example.test');
        $this->actingAs($warehouse)->post('/almacen/productos', [
            'sku' => 'aq-test-001',
            'name' => 'Filtro de Prueba',
            'category' => 'accesorio',
            'water_type' => '',
            'brand' => 'AQUARIUM',
            'price' => 650,
            'warehouse_location' => 'a-01-02',
        ])->assertRedirect();

        $product = Product::where('sku', 'AQ-TEST-001')->firstOrFail();
        $this->assertSame(0, $product->stock);
        $this->assertSame('A-01-02', $product->warehouse_location);

        $this->actingAs($warehouse)->post('/almacen/productos', [
            'sku' => 'AQ-HEALTH-TEST-001',
            'name' => 'Kit de observación clínica',
            'category' => 'medicina',
            'water_type' => '',
            'price' => 350,
        ])->assertRedirect();
        $this->assertDatabaseHas('products', ['sku' => 'AQ-HEALTH-TEST-001', 'category' => 'medicina', 'stock' => 0]);

        $this->actingAs($warehouse)->patch("/almacen/productos/{$product->id}/ubicacion", [
            'warehouse_location' => 'B-03-01',
        ])->assertRedirect();
        $this->assertDatabaseHas('products', ['id' => $product->id, 'warehouse_location' => 'B-03-01']);

        $this->from('/almacen')->patch("/almacen/productos/{$product->id}/ubicacion", [
            'warehouse_location' => 'Ubicación no válida!',
        ])->assertRedirect('/almacen')->assertSessionHasErrors('warehouse_location');

        $this->post("/almacen/productos/{$product->id}/lotes", [
            'lot_code' => 'LOTE-2026-001',
            'supplier' => 'Proveedor local',
            'received_at' => now()->toDateString(),
            'quantity' => 18,
            'unit_cost' => 320,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 18, 'cost_price' => 320]);
        $this->assertDatabaseHas('inventory_lots', ['product_id' => $product->id, 'lot_code' => 'LOTE-2026-001', 'quantity_remaining' => 18]);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'quantity_delta' => 18, 'quantity_before' => 0, 'quantity_after' => 18]);
    }

    public function test_warehouse_can_record_mortality_but_cannot_overdraw_stock(): void
    {
        $warehouse = $this->makeUser(User::ROLE_ALMACENISTA, 'warehouse@example.test');
        $this->actingAs($warehouse);
        $product = Product::create([
            'sku' => 'AQ-FISH-TEST', 'name' => 'Pez de prueba', 'category' => 'pez', 'water_type' => 'dulce',
            'price' => 200, 'stock' => 7, 'is_active' => true,
        ]);

        $this->post("/almacen/productos/{$product->id}/salidas", [
            'type' => 'mortalidad_biologica', 'quantity' => 2, 'reason' => 'Mortalidad registrada en revisión diaria',
        ])->assertRedirect();

        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 5]);
        $this->assertDatabaseHas('inventory_movements', ['product_id' => $product->id, 'type' => 'mortalidad_biologica', 'quantity_delta' => -2, 'quantity_before' => 7, 'quantity_after' => 5]);

        $this->from('/almacen')->post("/almacen/productos/{$product->id}/salidas", [
            'type' => 'merma', 'quantity' => 6, 'reason' => 'Daño',
        ])->assertRedirect('/almacen')->assertSessionHasErrors('quantity');

        $this->assertSame(1, InventoryMovement::where('product_id', $product->id)->count());
    }

    public function test_product_cannot_be_retired_while_it_has_stock(): void
    {
        $warehouse = $this->makeUser(User::ROLE_ALMACENISTA, 'warehouse@example.test');
        $product = Product::create([
            'sku' => 'AQ-KEEP-001', 'name' => 'Producto con stock', 'category' => 'accesorio', 'price' => 50,
            'stock' => 1, 'is_active' => true,
        ]);

        $this->actingAs($warehouse)->delete("/almacen/productos/{$product->id}")
            ->assertRedirect()->assertSessionHasErrors('product');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => true]);
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
