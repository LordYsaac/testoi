-- =====================================================================
-- OPTICA ERP - Datos semilla (roles, permisos, usuario admin, catalogos)
-- Cargar DESPUES de schema.sql y schema_logic.sql
-- =====================================================================

SET NAMES utf8mb4;

-- =====================================================================
-- ROLES
-- =====================================================================
INSERT INTO roles (nombre, descripcion, es_sistema) VALUES
('Super Administrador', 'Acceso total al sistema, incluida configuracion y seguridad', 1),
('Administrador',       'Gestion operativa completa, sin acceso a configuracion critica del sistema', 1),
('Recepcion',           'Gestion de clientes, citas y caja de recepcion', 1),
('Doctor/Oftalmologo',  'Historial clinico, examenes y recetas', 1),
('Optometra',           'Historial clinico y recetas (sin diagnostico medico)', 1),
('Cajero',              'Facturacion, cobros y caja', 1),
('Inventario',          'Gestion de productos, compras y proveedores', 1),
('Vendedor',            'Ventas de productos y consulta de inventario', 1);

-- =====================================================================
-- PERMISOS (modulo.accion)
-- =====================================================================
INSERT INTO permisos (modulo, accion, descripcion) VALUES
-- Dashboard
('dashboard','ver','Ver panel principal'),
-- Usuarios y roles
('usuarios','ver','Ver usuarios'), ('usuarios','crear','Crear usuarios'), ('usuarios','editar','Editar usuarios'), ('usuarios','eliminar','Eliminar/desactivar usuarios'),
('roles','ver','Ver roles y permisos'), ('roles','crear','Crear roles'), ('roles','editar','Editar permisos de roles'), ('roles','eliminar','Eliminar roles'),
-- Clientes
('clientes','ver','Ver clientes'), ('clientes','crear','Crear clientes'), ('clientes','editar','Editar clientes'), ('clientes','eliminar','Desactivar clientes'), ('clientes','exportar','Exportar clientes'),
-- Clinico
('expedientes','ver','Ver historial clinico'), ('expedientes','crear','Crear entradas de historial clinico'), ('expedientes','editar','Editar historial clinico'),
('recetas','ver','Ver recetas'), ('recetas','crear','Emitir recetas'), ('recetas','anular','Anular recetas'), ('recetas','imprimir','Imprimir/exportar recetas'),
('citas','ver','Ver agenda'), ('citas','crear','Crear citas'), ('citas','editar','Editar/reagendar citas'), ('citas','eliminar','Cancelar citas'),
-- Inventario
('productos','ver','Ver inventario'), ('productos','crear','Crear productos'), ('productos','editar','Editar productos'), ('productos','eliminar','Desactivar productos'),
('inventario','ajustar','Registrar entradas/salidas/ajustes de inventario'), ('inventario','kardex','Ver kardex'),
('proveedores','ver','Ver proveedores'), ('proveedores','crear','Crear proveedores'), ('proveedores','editar','Editar proveedores'),
('compras','ver','Ver compras'), ('compras','crear','Registrar compras'), ('compras','pagar','Registrar pagos a proveedores'),
-- Facturacion
('facturas','ver','Ver facturas'), ('facturas','crear','Facturar'), ('facturas','anular','Anular facturas'), ('facturas','cotizar','Crear cotizaciones/apartados'),
('caja','abrir','Abrir caja'), ('caja','cerrar','Cerrar caja'), ('caja','movimientos','Registrar ingresos/egresos'),
('cuentas_cobrar','ver','Ver cuentas por cobrar'), ('cuentas_cobrar','abonar','Registrar abonos de clientes'),
-- Reportes y configuracion
('reportes','ver','Ver y exportar reportes'),
('configuracion','ver','Ver configuracion del sistema'), ('configuracion','editar','Editar configuracion del sistema'),
('auditoria','ver','Ver bitacora de auditoria');

-- =====================================================================
-- ASIGNACION DE PERMISOS POR ROL
-- =====================================================================

-- Super Administrador: todos los permisos
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT (SELECT id FROM roles WHERE nombre = 'Super Administrador'), id FROM permisos;

-- Administrador: todo excepto configuracion critica del sistema y auditoria
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT (SELECT id FROM roles WHERE nombre = 'Administrador'), id FROM permisos
WHERE clave NOT IN ('configuracion.editar');

-- Recepcion: clientes, citas, consulta de recetas/expedientes, facturacion basica
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT (SELECT id FROM roles WHERE nombre = 'Recepcion'), id FROM permisos
WHERE clave IN ('dashboard.ver','clientes.ver','clientes.crear','clientes.editar',
                 'citas.ver','citas.crear','citas.editar','citas.eliminar',
                 'expedientes.ver','recetas.ver','recetas.imprimir',
                 'facturas.ver','facturas.crear','facturas.cotizar',
                 'caja.abrir','caja.cerrar','caja.movimientos');

-- Doctor/Oftalmologo: historial clinico completo, recetas, citas propias
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT (SELECT id FROM roles WHERE nombre = 'Doctor/Oftalmologo'), id FROM permisos
WHERE clave IN ('dashboard.ver','clientes.ver','expedientes.ver','expedientes.crear','expedientes.editar',
                 'recetas.ver','recetas.crear','recetas.anular','recetas.imprimir',
                 'citas.ver','citas.editar');

-- Optometra: igual que doctor pero sin anular recetas de otros
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT (SELECT id FROM roles WHERE nombre = 'Optometra'), id FROM permisos
WHERE clave IN ('dashboard.ver','clientes.ver','expedientes.ver','expedientes.crear','expedientes.editar',
                 'recetas.ver','recetas.crear','recetas.imprimir','citas.ver');

-- Cajero: facturacion y caja
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT (SELECT id FROM roles WHERE nombre = 'Cajero'), id FROM permisos
WHERE clave IN ('dashboard.ver','clientes.ver','productos.ver',
                 'facturas.ver','facturas.crear','facturas.cotizar',
                 'caja.abrir','caja.cerrar','caja.movimientos',
                 'cuentas_cobrar.ver','cuentas_cobrar.abonar');

-- Inventario: productos, proveedores, compras, kardex
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT (SELECT id FROM roles WHERE nombre = 'Inventario'), id FROM permisos
WHERE clave IN ('dashboard.ver','productos.ver','productos.crear','productos.editar','productos.eliminar',
                 'inventario.ajustar','inventario.kardex',
                 'proveedores.ver','proveedores.crear','proveedores.editar',
                 'compras.ver','compras.crear','compras.pagar','reportes.ver');

-- Vendedor: ventas de productos e inventario en solo lectura
INSERT INTO roles_permisos (rol_id, permiso_id)
SELECT (SELECT id FROM roles WHERE nombre = 'Vendedor'), id FROM permisos
WHERE clave IN ('dashboard.ver','clientes.ver','clientes.crear','productos.ver',
                 'facturas.ver','facturas.crear','facturas.cotizar');

-- =====================================================================
-- USUARIO ADMINISTRADOR INICIAL
-- Usuario: admin   |   Password: Admin#2026   (CAMBIAR EN EL PRIMER LOGIN)
-- =====================================================================
INSERT INTO usuarios (codigo, nombre, apellido, email, username, password_hash, rol_id, estado, debe_cambiar_password)
VALUES ('USR-000001', 'Administrador', 'del Sistema', 'admin@tuoptica.com', 'admin',
        '$2y$10$jKKvJHTNvQC.PKsbzuUzQebCAPlZhGXe/v0fE6.XIzcAo8dKnvju.',
        (SELECT id FROM roles WHERE nombre = 'Super Administrador'), 'activo', 1);

-- =====================================================================
-- CONFIGURACION GENERAL (fila unica)
-- =====================================================================
INSERT INTO configuracion_empresa
    (id, nombre_empresa, nombre_comercial, rnc, direccion, telefono, whatsapp, email,
     moneda_codigo, moneda_simbolo, itbis_porcentaje, formato_factura, pie_factura, zona_horaria)
VALUES
    (1, 'Mi Optica, SRL', 'Mi Optica', '1-30-00000-1', 'Av. Principal #123, Santo Domingo',
     '809-000-0000', '809-000-0000', 'info@tuoptica.com',
     'DOP', 'RD$', 18.00, 'carta', 'Gracias por su preferencia. Esta factura es su garantia.', 'America/Santo_Domingo');

INSERT INTO configuracion_ncf (tipo_ncf, descripcion, serie, secuencia_actual, secuencia_inicial, secuencia_final, fecha_vencimiento, estado) VALUES
('B01', 'Factura de Credito Fiscal', 'B', 1, 1, 50000, '2026-12-31', 'activo'),
('B02', 'Factura de Consumo',        'B', 1, 1, 50000, '2026-12-31', 'activo');

INSERT INTO configuracion_smtp (id, estado) VALUES (1, 'inactivo');

-- =====================================================================
-- CATALOGOS BASE
-- =====================================================================
INSERT INTO categorias_productos (nombre, descripcion) VALUES
('Monturas', 'Monturas para lentes oftalmicos'),
('Lentes', 'Lunas/cristales oftalmicos'),
('Lentes de Contacto', 'Lentes de contacto blandos y rigidos'),
('Gafas de Sol', 'Gafas de sol con y sin graduacion'),
('Accesorios', 'Estuches, cadenas, limpiadores, liquidos'),
('Medicamentos', 'Gotas y medicamentos oftalmicos'),
('Insumos', 'Insumos clinicos y de consultorio');

INSERT INTO seguros_medicos (nombre) VALUES
('Particular (sin seguro)'), ('ARS Humano'), ('ARS Palic'), ('SENASA'), ('ARS Universal'), ('Mapfre Salud');

-- =====================================================================
-- DATOS DE EJEMPLO (opcional, util para demo/QA - eliminar en produccion)
-- =====================================================================
INSERT INTO usuarios (codigo, nombre, apellido, email, username, password_hash, rol_id, estado, debe_cambiar_password) VALUES
('USR-000002','Maria','Rodriguez','doctora@tuoptica.com','mrodriguez','$2y$10$jKKvJHTNvQC.PKsbzuUzQebCAPlZhGXe/v0fE6.XIzcAo8dKnvju.',(SELECT id FROM roles WHERE nombre='Doctor/Oftalmologo'),'activo',1),
('USR-000003','Carlos','Jimenez','recepcion@tuoptica.com','cjimenez','$2y$10$jKKvJHTNvQC.PKsbzuUzQebCAPlZhGXe/v0fE6.XIzcAo8dKnvju.',(SELECT id FROM roles WHERE nombre='Recepcion'),'activo',1);

INSERT INTO clientes (nombres, apellidos, sexo, fecha_nacimiento, telefono, email, cedula_pasaporte, seguro_medico_id) VALUES
('Ana', 'Gonzalez Perez', 'F', '1990-04-12', '809-555-0101', 'ana.gonzalez@example.com', '001-1234567-8', (SELECT id FROM seguros_medicos WHERE nombre='ARS Humano')),
('Luis', 'Martinez Diaz', 'M', '1985-11-03', '809-555-0102', 'luis.martinez@example.com', '001-2345678-9', (SELECT id FROM seguros_medicos WHERE nombre='Particular (sin seguro)'));

INSERT INTO productos (categoria_id, nombre, marca, modelo, color, costo, precio, stock_minimo) VALUES
((SELECT id FROM categorias_productos WHERE nombre='Monturas'), 'Montura Clasica Acetato', 'Ray-Ban', 'RB5228', 'Negro', 45.00, 110.00, 5),
((SELECT id FROM categorias_productos WHERE nombre='Lentes'), 'Luna Monofocal Antirreflejo', 'Essilor', 'Crizal', 'Transparente', 25.00, 65.00, 10),
((SELECT id FROM categorias_productos WHERE nombre='Gafas de Sol'), 'Gafas de Sol Polarizadas', 'Oakley', 'Holbrook', 'Negro/Gris', 60.00, 150.00, 3);
