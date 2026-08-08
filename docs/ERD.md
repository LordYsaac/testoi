# Diagrama Entidad-Relación

48 tablas en un solo diagrama es ilegible, así que se presenta dividido por
dominio funcional. El esquema completo, con tipos de dato, índices y
comentarios de cada columna, está en `database/schema.sql` (es la fuente
de verdad; estos diagramas son una vista de las relaciones).

## 1. Seguridad, usuarios y configuración

```mermaid
erDiagram
    ROLES ||--o{ USUARIOS : "tiene"
    ROLES ||--o{ ROLES_PERMISOS : "otorga"
    PERMISOS ||--o{ ROLES_PERMISOS : "concedido en"
    USUARIOS ||--o{ AUDITORIA : "genera"
    USUARIOS ||--o{ NOTIFICACIONES : "recibe"
    USUARIOS ||--o{ SESIONES_ACTIVAS : "abre"

    ROLES {
        int id PK
        string nombre
        bool es_sistema
    }
    PERMISOS {
        int id PK
        string modulo
        string accion
        string clave "generada: modulo.accion"
    }
    ROLES_PERMISOS {
        int rol_id PK,FK
        int permiso_id PK,FK
    }
    USUARIOS {
        int id PK
        string codigo UK
        string username UK
        string email UK
        string password_hash
        int rol_id FK
        string estado
        int intentos_fallidos
        datetime bloqueado_hasta
    }
    CONFIGURACION_EMPRESA {
        tinyint id PK "fila unica = 1"
        string nombre_empresa
        string rnc
        string moneda_simbolo
        decimal itbis_porcentaje
    }
    AUDITORIA {
        bigint id PK
        int usuario_id FK
        string accion
        string tabla_afectada
        json datos_anteriores
        json datos_nuevos
    }
```

## 2. Clientes e historial clínico oftalmológico

El expediente clínico es un **agregado**: un encabezado con hasta 7
secciones 1:1 y dos listas 1:N. Todas las secciones son opcionales por
consulta (no todas se llenan siempre), por eso están separadas en vez de
una sola tabla ancha con decenas de columnas nulas.

```mermaid
erDiagram
    CLIENTES ||--o| ANTECEDENTES_MEDICOS : "tiene"
    CLIENTES ||--o{ EXPEDIENTES_CLINICOS : "acumula"
    CLIENTES ||--o{ RECETAS : "recibe"
    CLIENTES ||--o{ CITAS : "agenda"
    CLIENTES }o--o| SEGUROS_MEDICOS : "afiliado a"

    EXPEDIENTES_CLINICOS ||--o| AGUDEZA_VISUAL : "seccion"
    EXPEDIENTES_CLINICOS ||--o| REFRACCION : "seccion"
    EXPEDIENTES_CLINICOS ||--o| DISTANCIA_PUPILAR : "seccion"
    EXPEDIENTES_CLINICOS ||--o| QUERATOMETRIA : "seccion"
    EXPEDIENTES_CLINICOS ||--o| TONOMETRIA : "seccion"
    EXPEDIENTES_CLINICOS ||--o| BIOMICROSCOPIA : "seccion"
    EXPEDIENTES_CLINICOS ||--o| FONDO_OJO : "seccion"
    EXPEDIENTES_CLINICOS ||--o{ DIAGNOSTICOS : "lista"
    EXPEDIENTES_CLINICOS ||--o{ TRATAMIENTOS : "lista"
    EXPEDIENTES_CLINICOS ||--o{ EXPEDIENTE_ADJUNTOS : "adjunta"
    EXPEDIENTES_CLINICOS ||--o{ RECETAS : "puede originar"
    USUARIOS ||--o{ EXPEDIENTES_CLINICOS : "atiende (doctor_id)"
    USUARIOS ||--o{ RECETAS : "emite (doctor_id)"

    CLIENTES {
        int id PK
        string codigo_cliente UK
        string nombres
        string apellidos
        date fecha_nacimiento "edad via fn_edad()"
        string cedula_pasaporte UK
        int seguro_medico_id FK
    }
    EXPEDIENTES_CLINICOS {
        int id PK
        int cliente_id FK
        int doctor_id FK
        datetime fecha
        text motivo_consulta
        date proxima_cita
    }
    REFRACCION {
        int expediente_id PK,FK
        decimal od_esfera
        decimal od_cilindro
        smallint od_eje
        decimal oi_esfera
        decimal oi_cilindro
        smallint oi_eje
    }
    RECETAS {
        int id PK
        int expediente_id FK
        int cliente_id FK
        int doctor_id FK
        string codigo_validacion UK "usado en el QR"
        string estado
    }
    CITAS {
        int id PK
        int cliente_id FK
        int doctor_id FK
        date fecha
        time hora
        string estado
    }
```

## 3. Inventario y compras

```mermaid
erDiagram
    CATEGORIAS_PRODUCTOS ||--o{ PRODUCTOS : "clasifica"
    PROVEEDORES ||--o{ PRODUCTOS : "suple"
    PROVEEDORES ||--o{ ORDENES_COMPRA : "recibe"
    PROVEEDORES ||--o{ COMPRAS : "factura"
    PRODUCTOS ||--o{ MOVIMIENTOS_INVENTARIO : "kardex"
    ORDENES_COMPRA ||--o{ ORDENES_COMPRA_DETALLE : "detalla"
    COMPRAS ||--o{ COMPRAS_DETALLE : "detalla"
    COMPRAS ||--o{ PAGOS_PROVEEDORES : "abona"
    ORDENES_COMPRA ||--o| COMPRAS : "puede generar"

    PRODUCTOS {
        int id PK
        string codigo UK
        string codigo_barras UK
        int categoria_id FK
        int proveedor_id FK
        decimal costo
        decimal precio
        int stock_actual "solo lo escribe el trigger"
        int stock_minimo
    }
    MOVIMIENTOS_INVENTARIO {
        bigint id PK
        int producto_id FK
        string tipo "entrada/salida/transferencia/ajuste"
        int cantidad "con signo"
        string referencia_tipo "compra/factura/ajuste_manual"
    }
    COMPRAS {
        int id PK
        int proveedor_id FK
        int orden_compra_id FK
        decimal total
        decimal saldo_pendiente
        string estado_pago
    }
```

## 4. Facturación, caja y cuentas por cobrar

```mermaid
erDiagram
    CLIENTES ||--o{ FACTURAS : "compra"
    FACTURAS ||--o{ FACTURAS_DETALLE : "detalla"
    FACTURAS ||--o{ FACTURA_PAGOS : "recibe pago"
    FACTURAS ||--o{ NOTAS_CREDITO : "puede generar"
    FACTURAS ||--o{ NOTAS_DEBITO : "puede generar"
    FACTURAS ||--o{ DEVOLUCIONES : "puede generar"
    CLIENTES ||--o{ ABONOS_CLIENTES : "abona"
    PRODUCTOS ||--o{ FACTURAS_DETALLE : "se vende en"
    USUARIOS ||--o{ CAJA_SESIONES : "abre/cierra"
    CAJA_SESIONES ||--o{ CAJA_MOVIMIENTOS : "registra"

    FACTURAS {
        int id PK
        string tipo "venta_producto/venta_medica/mixta/cotizacion/apartado"
        string ncf "preparado para DGII"
        int cliente_id FK
        decimal total
        decimal saldo_pendiente
        string estado
    }
    CAJA_SESIONES {
        int id PK
        int usuario_id FK
        decimal monto_apertura
        decimal monto_esperado
        decimal diferencia
        string estado
    }
```

## Convenciones aplicadas en las 48 tablas

- **Motor:** InnoDB en todas las tablas (requerido para llaves foráneas).
- **Charset:** `utf8mb4` / `utf8mb4_unicode_ci` (soporta acentos, ñ y emoji sin problemas).
- **Claves primarias:** `id` autoincremental `INT UNSIGNED` (o `BIGINT UNSIGNED` en tablas de alto volumen: `auditoria`, `movimientos_inventario`, `notificaciones`).
- **Llaves foráneas:** ver la convención de `ON DELETE` explicada en `docs/ARCHITECTURE.md` (decisión de diseño #5).
- **Índices adicionales:** sobre columnas de búsqueda/filtro frecuente (nombres de cliente, fechas de factura/cita, códigos de barra), más un índice `FULLTEXT` en `clientes` para la búsqueda rápida.
