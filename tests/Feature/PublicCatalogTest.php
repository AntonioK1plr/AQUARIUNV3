<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_see_the_complete_active_catalog(): void
    {
        Product::create($this->product('AQ-001', 'Pez Payaso', 'salada'));
        Product::create($this->product('AQ-002', 'Pez Disco', 'dulce'));
        Product::create($this->product('AQ-003', 'Producto oculto', 'dulce', false));

        $this->get('/catalogo')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 2)
                ->where('products.data.0.name', 'Pez Disco')
                ->where('products.data.1.name', 'Pez Payaso'));
    }

    public function test_a_guest_can_filter_the_catalog_by_search_and_water_type(): void
    {
        Product::create($this->product('AQ-001', 'Pez Payaso', 'salada', true, false, 'Arrecife'));
        Product::create($this->product('AQ-002', 'Pez Disco', 'dulce', true, false, 'Amazonia'));

        $this->get('/catalogo?q=payaso&water_type=salada')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.sku', 'AQ-001')
                ->where('filters.q', 'payaso')
                ->where('filters.water_type', 'salada'));
    }

    public function test_a_guest_can_search_by_scientific_name_and_view_species_care_fields(): void
    {
        Product::create(array_merge($this->product('AQ-003', 'Guppy', 'dulce'), [
            'scientific_name' => 'Poecilia reticulata',
            'adult_size_cm' => 5,
            'minimum_tank_liters' => 60,
            'minimum_group_size' => 6,
            'care_level' => 'inicial',
            'care_notes' => 'Vivíparo; considera su reproducción.',
        ]));

        $this->get('/catalogo?q=Poecilia')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.scientific_name', 'Poecilia reticulata')
                ->where('products.data.0.minimum_tank_liters', 60)
                ->where('products.data.0.minimum_group_size', 6));
    }

    public function test_a_guest_can_filter_by_brand_and_price_range(): void
    {
        Product::create($this->product('AQ-001', 'Filtro Cascada', 'dulce', true, false, 'AQUARIUM', 850));
        Product::create($this->product('AQ-002', 'Filtro Externo', 'dulce', true, false, 'AQUARIUM', 1800));
        Product::create($this->product('AQ-003', 'Filtro Compacto', 'dulce', true, false, 'Oceanic', 900));

        $this->get('/catalogo?brand=AQUARIUM&min_price=800&max_price=1000')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.sku', 'AQ-001')
                ->where('filters.brand', 'AQUARIUM')
                ->has('brands', 2));
    }

    public function test_a_guest_can_filter_catalog_by_product_category(): void
    {
        Product::create(array_merge($this->product('AQ-FOOD-001', 'Alimento tropical', 'dulce'), ['category' => 'alimento']));
        Product::create(array_merge($this->product('AQ-SUBSTRATE-001', 'Arena inerte', 'dulce'), ['category' => 'sustrato']));

        $this->get('/catalogo?category=sustrato')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Catalog/Index')
                ->has('products.data', 1)
                ->where('products.data.0.sku', 'AQ-SUBSTRATE-001')
                ->where('filters.category', 'sustrato'));
    }

    public function test_a_guest_cannot_submit_an_inverted_price_range(): void
    {
        $this->get('/catalogo?min_price=1200&max_price=500')
            ->assertRedirect()
            ->assertSessionHasErrors('max_price');
    }

    public function test_home_uses_database_backed_featured_products(): void
    {
        Product::create($this->product('AQ-001', 'Pez Payaso', 'salada', true, true));
        Product::create($this->product('AQ-002', 'Pez Disco', 'dulce', true, false));

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Home')
                ->has('featuredProducts', 1)
                ->where('featuredProducts.0.sku', 'AQ-001'));
    }

    private function product(string $sku, string $name, string $waterType, bool $active = true, bool $featured = false, ?string $brand = null, int $price = 100): array
    {
        return [
            'sku' => $sku,
            'name' => $name,
            'description' => 'Producto de prueba',
            'category' => 'pez',
            'water_type' => $waterType,
            'brand' => $brand,
            'price' => $price,
            'stock' => 5,
            'image_path' => '/images/fish-clown.jpg',
            'is_active' => $active,
            'is_featured' => $featured,
        ];
    }
}
