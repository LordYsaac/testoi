<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CajaController;
use App\Controllers\CitasController;
use App\Controllers\ClientesController;
use App\Controllers\ComprasController;
use App\Controllers\CuentasPorCobrarController;
use App\Controllers\DashboardController;
use App\Controllers\ExpedientesController;
use App\Controllers\FacturasController;
use App\Controllers\ProductosController;
use App\Controllers\ProveedoresController;
use App\Controllers\RecetasController;
use App\Controllers\ReportesController;
use App\Controllers\RolesController;
use App\Controllers\UsuariosController;
use App\Core\Middleware\AuthMiddleware;
use App\Core\Middleware\RoleMiddleware;
use App\Core\Router;

$router = new Router();

// -----------------------------------------------------------------------
// Autenticacion (publico)
// -----------------------------------------------------------------------
$router->get('login', [AuthController::class, 'mostrarLogin']);
$router->post('login', [AuthController::class, 'login']);
$router->get('logout', [AuthController::class, 'logout'], [AuthMiddleware::class]);
$router->get('cambiar-password', [AuthController::class, 'mostrarCambiarPassword'], [AuthMiddleware::class]);
$router->post('cambiar-password', [AuthController::class, 'cambiarPassword'], [AuthMiddleware::class]);

// Verificacion 2FA (segundo paso del login: la sesion aun no esta completa, no lleva AuthMiddleware)
$router->get('verificar-2fa', [AuthController::class, 'mostrarVerificar2FA']);
$router->post('verificar-2fa', [AuthController::class, 'verificar2FA']);

// Seguridad de la cuenta (activar/desactivar 2FA)
$router->get('seguridad', [AuthController::class, 'mostrarSeguridad'], [AuthMiddleware::class]);
$router->post('seguridad/2fa/generar', [AuthController::class, 'generar2FA'], [AuthMiddleware::class]);
$router->post('seguridad/2fa/activar', [AuthController::class, 'activar2FA'], [AuthMiddleware::class]);
$router->post('seguridad/2fa/desactivar', [AuthController::class, 'desactivar2FA'], [AuthMiddleware::class]);

// Validacion publica de recetas por QR (sin login: la usa cualquiera que escanee el codigo)
$router->get('validar-receta/{codigo}', [RecetasController::class, 'validarPublico']);

// -----------------------------------------------------------------------
// Dashboard
// -----------------------------------------------------------------------
$router->get('', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('dashboard', [DashboardController::class, 'index'], [AuthMiddleware::class]);
$router->get('notificaciones/marcar-leida/{id}', [DashboardController::class, 'marcarNotificacionLeida'], [AuthMiddleware::class]);

// -----------------------------------------------------------------------
// Usuarios
// -----------------------------------------------------------------------
$router->get('usuarios', [UsuariosController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'usuarios.ver']]);
$router->get('usuarios/crear', [UsuariosController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'usuarios.crear']]);
$router->post('usuarios/guardar', [UsuariosController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'usuarios.crear']]);
$router->get('usuarios/editar/{id}', [UsuariosController::class, 'editar'], [AuthMiddleware::class, [RoleMiddleware::class, 'usuarios.editar']]);
$router->post('usuarios/actualizar/{id}', [UsuariosController::class, 'actualizar'], [AuthMiddleware::class, [RoleMiddleware::class, 'usuarios.editar']]);
$router->post('usuarios/eliminar/{id}', [UsuariosController::class, 'eliminar'], [AuthMiddleware::class, [RoleMiddleware::class, 'usuarios.eliminar']]);
$router->post('usuarios/restablecer-password/{id}', [UsuariosController::class, 'restablecerPassword'], [AuthMiddleware::class, [RoleMiddleware::class, 'usuarios.editar']]);

// -----------------------------------------------------------------------
// Roles y permisos
// -----------------------------------------------------------------------
$router->get('roles', [RolesController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'roles.ver']]);
$router->get('roles/editar/{id}', [RolesController::class, 'editar'], [AuthMiddleware::class, [RoleMiddleware::class, 'roles.editar']]);
$router->post('roles/actualizar/{id}', [RolesController::class, 'actualizar'], [AuthMiddleware::class, [RoleMiddleware::class, 'roles.editar']]);
$router->get('roles/crear', [RolesController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'roles.crear']]);
$router->post('roles/guardar', [RolesController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'roles.crear']]);

// -----------------------------------------------------------------------
// Clientes
// -----------------------------------------------------------------------
$router->get('clientes', [ClientesController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.ver']]);
$router->get('clientes/buscar', [ClientesController::class, 'buscarAjax'], [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.ver']]);
$router->get('clientes/crear', [ClientesController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.crear']]);
$router->post('clientes/guardar', [ClientesController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.crear']]);
$router->get('clientes/ver/{id}', [ClientesController::class, 'ver'], [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.ver']]);
$router->get('clientes/editar/{id}', [ClientesController::class, 'editar'], [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.editar']]);
$router->post('clientes/actualizar/{id}', [ClientesController::class, 'actualizar'], [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.editar']]);
$router->post('clientes/eliminar/{id}', [ClientesController::class, 'eliminar'], [AuthMiddleware::class, [RoleMiddleware::class, 'clientes.eliminar']]);
$router->post('clientes/antecedentes/{id}', [ClientesController::class, 'guardarAntecedentes'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.editar']]);

// -----------------------------------------------------------------------
// Expedientes clinicos (historial oftalmologico)
// -----------------------------------------------------------------------
$router->get('expedientes/cliente/{clienteId}', [ExpedientesController::class, 'porCliente'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.ver']]);
$router->get('expedientes/crear/{clienteId}', [ExpedientesController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.crear']]);
$router->post('expedientes/guardar', [ExpedientesController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.crear']]);
$router->get('expedientes/ver/{id}', [ExpedientesController::class, 'ver'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.ver']]);
$router->get('expedientes/editar/{id}', [ExpedientesController::class, 'editar'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.editar']]);
$router->post('expedientes/actualizar/{id}', [ExpedientesController::class, 'actualizar'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.editar']]);
$router->post('expedientes/adjuntar/{id}', [ExpedientesController::class, 'subirAdjunto'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.editar']]);
$router->get('expedientes/adjunto/eliminar/{id}', [ExpedientesController::class, 'eliminarAdjunto'], [AuthMiddleware::class, [RoleMiddleware::class, 'expedientes.editar']]);

// -----------------------------------------------------------------------
// Recetas
// -----------------------------------------------------------------------
$router->get('recetas/crear/{clienteId}', [RecetasController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'recetas.crear']]);
$router->post('recetas/guardar', [RecetasController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'recetas.crear']]);
$router->get('recetas/ver/{id}', [RecetasController::class, 'ver'], [AuthMiddleware::class, [RoleMiddleware::class, 'recetas.ver']]);
$router->get('recetas/imprimir/{id}', [RecetasController::class, 'imprimir'], [AuthMiddleware::class, [RoleMiddleware::class, 'recetas.imprimir']]);
$router->get('recetas/pdf/{id}', [RecetasController::class, 'pdf'], [AuthMiddleware::class, [RoleMiddleware::class, 'recetas.imprimir']]);
$router->post('recetas/anular/{id}', [RecetasController::class, 'anular'], [AuthMiddleware::class, [RoleMiddleware::class, 'recetas.anular']]);

// -----------------------------------------------------------------------
// Inventario / Productos
// -----------------------------------------------------------------------
$router->get('productos', [ProductosController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'productos.ver']]);
$router->get('productos/crear', [ProductosController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'productos.crear']]);
$router->post('productos/guardar', [ProductosController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'productos.crear']]);
$router->get('productos/ver/{id}', [ProductosController::class, 'ver'], [AuthMiddleware::class, [RoleMiddleware::class, 'productos.ver']]);
$router->get('productos/editar/{id}', [ProductosController::class, 'editar'], [AuthMiddleware::class, [RoleMiddleware::class, 'productos.editar']]);
$router->post('productos/actualizar/{id}', [ProductosController::class, 'actualizar'], [AuthMiddleware::class, [RoleMiddleware::class, 'productos.editar']]);
$router->post('productos/eliminar/{id}', [ProductosController::class, 'eliminar'], [AuthMiddleware::class, [RoleMiddleware::class, 'productos.eliminar']]);
$router->post('productos/ajustar/{id}', [ProductosController::class, 'ajustarInventario'], [AuthMiddleware::class, [RoleMiddleware::class, 'inventario.ajustar']]);

// -----------------------------------------------------------------------
// Proveedores
// -----------------------------------------------------------------------
$router->get('proveedores', [ProveedoresController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'proveedores.ver']]);
$router->get('proveedores/crear', [ProveedoresController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'proveedores.crear']]);
$router->post('proveedores/guardar', [ProveedoresController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'proveedores.crear']]);
$router->get('proveedores/ver/{id}', [ProveedoresController::class, 'ver'], [AuthMiddleware::class, [RoleMiddleware::class, 'proveedores.ver']]);
$router->get('proveedores/editar/{id}', [ProveedoresController::class, 'editar'], [AuthMiddleware::class, [RoleMiddleware::class, 'proveedores.editar']]);
$router->post('proveedores/actualizar/{id}', [ProveedoresController::class, 'actualizar'], [AuthMiddleware::class, [RoleMiddleware::class, 'proveedores.editar']]);
$router->post('proveedores/eliminar/{id}', [ProveedoresController::class, 'eliminar'], [AuthMiddleware::class, [RoleMiddleware::class, 'proveedores.editar']]);

// -----------------------------------------------------------------------
// Compras
// -----------------------------------------------------------------------
$router->get('compras', [ComprasController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'compras.ver']]);
$router->get('compras/crear', [ComprasController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'compras.crear']]);
$router->post('compras/guardar', [ComprasController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'compras.crear']]);
$router->get('compras/ver/{id}', [ComprasController::class, 'ver'], [AuthMiddleware::class, [RoleMiddleware::class, 'compras.ver']]);
$router->post('compras/pagar/{id}', [ComprasController::class, 'registrarPago'], [AuthMiddleware::class, [RoleMiddleware::class, 'compras.pagar']]);

// -----------------------------------------------------------------------
// Facturacion
// -----------------------------------------------------------------------
$router->get('facturas', [FacturasController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'facturas.ver']]);
$router->get('facturas/crear', [FacturasController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'facturas.crear']]);
$router->post('facturas/guardar', [FacturasController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'facturas.crear']]);
$router->get('facturas/ver/{id}', [FacturasController::class, 'ver'], [AuthMiddleware::class, [RoleMiddleware::class, 'facturas.ver']]);
$router->get('facturas/imprimir/{id}', [FacturasController::class, 'imprimir'], [AuthMiddleware::class, [RoleMiddleware::class, 'facturas.ver']]);
$router->post('facturas/anular/{id}', [FacturasController::class, 'anular'], [AuthMiddleware::class, [RoleMiddleware::class, 'facturas.anular']]);

// -----------------------------------------------------------------------
// Caja
// -----------------------------------------------------------------------
$router->get('caja', [CajaController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'caja.abrir']]);
$router->post('caja/abrir', [CajaController::class, 'abrir'], [AuthMiddleware::class, [RoleMiddleware::class, 'caja.abrir']]);
$router->post('caja/cerrar', [CajaController::class, 'cerrar'], [AuthMiddleware::class, [RoleMiddleware::class, 'caja.cerrar']]);
$router->post('caja/movimiento', [CajaController::class, 'registrarMovimiento'], [AuthMiddleware::class, [RoleMiddleware::class, 'caja.movimientos']]);

// -----------------------------------------------------------------------
// Cuentas por cobrar
// -----------------------------------------------------------------------
$router->get('cuentas-por-cobrar', [CuentasPorCobrarController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'cuentas_cobrar.ver']]);
$router->get('cuentas-por-cobrar/cliente/{clienteId}', [CuentasPorCobrarController::class, 'cliente'], [AuthMiddleware::class, [RoleMiddleware::class, 'cuentas_cobrar.ver']]);
$router->post('cuentas-por-cobrar/abonar/{facturaId}', [CuentasPorCobrarController::class, 'abonar'], [AuthMiddleware::class, [RoleMiddleware::class, 'cuentas_cobrar.abonar']]);

// -----------------------------------------------------------------------
// Agenda medica (Citas)
// -----------------------------------------------------------------------
$router->get('citas', [CitasController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'citas.ver']]);
$router->get('citas/dia/{fecha}', [CitasController::class, 'dia'], [AuthMiddleware::class, [RoleMiddleware::class, 'citas.ver']]);
$router->get('citas/crear', [CitasController::class, 'crear'], [AuthMiddleware::class, [RoleMiddleware::class, 'citas.crear']]);
$router->post('citas/guardar', [CitasController::class, 'guardar'], [AuthMiddleware::class, [RoleMiddleware::class, 'citas.crear']]);
$router->get('citas/editar/{id}', [CitasController::class, 'editar'], [AuthMiddleware::class, [RoleMiddleware::class, 'citas.editar']]);
$router->post('citas/actualizar/{id}', [CitasController::class, 'actualizar'], [AuthMiddleware::class, [RoleMiddleware::class, 'citas.editar']]);
$router->post('citas/estado/{id}', [CitasController::class, 'cambiarEstado'], [AuthMiddleware::class, [RoleMiddleware::class, 'citas.editar']]);

// -----------------------------------------------------------------------
// Reportes
// -----------------------------------------------------------------------
$router->get('reportes', [ReportesController::class, 'index'], [AuthMiddleware::class, [RoleMiddleware::class, 'reportes.ver']]);
$router->get('reportes/ventas', [ReportesController::class, 'ventas'], [AuthMiddleware::class, [RoleMiddleware::class, 'reportes.ver']]);
$router->get('reportes/productos-mas-vendidos', [ReportesController::class, 'productosMasVendidos'], [AuthMiddleware::class, [RoleMiddleware::class, 'reportes.ver']]);
$router->get('reportes/clientes-frecuentes', [ReportesController::class, 'clientesFrecuentes'], [AuthMiddleware::class, [RoleMiddleware::class, 'reportes.ver']]);
$router->get('reportes/inventario', [ReportesController::class, 'inventario'], [AuthMiddleware::class, [RoleMiddleware::class, 'reportes.ver']]);
$router->get('reportes/pacientes-atendidos', [ReportesController::class, 'pacientesAtendidos'], [AuthMiddleware::class, [RoleMiddleware::class, 'reportes.ver']]);
$router->get('reportes/recetas-emitidas', [ReportesController::class, 'recetasEmitidas'], [AuthMiddleware::class, [RoleMiddleware::class, 'reportes.ver']]);
$router->get('reportes/cuentas', [ReportesController::class, 'cuentas'], [AuthMiddleware::class, [RoleMiddleware::class, 'reportes.ver']]);

return $router;
