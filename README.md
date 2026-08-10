# Sistema de Gestión de Óptica — ERP + Historia Clínica Oftalmológica

Sistema web modular para la administración integral de una óptica y su
consulta oftalmológica: clientes, historial clínico, recetas, usuarios y
roles, sobre una base de datos normalizada (3FN) pensada para crecer hacia
inventario, facturación, compras y caja.

**Stack:** PHP 8.3 (compatible desde 7.4) · MySQL/MariaDB (InnoDB) · MVC a
medida (sin framework pesado) · Bootstrap 5 · JavaScript ES6 · PDO con
prepared statements en el 100% de las consultas.

---

## Estado del proyecto: Fase 4 de un desarrollo por fases

La especificación original abarca un ERP + EMR comercial completo. Este
repositorio lleva **cuatro fases completadas**, cubriendo el ciclo
operativo diario completo de una óptica más las capas de seguridad y
control de gestión.

### ✅ Incluido y probado de punta a punta en esta entrega

| Módulo | Estado |
|---|---|
| Base de datos completa (48 tablas, vistas, triggers, funciones, SPs) | ✅ |
| Autenticación, Usuarios y Roles con permisos granulares | ✅ |
| **Doble factor de autenticación (2FA/TOTP)**, compatible con Google Authenticator/Authy | ✅ |
| Dashboard (KPIs, gráfico de ventas, alertas, notificaciones) | ✅ |
| Clientes · Historial clínico oftalmológico · Recetas con QR | ✅ |
| Inventario · Proveedores · Compras | ✅ |
| Facturación (NCF, pago mixto) · Caja · Cuentas por cobrar | ✅ |
| **Agenda médica** (calendario mensual, vista de día, estados, deteccion de choques de horario) | ✅ |
| **Reportes** (ventas, productos más vendidos, clientes frecuentes, inventario valorizado, pacientes atendidos, recetas emitidas, cuentas por cobrar/pagar) con **exportación CSV** | ✅ |
| API REST v1 (autenticada por API key) | ✅ |

El 2FA está implementado con TOTP (RFC 6238) **sin dependencias
externas** — verificado contra los vectores de prueba oficiales del RFC
4226. Funciona con cualquier app autenticadora estándar.

### 🚧 Pendiente (Fase 5 — requiere credenciales/API externas o es de menor prioridad operativa)

Notas de crédito/débito y devoluciones con UI dedicada · Conversión de
cotización a factura real · WhatsApp API, Google Calendar, facturación
electrónica (e-CF) y pasarelas de pago — estas cuatro requieren
credenciales reales de terceros que no existen en este entorno de
desarrollo, así que se dejan preparadas en `configuracion_integraciones`
en vez de simuladas. Exportación a Excel/PDF de reportes (hoy es CSV
nativo; Excel/PDF requieren `PhpSpreadsheet`/`Dompdf` vía Composer).

Ver **[docs/ROADMAP.md](docs/ROADMAP.md)** para el detalle.

---

## Instalación rápida

Ver **[docs/INSTALL.md](docs/INSTALL.md)** para la guía completa
(incluye configuración específica para hosting compartido con
cPanel + LiteSpeed). Resumen:

```bash
# 1. Base de datos
mysql -u root -p -e "CREATE DATABASE optica_erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p optica_erp < database/schema.sql
mysql -u root -p optica_erp < database/schema_logic.sql
mysql -u root -p optica_erp < database/seed.sql

# 2. Configuracion
cp .env.example .env
# editar .env con las credenciales de BD

# 3. Dependencias opcionales (PDF/correo) -- el nucleo funciona sin esto
composer install

# 4. Apuntar el document root del hosting a la carpeta public/
```

**Acceso inicial:** usuario `admin` / contraseña `Admin#2026` (el sistema
forzará el cambio de contraseña en el primer inicio de sesión).

---

## Documentación

- [docs/INSTALL.md](docs/INSTALL.md) — Instalación paso a paso (incluye LiteSpeed/cPanel)
- [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) — Arquitectura, decisiones de diseño, diagramas
- [docs/ERD.md](docs/ERD.md) — Diagrama entidad-relación por dominio
- [docs/USER_MANUAL.md](docs/USER_MANUAL.md) — Manual de usuario de los módulos activos
- [docs/API.md](docs/API.md) — API REST v1
- [docs/ROADMAP.md](docs/ROADMAP.md) — Qué falta y sugerencia de orden de construcción

## Estructura del proyecto

```
optica-erp/
├── app/
│   ├── Controllers/       Controladores (uno por módulo) + Api/ para la API REST
│   ├── Models/             Acceso a datos (PDO + prepared statements)
│   ├── Core/                Framework propio: Router, Auth, Database, Validator...
│   ├── Views/                Plantillas PHP (layout + una carpeta por módulo)
│   └── Helpers/             Funciones globales (e(), moneda(), edad_desde()...)
├── public/                  Document root: index.php (front controller) + assets/
├── config/                   config.php, database.php, api.php
├── database/                 schema.sql, schema_logic.sql (vistas/triggers/SPs), seed.sql
├── routes/                   web.php, api.php
├── scripts/cron/             Recordatorios de citas, respaldo/restauración de BD
├── storage/                  logs/, uploads/ (fuera del alcance público)
└── docs/                     Toda la documentación
```

## Seguridad implementada

PDO + prepared statements en toda consulta · Sanitización de salida
(`e()`/htmlspecialchars) en toda vista · Protección CSRF por token de sesión
en cada formulario · `password_hash`/`password_verify` (bcrypt) ·
Bloqueo temporal tras 5 intentos fallidos · Sesiones con cookies
`HttpOnly`+`SameSite`, regeneración periódica de ID · Control de permisos
por rol a nivel de ruta (middleware) · Bitácora de auditoría (quién,
qué, cuándo, valores antes/después) · Cabeceras de seguridad (CSP,
X-Frame-Options, X-Content-Type-Options) · Validación de subida de
archivos por MIME real (no solo extensión).

## Licencia

Software propietario desarrollado a medida. Todos los derechos reservados
al propietario del negocio para el que fue construido.
