# Sistema de Gestión de Óptica — ERP + Historia Clínica Oftalmológica

Sistema web modular para la administración integral de una óptica y su
consulta oftalmológica: clientes, historial clínico, recetas, usuarios y
roles, sobre una base de datos normalizada (3FN) pensada para crecer hacia
inventario, facturación, compras y caja.

**Stack:** PHP 8.3 (compatible desde 7.4) · MySQL/MariaDB (InnoDB) · MVC a
medida (sin framework pesado) · Bootstrap 5 · JavaScript ES6 · PDO con
prepared statements en el 100% de las consultas.

---

## Estado del proyecto: Fase 3 de un desarrollo por fases

La especificación original abarca un ERP + EMR comercial completo (18+
módulos). Eso es, honestamente, un desarrollo de varias semanas de un
equipo, no de una sola entrega. Este repositorio lleva **tres fases
completadas**: la base de datos modela el sistema completo, y los
módulos clínicos, operativos y financieros centrales están funcionales
— no maquetas.

### ✅ Incluido y probado de punta a punta en esta entrega

| Módulo | Estado |
|---|---|
| Base de datos completa (48 tablas, vistas, triggers, funciones, SPs) | ✅ |
| Autenticación, Usuarios y Roles con permisos granulares | ✅ |
| Dashboard (KPIs, gráfico de ventas, alertas, notificaciones) | ✅ |
| Clientes (CRUD, búsqueda, frecuentes/morosos, antecedentes) | ✅ |
| Historial clínico oftalmológico completo | ✅ |
| Recetas ópticas imprimibles con QR de validación pública | ✅ |
| Inventario (productos, kardex, ajustes) · Proveedores · Compras | ✅ |
| **Facturación** (NCF automático, líneas de producto/servicio, pago mixto) | ✅ |
| **Caja** (apertura/cierre con cálculo de diferencia, movimientos) | ✅ |
| **Cuentas por cobrar** (morosos, abonos) | ✅ |
| API REST v1 (autenticada por API key) | ✅ |

Facturación, Compras e Historial clínico comparten el mismo patrón
verificado: **toda operación multi-tabla ocurre en una única transacción**
(factura + detalle + movimiento de inventario + pago + movimiento de caja,
o se guarda todo, o no se guarda nada). Probado con casos reales: venta
con producto + servicio + pago mixto (efectivo/tarjeta) genera
automáticamente el NCF, descuenta inventario, y concilia con la caja
abierta — anularla repone el inventario exacto.

### 🚧 Diseñado en la base de datos, pendiente de interfaz (Fase 4)

Agenda médica (calendario visual — la lógica y vista de dashboard ya
existen) · Reportes exportables (Excel/PDF/CSV) · Notas de crédito/débito
y devoluciones · Cotizaciones/Apartados como flujo distinto (hoy
"Cotización" ya es un tipo de factura simplificado, sin NCF ni
inventario) · Doble factor de autenticación · Integraciones (WhatsApp,
Google Calendar, facturación electrónica e-CF, pasarelas de pago).

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
