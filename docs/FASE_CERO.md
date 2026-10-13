# AQUARIUM — Fase cero

## Propósito

Establecer la línea base técnica, preparar un entorno local repetible y registrar las brechas antes de implementar requisitos funcionales. **Main Work Aquarium es la fuente normativa del alcance.** Los demás archivos compartidos sirven únicamente como referencia. Si Main Work contiene contradicciones internas, se registrarán para resolverlas sin cambiar el alcance por inferencia.

## Línea base acordada

| Área | Decisión |
|---|---|
| Backend | Laravel 12 y PHP 8.2+ |
| UI | React 18 con Inertia.js 1 y Vite |
| CSS | Tailwind CSS 3 |
| Datos locales | PostgreSQL 16 y Redis 7 mediante Docker Compose |
| Pruebas | PHPUnit; Selenium WebDriver y Apache JMeter según los casos y requisitos de Main Work |
| Futuro despliegue | Render para la aplicación; Supabase PostgreSQL y Storage |
| Pagos | Adaptador simulado durante desarrollo académico local |

### Cambios de compatibilidad aprobados

- El 13 de octubre de 2026 se aprobó actualizar Laravel 11 a Laravel 12 con PHP 8.2, y registrar el cambio en la línea base. Laravel 11 terminó su periodo de correcciones de seguridad el 12 de marzo de 2026. La versión 12 mantiene soporte de seguridad previsto hasta el 24 de febrero de 2027 ([ciclo oficial de versiones](https://laravel.com/framework/docs/11.x/releases)).
- PHPUnit 11.5 y Collision 8.6 reemplazan PHPUnit 10.x de Main Work por compatibilidad con Laravel 12/PHP 8.2; se conserva el propósito de las pruebas, no se cambia su cobertura definida.
- El adaptador Laravel 1.3.3 incluye soporte de Laravel 12; se conserva Inertia 1.x como especifica Main Work. Se mantiene React 18.
- Main Work contiene dos incompatibilidades internas de línea base: OE-03 indica Python 3.12 y SQLite/PostgreSQL, mientras la pila de 5.4 detalla Laravel/PHP, React/Inertia y PostgreSQL; OE-08 menciona AWS, mientras las secciones 5.4-5.5 y el diagrama definen Render + Supabase. Se sigue la selección explícita del usuario: Laravel/React/Inertia, PostgreSQL, Render y Supabase; AWS/Python quedan registrados como contradicciones del documento, no como trabajo adicional aprobado.

## Estado del entorno y trabajo

- Esqueleto Laravel creado en `aquarium/`; dependencias instaladas: Laravel 12.69.3, Inertia Laravel 1.3.4, PHPUnit 11.5.57, Collision 8.9.5 y Predis 2.4.1.
- Configurados React 18, Inertia React 1.3.0, Vite 8.3.4 y Tailwind 3.4.19. La portada pública responsive sigue la referencia visual AQUARIUM.png: navegación, hero de búsqueda, categorías, especies recomendadas, clínica veterinaria y pie de página. Las fotos se guardan localmente dentro del proyecto.
- La portada tiene filtros y contador/toast del carrito únicamente en estado React local de demostración; todavía no hay persistencia, API, autenticación, pagos ni citas médicas implementadas. La liga Figma compartida requiere iniciar sesión; la imagen adjunta basta para esta primera pantalla.
- Se añadió `docker-compose.yml` con PostgreSQL 16 y Redis 7, almacenamiento local persistente y verificaciones de salud. Credenciales dentro de Compose solo son valores de desarrollo local; no deben reutilizarse en nube.
- `.env.example` usa PostgreSQL/Redis local, y `.env` permanece ignorado por Git con la misma configuración local. `.nvmrc` fija Node 20.19.0, compatible con la versión actual de Vite; Main Work solo especifica Node 18/20. `scripts/php-local.ps1` carga los módulos PHP locales por proceso.
- Entorno detectado: PHP 8.2 y Composer 2 instalados; Node 24.21 activo. Las DLL `pdo_pgsql`, `pgsql`, `gd` y `zip` existen en XAMPP y se cargaron correctamente en una invocación temporal de PHP; `scripts/php-local.ps1` prepara esas extensiones sin editar el `php.ini` global.
- Las dependencias npm se instalaron; Vite se actualizó a 8.3.4 para eliminar sus avisos conocidos. npm aún reporta 7 avisos de dependencias del árbol de Tailwind 3 (5 altos, 2 moderados). Main Work especifica Tailwind 3; no se cambia a la versión mayor 4 sin autorización. Estos avisos quedan abiertos y deben resolverse o aceptarse explícitamente antes de publicar.
- `npm run build`, `scripts/php-local.ps1 artisan route:list` y `docker compose config --quiet` finalizaron correctamente. PostgreSQL y Redis están `healthy`; Redis respondió `PONG`, PostgreSQL acepta conexiones y Laravel creó sus tablas base. Son comprobaciones de compilación y entorno; no se han ejecutado pruebas PHPUnit, Selenium ni JMeter.
- La portada respondió HTTP 200 en `http://127.0.0.1:8000` y el navegador mostró el contenido React/Inertia. Se usa el build local de Vite; no requiere que el servidor de desarrollo de Vite permanezca activo. Docker Desktop no estaba activo durante esta revisión, así que PostgreSQL/Redis no están disponibles ahora.

## Acciones para cerrar fase cero

1. Activar Node 20.19 en la máquina de desarrollo; las dependencias están instaladas y fijadas en `package-lock.json`.
2. Confirmar persistencia de los volúmenes locales de PostgreSQL/Redis después de reiniciar los contenedores.
3. Definir de forma reproducible la versión y extensiones PHP para todos los desarrolladores; el lanzador local ya carga los módulos requeridos en este equipo.
4. Confirmar inicio local de Docker Desktop y persistencia de volúmenes; la portada sí se muestra, pero funcionalidades que consulten base de datos aún dependerán de esos servicios.
5. Crear la matriz trazable desde requisitos y casos de Main Work hacia módulo, prioridad, prueba y evidencia. Registrar antes cualquier contradicción hallada dentro de Main Work.

## Criterio de salida

La fase cero termina cuando el equipo puede clonar el repositorio, preparar el entorno documentado, iniciar servicios y cargar la app local conectada a PostgreSQL sin credenciales de nube; además, los requisitos de Main Work quedan listos para trazarse a implementación y evidencia de prueba. Después se inicia una primera ruta vertical elegida de acuerdo con la matriz de requisitos, no por suposición.
