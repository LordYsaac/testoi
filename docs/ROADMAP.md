# Roadmap — Fase 4

La base de datos (`database/schema.sql`) ya modela **todos** los módulos
de la especificación original. Fases 1, 2 y 3 (clínico/recetas,
inventario/compras, facturación/caja/cuentas por cobrar) están completas
y probadas — ver `README.md`.

## Fase 4 — Agenda visual, reportes, notificaciones e integraciones

| Pendiente | Notas |
|---|---|
| **Agenda médica (calendario)** | La tabla `citas` y `vista_citas_dia` ya existen (el dashboard ya las consume); falta una vista de calendario/lista dedicada con creación y reprogramación. |
| **Reportes exportables** | Usar `sp_reporte_ventas_periodo` como base; replicar para compras, inventario, pacientes atendidos. Para Excel, `PhpSpreadsheet` vía Composer; CSV no necesita librería. |
| **Notas de crédito/débito y devoluciones** | Tablas listas (`notas_credito`, `notas_debito`, `devoluciones`); falta UI. Una devolución debe generar un movimiento de entrada de inventario — mismo patrón que la anulación de factura ya implementada en `Factura::anular()`. |
| **Cotizaciones/Apartados como flujo distinto** | Hoy "Cotización" es un tipo de factura simplificado (sin NCF ni movimiento de inventario). Convertir una cotización en factura real, o reservar stock en un apartado sin descontarlo aún, son extensiones naturales de `Factura::crearCompleta()`. |
| **Doble factor de autenticación** | `usuarios.two_factor_secret`/`two_factor_activo` ya existen; falta la lógica TOTP y su UI. |
| **WhatsApp API / Google Calendar / Facturación electrónica (e-CF) / Pasarelas de pago** | `configuracion_integraciones` ya reserva el espacio de configuración. |

## Cómo continuar (para quien retome el proyecto)

1. Lea `docs/ARCHITECTURE.md` — las decisiones de diseño explican los patrones a replicar.
2. Para un módulo CRUD estándar: copie el patrón de `ProveedoresController` + `Proveedor`.
3. Para un módulo con transacción multi-tabla y efectos en inventario/caja: copie `Factura::crearCompleta()` o `Compra::crearCompleta()` — ambos resuelven el mismo problema (agregado transaccional con movimiento de inventario) desde direcciones opuestas.
4. Cada módulo nuevo: 1 Controller + 1 Model + rutas en `routes/web.php` con su `RoleMiddleware` + vistas en `app/Views/<modulo>/`. Los permisos ya están sembrados en `database/seed.sql` donde aplica; para módulos nuevos no contemplados originalmente, agréguelos a la tabla `permisos` y a `roles_permisos` en un archivo de migración (ver `database/migrations/README.md`).


