# Estate (Gestión de fincas)

App web en PHP + MySQL/MariaDB para gestionar **usuarios** (admin/cuadrillero), **fincas**, **peones** y **asistencias**.

## Requisitos

- PHP 8.1+ (recomendado). También puede funcionar con PHP 7.x, pero se recomienda 8.x.
- MySQL o MariaDB.
- (Opcional) Apache/Nginx. Para desarrollo local alcanza con el servidor embebido de PHP.

## Correr en local (Windows/macOS/Linux)

### 1) Crear base de datos e importar el SQL

1. Crea una base de datos (ej: `u404968876_estate`).
2. Importa el dump incluido:
   - Archivo: `u404968876_estate.sql`

Opciones para importar:

- **phpMyAdmin**: Importar → elegir `u404968876_estate.sql`.
- **CLI** (MySQL/MariaDB):
  - `mysql -u TU_USUARIO -p u404968876_estate < u404968876_estate.sql`

### 2) Configurar la conexión

Edita las credenciales en estate/config.php:

- `DB_HOST`
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

> Recomendación: no subas credenciales reales al repo. Para producción, usa variables de entorno o un archivo de configuración fuera del control de versiones.

### 3) Levantar el servidor

Desde la carpeta del proyecto (donde está `index.php`):

- `php -S localhost:8000 -t .`

Luego abre:

- `http://localhost:8000/`

El entrypoint es `index.php`, que reutiliza el formulario y la lógica de `login.php`.

## Credenciales de prueba (si importaste el SQL tal cual)

El dump trae usuarios de ejemplo en la tabla `usuarios`. Por defecto hay un admin y (al menos) un cuadrillero.

- Admin: `admin@gmail.com`
- Cuadrillero: `cuadrillero2@gmial.com`

Las contraseñas dependen de tu dump:
- Hay registros en **texto plano** (ej: `12345678`) y también registros con **hash** (bcrypt).
- El login actual acepta ambas cosas (hash o texto plano) para mantener compatibilidad con datos viejos.

> Importante: para un entorno real, migra todas las contraseñas a `password_hash()` y elimina el fallback de texto plano.

## Roles y pantallas

- **Admin**
  - Panel principal: `panel-admin.php`
  - Gestión: peones, cuadrilleros, fincas.

- **Cuadrillero**
  - Panel principal: `panel-cuadrillero.php`
  - Ve fincas asignadas (por `fincas.cuadrillero_id`) y peones asignados (por `peones.cuadrilla_id`).

## Estructura de base de datos (resumen)

Tablas principales:

- `usuarios`: login + rol (`admin` / `cuadrillero`).
- `fincas`: datos de finca + `cuadrillero_id`.
- `peones`: trabajadores + `cuadrilla_id` (usuario cuadrillero).
- `asistencias_peones`: asistencia por (finca, peón, fecha).

## Despliegue (Hostinger / hosting compartido)

1. Subir el contenido de la carpeta `estate/` al directorio público (ej: `public_html`).
2. Crear la base de datos en el panel del hosting e importar `u404968876_estate.sql`.
3. Actualizar estate/config.php con las credenciales del hosting.
4. Probar el acceso entrando a la URL del sitio (la home carga `index.php`).

## Cron de limpieza de fotos (7 días)

Para evitar acumulación de imágenes, se incluye un script de limpieza:

- Archivo: `cleanup_peon_fotos.php`

Ejemplos de programación:

**Linux / Hosting con cron (recomendado: 1 vez al día)**

- `0 3 * * * /usr/bin/php /ruta/a/estate/cleanup_peon_fotos.php >/dev/null 2>&1`

**Windows (Task Scheduler)**

- Programa: `php`
- Argumentos: `C:\ruta\a\estate\cleanup_peon_fotos.php`
- Frecuencia: diaria

## Notas de seguridad

- Evita commitear credenciales en estate/config.php.
- Activa siempre HTTPS en producción.
- Migra contraseñas a hash y aplica políticas de contraseña.
- Considera agregar protección CSRF en formularios de alta/baja/edición.
