# Roadmap — Fase 5

Fases 1 a 4 completas y probadas de punta a punta — ver `README.md`.
Lo que queda son piezas que, o requieren credenciales de terceros que no
existen en este entorno de desarrollo, o son de menor prioridad
operativa frente a lo ya construido.

## Fase 5 — Integraciones externas y refinamientos

| Pendiente | Por que no se hizo ya |
|---|---|
| **WhatsApp Business API** | Requiere una cuenta de Meta Business + numero verificado. `configuracion_integraciones` ya tiene el espacio reservado (`proveedor='whatsapp_api'`); implementar el cliente HTTP una vez se tengan las credenciales reales. |
| **Google Calendar** | Requiere credenciales OAuth de un proyecto en Google Cloud Console. Igual que arriba, `configuracion_integraciones` ya reserva `proveedor='google_calendar'`. |
| **Facturación electronica (e-CF, DGII)** | Requiere certificado digital y alta ante la DGII (Republica Dominicana). El modelo de datos (`facturas.ncf`, `configuracion_ncf`) ya sigue el formato NCF tradicional; el e-CF es un protocolo XML/firma digital adicional sobre esa base. |
| **Pasarelas de pago** | Requiere cuenta comercial con el proveedor (Stripe, Azul, CardNet...) y sus credenciales de API. |
| **Notas de credito/debito y devoluciones (UI)** | Tablas listas (`notas_credito`, `notas_debito`, `devoluciones`); una devolucion debe generar un movimiento de entrada de inventario — mismo patron que `Factura::anular()`. Menor prioridad porque `Factura::anular()` ya cubre el caso mas comun (anular una venta completa). |
| **Cotizacion → Factura real** | Hoy "Cotización" es un tipo de factura simplificado (sin NCF ni movimiento de inventario). Falta el boton "Convertir a factura" que tome una cotizacion existente y genere la venta real. |
| **Exportacion de reportes a Excel/PDF** | Hoy los reportes exportan a CSV (nativo, sin dependencias, se abre directo en Excel). Un Excel con formato o un PDF requieren `PhpSpreadsheet`/`Dompdf` via Composer. |

## Como continuar (para quien retome el proyecto)

1. Lea `docs/ARCHITECTURE.md` para los patrones ya establecidos.
2. Para integraciones externas: cree una clase en `app/Core/Integraciones/` (ej. `WhatsAppService.php`) que lea sus credenciales de `configuracion_integraciones` (columna `parametros`, JSON) y exponga metodos simples (`enviarMensaje()`, etc.); llamela desde los controladores existentes donde aplique (ej. recordatorio de citas).
3. Para Cotizacion → Factura: en `FacturasController`, un nuevo metodo que tome una factura `tipo='cotizacion'`, la duplique como `tipo='venta_producto'` reutilizando `Factura::crearCompleta()` con las mismas lineas, y marque la cotizacion original como convertida.
4. Los permisos para modulos nuevos se agregan a `permisos`/`roles_permisos` via un archivo en `database/migrations/` (ver su README).



