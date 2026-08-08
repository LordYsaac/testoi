-- =====================================================================
-- OPTICA ERP - Esquema de base de datos
-- Motor: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- Normalizado a 3FN. Diseñado para MySQL 5.7+/MariaDB 10.2+
--
-- Convenciones de integridad referencial usadas en todo el esquema:
--   - Datos maestros referenciados por historial transaccional
--     (clientes, usuarios, productos, proveedores) -> ON DELETE RESTRICT
--     No se permite borrar un maestro con historial; se desactiva
--     (columna `estado`) en su lugar. Este sistema NO hace hard-delete
--     de información clínica, de inventario ni financiera.
--   - Tablas detalle que pertenecen exclusivamente a un padre
--     (detalle de factura, secciones del expediente clínico, etc.)
--     -> ON DELETE CASCADE
--   - Referencias descriptivas opcionales (aseguradora, proveedor
--     de un producto) -> ON DELETE SET NULL
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';

-- =====================================================================
-- SECCION 1: SEGURIDAD, USUARIOS Y CONFIGURACION
-- =====================================================================

CREATE TABLE roles (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(50)  NOT NULL,
    descripcion     VARCHAR(255) NULL,
    es_sistema      TINYINT(1)   NOT NULL DEFAULT 0 COMMENT 'Roles base que no se pueden eliminar (solo renombrar permisos)',
    estado          ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_roles_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Roles del sistema: Super Administrador, Administrador, Recepcion, Doctor/Oftalmologo, Optometra, Cajero, Inventario, Vendedor';

CREATE TABLE permisos (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modulo      VARCHAR(50)  NOT NULL COMMENT 'Ej: clientes, expedientes, inventario, facturacion',
    accion      VARCHAR(50)  NOT NULL COMMENT 'Ej: ver, crear, editar, eliminar, anular, exportar',
    clave       VARCHAR(101) AS (CONCAT(modulo, '.', accion)) STORED,
    descripcion VARCHAR(255) NULL,
    UNIQUE KEY uq_permisos_modulo_accion (modulo, accion),
    UNIQUE KEY uq_permisos_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catalogo de permisos granulares por modulo y accion';

CREATE TABLE roles_permisos (
    rol_id      INT UNSIGNED NOT NULL,
    permiso_id  INT UNSIGNED NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    CONSTRAINT fk_rp_rol     FOREIGN KEY (rol_id)     REFERENCES roles(id)    ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_rp_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Matriz de permisos personalizados por rol';

CREATE TABLE usuarios (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo           VARCHAR(20)  NOT NULL,
    nombre           VARCHAR(80)  NOT NULL,
    apellido         VARCHAR(80)  NOT NULL,
    email            VARCHAR(150) NOT NULL,
    username         VARCHAR(50)  NOT NULL,
    password_hash    VARCHAR(255) NOT NULL,
    telefono         VARCHAR(20)  NULL,
    foto             VARCHAR(255) NULL,
    rol_id           INT UNSIGNED NOT NULL,
    firma_digital    VARCHAR(255) NULL COMMENT 'Ruta de imagen de firma escaneada, para doctores',
    cmd_colegiado    VARCHAR(50)  NULL COMMENT 'Numero de exequatur/colegiatura del especialista',
    estado           ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    debe_cambiar_password TINYINT(1) NOT NULL DEFAULT 1,
    intentos_fallidos INT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta  DATETIME NULL,
    two_factor_secret VARCHAR(255) NULL COMMENT 'Preparado para 2FA (TOTP)',
    two_factor_activo TINYINT(1) NOT NULL DEFAULT 0,
    ultimo_login     DATETIME NULL,
    ultimo_login_ip  VARCHAR(45) NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_codigo (codigo),
    UNIQUE KEY uq_usuarios_email (email),
    UNIQUE KEY uq_usuarios_username (username),
    KEY idx_usuarios_rol (rol_id),
    CONSTRAINT fk_usuarios_rol FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Cuentas de usuario del personal (todos los roles)';

CREATE TABLE configuracion_empresa (
    id                  TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    nombre_empresa      VARCHAR(150) NOT NULL,
    nombre_comercial    VARCHAR(150) NULL,
    rnc                 VARCHAR(20)  NULL,
    logo                VARCHAR(255) NULL,
    favicon             VARCHAR(255) NULL,
    direccion           VARCHAR(255) NULL,
    telefono            VARCHAR(20)  NULL,
    whatsapp            VARCHAR(20)  NULL,
    email               VARCHAR(150) NULL,
    facebook            VARCHAR(150) NULL,
    instagram           VARCHAR(150) NULL,
    sitio_web           VARCHAR(150) NULL,
    moneda_codigo       VARCHAR(3)   NOT NULL DEFAULT 'DOP',
    moneda_simbolo      VARCHAR(5)   NOT NULL DEFAULT 'RD$',
    itbis_porcentaje    DECIMAL(5,2) NOT NULL DEFAULT 18.00,
    formato_factura     ENUM('carta','termica_80mm','termica_58mm') NOT NULL DEFAULT 'carta',
    pie_factura         VARCHAR(500) NULL COMMENT 'Texto legal / agradecimiento al pie de la factura',
    firma_digital_texto VARCHAR(255) NULL,
    horario_atencion    JSON NULL COMMENT 'Horario por dia de la semana',
    tema_defecto        ENUM('claro','oscuro') NOT NULL DEFAULT 'claro',
    zona_horaria        VARCHAR(50)  NOT NULL DEFAULT 'America/Santo_Domingo',
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuracion general de la empresa. Fila unica (id=1)';

CREATE TABLE configuracion_ncf (
    id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo_ncf           VARCHAR(3)  NOT NULL COMMENT 'Ej: B01 credito fiscal, B02 consumo, B14 regimen especial, B15 gubernamental',
    descripcion        VARCHAR(100) NOT NULL,
    serie              CHAR(1)     NOT NULL DEFAULT 'B',
    secuencia_actual   INT UNSIGNED NOT NULL,
    secuencia_inicial  INT UNSIGNED NOT NULL,
    secuencia_final    INT UNSIGNED NOT NULL,
    fecha_vencimiento  DATE NULL,
    estado             ENUM('activo','agotado','vencido') NOT NULL DEFAULT 'activo',
    UNIQUE KEY uq_ncf_tipo (tipo_ncf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Secuencias de Numeros de Comprobante Fiscal (DGII, Rep. Dominicana). Preparado para e-CF.';

CREATE TABLE configuracion_smtp (
    id                TINYINT UNSIGNED PRIMARY KEY DEFAULT 1,
    host              VARCHAR(150) NULL,
    puerto            SMALLINT UNSIGNED NULL DEFAULT 587,
    usuario           VARCHAR(150) NULL,
    password_cifrado  VARCHAR(255) NULL,
    cifrado           ENUM('tls','ssl','ninguno') NOT NULL DEFAULT 'tls',
    remitente_nombre  VARCHAR(150) NULL,
    remitente_email   VARCHAR(150) NULL,
    estado            ENUM('activo','inactivo') NOT NULL DEFAULT 'inactivo',
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Configuracion SMTP para envio de correos (recordatorios, facturas, recetas)';

CREATE TABLE configuracion_integraciones (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proveedor      VARCHAR(50) NOT NULL COMMENT 'whatsapp_api, google_calendar, pasarela_pago, facturacion_electronica',
    parametros     JSON NULL COMMENT 'Tokens/keys cifradas o referencias a secretos',
    estado         ENUM('activo','inactivo') NOT NULL DEFAULT 'inactivo',
    updated_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_integraciones_proveedor (proveedor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Config. de integraciones externas preparadas (no activas por defecto)';

CREATE TABLE auditoria (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id       INT UNSIGNED NULL,
    accion           VARCHAR(30)  NOT NULL COMMENT 'crear, actualizar, eliminar, anular, login, logout, login_fallido',
    modulo           VARCHAR(50)  NOT NULL,
    tabla_afectada   VARCHAR(64)  NULL,
    registro_id      INT UNSIGNED NULL,
    datos_anteriores JSON NULL,
    datos_nuevos     JSON NULL,
    ip_address       VARCHAR(45)  NULL,
    user_agent       VARCHAR(255) NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_auditoria_usuario (usuario_id),
    KEY idx_auditoria_tabla_registro (tabla_afectada, registro_id),
    KEY idx_auditoria_fecha (created_at),
    CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Bitacora de auditoria: quien hizo que, cuando y con que datos';

CREATE TABLE sesiones_activas (
    id             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id     INT UNSIGNED NOT NULL,
    session_id     VARCHAR(128) NOT NULL,
    ip_address     VARCHAR(45)  NULL,
    user_agent     VARCHAR(255) NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_activity  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sesiones_session_id (session_id),
    KEY idx_sesiones_usuario (usuario_id),
    CONSTRAINT fk_sesiones_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Control de sesiones activas por usuario, permite cierre remoto';

CREATE TABLE notificaciones (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NULL COMMENT 'Notificacion dirigida a un usuario especifico',
    rol_id      INT UNSIGNED NULL COMMENT 'O dirigida a todos los usuarios de un rol',
    tipo        VARCHAR(30)  NOT NULL COMMENT 'stock_bajo, cita, pago, compra, paciente, mensaje',
    titulo      VARCHAR(150) NOT NULL,
    mensaje     VARCHAR(500) NOT NULL,
    enlace      VARCHAR(255) NULL,
    leida       TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_notif_usuario (usuario_id, leida),
    KEY idx_notif_rol (rol_id, leida),
    CONSTRAINT fk_notif_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_notif_rol     FOREIGN KEY (rol_id)     REFERENCES roles(id)    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notificaciones internas del sistema';

CREATE TABLE backups_log (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_archivo VARCHAR(255) NOT NULL,
    tamano_bytes   BIGINT UNSIGNED NULL,
    tipo           ENUM('manual','automatico') NOT NULL DEFAULT 'automatico',
    usuario_id     INT UNSIGNED NULL,
    estado         ENUM('exitoso','fallido') NOT NULL,
    mensaje        VARCHAR(500) NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_backups_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Historial de respaldos de base de datos';

CREATE TABLE cron_jobs_log (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre_job    VARCHAR(100) NOT NULL,
    resultado     ENUM('exitoso','fallido') NOT NULL,
    mensaje       VARCHAR(500) NULL,
    ejecutado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cron_nombre_fecha (nombre_job, ejecutado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Registro de ejecucion de tareas programadas (cron)';

-- =====================================================================
-- SECCION 2: CLIENTES
-- =====================================================================

CREATE TABLE seguros_medicos (
    id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre    VARCHAR(100) NOT NULL,
    contacto  VARCHAR(150) NULL,
    telefono  VARCHAR(20)  NULL,
    estado    ENUM('activo','inactivo') NOT NULL DEFAULT 'activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catalogo de seguros/ARS aceptados';

CREATE TABLE clientes (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo_cliente    VARCHAR(20)  NOT NULL,
    foto              VARCHAR(255) NULL,
    nombres           VARCHAR(100) NOT NULL,
    apellidos         VARCHAR(100) NOT NULL,
    sexo              ENUM('M','F') NULL,
    fecha_nacimiento  DATE NULL,
    telefono          VARCHAR(20)  NULL,
    whatsapp          VARCHAR(20)  NULL,
    email             VARCHAR(150) NULL,
    direccion         VARCHAR(255) NULL,
    cedula_pasaporte  VARCHAR(20)  NULL,
    seguro_medico_id  INT UNSIGNED NULL,
    numero_afiliado   VARCHAR(50)  NULL,
    observaciones     TEXT NULL,
    estado            ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_by        INT UNSIGNED NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_clientes_codigo (codigo_cliente),
    UNIQUE KEY uq_clientes_cedula (cedula_pasaporte),
    KEY idx_clientes_nombre (nombres, apellidos),
    KEY idx_clientes_telefono (telefono),
    FULLTEXT KEY ftx_clientes_busqueda (nombres, apellidos),
    CONSTRAINT fk_clientes_seguro FOREIGN KEY (seguro_medico_id) REFERENCES seguros_medicos(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_clientes_creador FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Clientes/pacientes de la optica';

-- =====================================================================
-- SECCION 3: HISTORIAL CLINICO OFTALMOLOGICO
-- =====================================================================

CREATE TABLE antecedentes_medicos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id   INT UNSIGNED NOT NULL,
    familiares   TEXT NULL,
    personales   TEXT NULL,
    quirurgicos  TEXT NULL,
    alergias     TEXT NULL,
    medicamentos TEXT NULL,
    updated_by   INT UNSIGNED NULL,
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_antecedentes_cliente (cliente_id),
    CONSTRAINT fk_antecedentes_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_antecedentes_usuario FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Antecedentes del paciente. Un registro vivo por cliente (se actualiza, no se re-captura en cada consulta)';

CREATE TABLE expedientes_clinicos (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id        INT UNSIGNED NOT NULL,
    doctor_id         INT UNSIGNED NULL,
    fecha             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    motivo_consulta   TEXT NOT NULL,
    indicaciones      TEXT NULL,
    observaciones     TEXT NULL,
    proxima_cita      DATE NULL,
    estado            ENUM('abierto','cerrado') NOT NULL DEFAULT 'abierto',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_expedientes_cliente_fecha (cliente_id, fecha),
    KEY idx_expedientes_doctor (doctor_id),
    CONSTRAINT fk_expedientes_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_expedientes_doctor  FOREIGN KEY (doctor_id)  REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Encabezado de cada consulta/entrada del expediente clinico (historial cronologico = listado por cliente_id, fecha)';

CREATE TABLE agudeza_visual (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id         INT UNSIGNED NOT NULL,
    od_sin_correccion     VARCHAR(15) NULL,
    od_con_correccion     VARCHAR(15) NULL,
    oi_sin_correccion     VARCHAR(15) NULL,
    oi_con_correccion     VARCHAR(15) NULL,
    od_vision_cercana     VARCHAR(15) NULL,
    oi_vision_cercana     VARCHAR(15) NULL,
    od_vision_lejana      VARCHAR(15) NULL,
    oi_vision_lejana      VARCHAR(15) NULL,
    UNIQUE KEY uq_av_expediente (expediente_id),
    CONSTRAINT fk_av_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Agudeza visual OD/OI, con y sin correccion, vision cercana y lejana';

CREATE TABLE refraccion (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id  INT UNSIGNED NOT NULL,
    od_esfera      DECIMAL(5,2) NULL,
    od_cilindro    DECIMAL(5,2) NULL,
    od_eje         SMALLINT UNSIGNED NULL COMMENT '0-180 grados',
    od_adicion     DECIMAL(4,2) NULL,
    od_prisma      VARCHAR(20)  NULL,
    oi_esfera      DECIMAL(5,2) NULL,
    oi_cilindro    DECIMAL(5,2) NULL,
    oi_eje         SMALLINT UNSIGNED NULL,
    oi_adicion     DECIMAL(4,2) NULL,
    oi_prisma      VARCHAR(20)  NULL,
    UNIQUE KEY uq_refraccion_expediente (expediente_id),
    CONSTRAINT fk_refraccion_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT chk_refraccion_od_eje CHECK (od_eje IS NULL OR od_eje BETWEEN 0 AND 180),
    CONSTRAINT chk_refraccion_oi_eje CHECK (oi_eje IS NULL OR oi_eje BETWEEN 0 AND 180)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Refraccion: esfera, cilindro, eje, adicion, prisma por ojo';

CREATE TABLE distancia_pupilar (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id  INT UNSIGNED NOT NULL,
    dp_binocular   DECIMAL(4,1) NULL,
    dp_od          DECIMAL(4,1) NULL COMMENT 'DP monocular OD',
    dp_oi          DECIMAL(4,1) NULL COMMENT 'DP monocular OI',
    UNIQUE KEY uq_dp_expediente (expediente_id),
    CONSTRAINT fk_dp_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Distancia pupilar binocular y monocular';

CREATE TABLE queratometria (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id  INT UNSIGNED NOT NULL,
    od_k1          DECIMAL(5,2) NULL,
    od_k2          DECIMAL(5,2) NULL,
    od_eje         SMALLINT UNSIGNED NULL,
    oi_k1          DECIMAL(5,2) NULL,
    oi_k2          DECIMAL(5,2) NULL,
    oi_eje         SMALLINT UNSIGNED NULL,
    UNIQUE KEY uq_kerato_expediente (expediente_id),
    CONSTRAINT fk_kerato_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Queratometria K1/K2/eje por ojo';

CREATE TABLE tonometria (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id  INT UNSIGNED NOT NULL,
    od_valor       DECIMAL(4,1) NULL COMMENT 'mmHg',
    oi_valor       DECIMAL(4,1) NULL COMMENT 'mmHg',
    metodo         VARCHAR(50)  NULL COMMENT 'Ej: Aplanacion Goldmann, No contacto',
    hora           TIME NULL,
    UNIQUE KEY uq_tono_expediente (expediente_id),
    CONSTRAINT fk_tono_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Tonometria (presion intraocular) por ojo';

CREATE TABLE biomicroscopia (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id       INT UNSIGNED NOT NULL,
    od_parpados         VARCHAR(255) NULL,
    od_conjuntiva       VARCHAR(255) NULL,
    od_cornea           VARCHAR(255) NULL,
    od_camara_anterior  VARCHAR(255) NULL,
    od_iris             VARCHAR(255) NULL,
    od_cristalino       VARCHAR(255) NULL,
    oi_parpados         VARCHAR(255) NULL,
    oi_conjuntiva       VARCHAR(255) NULL,
    oi_cornea           VARCHAR(255) NULL,
    oi_camara_anterior  VARCHAR(255) NULL,
    oi_iris             VARCHAR(255) NULL,
    oi_cristalino       VARCHAR(255) NULL,
    observaciones       TEXT NULL,
    UNIQUE KEY uq_biomicro_expediente (expediente_id),
    CONSTRAINT fk_biomicro_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Biomicroscopia (lampara de hendidura) por estructura y ojo';

CREATE TABLE fondo_ojo (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id  INT UNSIGNED NOT NULL,
    od_papila      VARCHAR(255) NULL,
    od_retina      VARCHAR(255) NULL,
    od_macula      VARCHAR(255) NULL,
    od_vasos       VARCHAR(255) NULL,
    od_periferia   VARCHAR(255) NULL,
    oi_papila      VARCHAR(255) NULL,
    oi_retina      VARCHAR(255) NULL,
    oi_macula      VARCHAR(255) NULL,
    oi_vasos       VARCHAR(255) NULL,
    oi_periferia   VARCHAR(255) NULL,
    observaciones  TEXT NULL,
    UNIQUE KEY uq_fondo_expediente (expediente_id),
    CONSTRAINT fk_fondo_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Examen de fondo de ojo por estructura y ojo';

CREATE TABLE diagnosticos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id  INT UNSIGNED NOT NULL,
    diagnostico    VARCHAR(500) NOT NULL,
    cie10          VARCHAR(10)  NULL COMMENT 'Codigo CIE-10 opcional',
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_diagnosticos_expediente (expediente_id),
    CONSTRAINT fk_diagnosticos_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Uno o varios diagnosticos por consulta';

CREATE TABLE tratamientos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id  INT UNSIGNED NOT NULL,
    tratamiento    VARCHAR(500) NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_tratamientos_expediente (expediente_id),
    CONSTRAINT fk_tratamientos_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Uno o varios tratamientos indicados por consulta';

CREATE TABLE expediente_adjuntos (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id  INT UNSIGNED NOT NULL,
    tipo           ENUM('pdf','imagen','estudio') NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    ruta_archivo   VARCHAR(255) NOT NULL,
    descripcion    VARCHAR(255) NULL,
    subido_por     INT UNSIGNED NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_adjuntos_expediente (expediente_id),
    CONSTRAINT fk_adjuntos_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_adjuntos_usuario FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='PDFs, imagenes y estudios anexados al expediente';

CREATE TABLE citas (
    id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id            INT UNSIGNED NOT NULL,
    doctor_id             INT UNSIGNED NULL,
    fecha                 DATE NOT NULL,
    hora                  TIME NOT NULL,
    motivo                VARCHAR(255) NULL,
    estado                ENUM('pendiente','confirmada','cancelada','finalizada') NOT NULL DEFAULT 'pendiente',
    recordatorio_enviado  TINYINT(1) NOT NULL DEFAULT 0,
    notas                 TEXT NULL,
    created_by            INT UNSIGNED NULL,
    created_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_citas_fecha (fecha, hora),
    KEY idx_citas_cliente (cliente_id),
    KEY idx_citas_doctor (doctor_id, fecha),
    CONSTRAINT fk_citas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_citas_doctor  FOREIGN KEY (doctor_id)  REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_citas_creador FOREIGN KEY (created_by) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Agenda medica / citas';

-- =====================================================================
-- SECCION 4: RECETAS
-- =====================================================================

CREATE TABLE recetas (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expediente_id       INT UNSIGNED NULL,
    cliente_id          INT UNSIGNED NOT NULL,
    doctor_id           INT UNSIGNED NOT NULL,
    fecha               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    od_esfera           DECIMAL(5,2) NULL,
    od_cilindro         DECIMAL(5,2) NULL,
    od_eje              SMALLINT UNSIGNED NULL,
    od_adicion          DECIMAL(4,2) NULL,
    od_dp               DECIMAL(4,1) NULL,
    oi_esfera           DECIMAL(5,2) NULL,
    oi_cilindro         DECIMAL(5,2) NULL,
    oi_eje              SMALLINT UNSIGNED NULL,
    oi_adicion          DECIMAL(4,2) NULL,
    oi_dp               DECIMAL(4,1) NULL,
    tipo_lente          VARCHAR(100) NULL COMMENT 'Monofocal, Bifocal, Progresivo, Contacto...',
    material            VARCHAR(100) NULL COMMENT 'CR-39, Policarbonato, Alto indice...',
    color               VARCHAR(50)  NULL,
    tratamiento_lente   VARCHAR(150) NULL COMMENT 'Antirreflejo, Filtro azul, Fotocromatico...',
    observaciones       TEXT NULL,
    codigo_validacion   VARCHAR(64)  NOT NULL COMMENT 'Token unico para validar la receta via QR',
    estado              ENUM('activa','anulada') NOT NULL DEFAULT 'activa',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_recetas_codigo (codigo_validacion),
    KEY idx_recetas_cliente (cliente_id),
    KEY idx_recetas_doctor (doctor_id),
    CONSTRAINT fk_recetas_expediente FOREIGN KEY (expediente_id) REFERENCES expedientes_clinicos(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_recetas_cliente    FOREIGN KEY (cliente_id)    REFERENCES clientes(id)  ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_recetas_doctor     FOREIGN KEY (doctor_id)     REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Recetas opticas imprimibles, validables por QR';

-- =====================================================================
-- SECCION 5: INVENTARIO
-- =====================================================================

CREATE TABLE categorias_productos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre       VARCHAR(80)  NOT NULL,
    descripcion  VARCHAR(255) NULL,
    estado       ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Monturas, Lentes, Lentes de contacto, Gafas de sol, Accesorios, Medicamentos, Insumos';

CREATE TABLE proveedores (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(150) NOT NULL,
    contacto_nombre VARCHAR(100) NULL,
    telefono        VARCHAR(20)  NULL,
    email           VARCHAR(150) NULL,
    direccion       VARCHAR(255) NULL,
    rnc             VARCHAR(20)  NULL,
    estado          ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Proveedores de productos';

CREATE TABLE productos (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    codigo            VARCHAR(30)  NOT NULL,
    codigo_barras     VARCHAR(50)  NULL,
    categoria_id      INT UNSIGNED NOT NULL,
    nombre            VARCHAR(150) NOT NULL,
    marca             VARCHAR(80)  NULL,
    modelo            VARCHAR(80)  NULL,
    color             VARCHAR(50)  NULL,
    material          VARCHAR(80)  NULL,
    proveedor_id      INT UNSIGNED NULL,
    costo             DECIMAL(10,2) NOT NULL DEFAULT 0,
    precio            DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock_actual      INT NOT NULL DEFAULT 0 COMMENT 'Mantenido automaticamente por trigger desde movimientos_inventario',
    stock_minimo      INT UNSIGNED NOT NULL DEFAULT 0,
    ubicacion         VARCHAR(100) NULL,
    lote              VARCHAR(50)  NULL,
    fecha_vencimiento DATE NULL,
    imagen            VARCHAR(255) NULL,
    estado            ENUM('activo','inactivo','descontinuado') NOT NULL DEFAULT 'activo',
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_productos_codigo (codigo),
    UNIQUE KEY uq_productos_barras (codigo_barras),
    KEY idx_productos_categoria (categoria_id),
    KEY idx_productos_proveedor (proveedor_id),
    KEY idx_productos_nombre (nombre),
    CONSTRAINT fk_productos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias_productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_productos_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Catalogo de productos: monturas, lentes, lentes de contacto, gafas de sol, accesorios, medicamentos, insumos';

CREATE TABLE movimientos_inventario (
    id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    producto_id      INT UNSIGNED NOT NULL,
    tipo             ENUM('entrada','salida','transferencia','ajuste') NOT NULL,
    cantidad         INT NOT NULL COMMENT 'Con signo: positivo aumenta stock, negativo lo reduce',
    costo_unitario   DECIMAL(10,2) NULL,
    motivo           VARCHAR(255) NULL,
    referencia_tipo  VARCHAR(30)  NULL COMMENT 'compra, factura, ajuste_manual, devolucion',
    referencia_id    INT UNSIGNED NULL,
    ubicacion_origen  VARCHAR(100) NULL,
    ubicacion_destino VARCHAR(100) NULL,
    usuario_id       INT UNSIGNED NOT NULL,
    fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_movimientos_producto_fecha (producto_id, fecha),
    KEY idx_movimientos_referencia (referencia_tipo, referencia_id),
    CONSTRAINT fk_movimientos_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_movimientos_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Kardex: entradas, salidas, transferencias y ajustes. Unica fuente de verdad del stock';

-- =====================================================================
-- SECCION 6: COMPRAS
-- =====================================================================

CREATE TABLE ordenes_compra (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    numero_orden   VARCHAR(30)  NOT NULL,
    proveedor_id   INT UNSIGNED NOT NULL,
    fecha          DATE NOT NULL,
    estado         ENUM('pendiente','recibida_parcial','recibida','cancelada') NOT NULL DEFAULT 'pendiente',
    total          DECIMAL(12,2) NOT NULL DEFAULT 0,
    observaciones  VARCHAR(500) NULL,
    usuario_id     INT UNSIGNED NOT NULL,
    created_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_ordenes_numero (numero_orden),
    KEY idx_ordenes_proveedor (proveedor_id),
    CONSTRAINT fk_ordenes_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_ordenes_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)   ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ordenes de compra a proveedores';

CREATE TABLE ordenes_compra_detalle (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    orden_compra_id  INT UNSIGNED NOT NULL,
    producto_id      INT UNSIGNED NOT NULL,
    cantidad         INT UNSIGNED NOT NULL,
    costo_unitario   DECIMAL(10,2) NOT NULL,
    subtotal         DECIMAL(12,2) NOT NULL,
    KEY idx_ocd_orden (orden_compra_id),
    CONSTRAINT fk_ocd_orden    FOREIGN KEY (orden_compra_id) REFERENCES ordenes_compra(id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_ocd_producto FOREIGN KEY (producto_id)     REFERENCES productos(id)      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Detalle de lineas de cada orden de compra';

CREATE TABLE compras (
    id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    proveedor_id             INT UNSIGNED NOT NULL,
    orden_compra_id          INT UNSIGNED NULL,
    numero_factura_proveedor VARCHAR(50) NULL,
    fecha                    DATE NOT NULL,
    subtotal                 DECIMAL(12,2) NOT NULL DEFAULT 0,
    itbis                    DECIMAL(12,2) NOT NULL DEFAULT 0,
    total                    DECIMAL(12,2) NOT NULL DEFAULT 0,
    saldo_pendiente          DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado_pago              ENUM('pendiente','parcial','pagada') NOT NULL DEFAULT 'pendiente',
    created_by               INT UNSIGNED NOT NULL,
    created_at               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_compras_proveedor (proveedor_id),
    KEY idx_compras_fecha (fecha),
    CONSTRAINT fk_compras_proveedor FOREIGN KEY (proveedor_id)    REFERENCES proveedores(id)   ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_compras_orden     FOREIGN KEY (orden_compra_id) REFERENCES ordenes_compra(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_compras_usuario   FOREIGN KEY (created_by)      REFERENCES usuarios(id)       ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Facturas de compra recibidas de proveedores (cuentas por pagar = suma de saldo_pendiente)';

CREATE TABLE compras_detalle (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compra_id      INT UNSIGNED NOT NULL,
    producto_id    INT UNSIGNED NOT NULL,
    cantidad       INT UNSIGNED NOT NULL,
    costo_unitario DECIMAL(10,2) NOT NULL,
    subtotal       DECIMAL(12,2) NOT NULL,
    KEY idx_cd_compra (compra_id),
    CONSTRAINT fk_cd_compra   FOREIGN KEY (compra_id)   REFERENCES compras(id)   ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_cd_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Detalle de lineas de cada compra';

CREATE TABLE pagos_proveedores (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    compra_id     INT UNSIGNED NOT NULL,
    proveedor_id  INT UNSIGNED NOT NULL,
    monto         DECIMAL(12,2) NOT NULL,
    metodo_pago   ENUM('efectivo','tarjeta','transferencia','cheque') NOT NULL,
    referencia    VARCHAR(100) NULL,
    fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id    INT UNSIGNED NOT NULL,
    KEY idx_pagosprov_compra (compra_id),
    CONSTRAINT fk_pagosprov_compra    FOREIGN KEY (compra_id)    REFERENCES compras(id)     ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pagosprov_proveedor FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_pagosprov_usuario   FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Abonos/pagos realizados a proveedores (cuentas por pagar)';

-- =====================================================================
-- SECCION 7: FACTURACION
-- =====================================================================

CREATE TABLE promociones (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre         VARCHAR(150) NOT NULL,
    tipo           ENUM('porcentaje','monto_fijo') NOT NULL,
    valor          DECIMAL(10,2) NOT NULL,
    aplicable_a    ENUM('producto','categoria','todo') NOT NULL DEFAULT 'todo',
    referencia_id  INT UNSIGNED NULL COMMENT 'producto_id o categoria_id segun aplicable_a',
    fecha_inicio   DATE NOT NULL,
    fecha_fin      DATE NOT NULL,
    estado         ENUM('activo','inactivo') NOT NULL DEFAULT 'activo',
    KEY idx_promociones_vigencia (fecha_inicio, fecha_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Descuentos y promociones vigentes';

CREATE TABLE facturas (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo              ENUM('venta_producto','venta_medica','mixta','cotizacion','apartado') NOT NULL,
    ncf               VARCHAR(19)  NULL,
    tipo_ncf          VARCHAR(3)   NULL,
    cliente_id        INT UNSIGNED NOT NULL,
    usuario_id        INT UNSIGNED NOT NULL,
    fecha             DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    subtotal          DECIMAL(12,2) NOT NULL DEFAULT 0,
    descuento         DECIMAL(12,2) NOT NULL DEFAULT 0,
    itbis             DECIMAL(12,2) NOT NULL DEFAULT 0,
    total             DECIMAL(12,2) NOT NULL DEFAULT 0,
    condicion_pago    ENUM('contado','credito') NOT NULL DEFAULT 'contado',
    saldo_pendiente   DECIMAL(12,2) NOT NULL DEFAULT 0,
    estado            ENUM('pendiente','pagada','anulada','vencida') NOT NULL DEFAULT 'pendiente',
    factura_referencia_id INT UNSIGNED NULL COMMENT 'Para notas de credito/debito o facturacion desde cotizacion/apartado',
    observaciones     VARCHAR(500) NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_facturas_cliente (cliente_id),
    KEY idx_facturas_fecha (fecha),
    KEY idx_facturas_estado (estado),
    CONSTRAINT fk_facturas_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_facturas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_facturas_referencia FOREIGN KEY (factura_referencia_id) REFERENCES facturas(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Facturas, cotizaciones y apartados. NCF preparado para regimen fiscal dominicano (DGII)';

CREATE TABLE facturas_detalle (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factura_id      INT UNSIGNED NOT NULL,
    producto_id     INT UNSIGNED NULL COMMENT 'NULL para lineas de servicio (ej. consulta medica)',
    descripcion     VARCHAR(255) NOT NULL,
    cantidad        DECIMAL(10,2) NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento       DECIMAL(10,2) NOT NULL DEFAULT 0,
    subtotal        DECIMAL(12,2) NOT NULL,
    KEY idx_fd_factura (factura_id),
    CONSTRAINT fk_fd_factura  FOREIGN KEY (factura_id)  REFERENCES facturas(id)  ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_fd_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Lineas de detalle de cada factura/cotizacion/apartado';

CREATE TABLE factura_pagos (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factura_id   INT UNSIGNED NOT NULL,
    metodo_pago  ENUM('efectivo','tarjeta','transferencia','cheque') NOT NULL,
    monto        DECIMAL(12,2) NOT NULL,
    referencia   VARCHAR(100) NULL,
    fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id   INT UNSIGNED NOT NULL,
    KEY idx_fp_factura (factura_id),
    CONSTRAINT fk_fp_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_fp_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Pagos aplicados a facturas. Soporta pago mixto (varias filas por factura)';

CREATE TABLE notas_credito (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factura_id  INT UNSIGNED NOT NULL,
    motivo      VARCHAR(255) NOT NULL,
    monto       DECIMAL(12,2) NOT NULL,
    fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id  INT UNSIGNED NOT NULL,
    estado      ENUM('activa','anulada') NOT NULL DEFAULT 'activa',
    CONSTRAINT fk_nc_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nc_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notas de credito sobre facturas';

CREATE TABLE notas_debito (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factura_id  INT UNSIGNED NOT NULL,
    motivo      VARCHAR(255) NOT NULL,
    monto       DECIMAL(12,2) NOT NULL,
    fecha       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id  INT UNSIGNED NOT NULL,
    estado      ENUM('activa','anulada') NOT NULL DEFAULT 'activa',
    CONSTRAINT fk_nd_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_nd_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Notas de debito sobre facturas';

CREATE TABLE devoluciones (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    factura_id   INT UNSIGNED NOT NULL,
    producto_id  INT UNSIGNED NOT NULL,
    cantidad     INT UNSIGNED NOT NULL,
    motivo       VARCHAR(255) NULL,
    monto        DECIMAL(12,2) NOT NULL,
    fecha        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id   INT UNSIGNED NOT NULL,
    CONSTRAINT fk_dev_factura  FOREIGN KEY (factura_id)  REFERENCES facturas(id)  ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_dev_producto FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_dev_usuario  FOREIGN KEY (usuario_id)  REFERENCES usuarios(id)  ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Devoluciones de productos facturados (genera movimiento de entrada en inventario)';

-- =====================================================================
-- SECCION 8: CAJA
-- =====================================================================

CREATE TABLE caja_sesiones (
    id                     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id             INT UNSIGNED NOT NULL,
    fecha_apertura         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    monto_apertura         DECIMAL(12,2) NOT NULL DEFAULT 0,
    fecha_cierre           DATETIME NULL,
    monto_cierre_declarado DECIMAL(12,2) NULL,
    monto_esperado         DECIMAL(12,2) NULL,
    diferencia             DECIMAL(12,2) NULL,
    estado                 ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
    observaciones          VARCHAR(500) NULL,
    KEY idx_caja_usuario_estado (usuario_id, estado),
    CONSTRAINT fk_caja_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Apertura y cierre de caja por turno/usuario';

CREATE TABLE caja_movimientos (
    id               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    caja_sesion_id   INT UNSIGNED NOT NULL,
    tipo             ENUM('ingreso','egreso','venta') NOT NULL,
    concepto         VARCHAR(255) NOT NULL,
    monto            DECIMAL(12,2) NOT NULL,
    referencia_tipo  VARCHAR(30) NULL,
    referencia_id    INT UNSIGNED NULL,
    fecha            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id       INT UNSIGNED NOT NULL,
    KEY idx_cajamov_sesion (caja_sesion_id),
    CONSTRAINT fk_cajamov_sesion  FOREIGN KEY (caja_sesion_id) REFERENCES caja_sesiones(id) ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT fk_cajamov_usuario FOREIGN KEY (usuario_id)     REFERENCES usuarios(id)      ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Ingresos y egresos de cada sesion de caja';

-- =====================================================================
-- SECCION 9: CUENTAS POR COBRAR
-- =====================================================================

CREATE TABLE abonos_clientes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cliente_id    INT UNSIGNED NOT NULL,
    factura_id    INT UNSIGNED NULL,
    monto         DECIMAL(12,2) NOT NULL,
    metodo_pago   ENUM('efectivo','tarjeta','transferencia','cheque') NOT NULL,
    fecha         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    usuario_id    INT UNSIGNED NOT NULL,
    observaciones VARCHAR(255) NULL,
    KEY idx_abonos_cliente (cliente_id),
    CONSTRAINT fk_abonos_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_abonos_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_abonos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Abonos de clientes a credito (cuentas por cobrar)';

SET FOREIGN_KEY_CHECKS = 1;
