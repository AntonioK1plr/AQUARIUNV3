<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $textReplacements = [
            'Kit de pruebas de agua dulce' => 'Kit para medir la calidad del agua dulce',
            'Kit de pruebas para agua marina' => 'Kit para medir la calidad del agua marina',
            'Pruebas para pH, amoniaco, nitrito y nitrato' => 'Mide pH, amoniaco, nitrito y nitrato',
            'Alimento de demostración formulado para' => 'Alimento formulado para',
            'Alimento de demostración que se hunde' => 'Alimento que se hunde',
            'Alimento complementario de demostración' => 'Alimento complementario',
            'Complemento vegetal de demostración' => 'Complemento vegetal',
            'Alimento de demostración para' => 'Alimento para',
            'Ficha demostrativa:' => 'Ficha informativa:',
            'Registro local para probar el catálogo.' => 'Esta ficha no corresponde a un medicamento comercializable.',
            'AQUARIUM · Demostración local' => 'AQUARIUM',
            'Tanque compacto de demostración' => 'Tanque compacto',
            'Tanque de demostración' => 'Tanque',
            ' (demo)' => '',
            'Equipo de filtración de referencia local para simular compras. Capacidad representativa; confirma caudal, modelo y compatibilidad antes de una compra real.' => 'Filtro de cascada; confirma el caudal y la compatibilidad con el volumen del acuario antes de elegirlo.',
            'Equipo externo de referencia local. La capacidad descrita es de demostración y no representa una ficha oficial del fabricante.' => 'Filtro externo; confirma la capacidad, el caudal y la compatibilidad en la ficha oficial del fabricante.',
            'Filtro de esponja de referencia local para tanques auxiliares.' => 'Filtro de esponja para tanques auxiliares.',
            'Filtro interno de referencia local para pruebas de catálogo. Verifica el caudal y el volumen real del modelo antes de comprar.' => 'Filtro interno; confirma caudal y compatibilidad en la ficha del fabricante.',
            'Equipo de circulación y filtración de referencia para simular compras' => 'Equipo de circulación y filtración',
            'Filtro de mochila de referencia local para variedad de marcas. Precio y existencia son ficticios para las pruebas.' => 'Filtro de mochila; confirma capacidad, caudal y compatibilidad en la ficha del fabricante.',
            'Arena de demostración' => 'Arena',
            'Sustrato inerte de demostración' => 'Sustrato inerte',
            'Sustrato de demostración' => 'Sustrato',
            'Piedra decorativa de demostración' => 'Piedra decorativa',
            'Piedras lisas de demostración' => 'Piedras lisas',
            'Producto de demostración para simular compras; confirmar conservación y composición antes de comercializar alimento real.' => 'Mezcla de alimento congelado para peces carnívoros. Revisa la conservación y composición indicadas por el fabricante.',
            'Demostración de dieta para' => 'Guía de dieta para',
            'Iluminación de demostración' => 'Iluminación',
            'Bomba de aire de demostración' => 'Bomba de aire',
            'Bomba de circulación de demostración' => 'Bomba de circulación',
            'Equipo de referencia local para sistemas marinos; la capacidad es ilustrativa' => 'Equipo para sistemas marinos; verifica la capacidad',
            'Equipo UV de demostración' => 'Equipo UV',
            'Instrumento de demostración' => 'Instrumento',
            'Accesorio de demostración' => 'Accesorio',
            'Instrumento local de demostración' => 'Instrumento',
            'Kit de demostración' => 'Kit',
        ];

        foreach (DB::table('products')->get(['id', 'name', 'description', 'brand']) as $product) {
            $changes = [];
            foreach (['name', 'description', 'brand'] as $field) {
                $value = $product->{$field};
                if (! is_string($value)) {
                    continue;
                }

                $cleaned = str_replace(array_keys($textReplacements), array_values($textReplacements), $value);
                if ($cleaned !== $value) {
                    $changes[$field] = $cleaned;
                }
            }

            if ($changes !== []) {
                DB::table('products')->where('id', $product->id)->update($changes);
            }
        }

        foreach ([
            'cliente.demo@aquarium.local' => 'Cliente AQUARIUM',
            'admin.demo@aquarium.local' => 'Administración AQUARIUM',
            'almacen.demo@aquarium.local' => 'Almacén AQUARIUM',
            'caja.demo@aquarium.local' => 'Caja POS AQUARIUM',
            'veterinario.demo@aquarium.local' => 'Veterinario AQUARIUM',
        ] as $email => $name) {
            DB::table('users')->where('email', $email)->update(['name' => $name]);
        }
    }

    public function down(): void
    {
        // Presentation cleanup only; restoring old demo labels is intentionally unsupported.
    }
};
