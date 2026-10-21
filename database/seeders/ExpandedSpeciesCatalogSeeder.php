<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ExpandedSpeciesCatalogSeeder extends Seeder
{
    /** Add 50 distinct, locally priced species for catalog and checkout simulations. */
    public function run(): void
    {
        $species = [
            // Agua dulce: ficha de cuidados y parámetros orientativos.
            ['Ancistrus', 'Ancistrus cirrhosus', 'Bagre de fondo con placas óseas; necesita refugios, madera apta y alimento vegetal además de biofilm.', 'dulce', 'Sudamérica', 'pacifico', 6.0, 7.5, 22, 27, 12, 100, 1, 'intermedio', 'No sustituye el control de algas del acuario. Proporciona refugios y espacio de fondo.', 240, 5],
            ['Coridora panda', 'Corydoras panda', 'Coridora pequeña de cardumen para acuarios maduros, con sustrato suave y oxigenación.', 'dulce', 'Perú', 'pacifico', 6.0, 7.5, 20, 25, 5, 60, 8, 'intermedio', 'Mantener un grupo de la misma especie; la arena fina reduce el riesgo de lesionar sus barbillas.', 115, 8],
            ['Coridora Sterbai', 'Corydoras sterbai', 'Coridora de fondo de agua cálida que requiere grupo y sustrato no abrasivo.', 'dulce', 'Brasil y Bolivia', 'pacifico', 6.0, 7.5, 24, 28, 7, 100, 6, 'intermedio', 'Asegura oxigenación y una dieta que llegue al fondo; no mantengas ejemplares aislados.', 185, 6],
            ['Coridora pigmea', 'Corydoras pygmaeus', 'Coridora miniatura que también nada en la zona media; requiere un cardumen numeroso.', 'dulce', 'Brasil', 'pacifico', 6.0, 7.5, 3, 5, 3, 40, 10, 'intermedio', 'Apta solo con compañeros pequeños y tranquilos; utiliza alimento de tamaño adecuado.', 95, 10],
            ['Otocinclus enano', 'Otocinclus macrospilus', 'Pequeño silúrido herbívoro para acuarios plantados maduros con biofilm disponible.', 'dulce', 'Sudamérica', 'pacifico', 6.0, 7.5, 21, 26, 4, 60, 6, 'avanzado', 'No se recomienda introducirlo en acuarios recién montados ni depender solo de las algas para alimentarlo.', 135, 6],
            ['Ramirezi', 'Mikrogeophagus ramirezi', 'Cíclido enano de agua cálida que requiere agua muy limpia y estable.', 'dulce', 'Cuenca del Orinoco', 'territorial', 5.0, 7.0, 26, 30, 7, 80, 1, 'avanzado', 'Evita cambios bruscos y compañeros que lo intimiden; requiere experiencia con parámetros cálidos.', 260, 4],
            ['Ramirezi boliviano', 'Mikrogeophagus altispinosus', 'Cíclido enano sudamericano de carácter relativamente tranquilo, con refugios y espacio de fondo.', 'dulce', 'Bolivia y Brasil', 'semi-agresivo', 6.0, 7.5, 23, 27, 9, 120, 1, 'intermedio', 'Puede defender territorio durante la reproducción; ofrece barreras visuales y refugios.', 290, 4],
            ['Cíclido cacatúa', 'Apistogramma cacatuoides', 'Cíclido enano que utiliza refugios y puede defender una zona durante la reproducción.', 'dulce', 'Amazonas', 'territorial', 6.0, 7.5, 23, 28, 8, 80, 1, 'intermedio', 'Evita mantener varios machos en espacios reducidos; diseña refugios y líneas de visión.', 280, 4],
            ['Apistogramma de Borell', 'Apistogramma borellii', 'Cíclido enano de agua dulce que requiere refugios y parámetros estables.', 'dulce', 'Cuenca del Paraguay', 'territorial', 6.0, 7.5, 20, 26, 7, 80, 1, 'intermedio', 'La convivencia depende del espacio y del diseño territorial, especialmente durante la cría.', 275, 4],
            ['Cíclido kribensis', 'Pelvicachromis pulcher', 'Cíclido africano de agua dulce que utiliza cuevas y puede volverse territorial al reproducirse.', 'dulce', 'África occidental', 'semi-agresivo', 6.0, 7.5, 24, 27, 10, 100, 1, 'intermedio', 'Proporciona cuevas y espacio para delimitar territorio; vigila la agresividad durante la cría.', 220, 5],
            ['Gourami enano', 'Trichogaster lalius', 'Pez laberíntido de nado pausado para acuarios tranquilos con vegetación y superficie accesible.', 'dulce', 'Asia meridional', 'pacifico', 6.0, 7.5, 22, 28, 8, 60, 1, 'intermedio', 'Evita corrientes fuertes y compañeros agresivos; ofrece vegetación y una superficie despejada.', 165, 6],
            ['Gourami azul', 'Trichopodus trichopterus', 'Gourami de mayor tamaño; puede defender territorio y necesita compañeros seleccionados con cuidado.', 'dulce', 'Sudeste asiático', 'semi-agresivo', 6.0, 8.0, 24, 28, 15, 150, 1, 'intermedio', 'El temperamento varía entre ejemplares. No lo combines con peces pequeños o de nado lento sin evaluar.', 210, 5],
            ['Pez paraíso', 'Macropodus opercularis', 'Pez laberíntido resistente que puede ser territorial y requiere espacio y refugios.', 'dulce', 'Asia oriental', 'semi-agresivo', 6.0, 8.0, 16, 18, 10, 100, 1, 'intermedio', 'Tolera temperaturas menores que muchos tropicales; no lo juntes automáticamente con peces pacíficos pequeños.', 190, 5],
            ['Arcoíris cola de tenedor', 'Pseudomugil furcatus', 'Pez pequeño de cardumen activo que luce mejor con vegetación y espacio de nado.', 'dulce', 'Papúa Nueva Guinea', 'pacifico', 6.5, 7.5, 24, 28, 6, 60, 8, 'intermedio', 'Mantén un grupo y evita compañeros grandes que puedan depredarlo.', 145, 8],
            ['Arcoíris neón enano', 'Melanotaenia praecox', 'Pez arcoíris activo de cardumen, apto para acuarios con espacio longitudinal.', 'dulce', 'Nueva Guinea', 'pacifico', 6.5, 7.5, 24, 28, 8, 100, 8, 'intermedio', 'Necesita un grupo y buena calidad de agua; considera su actividad y tamaño adulto.', 175, 8],
            ['Neón chino', 'Tanichthys albonubes', 'Pez pequeño de cardumen que prefiere agua templada y bien oxigenada.', 'dulce', 'China', 'pacifico', 6.0, 8.0, 16, 22, 4, 40, 8, 'inicial', 'No requiere agua tropical muy caliente; selecciona compañeros con preferencias térmicas compatibles.', 55, 12],
            ['Tetra ember', 'Hyphessobrycon amandae', 'Tetra diminuto de cardumen para acuarios plantados y compañeros tranquilos.', 'dulce', 'Brasil', 'pacifico', 5.5, 7.5, 23, 28, 2, 40, 10, 'intermedio', 'Elige compañeros pequeños; ofrece alimento de partícula fina y mantén un cardumen amplio.', 55, 12],
            ['Tetra neón negro', 'Hyphessobrycon herbertaxelrodi', 'Tetra de cardumen con franjas contrastantes, adecuado para montajes comunitarios compatibles.', 'dulce', 'Brasil', 'pacifico', 5.5, 7.5, 23, 27, 4, 60, 8, 'inicial', 'Mantén el grupo y evita depredadores o peces que mordisqueen aletas.', 65, 10],
            ['Tetra nariz de borracho', 'Hemigrammus bleheri', 'Tetra de cardumen sensible a cambios bruscos; luce mejor en grupos numerosos.', 'dulce', 'Amazonas', 'pacifico', 5.5, 7.0, 24, 28, 5, 80, 10, 'intermedio', 'Requiere agua estable y una aclimatación gradual; su coloración no sustituye medir parámetros.', 90, 10],
            ['Tetra limón', 'Hyphessobrycon pulchripinnis', 'Tetra de cardumen pacífico para acuarios comunitarios plantados.', 'dulce', 'Brasil', 'pacifico', 5.5, 7.5, 23, 28, 5, 60, 8, 'inicial', 'Conserva un grupo suficiente y dieta variada; evita compañeros agresivos.', 65, 10],
            ['Tetra cardenal', 'Paracheirodon axelrodi', 'Tetra de cardumen de colores intensos, distinto del neón común; requiere estabilidad y grupo.', 'dulce', 'Amazonas y Orinoco', 'pacifico', 4.5, 7.0, 23, 27, 5, 80, 10, 'intermedio', 'No confundir con Paracheirodon innesi. Aclimata lentamente y mantén parámetros estables.', 85, 10],
            ['Tetra emperador', 'Nematobrycon palmeri', 'Tetra de cardumen que ocupa la zona media y requiere espacio de nado y vegetación.', 'dulce', 'Colombia', 'pacifico', 5.5, 7.5, 23, 27, 6, 80, 8, 'intermedio', 'Mantén grupo y refugios vegetales; evalúa la convivencia entre machos.', 85, 8],
            ['Rasbora corderito', 'Trigonostigma espei', 'Rasbora pequeña de cardumen para montajes plantados y compañeros pacíficos.', 'dulce', 'Sudeste asiático', 'pacifico', 5.5, 7.5, 23, 28, 4, 50, 8, 'intermedio', 'En grupos pequeños se muestra tímida; ofrece vegetación y agua estable.', 65, 10],
            ['Barbo tigre', 'Puntigrus tetrazona', 'Barbo activo de cardumen; puede mordisquear aletas largas si se mantiene en grupos insuficientes.', 'dulce', 'Sudeste asiático', 'semi-agresivo', 6.0, 7.5, 22, 28, 7, 120, 8, 'intermedio', 'Mantén un grupo amplio y evita compañeros lentos o de aletas largas.', 75, 10],
            ['Danio galaxia', 'Danio margaritatus', 'Pez pequeño tímido que requiere grupo, vegetación densa y compañeros de talla similar.', 'dulce', 'Myanmar', 'pacifico', 6.5, 7.5, 20, 26, 2, 50, 10, 'intermedio', 'Puede competir mal por alimento con peces grandes; proporciona refugio y comida pequeña.', 95, 10],
            ['Gourami chispeante', 'Trichopsis pumila', 'Laberíntido diminuto que requiere aguas tranquilas, vegetación y compañeros pequeños.', 'dulce', 'Sudeste asiático', 'pacifico', 6.0, 7.5, 24, 28, 4, 40, 6, 'intermedio', 'Evita corrientes fuertes y peces que compitan agresivamente por alimento.', 105, 8],
            ['Endler', 'Poecilia wingei', 'Pez vivíparo pequeño de agua dulce disponible en variedades de color.', 'dulce', 'Venezuela', 'pacifico', 6.5, 8.0, 22, 28, 4, 40, 6, 'inicial', 'Se reproduce con rapidez; planifica población y evita liberar ejemplares al ambiente.', 55, 12],
            ['Platy variable', 'Xiphophorus variatus', 'Vivíparo activo con variedades de color; requiere agua estable y espacio para el grupo.', 'dulce', 'México', 'pacifico', 7.0, 8.0, 22, 26, 7, 60, 5, 'inicial', 'Puede reproducirse con rapidez. Las especies introducidas no deben liberarse en cuerpos de agua.', 65, 10],
            ['Molly vela', 'Poecilia latipinna', 'Molly de mayor tamaño que necesita espacio, agua mineralizada y buena oxigenación.', 'dulce', 'Norteamérica', 'pacifico', 7.0, 8.2, 22, 28, 12, 120, 5, 'intermedio', 'Considera el tamaño adulto y la calidad/mineralización del agua antes de elegirlo.', 110, 8],
            ['Heterandria enana', 'Heterandria formosa', 'Vivíparo diminuto para acuarios tranquilos, plantados y con compañeros pequeños.', 'dulce', 'Estados Unidos', 'pacifico', 6.5, 8.0, 20, 26, 3, 40, 6, 'intermedio', 'No combinar con peces grandes; confirma que el alimento sea de tamaño apropiado.', 120, 6],
            ['Betta pacífico', 'Betta imbellis', 'Betta de agua dulce distinto del Betta splendens; aun así requiere evaluación individual de convivencia.', 'dulce', 'Sudeste asiático', 'territorial', 6.0, 7.5, 24, 28, 6, 30, 1, 'avanzado', 'No asumir compatibilidad comunitaria ni alojar dos machos juntos; necesita refugios y acceso a superficie.', 320, 3],
            ['Locha kuhli', 'Pangio kuhlii', 'Locha nocturna y gregaria que usa sustrato suave, refugios y zonas sombreadas.', 'dulce', 'Sudeste asiático', 'pacifico', 5.5, 7.0, 24, 28, 10, 80, 6, 'intermedio', 'Asegura una tapa sin huecos y sustrato que no dañe la piel; es más activa en grupo.', 120, 8],
            ['Locha cebra', 'Botia striata', 'Locha activa de grupo que requiere espacio de fondo y numerosos refugios.', 'dulce', 'India', 'semi-agresivo', 6.0, 7.5, 23, 28, 10, 150, 5, 'avanzado', 'No es adecuada para acuarios pequeños ni para mantenerse sola; verifica su talla adulta.', 260, 5],
            ['Locha de colina', 'Sewellia lineolata', 'Pez de corriente de agua dulce que necesita agua muy oxigenada y superficies con biofilm.', 'dulce', 'Vietnam', 'pacifico', 6.5, 7.5, 20, 25, 6, 80, 4, 'avanzado', 'Requiere circulación y oxigenación altas; no es apropiada para acuarios cálidos de baja corriente.', 280, 4],
            ['Comealgas siamés', 'Crossocheilus oblongus', 'Pez de agua dulce activo que consume algunos tipos de algas cuando es joven.', 'dulce', 'Sudeste asiático', 'pacifico', 6.0, 7.5, 24, 26, 15, 180, 1, 'intermedio', 'Crece y nada activamente; no se vende como solución única para controlar algas.', 190, 5],
            ['Pez lápiz dorado', 'Nannostomus beckfordi', 'Pez lápiz pacífico que requiere un grupo, vegetación y compañeros tranquilos.', 'dulce', 'Sudamérica', 'pacifico', 5.5, 7.5, 23, 28, 6, 60, 8, 'intermedio', 'Puede saltar: utiliza tapa. Alimenta con porciones pequeñas y variadas.', 90, 8],
            ['Pez hacha mármol', 'Carnegiella strigata', 'Pez de superficie gregario con forma característica; requiere tapa segura.', 'dulce', 'Sudamérica', 'pacifico', 5.5, 7.0, 24, 28, 4, 60, 8, 'intermedio', 'Excelente saltador; tapa imprescindible y compañeros que no compitan en exceso por comida.', 120, 8],
            ['Panchax rayado', 'Aplocheilus lineatus', 'Pez de superficie depredador de insectos, con boca amplia y tendencia a saltar.', 'dulce', 'India y Sri Lanka', 'depredador', 6.0, 7.5, 22, 26, 10, 100, 1, 'intermedio', 'No combinar con peces pequeños que pueda ingerir; usa tapa segura.', 190, 4],
            ['Gobio pavo real', 'Tateurndina ocellicauda', 'Gobio pacífico de agua dulce que necesita refugios y zonas tranquilas.', 'dulce', 'Papúa Nueva Guinea', 'pacifico', 6.5, 7.5, 22, 26, 7, 60, 1, 'intermedio', 'Alimenta con variedad de alimentos pequeños; puede competir mal con peces voraces.', 185, 5],
            ['Pez cuerda', 'Erpetoichthys calabaricus', 'Pez alargado de gran talla que requiere acuario amplio, tapa robusta y compañeros compatibles.', 'dulce', 'África occidental', 'depredador', 6.0, 7.5, 24, 28, 40, 400, 1, 'avanzado', 'Puede escapar por aberturas pequeñas y depredar peces que quepan en su boca; solo para montajes grandes.', 1250, 2],

            // Agua salada: especies con requisitos de volumen y dificultad visibles.
            ['Pez cardenal de Banggai', 'Pterapogon kauderni', 'Pez marino de nado pausado para acuario estable y maduro.', 'salada', 'Islas Banggai', 'pacifico', 8.1, 8.4, 24, 28, 8, 120, 1, 'intermedio', 'Confirma el origen y la disponibilidad responsable del ejemplar; no mezclar con depredadores.', 690, 4],
            ['Gramma real', 'Gramma loreto', 'Pez marino pequeño que utiliza grietas y cuevas como refugio.', 'salada', 'Caribe', 'semi-agresivo', 8.1, 8.4, 24, 27, 8, 120, 1, 'intermedio', 'Puede defender cuevas; planifica refugios y evita alojar individuos territoriales juntos en espacios reducidos.', 590, 4],
            ['Gobio payaso amarillo', 'Gobiodon okinawae', 'Gobio marino pequeño asociado a ramas de coral; necesita acuario maduro.', 'salada', 'Indo-Pacífico', 'pacifico', 8.1, 8.4, 24, 28, 4, 80, 1, 'intermedio', 'Revisa compatibilidad con corales y la disponibilidad de alimento pequeño; no es un pez de inicio para tanque nuevo.', 520, 4],
            ['Pez fuego púrpura', 'Nemateleotris magnifica', 'Pez marino tímido que utiliza cuevas y puede saltar al asustarse.', 'salada', 'Indo-Pacífico', 'pacifico', 8.1, 8.4, 24, 27, 9, 100, 1, 'intermedio', 'Tapa segura y refugios imprescindibles; introduce con compañeros no agresivos.', 720, 4],
            ['Damisela cola amarilla', 'Chrysiptera parasema', 'Damisela marina resistente, pero puede defender territorio al madurar.', 'salada', 'Indo-Pacífico', 'semi-agresivo', 8.1, 8.4, 24, 28, 8, 100, 1, 'intermedio', 'La agresividad puede aumentar con la edad; elige compañeros robustos y refugios suficientes.', 390, 5],
            ['Chromis verde', 'Chromis viridis', 'Pez marino de cardumen que necesita espacio de nado y sistema maduro.', 'salada', 'Indo-Pacífico', 'pacifico', 8.1, 8.4, 24, 28, 8, 180, 5, 'intermedio', 'Requiere calidad de agua estable; no introduzcas un grupo sin plan de volumen y filtración.', 420, 8],
            ['Blénido bicolor', 'Ecsenius bicolor', 'Blénido marino que utiliza grietas y puede picotear biofilm y algas.', 'salada', 'Indo-Pacífico', 'semi-agresivo', 8.1, 8.4, 24, 27, 11, 120, 1, 'intermedio', 'Proporciona refugios y alimento complementario; algunos ejemplares pueden mordisquear corales.', 650, 4],
            ['Blénido comealgas', 'Salarias fasciatus', 'Blénido marino que pastorea superficies; requiere acuario maduro con alimento disponible.', 'salada', 'Indo-Pacífico', 'semi-agresivo', 8.1, 8.4, 24, 27, 14, 150, 1, 'intermedio', 'No depender solo de algas naturales; algunos individuos aceptan con dificultad alimentos sustitutos.', 780, 4],
            ['Zebrasoma amarillo', 'Zebrasoma flavescens', 'Pez cirujano marino de nado activo, gran talla y requerimientos de espacio elevados.', 'salada', 'Pacífico central', 'semi-agresivo', 8.1, 8.4, 24, 28, 20, 400, 1, 'avanzado', 'Necesita acuario largo y maduro; puede ser territorial con otros cirujanos. La cifra de volumen es orientativa.', 2450, 2],
            ['Pez ángel coral beauty', 'Centropyge bispinosa', 'Pez ángel enano marino que requiere roca viva, refugios y alimentación variada.', 'salada', 'Indo-Pacífico', 'semi-agresivo', 8.1, 8.4, 24, 28, 10, 250, 1, 'avanzado', 'Puede mordisquear corales e invertebrados; no garantizar compatibilidad con arrecifes.', 1850, 3],
        ];

        foreach ($species as [$name, $scientific, $description, $water, $origin, $temperament, $minPh, $maxPh, $minTemp, $maxTemp, $adultSize, $liters, $group, $level, $notes, $price, $stock]) {
            $slug = str_replace(' ', '-', strtolower($scientific));
            $sku = 'AQ-FISH-'.strtoupper(str_replace('-', '', $slug));
            Product::firstOrCreate(['sku' => $sku], [
                'name' => $name,
                'scientific_name' => $scientific,
                'description' => $description,
                'category' => 'pez',
                'water_type' => $water,
                'origin' => $origin,
                'temperament' => $temperament,
                'min_ph' => $minPh,
                'max_ph' => $maxPh,
                'min_temp_c' => $minTemp,
                'max_temp_c' => $maxTemp,
                'adult_size_cm' => $adultSize,
                'minimum_tank_liters' => $liters,
                'minimum_group_size' => $group,
                'care_level' => $level,
                'care_notes' => $notes,
                'price' => $price,
                'stock' => $stock,
                'image_path' => '/images/fish-'.$slug.'.jpg',
                'is_active' => true,
                'is_featured' => false,
            ]);
        }
    }
}
