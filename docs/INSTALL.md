# Manual de instalación

## Requisitos

- PHP 8.3 (mínimo 7.4) con extensiones: `pdo_mysql`, `mbstring`, `json`, `gd`, `fileinfo`, `curl`
- MySQL 5.7+ o MariaDB 10.2+ (motor InnoDB, requerido para llaves foráneas)
- Servidor web LiteSpeed o Apache con `mod_rewrite` (LiteSpeed lee `.htaccess` de forma nativa)
- Composer (opcional — solo necesario para generación de PDF en servidor y envío de correo SMTP; el resto del sistema funciona sin él)

## 1. Subir los archivos

Suba **todo el contenido del proyecto** a su hosting. La carpeta `public/`
debe ser la que el navegador puede ver; `app/`, `config/`, `database/` y
`storage/` deben quedar **fuera** del acceso público directo.

### Opción recomendada: Document Root → `public/`

Si su hosting permite elegir el document root (VPS, cPanel con dominio
adicional, WHM), apunte el dominio directamente a la carpeta `public/`.
Con esto `app/`, `config/`, `database/` y `storage/` quedan fuera del
alcance de cualquier petición HTTP, sin necesidad de reglas adicionales.

### Opción alternativa: dominio principal de cPanel (`public_html`)

Si su document root está fijo en `public_html` y no puede cambiarlo:

1. Suba todo el proyecto dentro de una carpeta, ej. `public_html/sistema/`.
2. El `.htaccess` en la raíz del proyecto (`/sistema/.htaccess`) ya
   incluye una regla de redirección hacia `public/`. Este archivo
   **bloquea explícitamente** el acceso directo a `app/`, `config/`,
   `database/`, `storage/` y `routes/` como capa adicional de seguridad.
3. Acceda como `https://sudominio.com/sistema/`.

## 2. Crear la base de datos

Desde phpMyAdmin (cPanel) o línea de comandos:

```sql
CREATE DATABASE optica_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'optica_user'@'localhost' IDENTIFIED BY 'una-clave-larga-y-unica';
GRANT ALL PRIVILEGES ON optica_erp.* TO 'optica_user'@'localhost';
FLUSH PRIVILEGES;
```

> **Importante (hosting con MySQL/MariaDB propio, no compartido):** si crea
> el usuario de base de datos como `root` o con el plugin de autenticación
> `unix_socket`/`auth_socket`, la conexión **por TCP** (la que usa PHP/PDO)
> fallará aunque el cliente `mysql` de línea de comandos funcione (este usa
> el socket local). Cree siempre un usuario dedicado como el del ejemplo
> anterior — es además la práctica correcta de seguridad (nunca usar
> `root` desde la aplicación).

Cargue el esquema **en este orden**:

```bash
mysql -u optica_user -p optica_erp < database/schema.sql
mysql -u optica_user -p optica_erp < database/schema_logic.sql
mysql -u optica_user -p optica_erp < database/seed.sql
```

Si su servidor tiene `log_bin` activo y la carga de `schema_logic.sql`
falla con un error sobre `SUPER privilege` al crear las funciones, ejecute
antes (una sola vez, como usuario con privilegios):

```sql
SET GLOBAL log_bin_trust_function_creators = 1;
```

## 3. Configurar `.env`

```bash
cp .env.example .env
```

Edite `.env` con sus datos reales:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sudominio.com

DB_HOST=127.0.0.1
DB_DATABASE=optica_erp
DB_USERNAME=optica_user
DB_PASSWORD=la-clave-que-eligio-arriba
```

`APP_DEBUG=false` es obligatorio en producción (con `true`, los errores
muestran detalles técnicos que no deben ser públicos).

## 4. Permisos de carpetas

```bash
chmod -R 755 storage
chmod -R 755 storage/uploads
chmod -R 755 storage/logs
```

El usuario del proceso PHP (normalmente el mismo de su cuenta de hosting)
necesita permiso de escritura en `storage/`.

## 5. Dependencias opcionales (Composer)

El núcleo del sistema (login, clientes, historial clínico, recetas, API)
**no requiere Composer** — trae su propio autoloader. Composer solo hace
falta para dos mejoras opcionales:

| Paquete | Para qué |
|---|---|
| `dompdf/dompdf` | Generar el PDF de la receta **en el servidor** (botón "Descargar PDF"). Sin esto, "Imprimir" sigue funcionando vía el diálogo de impresión del navegador ("Guardar como PDF"), sin instalar nada. |
| `phpmailer/phpmailer` | Envío de correo SMTP (recordatorios de citas). |

```bash
composer install
```

Si su hosting no tiene acceso SSH a Composer, puede omitir este paso: el
sistema funciona igual, simplemente sin esas dos mejoras.

## 6. Cron Jobs (opcional)

En cPanel → "Cron Jobs":

```
# Recordatorio de citas de mañana, todos los dias a las 5:00pm
0 17 * * * php /home/usuario/ruta/al/proyecto/scripts/cron/recordatorio_citas.php

# Respaldo diario de la base de datos a las 3:00am
0 3 * * * /home/usuario/ruta/al/proyecto/scripts/cron/backup_bd.sh
```

## 7. Primer inicio de sesión

Acceda a `https://sudominio.com/` (o `/sistema/` según el caso).

- **Usuario:** `admin`
- **Contraseña:** `Admin#2026`

El sistema le pedirá cambiarla de inmediato. **Elimine también los dos
usuarios de ejemplo** (`mrodriguez`, `cjimenez`) o cambie sus contraseñas
desde *Usuarios* antes de usar el sistema en producción — vienen con la
misma contraseña sembrada solo para pruebas.

Luego, desde *Configuración* (Fase 2) o directamente en la tabla
`configuracion_empresa`, actualice el nombre, logo, RNC y datos reales de
su óptica — la fila sembrada trae valores de ejemplo.

## Solución de problemas comunes

| Síntoma | Causa probable |
|---|---|
| Error 500 en `/login` | Credenciales de BD incorrectas en `.env`, o usuario de BD sin acceso TCP (ver nota arriba). Revise `storage/logs/php_errors.log`. |
| Página en blanco | `APP_DEBUG=false` oculta el error; cambie temporalmente a `true`, recargue, vea el error, y vuelva a `false`. |
| CSS/JS no cargan | El document root no apunta a `public/` y el `.htaccess` raíz no está activo, o `mod_rewrite` está deshabilitado. |
| "419 Sesión expirada" al enviar un formulario | El formulario quedó abierto más de 30 min, o las cookies de sesión están bloqueadas. Recargue e intente de nuevo. |
| Los triggers no se crean | Ejecute `SET GLOBAL log_bin_trust_function_creators = 1;` (ver paso 2). |
