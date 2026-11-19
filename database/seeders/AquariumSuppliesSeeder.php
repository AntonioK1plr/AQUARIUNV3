<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class AquariumSuppliesSeeder extends Seeder
{
    /** Aquarium supply catalog; repeat runs preserve existing stock and prices. */
    public function run(): void
    {
        $products = [
            ['AQ-TANK-COMMUNITY-60', 'Acuario comunitario 60 L', 'Pecera de vidrio para montaje de agua dulce. El volumen no sustituye la selección compatible de especies ni el ciclado.', 'acuario', null, 899, 4, '/images/cat-aquariums.jpg'],
            ['AQ-TANK-PLANTED-120', 'Acuario plantado 120 L', 'Pecera de 120 L para acuarios de agua dulce con espacio para plantas, filtro y refugios.', 'acuario', null, 1890, 3, '/images/cat-aquariums.jpg'],
            ['AQ-TANK-MARINE-300', 'Acuario marino 300 L', 'Pecera para sistema marino. Requiere filtración, circulación, control de salinidad y maduración del sistema.', 'acuario', 'salada', 5490, 2, '/images/cat-saltwater.jpg'],
            ['AQ-TANK-QUARANTINE-40', 'Acuario hospital / cuarentena 40 L', 'Tanque auxiliar para aislamiento y observación. No incluye medicamento; el tratamiento depende de una evaluación veterinaria.', 'acuario', null, 790, 3, '/images/cat-aquariums.jpg'],

            ['AQ-FILTER-HANG-120', 'Filtro de cascada 60–120 L', 'Filtro para acuarios pequeños y medianos; confirma caudal y carga biológica según población.', 'filtracion', null, 590, 6, '/images/cat-equipment.jpg'],
            ['AQ-FILTER-CANISTER-400', 'Filtro externo canister 200–400 L', 'Filtro externo para acuarios de mayor volumen. La capacidad nominal debe ajustarse a la carga real del acuario.', 'filtracion', null, 2390, 3, '/images/cat-equipment.jpg'],
            ['AQ-FILTER-SPONGE', 'Filtro de esponja para cuarentena', 'Filtración suave para tanque hospital o cuarentena; requiere esponja madura para aportar filtración biológica.', 'filtracion', null, 190, 8, '/images/cat-equipment.jpg'],
            ['AQ-FILTER-MEDIA-CERAMIC', 'Material biológico cerámico 500 ml', 'Medio poroso para colonización bacteriana en el filtro. Enjuaga con agua del acuario para conservar la colonia.', 'filtracion', null, 230, 10, '/images/cat-equipment.jpg'],
            ['AQ-HEATER-100W', 'Calentador con termostato 100 W', 'Calentador regulable para montajes tropicales compatibles; verifica el volumen y la temperatura objetivo de cada especie.', 'accesorio', null, 320, 7, '/images/cat-equipment.jpg'],
            ['AQ-WATER-TEST-KIT', 'Kit para medir la calidad del agua dulce', 'Mide pH, amoniaco, nitrito y nitrato durante el ciclado y el mantenimiento del acuario.', 'medicina', null, 690, 6, '/images/cat-equipment.jpg'],
            ['AQ-CONDITIONER-300ML', 'Acondicionador de agua 300 ml', 'Acondicionador para preparar agua nueva. Sigue la etiqueta del fabricante y comprueba los parámetros antes de usarla.', 'accesorio', null, 180, 10, '/images/cat-food.jpg'],

            ['AQ-SUBSTRATE-QUARTZ-5KG', 'Arena de cuarzo inerte 5 kg', 'Sustrato inerte para acuarios de agua dulce. Enjuaga antes de usar y evita bordes cortantes.', 'sustrato', null, 260, 8, '/images/cat-aquariums.jpg'],
            ['AQ-SUBSTRATE-FINE-5KG', 'Arena fina para peces de fondo 5 kg', 'Sustrato fino para acuarios con coridoras. Mantén la superficie limpia y sin compactación excesiva.', 'sustrato', null, 290, 7, '/images/cat-aquariums.jpg'],
            ['AQ-SUBSTRATE-PLANTED-3L', 'Sustrato para acuario plantado 3 L', 'Sustrato para plantas acuáticas; revisa si altera pH o dureza antes de combinarlo con especies sensibles.', 'sustrato', 'dulce', 350, 5, '/images/cat-aquariums.jpg'],
            ['AQ-SUBSTRATE-ARAGONITE-5KG', 'Arena de aragonita marina 5 kg', 'Sustrato mineral para montajes marinos; puede influir en pH y alcalinidad, por lo que requiere monitoreo.', 'sustrato', 'salada', 420, 5, '/images/cat-saltwater.jpg'],

            ['AQ-DECOR-ROCK-INERT', 'Roca decorativa inerte para acuario', 'Decoración para acuario; enjuaga, verifica que sea apta para uso acuático y evita rocas con bordes que puedan herir peces.', 'decoracion', null, 160, 10, '/images/cat-aquariums.jpg'],
            ['AQ-DECOR-DRIFTWOOD', 'Tronco natural para acuario', 'Elemento de refugio y decoración. Curar y preparar antes de incorporarlo; puede teñir ligeramente el agua.', 'decoracion', 'dulce', 240, 6, '/images/cat-aquariums.jpg'],
            ['AQ-DECOR-CAVE', 'Cueva de refugio para peces', 'Refugio de superficie lisa para descanso y reducción de estrés. Selecciona el tamaño según los ejemplares.', 'decoracion', null, 130, 8, '/images/cat-aquariums.jpg'],

            ['AQ-FOOD-TROPICAL-FLAKES', 'Alimento completo en hojuelas tropicales', 'Alimento para guppy, platy, molly, cola de espada, danio cebra, tetra monja, barbo cereza, rasbora arlequín y tetra pristella. Porciones pequeñas.', 'alimento', 'dulce', 145, 12, '/images/cat-food.jpg'],
            ['AQ-FOOD-LIVEBEARER', 'Microgránulo para vivíparos', 'Alimento para guppy, platy, molly y cola de espada. Complementa con una dieta variada y evita sobrealimentar.', 'alimento', 'dulce', 165, 10, '/images/cat-food.jpg'],
            ['AQ-FOOD-BETTA', 'Pellets flotantes para betta', 'Alimento formulado para betta. Ofrece porciones pequeñas y retira sobrantes.', 'alimento', 'dulce', 120, 8, '/images/cat-food.jpg'],
            ['AQ-FOOD-DISCUS', 'Granulado para peces disco', 'Alimento para peces disco; combina variedad alimentaria con higiene y control del agua.', 'alimento', 'dulce', 220, 6, '/images/cat-food.jpg'],
            ['AQ-FOOD-BOTTOM-WAFERS', 'Tabletas de fondo para coridoras', 'Alimento que se hunde y llega a peces de fondo, incluidas coridoras. No sustituye una dieta específica completa.', 'alimento', 'dulce', 155, 9, '/images/cat-food.jpg'],
            ['AQ-FOOD-ANGEL', 'Granulado tropical para pez ángel', 'Alimento para pez ángel adulto; selecciona tamaño de partícula adecuado y evita compañeros que compitan por alimento.', 'alimento', 'dulce', 180, 7, '/images/cat-food.jpg'],
            ['AQ-FOOD-MARINE-OMNIVORE', 'Alimento marino para peces omnívoros', 'Alimento para peces marinos omnívoros como el pez payaso; ofrece variedad y controla los sobrantes.', 'alimento', 'salada', 240, 8, '/images/cat-food.jpg'],
            ['AQ-FOOD-MARINE-ALGAE', 'Alimento marino con algas', 'Complemento vegetal para peces marinos herbívoros como el pez cirujano azul.', 'alimento', 'salada', 210, 8, '/images/cat-food.jpg'],

            ['AQ-HEALTH-QUARANTINE-SET', 'Kit básico de observación y cuarentena', 'Incluye utensilios de uso separado para observar ejemplares nuevos. No contiene fármacos y no reemplaza una consulta veterinaria.', 'medicina', null, 390, 4, '/images/cat-equipment.jpg'],
            ['AQ-HEALTH-PARASITE-REFERENCE', 'Ficha informativa: tratamiento antiparasitario', 'Esta ficha no corresponde a un medicamento comercializable ni contiene principio activo o dosis. Todo tratamiento requiere diagnóstico veterinario.', 'medicina', null, 0, 0, '/images/cat-equipment.jpg'],
            ['AQ-HEALTH-BACTERIAL-REFERENCE', 'Ficha informativa: tratamiento antibacteriano', 'Esta ficha no corresponde a un medicamento comercializable ni contiene principio activo o dosis. Los antimicrobianos requieren evaluación veterinaria.', 'medicina', null, 0, 0, '/images/cat-equipment.jpg'],
        ];

        foreach ($products as [$sku, $name, $description, $category, $waterType, $price, $stock, $image]) {
            $product = Product::firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'description' => $description,
                'category' => $category,
                'water_type' => $waterType,
                'brand' => 'AQUARIUM',
                'price' => $price,
                'stock' => $stock,
                'image_path' => $image,
                'is_active' => true,
                'is_featured' => false,
            ]);
            $product->forceFill(['name' => $name, 'description' => $description, 'brand' => 'AQUARIUM'])->save();
        }

        $expandedProducts = [
            ['AQ-TANK-NANO-20', 'Acuario nano 20 L', 'Tanque compacto para montajes apropiados a su volumen; requiere filtro, tapa y selección cuidadosa de organismos.', 'acuario', null, null, 490, 6, '/images/tank-nano.jpg'],
            ['AQ-TANK-COMMUNITY-90', 'Acuario comunitario 90 L', 'Pecera de 90 L para un montaje de agua dulce planificado; el volumen útil cambia con sustrato y decoración.', 'acuario', 'dulce', null, 1290, 4, '/images/tank-planted.jpg'],
            ['AQ-TANK-PLANTED-180', 'Acuario plantado 180 L', 'Tanque de 180 L para acuario plantado, con espacio para filtro, iluminación y refugios.', 'acuario', 'dulce', null, 2890, 3, '/images/tank-planted.jpg'],
            ['AQ-TANK-MARINE-450', 'Acuario marino 450 L', 'Tanque para sistema marino de gran volumen; planifica soporte, circulación, filtración y maduración.', 'acuario', 'salada', null, 8990, 2, '/images/tank-marine.jpg'],
            ['AQ-TANK-BREEDER-60', 'Acuario de cría 60 L', 'Tanque auxiliar para separación y observación; requiere filtro maduro y seguimiento de calidad de agua.', 'acuario', 'dulce', null, 950, 3, '/images/tank-nano.jpg'],

            ['AQ-FILTER-FLUVAL-HOB', 'Fluval · Filtro de cascada para 60–130 L', 'Filtro de cascada; confirma el caudal y la compatibilidad con el volumen del acuario antes de elegirlo.', 'filtracion', null, 'Fluval', 1190, 5, '/images/filter-hob.jpg'],
            ['AQ-FILTER-EHEIM-CANISTER', 'EHEIM · Filtro externo para 200–350 L', 'Filtro externo; confirma la capacidad, el caudal y la compatibilidad en la ficha oficial del fabricante.', 'filtracion', null, 'EHEIM', 3590, 3, '/images/filter-canister.jpg'],
            ['AQ-FILTER-AQUAEL-SPONGE', 'Aquael · Filtro de esponja para cuarentena', 'Filtro de esponja para tanques auxiliares. Requiere material biológico maduro para aportar filtración.', 'filtracion', null, 'Aquael', 350, 8, '/images/filter-sponge.jpg'],
            ['AQ-FILTER-TETRA-INTERNAL', 'Tetra · Filtro interno para 60–100 L', 'Filtro interno para acuarios de 60 a 100 L; confirma caudal y compatibilidad en la ficha del fabricante.', 'filtracion', null, 'Tetra', 790, 5, '/images/filter-internal.jpg'],
            ['AQ-FILTER-SICCE-SUMP', 'Sicce · Bomba y filtración para sump', 'Equipo de circulación y filtración para montajes marinos. La selección final depende del sistema.', 'filtracion', 'salada', 'Sicce', 2190, 3, '/images/filter-sump.jpg'],
            ['AQ-FILTER-SEACHEM-HOB', 'Seachem · Filtro de mochila para acuario', 'Filtro de mochila; confirma capacidad, caudal y compatibilidad en la ficha del fabricante.', 'filtracion', null, 'Seachem', 1450, 4, '/images/filter-hob.jpg'],

            ['AQ-SUBSTRATE-BLACK-SAND-5KG', 'Arena negra inerte 5 kg', 'Arena para acuarios de agua dulce; enjuagar y comprobar que no tenga filos ni altere parámetros.', 'sustrato', 'dulce', null, 320, 7, '/images/substrate-sand.jpg'],
            ['AQ-SUBSTRATE-RIVER-GRAVEL-5KG', 'Grava de río redondeada 5 kg', 'Grava para montajes de agua dulce; comprobar tamaño de grano y limpieza antes de introducir peces de fondo.', 'sustrato', 'dulce', null, 280, 8, '/images/substrate-gravel.jpg'],
            ['AQ-SUBSTRATE-PLANTED-PRO-5L', 'Sustrato nutritivo para plantado 5 L', 'Sustrato para acuario plantado. Puede alterar parámetros; seguir indicaciones del fabricante y monitorear el agua.', 'sustrato', 'dulce', null, 520, 5, '/images/substrate-planted.jpg'],
            ['AQ-SUBSTRATE-CORAL-CRUSH-5KG', 'Sustrato coralino marino 5 kg', 'Sustrato mineral para acuario marino que puede modificar pH y alcalinidad; medir los parámetros del sistema.', 'sustrato', 'salada', null, 460, 5, '/images/substrate-sand.jpg'],
            ['AQ-SUBSTRATE-QUARTZ-WHITE-5KG', 'Arena de cuarzo blanca 5 kg', 'Sustrato inerte para acuarios de agua dulce; lavar antes de uso.', 'sustrato', 'dulce', null, 295, 8, '/images/substrate-gravel.jpg'],

            ['AQ-DECOR-DRAGON-STONE', 'Piedra Dragon para aquascaping (2 kg)', 'Piedra decorativa para paisajismo acuático. Cepillar y enjuagar; verificar composición y efecto en los parámetros antes de usar.', 'decoracion', 'dulce', null, 390, 5, '/images/decor-dragonstone.jpg'],
            ['AQ-DECOR-LAVA-ROCK', 'Roca volcánica porosa (2 kg)', 'Roca de decoración porosa para acuario. Confirmar que esté limpia, sea apta para uso acuático y no tenga bordes cortantes.', 'decoracion', null, null, 280, 6, '/images/decor-lava.jpg'],
            ['AQ-DECOR-SMOOTH-PEBBLES', 'Piedras lisas de río (2 kg)', 'Piedras lisas para refugios y decoración; lavar y verificar que no alteren la química del agua.', 'decoracion', null, null, 220, 7, '/images/decor-pebbles.jpg'],

            ['AQ-FOOD-ALGAE-WAFER', 'Tabletas vegetales para peces de fondo', 'Alimento complementario para peces herbívoros y de fondo; revisar especie y ración recomendada.', 'alimento', 'dulce', null, 175, 12, '/images/food-fish.jpg'],
            ['AQ-FOOD-MICRO-PELLET', 'Microgránulo para peces pequeños', 'Alimento de partícula pequeña para cardúmenes como tetras y rasboras; porciones moderadas para evitar residuos.', 'alimento', 'dulce', null, 155, 12, '/images/food-fish.jpg'],
            ['AQ-FOOD-FROZEN-MIX', 'Mezcla congelada para peces carnívoros', 'Mezcla de alimento congelado para peces carnívoros. Revisa la conservación y composición indicadas por el fabricante.', 'alimento', null, null, 220, 6, '/images/food-fish.jpg'],
            ['AQ-FOOD-MARINE-HERBIVORE', 'Alimento marino con algas nori', 'Alimento complementario para peces marinos herbívoros; no sustituye dieta variada ni asesoría de especie.', 'alimento', 'salada', null, 230, 8, '/images/food-fish.jpg'],
            ['AQ-FOOD-TETRA-RASBORA-MICRO', 'Microalimento para tetras, rasboras y danios', 'Guía de dieta para tetra ember, tetra neón negro, nariz de borracho, tetra limón, cardenal, emperador, pristella, rasbora espei, arlequín, danio galaxia, neón chino y arcoíris pequeños.', 'alimento', 'dulce', null, 165, 12, '/images/food-fish.jpg'],
            ['AQ-FOOD-CORY-LOACH-SINKING', 'Alimento hundible para coridoras y lochas', 'Guía de dieta para Ancistrus, coridoras panda, Sterbai, pigmea y pimienta, Otocinclus, kuhli, locha cebra y locha de colina; complementar según cada especie.', 'alimento', 'dulce', null, 185, 10, '/images/food-fish.jpg'],
            ['AQ-FOOD-DWARF-CICHLID', 'Granulado para cíclidos enanos', 'Guía de dieta para ramirezi, ram boliviano, Apistogramma cacatuoides y Borellii, kribensis y gobio pavo real; variar proteína y tamaño de partícula.', 'alimento', 'dulce', null, 195, 8, '/images/food-fish.jpg'],
            ['AQ-FOOD-LABYRINTH-LIVEBEARER', 'Alimento variado para gouramis y vivíparos', 'Guía de dieta para gourami enano y azul, pez paraíso, gourami chispeante, bettas, guppy/Endler, platy, molly vela, Heterandria y pez arcoíris; confirmar dieta individual.', 'alimento', 'dulce', null, 175, 10, '/images/food-fish.jpg'],
            ['AQ-FOOD-MARINE-OMNIVORE-REEF', 'Alimento marino variado para peces de arrecife', 'Guía de dieta para pez payaso, cardenal de Banggai, gramma real, gobios, pez fuego, damiselas, chromis y blénidos; alternar partículas y alimentos apropiados para cada especie.', 'alimento', 'salada', null, 260, 8, '/images/food-fish.jpg'],
            ['AQ-FOOD-MARINE-TANG-ANGEL', 'Alimento marino vegetal y mixto', 'Guía de dieta para pez cirujano azul y amarillo, blénidos herbívoros y pez ángel coral beauty; aportar algas y mezcla variada, sin depender del crecimiento natural del acuario.', 'alimento', 'salada', null, 285, 6, '/images/food-fish.jpg'],

            ['AQ-EQUIP-LED-60', 'Lámpara LED regulable 60 cm', 'Iluminación para acuarios de agua dulce; espectro y potencia deben corresponder al montaje.', 'accesorio', 'dulce', null, 690, 5, '/images/cat-equipment.jpg'],
            ['AQ-EQUIP-AIR-PUMP-2', 'Bomba de aire de doble salida', 'Bomba de aire para accesorios de aireación; utiliza válvula antirretorno y ajusta el flujo.', 'accesorio', null, null, 260, 7, '/images/cat-equipment.jpg'],
            ['AQ-EQUIP-POWERHEAD-2000', 'Bomba de circulación regulable 2,000 L/h', 'Bomba de circulación; el caudal debe adecuarse al tamaño del acuario y a sus habitantes.', 'accesorio', null, null, 980, 4, '/images/cat-equipment.jpg'],
            ['AQ-EQUIP-PROTEIN-SKIMMER', 'Skimmer marino para sump', 'Equipo para sistemas marinos; verifica la capacidad y requiere selección según carga biológica.', 'accesorio', 'salada', null, 2890, 2, '/images/filter-sump.jpg'],
            ['AQ-EQUIP-UV-CLARIFIER', 'Clarificador UV para acuario', 'Equipo UV. No sustituye cuarentena, diagnóstico ni mantenimiento del filtro.', 'accesorio', null, null, 1190, 3, '/images/cat-equipment.jpg'],
            ['AQ-EQUIP-THERMOMETER', 'Termómetro digital con sonda', 'Instrumento para monitorear la temperatura del acuario.', 'accesorio', null, null, 180, 10, '/images/cat-equipment.jpg'],
            ['AQ-EQUIP-SIPHON', 'Sifón manual para limpieza', 'Accesorio para retirar residuos del sustrato durante cambios parciales de agua.', 'accesorio', null, null, 220, 8, '/images/cat-equipment.jpg'],
            ['AQ-EQUIP-NET-10CM', 'Red suave para acuario 10 cm', 'Red de manejo para uso cuidadoso; evita perseguir o manipular peces innecesariamente.', 'accesorio', null, null, 95, 12, '/images/cat-equipment.jpg'],
            ['AQ-EQUIP-REFRACTOMETER', 'Refractómetro para salinidad', 'Instrumento para monitoreo de salinidad en sistemas marinos; requiere calibración adecuada.', 'accesorio', 'salada', null, 890, 4, '/images/cat-equipment.jpg'],
            ['AQ-EQUIP-WATER-TEST-MARINE', 'Kit para medir la calidad del agua marina', 'Kit para medir parámetros relevantes de acuarios marinos; seguir instrucciones del fabricante.', 'accesorio', 'salada', null, 970, 4, '/images/cat-equipment.jpg'],
        ];

        foreach ($expandedProducts as [$sku, $name, $description, $category, $waterType, $brand, $price, $stock, $image]) {
            $product = Product::firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'description' => $description,
                'category' => $category,
                'water_type' => $waterType,
                'brand' => $brand ?? 'AQUARIUM',
                'price' => $price,
                'stock' => $stock,
                'image_path' => $image,
                'is_active' => true,
                'is_featured' => false,
            ]);
            $product->forceFill(['name' => $name, 'description' => $description, 'brand' => $brand ?? 'AQUARIUM'])->save();
        }
    }
}
