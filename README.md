# AQUARIUM

Aplicación local en preparación para el sistema AQUARIUM. **QU4.pdf es la línea base normativa actual**; el diagrama de despliegue determina la topología prevista. Los demás documentos son referencias históricas o técnicas.

La portada pública conserva el diseño de AQUARIUM.png; la navegación incluye Inicio, Catálogo, Clínica e Información (servicios del negocio). La ruta `/catalogo` consulta PostgreSQL local y permite búsqueda Full Text por nombre, descripción, SKU y marca, además de filtros combinables por tipo de agua, marca y rango de precio. La búsqueda en PostgreSQL usa un índice GIN. El registro, inicio/cierre de sesión, recuperación de contraseña y edición de perfil funcionan localmente; el registro público siempre crea cuentas con rol `cliente`, y el middleware aplica permisos por rol. El carrito persiste en la sesión, aparta inventario por 30 minutos y permite confirmar pedidos web con pago simulado, seleccionar Click & Collect o envío, emitir referencia/código de recolección y consultar el historial en `/cuenta/pedidos`.

Los módulos operativos disponibles son `/admin/empleados` (solo administrador) y `/almacen` (administrador o almacenista). Empleados permite registrar y consultar nombre, rol, correo y teléfono. Almacén permite crear productos, recibir lotes, registrar mermas/mortalidad/ajustes con bitácora y retirar productos sin borrar su historial; las reservas activas se protegen de ventas y salidas. Para crear la primera cuenta de personal de forma privada en el entorno local, ejecuta `./scripts/php-local.ps1 artisan aquarium:staff:create` y sigue las preguntas de consola.

La ruta `/citas` permite a los clientes reservar y consultar citas; `/veterinaria/citas` permite a cada veterinario atender solo sus citas, revisar datos del cliente y guardar diagnóstico/prescripción. Los cargos y reembolsos son registros simulados locales; no se almacena información de tarjetas ni se llama a pasarelas. El monto ilustrativo se configura con `AQUARIUM_SIMULATED_CONSULTATION_FEE` (predeterminado: $500 MXN) y no representa una tarifa aprobada para producción. Una cancelación de cliente o clínica marca reembolso simulado; una inasistencia no lo marca.

El módulo `/pos` está disponible para administrador y cajero POS. Registra ventas de mostrador, calcula IVA del 16% y descuenta existencias de forma atómica con bitácora. Acepta tarjeta, transferencia y efectivo solo en modo simulado; para efectivo calcula cambio, sin capturar ni mover dinero real. `/pos/cierre-z` genera el corte por cajero y fecha, concilia efectivo esperado/contado y bloquea esa caja durante el resto del día. El checkout web usa los mismos productos e inventario. Para Click & Collect, almacén prepara pedidos en `/almacen/picking` y POS confirma la entrega en `/pos/recolecciones` validando el código; los cambios quedan en una bitácora con operador y estados. Para envío, checkout valida y conserva la dirección; almacén marca el pedido listo y registra la salida con una referencia local simulada que ve el cliente en su historial. No se conecta con paqueterías ni proveedores de pago.

El carrito compara compatibilidad por tipo de agua, rango de pH, temperatura y temperamento; muestra advertencias que el cliente debe confirmar antes del checkout. La carta de cuidado responsable se acepta antes de agregar un ejemplar vivo y su consentimiento queda registrado con el pedido. Usa `./scripts/php-local.ps1 artisan aquarium:carts:expire-reservations --dry-run` para contar apartados vencidos o sin `--dry-run` para liberarlos.

Los pedidos Click & Collect que siguen sin recogerse al vencer el plazo local de 48 horas se pueden procesar con `./scripts/php-local.ps1 artisan aquarium:orders:expire-pickups --dry-run` para previsualizar y sin `--dry-run` para ejecutar. La tarea marca cancelación y reembolso simulados y devuelve el inventario con movimiento auditable.

En esta versión los vencimientos del carrito y Click & Collect están programados cada minuto mientras esté activo el planificador local de Laravel. Inicia `./scripts/php-local.ps1 artisan schedule:work` en una terminal adicional para liberar apartados de 30 minutos y procesar pedidos sin recoger a las 48 horas. Las previsualizaciones manuales con `--dry-run` siguen disponibles.

El administrador consulta resultados en `/admin/pruebas`. PHPUnit, PostgreSQL, Selenium y JMeter generan informes independientes que el panel presenta junto con su historial. Para PHPUnit (SQLite en memoria): `./scripts/php-local.ps1 artisan aquarium:tests:report`. Para interfaz: `npm run test:e2e`. Para carga gradual de 50 usuarios: `./scripts/run-jmeter-local.ps1 -Users 50 -RampSeconds 10 -DurationSeconds 30 -BaseUrl http://127.0.0.1:8080`. Para 500 usuarios con 60 s de rampa y 60 s adicionales a carga plena: `./scripts/run-jmeter-local.ps1 -Users 500 -RampSeconds 60 -DurationSeconds 120 -BaseUrl http://127.0.0.1:8080`. El perfil Docker multiworker de Nginx + PHP-FPM se prepara con `./scripts/prepare-loadtest-image.ps1` y se levanta con `docker compose --profile loadtest up -d --build loadtest-app loadtest-nginx`; responde en `http://127.0.0.1:8080`, separado del servidor interactivo `8001`. JTL, dashboard y JSON permanecen en `storage/app/testing/`, excluido de Git.

La sucursal atiende de 09:00 a 22:00. Las citas solo pueden reservarse entre 09:00 y 19:00, y únicamente si ese veterinario no tiene ya una cita en la hora solicitada. El número de citas concurrentes de la sucursal depende del personal veterinario disponible; no hay un límite global separado configurado.

## Línea base técnica

- PHP 8.2+ y Laravel 12 (en este entorno: Laravel 12.69.3).
- React 18.3.1 con Inertia.js 1.x.
- Vite 8.3.4 y Tailwind CSS 3.4.19.
- PostgreSQL 16 y Redis 7 en Docker Compose.
- PHPUnit 11.5.57; Selenium WebDriver 4.51.0; Apache JMeter 5.6.3 con Eclipse Temurin JDK 21.0.12.1 (instalados localmente bajo `tools/`, ignorado por Git).
- Destino futuro previsto: Render y Supabase; el desarrollo actual no se conecta a esos servicios.

Se toma QU4.pdf como línea base funcional y se usan versiones actuales compatibles con sus requisitos. Las sustituciones o actualizaciones de versión se registran en [`docs/FASE_CERO.md`](docs/FASE_CERO.md).

## Requisitos locales

- PHP 8.2 con Composer 2.
- Node.js 20.19.0 o superior compatible con Vite 8 y npm (en esta máquina: Node 24.21.0 / npm 11.19.0).
- Docker Desktop con Docker Compose.

Para preparar las herramientas portables de carga en Windows, ejecutar una vez `./scripts/install-testing-tools.ps1`. Instala JMeter y Temurin dentro de `tools/`, verifica sus hashes y no modifica el Java global. Para Selenium, ejecutar `npm ci`; Selenium Manager obtiene ChromeDriver cuando se requiera.

Las cuentas de demostración se crean explícitamente con `./scripts/php-local.ps1 artisan db:seed --class=LocalDemoAccountsSeeder`. Antes, configura `$env:AQUARIUM_DEMO_PASSWORD` en la sesión local de PowerShell con una contraseña propia de al menos 12 caracteres. El seeder solo funciona en entornos `local` y `testing`; la contraseña no está incluida en el repositorio.

## Preparación e inicio

En PowerShell, desde la carpeta del repositorio:

```powershell
composer install
npm ci
Copy-Item .env.example .env   # solo si aún no existe .env
docker compose up -d
./scripts/php-local.ps1 artisan key:generate  # solo para una instalación nueva
./scripts/php-local.ps1 artisan migrate
```

Abre dos terminales en la carpeta del repositorio:

```powershell
npm run dev
```

```powershell
./scripts/serve-local.ps1
```

Visita `http://127.0.0.1:8001`. No subas `.env`; contiene la configuración privada de cada máquina. Las credenciales definidas en Compose son exclusivamente para desarrollo local.

Si Docker Desktop no está disponible, la portada y el catálogo se pueden mostrar temporalmente con SQLite local. Primero prepara esa base (una sola vez o después de cambiar migraciones):

```powershell
./scripts/prepare-local-sqlite.ps1
./scripts/serve-local-sqlite.ps1
```

Este modo escucha solo en `127.0.0.1:8001` y no reemplaza la base PostgreSQL elegida para la etapa local completa. Los datos SQLite son solo semilla de demostración.

## Comprobaciones realizadas

- Compilación de recursos con Vite.
- Inspección de rutas Laravel.
- Validación de la configuración Compose.
- PHPUnit: 54 pruebas y 622 aserciones aprobadas para registro, autenticación, roles, personal, inventario, notificaciones, clínica, POS y cierre Z simulado, carrito/checkout, dirección y despacho local simulado, preparación/entrega y vencimiento Click & Collect, reportes, pagos simulados, catálogo, filtros y portada. PHPUnit usa SQLite en memoria y no modifica PostgreSQL; la misma suite pasó también en una base de prueba PostgreSQL aislada. El último resultado se consulta en `/admin/pruebas`.
- `qrcode.react` 4.2.0 genera en el navegador los pases QR locales de Click & Collect; el POS recibe el código leído por escáner como entrada de texto y mantiene validación manual disponible.
- `npm run build` compila los recursos con Vite 8.3.4; `git diff --check` no reporta errores de whitespace. Inicio, catálogo y filtro por agua salada responden HTTP 200 en `127.0.0.1:8001`. Una muestra paralela de seis GET obtuvo 6/6 respuestas 200 y p95 de 2,204.62 ms con PHP de desarrollo + PostgreSQL local; es solo un diagnóstico pequeño, no una prueba de capacidad. La suite completa también se ejecuta mediante `scripts/test-postgres-local.ps1` en una base PostgreSQL segregada; produce archivos JUnit/JSON y se muestra por separado en `/admin/pruebas`.
- Plan JMeter de solo lectura en `tests/performance/aquarium-public-flow.jmx` (500 usuarios configurables) y lanzador `scripts/run-jmeter-local.ps1`. Apache JMeter 5.6.3 y Temurin JDK 21.0.12.1 están instalados y verificados localmente. Perfil de carga local Nginx + PHP-FPM en Docker, en el puerto `8080`: 50 usuarios, rampa 10 s, 30 s, 6,421 solicitudes, 0 fallas, promedio 195 ms, máximo 529 ms; 500 usuarios, rampa 60 s, 60 s, 13,372 solicitudes, 0 fallas, promedio 1,169.66 ms, p95 2,012 ms, máximo 2,472 ms. Es evidencia repetible del equipo local, no una certificación de producción ni de SLA; un intento anterior con el perfil Docker anterior falló y se conserva en el historial. El resumen y el dashboard se consultan en `/admin/pruebas` y `storage/app/testing/jmeter/`.
- Selenium WebDriver 4.51.0, con Selenium Manager para ChromeDriver: 8 escenarios aprobados (Inicio/navegación, filtro público de Catálogo, autenticación para agenda y acceso por rol a Cuenta, Reportes, Almacén, POS y Agenda clínica). `npm run test:e2e` corre los tres escenarios públicos; para incluir los cinco módulos de rol, define `AQUARIUM_E2E_ACCOUNTS_JSON` en la sesión PowerShell con las cuentas de demostración y vuelve a ejecutar el comando. Las contraseñas se leen solo del entorno, no se guardan en el repositorio ni en el reporte. El JSON queda en `storage/app/testing/selenium/` y aparece en `/admin/pruebas`.
- `npm audit` identifica 7 vulnerabilidades en herramientas de desarrollo (5 altas, 2 moderadas) de la cadena que arrastra Tailwind CSS 3. La corrección automática requiere Tailwind 4, que no forma parte de la línea base visual; se conserva Tailwind 3 hasta validar una migración.
- Docker Desktop está activo: PostgreSQL 16.15 y Redis 7 aparecen saludables; las 20 migraciones están aplicadas en PostgreSQL y el servidor local en `127.0.0.1:8001` usa esa conexión. El servidor PHP de desarrollo no sirve para certificar carga de 500 usuarios.

El catálogo público tiene persistencia y búsqueda indexada; están implementadas autenticación por roles, perfil y recuperación de contraseña, gestión de empleados, inventario con auditoría y apartado temporal, ubicación física de productos y picking ordenado por pasillo, expediente clínico con PDF local restringido, mediciones y prescripción estructurada más hash de integridad local, agenda veterinaria, POS con efectivo/cambio y cierre Z simulados, carrito con compatibilidad biológica, notificaciones in-app locales, preparación/entrega con pase QR y vencimiento Click & Collect, además de despacho local simulado. La capacidad de citas es el total de veterinarios disponibles por horario; la sucursal atiende de 09:00 a 22:00 y permite reservar hasta las 19:00. El pie del PDF clínico muestra “SELLO NOM-151 · SIMULACIÓN LOCAL” y explica que no es sello oficial, firma electrónica, sello de tiempo ni certificación. La carga de 50 usuarios pasó; la de 500 usuarios se sostuvo durante 60 segundos a concurrencia plena sobre Docker/Nginx/PHP-FPM, con cero errores. Selenium aprobó ocho escenarios, incluidos los cinco roles. Las cinco cuentas de prueba (cliente, administrador, almacenista, cajero POS y veterinario) ya están creadas en PostgreSQL local; las credenciales son solo para este entorno. Quedan la trazabilidad completa QU4 → módulos → casos → evidencias y las validaciones visuales/manuales finales. No se ha desplegado a la nube. Consulta `docs/FASE_CERO.md` para el estado y `../docs/Plan_Pruebas_Local_AQUARIUM.docx` para el plan local.
