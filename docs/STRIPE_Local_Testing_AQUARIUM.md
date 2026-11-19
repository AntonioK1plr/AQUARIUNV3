# Pasarela Stripe en AQUARIUM: configuración y pruebas locales

## Estado y alcance

La tienda web (`/carrito`) y la agenda de citas (`/citas`) aceptan pagos con tarjeta mediante **Stripe Payment Element + PaymentIntents** en MXN. Se retiraron los pagos simulados del checkout de la tienda y de las citas. El POS físico mantiene sus registros simulados independientes porque no es un pago en línea.

La integración está limitada deliberadamente a `APP_ENV=local`, claves `pk_test_` / `sk_test_`, `STRIPE_TEST_MODE_ONLY=true` y un secreto de webhook `whsec_`. Si falta cualquiera de esos requisitos, el botón queda desactivado. No se deben usar claves live ni datos de tarjetas reales. AQUARIUM no recibe ni guarda el número de tarjeta o CVC: Stripe.js muestra el formulario seguro.

El pedido o la cita se crea como pendiente. La interfaz no considera el retorno del navegador una prueba de pago: el webhook firmado `payment_intent.succeeded` verifica modo, moneda e importe y registra el evento de forma idempotente. Entonces confirma la compra y descuenta inventario, o confirma el horario de la cita. Una cita aparta temporalmente el horario por 30 minutos mientras se completa el pago. Si se cancela una cita pagada, AQUARIUM solicita el reembolso a Stripe; la inasistencia no lo solicita. Los eventos fallidos o cancelados no se marcan pagados.

## Requisitos

- Docker Desktop en ejecución.
- PHP y Composer, Node.js y npm disponibles mediante las herramientas/scripts del proyecto.
- Stripe CLI instalada y una cuenta de Stripe. La CLI y las claves de prueba solo conectan el entorno local con el sandbox de Stripe; no hace falta desplegar AQUARIUM ni crear recursos de producción.
- PostgreSQL local del proyecto disponible en Docker.

## 1. Configura credenciales de prueba

En el Dashboard de Stripe, activa **modo de prueba** y copia la clave publicable y la clave secreta de prueba. En el archivo `.env` local (nunca en Git), usa:

```dotenv
APP_ENV=local
APP_URL=http://127.0.0.1:8001
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=
STRIPE_TEST_MODE_ONLY=true
AQUARIUM_CONSULTATION_FEE=500
```

No compartas las claves secretas (`sk_test_...`, `whsec_...`) ni las escribas en el frontend, documentos públicos, capturas, Git o el chat. `STRIPE_KEY` es publicable, pero también conviene mantener la configuración en `.env`.

## 2. Enciende AQUARIUM localmente

Abre PowerShell en la carpeta `aquarium`:

```powershell
docker compose up -d postgres redis
./scripts/php-local.ps1 artisan migrate
```

En una terminal, arranca Laravel:

```powershell
./scripts/serve-local.ps1 -Port 8001
```

En otra terminal, sirve los assets de desarrollo:

```powershell
npm run dev
```

Abre `http://127.0.0.1:8001`. Si el frontend no carga, confirma que Vite siga ejecutándose y que Docker muestre PostgreSQL saludable. No cambies la URL pública a Stripe en modo live.

## 3. Reenvía webhooks a localhost

En otra terminal:

```powershell
stripe login
stripe listen --forward-to 127.0.0.1:8001/webhooks/stripe
```

La salida muestra un secreto de firma temporal con prefijo `whsec_`. Cópialo únicamente a `STRIPE_WEBHOOK_SECRET` en el `.env` local. Deja `stripe listen` activo durante la prueba y reinicia Laravel para que vuelva a leer la configuración. Si cierras y vuelves a abrir `stripe listen`, actualiza el secreto local que emite la CLI. El listener también recibe `refund.updated` para actualizar el resultado de los reembolsos.

La pantalla del checkout se habilita después de que Laravel tenga las tres credenciales de prueba (publicable, secreta y webhook). No se escribe información de tarjeta en las bases de datos locales.

## 4. Ejecuta escenarios manuales de pago

Inicia sesión con una cuenta de cliente de prueba, agrega un artículo disponible y abre `/carrito`, o abre `/citas` para capturar los datos de la consulta y elegir un horario disponible. Continúa al formulario de Stripe. En el Payment Element introduce tarjetas de prueba oficiales:

| Escenario | Número de prueba | Fecha | CVC |
|---|---|---|---|
| Pago aprobado | `4242 4242 4242 4242` | Cualquier fecha futura | Cualquier CVC de 3 dígitos |
| Pago rechazado | `4000 0000 0000 0002` | Cualquier fecha futura | Cualquier CVC de 3 dígitos |
| Autenticación 3D Secure | `4000 0025 0000 3155` | Cualquier fecha futura | Cualquier CVC de 3 dígitos |

Usa los datos solamente en el formulario servido por Stripe en su entorno de prueba. Si el escenario exige autenticación, completa el reto de prueba que presenta Stripe. No uses un pago de Stripe como objetivo de JMeter: Stripe limita automatización y solicitudes; la carga debe ejecutarse contra los servicios locales controlados.

## 5. Comprueba pedido, inventario y eventos

- Un pago aprobado termina con estado `pagada_stripe_test`; el inventario y su bitácora se actualizan al conciliar el webhook firmado.
- Una cita aprobada cambia de `pago_pendiente` a `programada` solo al conciliar el webhook. Si cliente o veterinario cancela una cita cobrada, se solicita un reembolso de prueba a Stripe; la inasistencia no se reembolsa.
- Si un pedido Click & Collect pagado no se recoge dentro de las 48 horas, el comando de vencimiento solicita un reembolso de prueba Stripe con clave idempotente antes de devolver el inventario.
- Un intento rechazado no se debe mostrar como pagado ni descontar existencias.
- Un webhook repetido no debe repetir el descuento: `stripe_webhook_events` conserva los IDs procesados.
- En **Mi cuenta → Mis pedidos**, revisa el estado del pedido. Si sigue en `pago_pendiente`, verifica que `stripe listen` esté activo, que su `whsec_` coincida con `.env` y que Laravel haya reiniciado.
- Los pagos de tienda y citas se conservan en `stripe_payment_attempts`; los IDs de webhooks procesados, en `stripe_webhook_events`, ambos en PostgreSQL local. Revisa `storage/logs/laravel.log` si hay errores de conciliación. El monto de consulta local se configura con `AQUARIUM_CONSULTATION_FEE` (predeterminado: $500 MXN).
- En el Dashboard de Stripe, comprueba que el selector de cuenta indique modo de prueba.

## 6. Apagado

Detén con `Ctrl+C` las terminales de Laravel, Vite y Stripe CLI. Para detener los contenedores locales sin borrar los datos:

```powershell
docker compose stop
```

No uses `docker compose down -v` si quieres conservar PostgreSQL.

## Límites antes de producción

Esta implementación aún no autoriza cobros reales. No configures claves live ni cambies `APP_ENV` para intentar habilitarlos. Antes de producción hace falta una revisión separada de seguridad y operación: URL HTTPS pública y estable para webhooks, secretos guardados en el gestor de secretos del hosting, gestión de reembolsos/disputas, avisos de privacidad y términos, conciliación contable, monitoreo y revisión del flujo de pago. Nunca pruebes tarjetas reales en esta versión.

## Referencias oficiales

- [Claves de API y modos de prueba](https://docs.stripe.com/keys)
- [Tarjetas de prueba](https://docs.stripe.com/testing)
- [Stripe CLI y webhooks locales](https://docs.stripe.com/webhooks)
- [Payment Element](https://docs.stripe.com/payments/payment-element)
- [Pruebas automatizadas y límites de Stripe](https://docs.stripe.com/automated-testing)
