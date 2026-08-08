-- =====================================================================
-- OPTICA ERP - Vistas, Triggers, Funciones y Procedimientos
-- Cargar DESPUES de schema.sql
-- =====================================================================

SET NAMES utf8mb4;

-- =====================================================================
-- FUNCIONES
-- =====================================================================

DROP FUNCTION IF EXISTS fn_edad;
DELIMITER $$
CREATE FUNCTION fn_edad(p_fecha_nacimiento DATE) RETURNS INT
    DETERMINISTIC
BEGIN
    IF p_fecha_nacimiento IS NULL THEN
        RETURN NULL;
    END IF;
    RETURN TIMESTAMPDIFF(YEAR, p_fecha_nacimiento, CURDATE());
END$$
DELIMITER ;

-- =====================================================================
-- VISTAS
-- =====================================================================

CREATE OR REPLACE VIEW vista_clientes AS
SELECT
    c.id, c.codigo_cliente, c.foto, c.nombres, c.apellidos,
    CONCAT(c.nombres, ' ', c.apellidos) AS nombre_completo,
    c.sexo, c.fecha_nacimiento, fn_edad(c.fecha_nacimiento) AS edad,
    c.telefono, c.whatsapp, c.email, c.direccion, c.cedula_pasaporte,
    sm.nombre AS seguro_medico, c.estado, c.created_at
FROM clientes c
LEFT JOIN seguros_medicos sm ON sm.id = c.seguro_medico_id;

CREATE OR REPLACE VIEW vista_stock_bajo AS
SELECT
    p.id, p.codigo, p.nombre, p.marca, cp.nombre AS categoria,
    p.stock_actual, p.stock_minimo,
    (p.stock_minimo - p.stock_actual) AS unidades_faltantes
FROM productos p
JOIN categorias_productos cp ON cp.id = p.categoria_id
WHERE p.stock_actual <= p.stock_minimo AND p.estado = 'activo';

CREATE OR REPLACE VIEW vista_kardex AS
SELECT
    m.id, m.producto_id, p.codigo AS producto_codigo, p.nombre AS producto_nombre,
    m.tipo, m.cantidad, m.costo_unitario, m.motivo, m.referencia_tipo, m.referencia_id,
    m.fecha, CONCAT(u.nombre, ' ', u.apellido) AS usuario,
    SUM(m.cantidad) OVER (
        PARTITION BY m.producto_id ORDER BY m.fecha, m.id
        ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW
    ) AS saldo_acumulado
FROM movimientos_inventario m
JOIN productos p ON p.id = m.producto_id
JOIN usuarios u ON u.id = m.usuario_id;

CREATE OR REPLACE VIEW vista_clientes_frecuentes AS
SELECT
    cl.id, cl.codigo_cliente, CONCAT(cl.nombres, ' ', cl.apellidos) AS cliente,
    COUNT(f.id) AS total_facturas, COALESCE(SUM(f.total), 0) AS monto_total,
    MAX(f.fecha) AS ultima_compra
FROM clientes cl
JOIN facturas f ON f.cliente_id = cl.id AND f.estado <> 'anulada'
GROUP BY cl.id, cl.codigo_cliente, cliente
ORDER BY total_facturas DESC, monto_total DESC;

CREATE OR REPLACE VIEW vista_clientes_morosos AS
SELECT
    cl.id, cl.codigo_cliente, CONCAT(cl.nombres, ' ', cl.apellidos) AS cliente,
    cl.telefono, cl.whatsapp,
    SUM(f.saldo_pendiente) AS saldo_total,
    MIN(f.fecha) AS factura_mas_antigua,
    DATEDIFF(CURDATE(), MIN(f.fecha)) AS dias_vencido
FROM clientes cl
JOIN facturas f ON f.cliente_id = cl.id
WHERE f.saldo_pendiente > 0 AND f.estado NOT IN ('anulada')
GROUP BY cl.id, cl.codigo_cliente, cliente, cl.telefono, cl.whatsapp;

CREATE OR REPLACE VIEW vista_ventas_dia AS
SELECT
    DATE(f.fecha) AS dia, COUNT(*) AS cantidad_facturas,
    SUM(f.total) AS total_vendido,
    SUM(f.total - f.saldo_pendiente) AS total_cobrado
FROM facturas f
WHERE f.estado <> 'anulada'
GROUP BY DATE(f.fecha);

CREATE OR REPLACE VIEW vista_cuentas_por_pagar AS
SELECT
    pr.id AS proveedor_id, pr.nombre AS proveedor,
    SUM(c.saldo_pendiente) AS saldo_total,
    COUNT(c.id) AS facturas_pendientes
FROM proveedores pr
JOIN compras c ON c.proveedor_id = pr.id
WHERE c.saldo_pendiente > 0
GROUP BY pr.id, pr.nombre;

CREATE OR REPLACE VIEW vista_historial_clinico AS
SELECT
    e.id AS expediente_id, e.cliente_id,
    CONCAT(cl.nombres, ' ', cl.apellidos) AS paciente,
    e.fecha, CONCAT(u.nombre, ' ', u.apellido) AS doctor,
    e.motivo_consulta, e.estado,
    (SELECT GROUP_CONCAT(d.diagnostico SEPARATOR '; ')
       FROM diagnosticos d WHERE d.expediente_id = e.id) AS diagnosticos
FROM expedientes_clinicos e
JOIN clientes cl ON cl.id = e.cliente_id
LEFT JOIN usuarios u ON u.id = e.doctor_id
ORDER BY e.fecha DESC;

CREATE OR REPLACE VIEW vista_citas_dia AS
SELECT
    c.id, c.fecha, c.hora,
    CONCAT(cl.nombres, ' ', cl.apellidos) AS paciente, cl.telefono,
    CONCAT(u.nombre, ' ', u.apellido) AS doctor,
    c.motivo, c.estado
FROM citas c
JOIN clientes cl ON cl.id = c.cliente_id
LEFT JOIN usuarios u ON u.id = c.doctor_id
WHERE c.fecha = CURDATE()
ORDER BY c.hora;

CREATE OR REPLACE VIEW vista_dashboard_kpis AS
SELECT
    (SELECT COALESCE(SUM(total), 0) FROM facturas WHERE DATE(fecha) = CURDATE() AND estado <> 'anulada') AS ventas_hoy,
    (SELECT COALESCE(SUM(total), 0) FROM facturas WHERE YEAR(fecha) = YEAR(CURDATE()) AND MONTH(fecha) = MONTH(CURDATE()) AND estado <> 'anulada') AS ventas_mes,
    (SELECT COUNT(*) FROM citas WHERE fecha = CURDATE() AND estado NOT IN ('cancelada')) AS citas_hoy,
    (SELECT COUNT(*) FROM expedientes_clinicos WHERE DATE(fecha) = CURDATE()) AS pacientes_atendidos_hoy,
    (SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo AND estado = 'activo') AS productos_stock_bajo,
    (SELECT COALESCE(SUM(saldo_pendiente), 0) FROM facturas WHERE saldo_pendiente > 0 AND estado <> 'anulada') AS cuentas_por_cobrar,
    (SELECT COUNT(*) FROM caja_sesiones WHERE estado = 'abierta') AS cajas_abiertas;

-- =====================================================================
-- TRIGGERS
-- =====================================================================

-- Genera clientes.codigo_cliente automaticamente si no se envia desde el formulario
DROP TRIGGER IF EXISTS trg_clientes_before_insert;
DELIMITER $$
CREATE TRIGGER trg_clientes_before_insert
BEFORE INSERT ON clientes
FOR EACH ROW
BEGIN
    DECLARE v_siguiente INT;
    IF NEW.codigo_cliente IS NULL OR NEW.codigo_cliente = '' THEN
        SELECT IFNULL(MAX(CAST(SUBSTRING(codigo_cliente, 5) AS UNSIGNED)), 0) + 1 INTO v_siguiente
        FROM clientes WHERE codigo_cliente REGEXP '^CLI-[0-9]+$';
        SET NEW.codigo_cliente = CONCAT('CLI-', LPAD(v_siguiente, 6, '0'));
    END IF;
END$$
DELIMITER ;

-- Genera productos.codigo automaticamente si no se envia desde el formulario
DROP TRIGGER IF EXISTS trg_productos_before_insert;
DELIMITER $$
CREATE TRIGGER trg_productos_before_insert
BEFORE INSERT ON productos
FOR EACH ROW
BEGIN
    DECLARE v_siguiente INT;
    IF NEW.codigo IS NULL OR NEW.codigo = '' THEN
        SELECT IFNULL(MAX(CAST(SUBSTRING(codigo, 6) AS UNSIGNED)), 0) + 1 INTO v_siguiente
        FROM productos WHERE codigo REGEXP '^PROD-[0-9]+$';
        SET NEW.codigo = CONCAT('PROD-', LPAD(v_siguiente, 6, '0'));
    END IF;
END$$
DELIMITER ;

-- Unica fuente de verdad del stock: cada movimiento ajusta productos.stock_actual
-- y dispara notificacion cuando el stock CRUZA hacia el minimo (no en cada movimiento).
DROP TRIGGER IF EXISTS trg_movimientos_inventario_after_insert;
DELIMITER $$
CREATE TRIGGER trg_movimientos_inventario_after_insert
AFTER INSERT ON movimientos_inventario
FOR EACH ROW
BEGIN
    DECLARE v_stock_anterior INT;
    DECLARE v_stock_nuevo INT;
    DECLARE v_stock_minimo INT;
    DECLARE v_nombre_producto VARCHAR(150);
    DECLARE v_codigo_producto VARCHAR(30);

    SELECT stock_actual, stock_minimo, nombre, codigo
      INTO v_stock_anterior, v_stock_minimo, v_nombre_producto, v_codigo_producto
      FROM productos WHERE id = NEW.producto_id
      FOR UPDATE;

    SET v_stock_nuevo = v_stock_anterior + NEW.cantidad;

    UPDATE productos SET stock_actual = v_stock_nuevo WHERE id = NEW.producto_id;

    IF v_stock_nuevo <= v_stock_minimo AND v_stock_anterior > v_stock_minimo THEN
        INSERT INTO notificaciones (usuario_id, rol_id, tipo, titulo, mensaje, enlace, leida, created_at)
        SELECT NULL, r.id, 'stock_bajo', 'Stock bajo',
               CONCAT('El producto "', v_nombre_producto, '" (', v_codigo_producto, ') alcanzo ',
                      v_stock_nuevo, ' unidades, en o por debajo del minimo (', v_stock_minimo, ').'),
               CONCAT('/productos/ver/', NEW.producto_id), 0, NOW()
        FROM roles r WHERE r.nombre IN ('Super Administrador', 'Inventario');
    END IF;
END$$
DELIMITER ;

-- Bitacora de auditoria automatica sobre cambios sensibles en usuarios.
-- La app fija @app_usuario_id y @app_ip antes de ejecutar UPDATEs (ver Core/Database.php)
DROP TRIGGER IF EXISTS trg_usuarios_after_update;
DELIMITER $$
CREATE TRIGGER trg_usuarios_after_update
AFTER UPDATE ON usuarios
FOR EACH ROW
BEGIN
    IF NOT (OLD.nombre <=> NEW.nombre AND OLD.apellido <=> NEW.apellido AND OLD.email <=> NEW.email
            AND OLD.rol_id <=> NEW.rol_id AND OLD.estado <=> NEW.estado) THEN
        INSERT INTO auditoria (usuario_id, accion, modulo, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip_address, created_at)
        VALUES (
            @app_usuario_id, 'actualizar', 'usuarios', 'usuarios', NEW.id,
            JSON_OBJECT('nombre', OLD.nombre, 'apellido', OLD.apellido, 'email', OLD.email, 'rol_id', OLD.rol_id, 'estado', OLD.estado),
            JSON_OBJECT('nombre', NEW.nombre, 'apellido', NEW.apellido, 'email', NEW.email, 'rol_id', NEW.rol_id, 'estado', NEW.estado),
            @app_ip, NOW()
        );
    END IF;
END$$
DELIMITER ;

-- Bitacora de auditoria sobre cambios de precio/costo en productos (dato sensible del negocio)
DROP TRIGGER IF EXISTS trg_productos_after_update;
DELIMITER $$
CREATE TRIGGER trg_productos_after_update
AFTER UPDATE ON productos
FOR EACH ROW
BEGIN
    IF NOT (OLD.costo <=> NEW.costo AND OLD.precio <=> NEW.precio) THEN
        INSERT INTO auditoria (usuario_id, accion, modulo, tabla_afectada, registro_id, datos_anteriores, datos_nuevos, ip_address, created_at)
        VALUES (
            @app_usuario_id, 'actualizar', 'inventario', 'productos', NEW.id,
            JSON_OBJECT('costo', OLD.costo, 'precio', OLD.precio),
            JSON_OBJECT('costo', NEW.costo, 'precio', NEW.precio),
            @app_ip, NOW()
        );
    END IF;
END$$
DELIMITER ;

-- =====================================================================
-- PROCEDIMIENTOS ALMACENADOS
-- =====================================================================

-- Cierre de caja: calcula el efectivo esperado a partir de los movimientos
-- de la sesion y registra la diferencia contra el monto declarado por el cajero.
DROP PROCEDURE IF EXISTS sp_cerrar_caja;
DELIMITER $$
CREATE PROCEDURE sp_cerrar_caja(
    IN  p_caja_sesion_id INT UNSIGNED,
    IN  p_monto_declarado DECIMAL(12,2),
    OUT p_diferencia DECIMAL(12,2)
)
BEGIN
    DECLARE v_apertura DECIMAL(12,2);
    DECLARE v_ingresos DECIMAL(12,2);
    DECLARE v_egresos DECIMAL(12,2);
    DECLARE v_esperado DECIMAL(12,2);

    SELECT monto_apertura INTO v_apertura
      FROM caja_sesiones WHERE id = p_caja_sesion_id AND estado = 'abierta';

    IF v_apertura IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La sesion de caja no existe o ya esta cerrada';
    END IF;

    SELECT COALESCE(SUM(CASE WHEN tipo IN ('ingreso','venta') THEN monto ELSE 0 END), 0),
           COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0)
      INTO v_ingresos, v_egresos
      FROM caja_movimientos WHERE caja_sesion_id = p_caja_sesion_id;

    SET v_esperado = v_apertura + v_ingresos - v_egresos;
    SET p_diferencia = p_monto_declarado - v_esperado;

    UPDATE caja_sesiones
       SET fecha_cierre = NOW(), monto_cierre_declarado = p_monto_declarado,
           monto_esperado = v_esperado, diferencia = p_diferencia, estado = 'cerrada'
     WHERE id = p_caja_sesion_id;
END$$
DELIMITER ;

-- Reporte de ventas por dia en un rango de fechas (reutilizado por Dashboard y Reportes)
DROP PROCEDURE IF EXISTS sp_reporte_ventas_periodo;
DELIMITER $$
CREATE PROCEDURE sp_reporte_ventas_periodo(IN p_desde DATE, IN p_hasta DATE)
BEGIN
    SELECT DATE(f.fecha) AS dia, COUNT(*) AS facturas,
           SUM(f.subtotal) AS subtotal, SUM(f.descuento) AS descuento,
           SUM(f.itbis) AS itbis, SUM(f.total) AS total
    FROM facturas f
    WHERE DATE(f.fecha) BETWEEN p_desde AND p_hasta AND f.estado <> 'anulada'
    GROUP BY DATE(f.fecha)
    ORDER BY dia;
END$$
DELIMITER ;

-- Registra un movimiento de inventario validando existencia de producto y
-- que una salida no deje el stock negativo (uso opcional; la app tambien
-- puede insertar directo en movimientos_inventario dentro de una transaccion).
DROP PROCEDURE IF EXISTS sp_registrar_movimiento_inventario;
DELIMITER $$
CREATE PROCEDURE sp_registrar_movimiento_inventario(
    IN p_producto_id INT UNSIGNED,
    IN p_tipo VARCHAR(20),
    IN p_cantidad INT,
    IN p_motivo VARCHAR(255),
    IN p_referencia_tipo VARCHAR(30),
    IN p_referencia_id INT UNSIGNED,
    IN p_usuario_id INT UNSIGNED
)
BEGIN
    DECLARE v_stock_actual INT;

    SELECT stock_actual INTO v_stock_actual FROM productos WHERE id = p_producto_id FOR UPDATE;

    IF v_stock_actual IS NULL THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Producto no encontrado';
    END IF;

    IF (v_stock_actual + p_cantidad) < 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'La operacion dejaria el stock en negativo';
    END IF;

    INSERT INTO movimientos_inventario
        (producto_id, tipo, cantidad, motivo, referencia_tipo, referencia_id, usuario_id, fecha)
    VALUES
        (p_producto_id, p_tipo, p_cantidad, p_motivo, p_referencia_tipo, p_referencia_id, p_usuario_id, NOW());
END$$
DELIMITER ;
