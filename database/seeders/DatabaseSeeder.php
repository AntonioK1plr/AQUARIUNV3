<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => 'AQ-FISH-OCELLARIS',
                'name' => 'Pez Payaso Ocellaris',
                'description' => 'Especie marina de temperamento pacífico. Requiere parámetros estables y aclimatación responsable.',
                'category' => 'pez', 'water_type' => 'salada', 'origin' => 'Pacífico', 'temperament' => 'pacifico',
                'min_ph' => 7.8, 'max_ph' => 8.4, 'min_temp_c' => 24, 'max_temp_c' => 28,
                'price' => 480, 'stock' => 12, 'image_path' => '/images/fish-clown.jpg',
                'is_active' => true, 'is_featured' => true,
            ],
            [
                'sku' => 'AQ-FISH-TANG-BLUE',
                'name' => 'Pez Cirujano Azul',
                'description' => 'Especie marina de nado activo. Requiere acuario amplio y parámetros estables.',
                'category' => 'pez', 'water_type' => 'salada', 'origin' => 'Indo-Pacífico', 'temperament' => 'semi-agresivo',
                'min_ph' => 8.1, 'max_ph' => 8.4, 'min_temp_c' => 24, 'max_temp_c' => 28,
                'price' => 1250, 'stock' => 8, 'image_path' => '/images/fish-tang.jpg',
                'is_active' => true, 'is_featured' => true,
            ],
            [
                'sku' => 'AQ-FISH-DISCUS-COBALT',
                'name' => 'Pez Disco Azul Cobalto',
                'description' => 'Pez tropical de agua dulce que requiere agua cálida y de buena calidad.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Amazonas', 'temperament' => 'pacifico',
                'min_ph' => 6.0, 'max_ph' => 7.0, 'min_temp_c' => 28, 'max_temp_c' => 30,
                'price' => 950, 'stock' => 6, 'image_path' => '/images/fish-discus.jpg',
                'is_active' => true, 'is_featured' => true,
            ],
            [
                'sku' => 'AQ-FISH-NEON-TETRA-10',
                'name' => 'Cardumen Neon Tetra (10x)',
                'description' => 'Grupo de diez peces pequeños de agua dulce; mantener en cardumen.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Amazonas', 'temperament' => 'pacifico',
                'min_ph' => 6.0, 'max_ph' => 7.5, 'min_temp_c' => 22, 'max_temp_c' => 26,
                'price' => 240, 'stock' => 20, 'image_path' => '/images/fish-tetra.jpg',
                'is_active' => true, 'is_featured' => true,
            ],
            [
                'sku' => 'AQ-FILTER-CASCADE-COMPACT',
                'name' => 'Filtro de Cascada Compacto',
                'description' => 'Filtro para acuarios pequeños con flujo ajustable y espacio para material biológico.',
                'category' => 'accesorio', 'water_type' => null, 'brand' => 'AQUARIUM',
                'price' => 890, 'stock' => 7, 'image_path' => '/images/cat-equipment.jpg',
                'is_active' => true, 'is_featured' => false,
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['sku' => $product['sku']], $product);
        }

        $this->call(SpeciesCatalogSeeder::class);
        $this->call(ExpandedSpeciesCatalogSeeder::class);
        $this->call(AquariumSuppliesSeeder::class);
    }
}
