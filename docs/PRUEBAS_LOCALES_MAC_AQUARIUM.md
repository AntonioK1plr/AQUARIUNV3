# Preparar AQUARIUM para pruebas locales en macOS

Guía para iniciar el proyecto en una Mac y, si se desea, trasladar la base local actual de Windows. El proyecto usa Laravel 12, PHP 8.2 o superior, React/Inertia, Node.js compatible con Vite 8, PostgreSQL 16 y Redis 7.

## 1. Instalar herramientas

Instala Homebrew siguiendo su [guía oficial](https://docs.brew.sh/Installation), luego instala PHP 8.4, Composer y Node.js. PHP 8.4 coincide con la imagen Docker preparada para Render:

```sh
brew install php@8.4 composer node
export PATH="$(brew --prefix php@8.4)/bin:$(brew --prefix php@8.4)/sbin:$PATH"
```

La fórmula `php@8.4` es keg-only. Si usarás varias ventanas de Terminal, agrega esa misma línea `export PATH=...` a `~/.zprofile` o ejecútala en cada ventana.

Vite 8 requiere Node.js 20.19 o superior, o 22.12 o superior. Homebrew puede instalar una versión más nueva compatible. Instala Docker Desktop para Mac desde la [guía oficial de Docker](https://docs.docker.com/desktop/setup/install/mac-install/); elige el paquete correspondiente a Apple silicon o Intel y abre Docker Desktop antes de seguir.

Comprueba las herramientas:

```sh
php -v
php -m | grep -E 'pdo_pgsql|pgsql|gd|intl|zip|bcmath'
composer --version
node --version
npm --version
docker compose version
```

Composer requiere que PHP tenga las extensiones `pdo_pgsql`, `pgsql`, `gd`, `intl` y `zip`. Si falta alguna, corrige la instalación PHP antes de instalar dependencias.

## 2. Obtener el repositorio

Clona la rama publicada:

```sh
git clone https://github.com/AntonioK1plr/AQUARIUNV3.git aquarium
cd aquarium
```

Si ya clonaste antes:

```sh
git pull origin main
```

El comando de actualización no incluye el archivo de respaldo de PostgreSQL ni el `.env`; ambos se transfieren y se configuran aparte.

## 3. Llevar la base actual de Windows a la Mac

La copia local que preparé está en Windows en:

```text
storage/app/testing/database-backups/AQUARIUM_mac_local_20261119.dump
```

El archivo contiene los registros locales de PostgreSQL —cuentas y hashes de contraseña, catálogo, stock, pedidos, citas e historial de pruebas—. **No está en Git y no se debe subir al repositorio.** Transfiérelo a la Mac por un medio privado y colócalo en la misma ruta dentro del clon del repositorio.

Antes de copiarlo a la Mac, crea la carpeta destino:

```sh
mkdir -p storage/app/testing/database-backups
```

Verifica la transferencia desde la carpeta `aquarium`:

```sh
shasum -a 256 storage/app/testing/database-backups/AQUARIUM_mac_local_20261119.dump
```

El resultado debe ser:

```text
69DB29B8E03BCD85D7F5958F97746C77927492FC51416F25602B39DF38AD841C
```

El respaldo queda separado del código y es una instantánea: los cambios hechos en la Mac no se sincronizan con Windows ni con Render.

La instantánea fue validada el 19/11/2026: contiene 8 cuentas en total, las cinco cuentas demo activas de la tabla siguiente, 135 productos activos, pedidos y citas de aceptación de Stripe en modo test. No contiene las claves Stripe ni el `.env`.

## 4. Crear la configuración local

Crea el `.env` privado:

```sh
cp .env.example .env
```

Abre `.env` en un editor local y usa estos valores para la base Docker local:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8001
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aquarium
DB_USERNAME=aquarium
DB_PASSWORD=aquarium_local
DB_SSLMODE=prefer
REDIS_HOST=127.0.0.1
STRIPE_MODE=test
STRIPE_LIVE_PAYMENTS_ENABLED=false
```

No copies el `.env` de Windows ni pongas claves en Git. `APP_KEY` se genera en la Mac. Para navegar por el sitio, puedes dejar vacíos los valores de Stripe. Para probar pagos, guarda en el `.env` de la Mac únicamente claves `pk_test_` y `sk_test_` de prueba.

## 5. Iniciar PostgreSQL y restaurar los datos

Desde la carpeta raíz del proyecto:

```sh
docker compose up -d postgres redis
docker compose ps
```

Espera a que PostgreSQL y Redis aparezcan saludables. **La restauración reemplaza el contenido de la base `aquarium` en la Mac.** Hazla solo sobre la base local nueva/vacía; si ya hay datos en esa Mac, expórtalos antes. No modifica la base original de Windows. Copia el respaldo al contenedor y restáuralo:

```sh
docker cp storage/app/testing/database-backups/AQUARIUM_mac_local_20261119.dump aquarium-postgres:/tmp/aquarium-mac.dump
docker exec aquarium-postgres pg_restore -U aquarium -d aquarium --clean --if-exists --no-owner --no-privileges /tmp/aquarium-mac.dump
docker exec aquarium-postgres rm -f /tmp/aquarium-mac.dump
```

Después instala dependencias, genera una clave local y aplica cualquier migración pendiente:

```sh
composer install
npm ci
php artisan key:generate
php artisan migrate
php artisan config:clear
```

No ejecutes `migrate:fresh`, `db:wipe` ni el seeder de catálogo sobre el respaldo restaurado; borrarían o duplicarían datos. Si quieres usar una contraseña conocida para las cinco cuentas de demostración, define `AQUARIUM_DEMO_PASSWORD` en el `.env` de la Mac con una contraseña local de al menos 12 caracteres y después ejecuta:

```sh
php artisan db:seed --class=LocalDemoAccountsSeeder
```

Ese seeder restablece las credenciales de las cinco cuentas demo; no modifica los demás usuarios, pedidos, citas ni productos. Las cuentas demo que están en la instantánea son:

| Rol | Correo |
|---|---|
| Cliente | `cliente.demo@aquarium.local` |
| Administrador | `admin.demo@aquarium.local` |
| Almacenista | `almacen.demo@aquarium.local` |
| Cajero POS | `caja.demo@aquarium.local` |
| Veterinario | `veterinario.demo@aquarium.local` |

## 6. Encender el sitio

Abre tres terminales en la carpeta `aquarium`.

Terminal 1, Laravel:

```sh
php artisan serve --host=127.0.0.1 --port=8001
```

Terminal 2, React/Vite:

```sh
npm run dev -- --host 127.0.0.1 --port 5173 --strictPort
```

Terminal 3, solo si probarás pagos con tarjeta de prueba, instala e inicia Stripe CLI:

```sh
brew install stripe-cli
stripe login
stripe listen --events payment_intent.succeeded,payment_intent.payment_failed,payment_intent.canceled,refund.updated --forward-to 127.0.0.1:8001/webhooks/stripe
```

La CLI mostrará un `whsec_` temporal. Guárdalo en `STRIPE_WEBHOOK_SECRET` dentro del `.env` local de la Mac, ejecuta `php artisan config:clear` y reinicia Laravel para que lea la variable. No compartas ni publiques ese secreto. Usa una tarjeta de prueba de Stripe, nunca una tarjeta real.

Abre [http://127.0.0.1:8001](http://127.0.0.1:8001). Para validar navegación, catálogo y autenticación con Selenium, instala Google Chrome para Mac; Selenium Manager descarga el controlador compatible cuando haga falta:

```sh
npm run test:e2e
```

## 7. Apagar sin perder los datos

Detén Laravel, Vite y Stripe CLI con `Ctrl+C`. Detén PostgreSQL y Redis sin borrar volúmenes:

```sh
docker compose stop
```

No uses `docker compose down -v`: `aquarium-postgres-data` conserva la base de datos de la Mac.

## 8. Subir esta rama a Render

La guía de despliegue y migración está en [RENDER_DEPLOYMENT_AQUARIUM.md](RENDER_DEPLOYMENT_AQUARIUM.md). El destino previsto para la revisión es Web Service Docker Free y Render Postgres Free. Render documenta que la base gratuita tiene límite de 1 GB, no incluye backups administrados y expira a los 30 días; la web gratuita se duerme tras 15 minutos sin tráfico y su sistema de archivos es efímero. Conserva una copia local y descarga otra antes del vencimiento. Consulta [las limitaciones vigentes de Render Free](https://render.com/docs/free).

Para el Web Service, usa `docker/render/Dockerfile`, contexto en la raíz del repositorio y health check `/up`. Configura secretos y variables desde el panel de Render; no subas `.env`, la copia `.dump`, claves Stripe ni la URL de la base. Mantén Stripe en modo `test` mientras se valida el despliegue. La base Render y la publicación no se han creado desde esta guía.
