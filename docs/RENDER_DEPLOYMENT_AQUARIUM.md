# Despliegue y migración de AQUARIUM a Render

## Estado y objetivo

Este repositorio queda preparado para publicar Laravel 12 + React/Inertia en un Web Service Docker de Render y mover la base PostgreSQL operativa a Render Postgres. La aplicación no depende de Supabase para las tablas de negocio; Laravel se conecta por `DB_URL`. El almacenamiento de archivos se configura por separado y no se cambia durante el traslado de la base.

El Dockerfile de Render construye los recursos de Vite, sirve Laravel con Nginx + PHP-FPM y publica `/up` como health check. La base no se crea ni se migra al construir la imagen.

Para clonar y probar la misma base en macOS antes del despliegue, sigue [`PRUEBAS_LOCALES_MAC_AQUARIUM.md`](PRUEBAS_LOCALES_MAC_AQUARIUM.md). El respaldo `.dump` se mantiene fuera de Git; transfiérelo de forma privada y conserva la copia local antes de migrar datos a Render.

## Plan gratuito para la revisión

La configuración elegida para esta etapa es **Web Service Free + Render Postgres Free**, sin crear servicios pagados ni cron jobs. Es apropiada para la presentación del 30 de noviembre y el posible periodo de correcciones hasta el 18 de diciembre, siempre que la base se cree a tiempo y se conserve un respaldo local.

Límites importantes del plan gratuito:

- La web se suspende después de 15 minutos sin tráfico y puede tardar alrededor de un minuto en despertar.
- PostgreSQL Free tiene límite de 1 GB, no incluye backups administrados y vence 30 días después de crearse. Tras vencer, hay un periodo de gracia limitado antes de que Render elimine la base.
- El sistema de archivos del Web Service es temporal; los archivos subidos ahí no sirven como almacenamiento permanente. Las imágenes incluidas en `public/` sí se compilan en la imagen de la aplicación.
- El plan Free no ofrece shell ni Pre-Deploy; tampoco se creará un Cron Job, que tiene costo mínimo mensual. Por ello las tareas programadas no correrán automáticamente.

Render documenta estos límites en [Deploy for Free](https://render.com/docs/free). Revisa el contador de caducidad de la base en el Dashboard y descarga un `pg_dump` antes de que venza. Si la base se crea el 19 de noviembre, el límite de 30 días queda aproximadamente el 19 de diciembre: alcanza el plazo máximo del 18 de diciembre con un margen de un día. No prolongues el uso con datos importantes sin exportar o actualizar el plan.

Las migraciones y restauraciones se ejecutan desde el entorno local contra la **External Database URL** protegida con `sslmode=require`. La aplicación en Render se conectará a la **Internal Database URL** porque web y base compartirán región. Render documenta el uso de la URL externa desde equipos locales y de la interna entre servicios en la misma región ([conexiones a Postgres](https://render.com/docs/postgresql-creating-connecting)). No actives `Pre-Deploy` ni un plan pagado.

## Antes de migrar datos

1. En Render Dashboard crea PostgreSQL **Free**, versión PostgreSQL compatible con el servidor actual y región cercana al Web Service. Copia la **Internal Database URL** como secreto; no la pongas en Git ni en capturas.
2. Anota tamaño y versión de la base actual, y genera una copia de seguridad comprobable. Si la base actual es Supabase, la copia lógica se hace con `pg_dump`; Supabase documenta la exportación de su base remota con `supabase db dump` y sus opciones de migración. [Migración y copias de Supabase](https://supabase.com/docs/guides/platform/migrating-within-supabase).
3. Programa una ventana sin escrituras (detén ventas, inventario, citas y usuarios) para la copia final. No cambies `DB_URL` hasta restaurar y verificar el destino. Conserva intacta la base anterior hasta que la nueva pase la revisión.
4. Exporta el esquema `public` de PostgreSQL sin propietarios ni ACL ligados al proyecto de origen. Restaura en la base Render vacía y verifica tablas, índices, secuencias, conteos de filas, stock agregado y movimientos históricos. No migres esquemas internos administrados de Supabase a Render.
5. Cambia `DB_URL` al Internal Database URL de Render en el Web Service; define `DB_CONNECTION=pgsql` y `DB_SSLMODE=require`. Mantén fuera del repositorio cualquier URI con usuario o contraseña.

La copia y restauración final requieren acceso autenticado a ambas bases. Este cambio no se ejecuta desde el repositorio y aún no se ha hecho.

## Crear el servicio web gratuito

1. Conecta en Render el repositorio y crea un **Web Service** con Runtime **Docker**. Render construye la imagen desde el Dockerfile del repositorio ([Docker en Render](https://render.com/docs/docker)).
2. Usa `docker/render/Dockerfile` como ruta de Dockerfile y la raíz del repo `aquarium` como contexto.
3. Configura Health Check Path `/up`.
4. En **Environment Variables** configura los valores del servicio. Render recomienda guardar ahí variables y secretos, no versionarlos ([Environment Variables and Secrets](https://render.com/docs/configure-environment-variables)):

| Variable | Valor |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | clave nueva generada localmente con `php artisan key:generate --show`; consérvala en un gestor seguro |
| `APP_URL` | URL HTTPS pública del servicio |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Internal Database URL de Render Postgres |
| `DB_SSLMODE` | `require` |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `LOG_CHANNEL` | `stderr` |
| `LOG_LEVEL` | `info` |
| `STRIPE_ENABLED` | `true` para staging de prueba |
| `STRIPE_MODE` | `test` |
| `STRIPE_LIVE_PAYMENTS_ENABLED` | `false` |
| `STRIPE_KEY` | clave publicable `pk_test_` recién rotada |
| `STRIPE_SECRET` | clave secreta `sk_test_` recién rotada |
| `STRIPE_WEBHOOK_SECRET` | secreto `whsec_` del endpoint de webhook de Render |

No agregues claves de Stripe al Dockerfile, archivos versionados, comandos de terminal compartidos ni logs.

5. **No configures Pre-Deploy:** no está disponible en el Web Service gratuito. Antes del primer despliegue, ejecuta las migraciones desde el contenedor local usando la URL externa de PostgreSQL y `DB_SSLMODE=require`. No uses `migrate:fresh`, `db:wipe` ni seeders sobre la base restaurada.
6. Selecciona el plan **Free** para el Web Service y mantén apagado Auto-Deploy mientras se terminan de poner las variables. El servicio se dormirá cuando no reciba visitas; no es un fallo.
7. No crees un Cron Job en esta etapa para mantener el costo en cero. La expiración de reservas y otras tareas programadas no tendrán ejecución periódica; la sección de información de cuidados no requiere ninguna tarea.

## Stripe: staging y activación real

La primera publicación debe usar Stripe en `test`. En Stripe Dashboard crea un webhook para `https://<dominio-render>/webhooks/stripe` con los eventos que usa el conciliador: `payment_intent.succeeded`, `payment_intent.payment_failed`, `payment_intent.canceled` y `refund.updated`. Copia su secreto `whsec_` en `STRIPE_WEBHOOK_SECRET` de Render. El modo test usa sus propias claves y firma; las claves live requieren su propio endpoint y secreto. Stripe firma los webhooks y recomienda verificar la firma antes de procesar el evento. [Documentación oficial de webhooks](https://docs.stripe.com/webhooks).

La clave secreta de prueba expuesta previamente debe revocarse/rotarse desde Stripe Dashboard. Obtén una clave nueva; no vuelvas a usar la expuesta. Las claves test y live son distintas y Stripe permite rotarlas desde su Dashboard. [Claves API de Stripe](https://docs.stripe.com/keys?locale=es-419).

Cuando negocio y Stripe estén listos para cobrar dinero real, cambia de manera coordinada `STRIPE_MODE=live`, `STRIPE_LIVE_PAYMENTS_ENABLED=true`, claves `pk_live_` / `sk_live_` y secreto live del webhook. El código bloquea live si el entorno no es `production`, si `APP_DEBUG=true` o si `APP_URL` no usa HTTPS. No actives esa combinación hasta completar verificación de la cuenta Stripe, términos, políticas, reembolsos y comprobación de una compra real aprobada por el negocio.

## Aumento solicitado de inventario

Después de restaurar los datos finales de la base de origen y antes de abrir el sistema al público, ejecuta una sola vez:

```sh
php artisan aquarium:increase-stock 25
```

El comando suma 25 al stock actual de cada fila de `products`, no sustituye el stock. Crea un movimiento `ajuste` por producto con cantidades antes/después y motivo. Si detecta el mismo motivo de ajuste, aborta para no volver a sumar. La ejecución debe ser en la base de Render ya restaurada; no la ejecutes antes de importar el stock histórico.

## Verificación posterior

- Comparar conteos de filas por tabla y claves/relaciones críticas con el origen.
- Validar conteo de productos y `stock` con reporte antes/después: diferencia de +25 por producto.
- Iniciar sesión con cuentas existentes y comprobar que sus contraseñas siguen funcionando.
- Completar flujo test de compra y cita; comprobar webhook, inventario, cancelación y reembolso.
- Reiniciar servicio y repetir lectura de catálogo, cuenta, pedidos y citas para confirmar persistencia.
- Revisar logs de Render y Stripe para respuestas HTTP, eventos webhook y errores SQL; nunca compartir secretos.

## Lo que hace falta para ejecutar la migración gratuita

- Acceso a Render Dashboard para crear la base Free y el Web Service Free.
- URL de conexión de origen accesible para exportar, y la URL interna de destino que Render genera.
- Claves Stripe de prueba recién rotadas.
- Programar la ventana de solo lectura para el corte de datos y conservar el respaldo local durante toda la revisión.

No se han cargado secretos ni se ha creado servicio remoto desde este cambio local.
