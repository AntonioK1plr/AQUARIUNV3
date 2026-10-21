<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class SpeciesCatalogSeeder extends Seeder
{
    /**
     * Add the local demonstration species without replacing existing prices,
     * stock, images, or other operational data on repeat runs.
     */
    public function run(): void
    {
        $existingCare = [
            'AQ-FISH-OCELLARIS' => [
                'scientific_name' => 'Amphiprion ocellaris', 'adult_size_cm' => 11,
                'minimum_tank_liters' => 100, 'minimum_group_size' => 1, 'care_level' => 'intermedio',
                'care_notes' => 'Mantén salinidad y temperatura estables; ofrece refugios y compatibilidad con el resto del acuario marino.',
            ],
            'AQ-FISH-TANG-BLUE' => [
                'scientific_name' => 'Paracanthurus hepatus', 'adult_size_cm' => 30,
                'minimum_tank_liters' => 400, 'minimum_group_size' => 1, 'care_level' => 'avanzado',
                'care_notes' => 'Nada activamente y alcanza gran tamaño; requiere acuario marino largo, amplio y maduro.',
            ],
            'AQ-FISH-DISCUS-COBALT' => [
                'scientific_name' => 'Symphysodon spp.', 'adult_size_cm' => 20,
                'minimum_tank_liters' => 250, 'minimum_group_size' => 5, 'care_level' => 'avanzado',
                'care_notes' => 'Necesita agua cálida, limpia y estable; mantener un grupo requiere volumen y filtración adecuados.',
            ],
            'AQ-FISH-NEON-TETRA-10' => [
                'scientific_name' => 'Paracheirodon innesi', 'adult_size_cm' => 4,
                'minimum_tank_liters' => 60, 'minimum_group_size' => 8, 'care_level' => 'intermedio',
                'care_notes' => 'Especie de cardumen; la presentación contiene diez ejemplares. Aclimata gradualmente y evita cambios bruscos.',
            ],
        ];

        foreach ($existingCare as $sku => $care) {
            $product = Product::query()->where('sku', $sku)->first();
            if (! $product) {
                continue;
            }

            $missingCare = collect($care)->filter(fn ($value, $key) => blank($product->{$key}) && filled($value))->all();
            if ($missingCare !== []) {
                $product->fill($missingCare)->save();
            }
        }

        $species = [
            [
                'sku' => 'AQ-FISH-GUPPY', 'name' => 'Guppy', 'scientific_name' => 'Poecilia reticulata',
                'description' => 'Pez vivíparo de agua dulce disponible en distintas variedades de color.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Centroamérica y Sudamérica', 'temperament' => 'pacifico',
                'min_ph' => 7.0, 'max_ph' => 8.0, 'min_temp_c' => 22, 'max_temp_c' => 28,
                'adult_size_cm' => 5, 'minimum_tank_liters' => 60, 'minimum_group_size' => 6, 'care_level' => 'inicial',
                'care_notes' => 'Vivíparo; puede reproducirse con rapidez. Planifica el crecimiento del grupo y evita la sobrepoblación.',
                'price' => 45, 'stock' => 12, 'image_path' => '/images/fish-poecilia-reticulata.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-PLATY', 'name' => 'Platy', 'scientific_name' => 'Xiphophorus maculatus',
                'description' => 'Pez vivíparo de agua dulce; se encuentra en varias combinaciones de color.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Centroamérica', 'temperament' => 'pacifico',
                'min_ph' => 7.0, 'max_ph' => 8.2, 'min_temp_c' => 22, 'max_temp_c' => 27,
                'adult_size_cm' => 6, 'minimum_tank_liters' => 60, 'minimum_group_size' => 5, 'care_level' => 'inicial',
                'care_notes' => 'Vivíparo y prolífico. Mantén parámetros estables y considera la cantidad de crías que puede generar.',
                'price' => 55, 'stock' => 12, 'image_path' => '/images/fish-xiphophorus-maculatus.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-MOLLY', 'name' => 'Molly común', 'scientific_name' => 'Poecilia sphenops',
                'description' => 'Pez vivíparo de agua dulce que prefiere agua mineralizada y estable.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'México y Centroamérica', 'temperament' => 'pacifico',
                'min_ph' => 7.0, 'max_ph' => 8.2, 'min_temp_c' => 24, 'max_temp_c' => 28,
                'adult_size_cm' => 8, 'minimum_tank_liters' => 80, 'minimum_group_size' => 5, 'care_level' => 'intermedio',
                'care_notes' => 'Revisa dureza y mineralización del agua; los cambios bruscos y el agua muy blanda pueden afectarlo.',
                'price' => 65, 'stock' => 10, 'image_path' => '/images/fish-poecilia-sphenops.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-SWORDTAIL', 'name' => 'Cola de espada', 'scientific_name' => 'Xiphophorus hellerii',
                'description' => 'Pez vivíparo activo de agua dulce, con una extensión característica en la aleta caudal.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'México y Centroamérica', 'temperament' => 'pacifico',
                'min_ph' => 7.0, 'max_ph' => 8.3, 'min_temp_c' => 22, 'max_temp_c' => 28,
                'adult_size_cm' => 12, 'minimum_tank_liters' => 100, 'minimum_group_size' => 5, 'care_level' => 'intermedio',
                'care_notes' => 'Requiere espacio para nadar; vigila las interacciones entre machos y la reproducción del grupo.',
                'price' => 75, 'stock' => 8, 'image_path' => '/images/fish-xiphophorus-hellerii.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-ZEBRA-DANIO', 'name' => 'Danio cebra', 'scientific_name' => 'Danio rerio',
                'description' => 'Pez de agua dulce muy activo que ocupa principalmente la zona media y superior.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Asia meridional', 'temperament' => 'pacifico',
                'min_ph' => 6.5, 'max_ph' => 7.5, 'min_temp_c' => 18, 'max_temp_c' => 26,
                'adult_size_cm' => 5, 'minimum_tank_liters' => 80, 'minimum_group_size' => 8, 'care_level' => 'inicial',
                'care_notes' => 'Especie gregaria y nadadora; necesita un grupo y longitud de acuario para desplazarse.',
                'price' => 40, 'stock' => 12, 'image_path' => '/images/fish-danio-rerio.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-BLACK-SKIRT-TETRA', 'name' => 'Tetra monja', 'scientific_name' => 'Gymnocorymbus ternetzi',
                'description' => 'Tetra de agua dulce para acuarios comunitarios con especies de tamaño y actividad compatibles.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Sudamérica', 'temperament' => 'pacifico',
                'min_ph' => 6.0, 'max_ph' => 7.5, 'min_temp_c' => 22, 'max_temp_c' => 27,
                'adult_size_cm' => 6, 'minimum_tank_liters' => 80, 'minimum_group_size' => 6, 'care_level' => 'inicial',
                'care_notes' => 'Mantén un grupo suficiente y evita compañeros con aletas largas si notas mordisqueo.',
                'price' => 50, 'stock' => 12, 'image_path' => '/images/fish-gymnocorymbus-ternetzi.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-CHERRY-BARB', 'name' => 'Barbo cereza', 'scientific_name' => 'Puntius titteya',
                'description' => 'Barbo pequeño y pacífico que luce mejor en un grupo con refugios vegetales.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Sri Lanka', 'temperament' => 'pacifico',
                'min_ph' => 6.0, 'max_ph' => 7.5, 'min_temp_c' => 23, 'max_temp_c' => 27,
                'adult_size_cm' => 5, 'minimum_tank_liters' => 60, 'minimum_group_size' => 8, 'care_level' => 'intermedio',
                'care_notes' => 'Gregario; se recomienda mantenerlo en grupo. Prefiere agua blanda y estable.',
                'price' => 60, 'stock' => 10, 'image_path' => '/images/fish-puntius-titteya.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-BETTA', 'name' => 'Betta', 'scientific_name' => 'Betta splendens',
                'description' => 'Pez laberíntido de agua dulce; la convivencia debe decidirse con cautela según el ejemplar.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Sudeste asiático', 'temperament' => 'territorial',
                'min_ph' => 6.0, 'max_ph' => 7.5, 'min_temp_c' => 24, 'max_temp_c' => 28,
                'adult_size_cm' => 7, 'minimum_tank_liters' => 20, 'minimum_group_size' => 1, 'care_level' => 'intermedio',
                'care_notes' => 'No alojes dos machos juntos. Proporciona superficie tranquila, refugios y evita compañeros mordedores de aletas.',
                'price' => 180, 'stock' => 6, 'image_path' => '/images/fish-betta-splendens.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-CORYDORAS-PEPPER', 'name' => 'Coridora pimienta', 'scientific_name' => 'Corydoras paleatus',
                'description' => 'Pez de fondo pacífico; requiere alimento que llegue al sustrato y convivencia en grupo.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Sudamérica', 'temperament' => 'pacifico',
                'min_ph' => 6.0, 'max_ph' => 7.5, 'min_temp_c' => 20, 'max_temp_c' => 26,
                'adult_size_cm' => 7, 'minimum_tank_liters' => 80, 'minimum_group_size' => 6, 'care_level' => 'intermedio',
                'care_notes' => 'Mantén un grupo de la misma especie y un sustrato que no dañe sus barbillas. No sustituye la limpieza del acuario.',
                'price' => 95, 'stock' => 8, 'image_path' => '/images/fish-corydoras-paleatus.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-ANGEL', 'name' => 'Pez ángel / escalar', 'scientific_name' => 'Pterophyllum scalare',
                'description' => 'Cíclido de agua dulce que alcanza gran altura y puede depredar peces pequeños.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Cuenca del Amazonas', 'temperament' => 'semi-agresivo',
                'min_ph' => 6.0, 'max_ph' => 7.5, 'min_temp_c' => 24, 'max_temp_c' => 30,
                'adult_size_cm' => 15, 'minimum_tank_liters' => 200, 'minimum_group_size' => 5, 'care_level' => 'intermedio',
                'care_notes' => 'Considera su altura y tamaño adulto. No lo combines con peces pequeños que pueda ingerir.',
                'price' => 220, 'stock' => 6, 'image_path' => '/images/fish-pterophyllum-scalare.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-HARLEQUIN-RASBORA', 'name' => 'Rasbora arlequín', 'scientific_name' => 'Trigonostigma heteromorpha',
                'description' => 'Pez de cardumen para acuario comunitario tranquilo, plantado y de agua estable.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Sudeste asiático', 'temperament' => 'pacifico',
                'min_ph' => 6.0, 'max_ph' => 7.5, 'min_temp_c' => 23, 'max_temp_c' => 28,
                'adult_size_cm' => 5, 'minimum_tank_liters' => 60, 'minimum_group_size' => 8, 'care_level' => 'intermedio',
                'care_notes' => 'Gregaria; el cardumen y los parámetros estables son más importantes que mantener pocos individuos aislados.',
                'price' => 65, 'stock' => 10, 'image_path' => '/images/fish-trigonostigma-heteromorpha.jpg', 'is_active' => true, 'is_featured' => false,
            ],
            [
                'sku' => 'AQ-FISH-PRISTELLA-TETRA', 'name' => 'Tetra pristella', 'scientific_name' => 'Pristella maxillaris',
                'description' => 'Tetra pequeño de agua dulce y comportamiento gregario, adecuado para grupos compatibles.',
                'category' => 'pez', 'water_type' => 'dulce', 'origin' => 'Sudamérica', 'temperament' => 'pacifico',
                'min_ph' => 6.0, 'max_ph' => 7.5, 'min_temp_c' => 22, 'max_temp_c' => 28,
                'adult_size_cm' => 5, 'minimum_tank_liters' => 60, 'minimum_group_size' => 6, 'care_level' => 'inicial',
                'care_notes' => 'Mantén un cardumen y evita mezclarlo con especies que puedan depredarlo.',
                'price' => 55, 'stock' => 10, 'image_path' => '/images/fish-pristella-maxillaris.jpg', 'is_active' => true, 'is_featured' => false,
            ],
        ];

        foreach ($species as $data) {
            $product = Product::firstOrNew(['sku' => $data['sku']]);

            if (! $product->exists) {
                $product->fill($data)->save();
                continue;
            }

            // Only fill missing care metadata; never alter live inventory or price.
            $metadata = collect($data)->only([
                'scientific_name', 'adult_size_cm', 'minimum_tank_liters',
                'minimum_group_size', 'care_level', 'care_notes',
            ])->filter(fn ($value, $key) => blank($product->{$key}) && filled($value))->all();

            if ($metadata !== []) {
                $product->fill($metadata)->save();
            }
        }

        $speciesImages = [
            'AQ-FISH-GUPPY' => '/images/fish-poecilia-reticulata.jpg',
            'AQ-FISH-PLATY' => '/images/fish-xiphophorus-maculatus.jpg',
            'AQ-FISH-MOLLY' => '/images/fish-poecilia-sphenops.jpg',
            'AQ-FISH-SWORDTAIL' => '/images/fish-xiphophorus-hellerii.jpg',
            'AQ-FISH-ZEBRA-DANIO' => '/images/fish-danio-rerio.jpg',
            'AQ-FISH-BLACK-SKIRT-TETRA' => '/images/fish-gymnocorymbus-ternetzi.jpg',
            'AQ-FISH-CHERRY-BARB' => '/images/fish-puntius-titteya.jpg',
            'AQ-FISH-BETTA' => '/images/fish-betta-splendens.jpg',
            'AQ-FISH-CORYDORAS-PEPPER' => '/images/fish-corydoras-paleatus.jpg',
            'AQ-FISH-ANGEL' => '/images/fish-pterophyllum-scalare.jpg',
            'AQ-FISH-HARLEQUIN-RASBORA' => '/images/fish-trigonostigma-heteromorpha.jpg',
            'AQ-FISH-PRISTELLA-TETRA' => '/images/fish-pristella-maxillaris.jpg',
        ];

        foreach ($speciesImages as $sku => $image) {
            Product::query()->where('sku', $sku)->where('image_path', '/images/cat-aquariums.jpg')->update(['image_path' => $image]);
        }
    }
}
