# AQUARIUM

Aplicación local en preparación para el sistema AQUARIUM. El alcance funcional y las prioridades se derivan de **Main Work Aquarium**; los demás documentos del proyecto son referencias.

## Línea base técnica

- PHP 8.2+ y Laravel 12 (en este entorno: Laravel 12.69.3).
- React 18.3.1 con Inertia.js 1.x.
- Vite 8.3.4 y Tailwind CSS 3.4.19.
- PostgreSQL 16 y Redis 7 en Docker Compose.
- PHPUnit 11.5.57 para la estrategia de pruebas.
- Destino previsto, según la decisión del proyecto: Render y Supabase.

Se conservan las versiones mayores especificadas por Main Work. Las actualizaciones de versión mayor que alteren esa línea base deben registrarse y comprobarse antes de adoptarlas. El estado, las decisiones de compatibilidad y las brechas están en [`docs/FASE_CERO.md`](docs/FASE_CERO.md).

## Requisitos locales

- PHP 8.2 con Composer 2.
- Node.js 20.19.0 (indicado por `.nvmrc`) y npm.
- Docker Desktop con Docker Compose.

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
./scripts/php-local.ps1 artisan serve
```

Visita `http://127.0.0.1:8000`. No subas `.env`; contiene la configuración privada de cada máquina. Las credenciales definidas en Compose son exclusivamente para desarrollo local.

## Comprobaciones realizadas

- Compilación de recursos con Vite.
- Inspección de rutas Laravel.
- Validación de la configuración Compose.
- PostgreSQL y Redis locales iniciaron con estado saludable.

La aplicación y la matriz de trazabilidad aún están en fase cero. No se han ejecutado las suites PHPUnit, Selenium ni JMeter; tampoco se ha desplegado a la nube. Consulta `docs/FASE_CERO.md` para las tareas pendientes y los avisos de dependencias conocidos.
