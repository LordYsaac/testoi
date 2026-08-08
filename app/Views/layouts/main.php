<?php

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Session;
use App\Core\Url;

$temaPreferido = $_COOKIE['tema'] ?? 'claro';
$bsTheme = $temaPreferido === 'oscuro' ? 'dark' : 'light';
$tituloPagina = $tituloPagina ?? 'Panel';
?>
<!DOCTYPE html>
<html lang="es" data-bs-theme="<?= e($bsTheme) ?>" data-tema-app="<?= e($temaPreferido) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina) ?> · <?= e(APP_NAME) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="<?= e(Url::asset('css/app.css')) ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
    <aside class="app-sidebar">
        <div class="brand">
            <div class="brand-mark"></div>
            <div>
                <div class="brand-name"><?= e(APP_NAME) ?></div>
                <div class="brand-sub">Gestion integral</div>
            </div>
        </div>

        <nav class="app-nav">
            <a class="nav-link <?= Url::esActiva('dashboard') || Url::actual() === '' ? 'active' : '' ?>" href="<?= e(Url::to('dashboard')) ?>">
                <i class="bi bi-grid-1x2"></i> Panel principal
            </a>

            <?php if (Auth::puede('clientes.ver')): ?>
            <div class="nav-section">Pacientes</div>
            <a class="nav-link <?= Url::esActiva('clientes') ? 'active' : '' ?>" href="<?= e(Url::to('clientes')) ?>">
                <i class="bi bi-people"></i> Clientes
            </a>
            <?php endif; ?>

            <?php if (Auth::puede('productos.ver') || Auth::puede('proveedores.ver') || Auth::puede('compras.ver')): ?>
            <div class="nav-section">Inventario y compras</div>
                <?php if (Auth::puede('productos.ver')): ?>
                <a class="nav-link <?= Url::esActiva('productos') ? 'active' : '' ?>" href="<?= e(Url::to('productos')) ?>"><i class="bi bi-box-seam"></i> Inventario</a>
                <?php endif; ?>
                <?php if (Auth::puede('proveedores.ver')): ?>
                <a class="nav-link <?= Url::esActiva('proveedores') ? 'active' : '' ?>" href="<?= e(Url::to('proveedores')) ?>"><i class="bi bi-truck"></i> Proveedores</a>
                <?php endif; ?>
                <?php if (Auth::puede('compras.ver')): ?>
                <a class="nav-link <?= Url::esActiva('compras') ? 'active' : '' ?>" href="<?= e(Url::to('compras')) ?>"><i class="bi bi-cart-check"></i> Compras</a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (Auth::puede('facturas.ver') || Auth::puede('caja.abrir') || Auth::puede('cuentas_cobrar.ver')): ?>
            <div class="nav-section">Ventas y caja</div>
                <?php if (Auth::puede('facturas.ver')): ?>
                <a class="nav-link <?= Url::esActiva('facturas') ? 'active' : '' ?>" href="<?= e(Url::to('facturas')) ?>"><i class="bi bi-receipt"></i> Facturacion</a>
                <?php endif; ?>
                <?php if (Auth::puede('caja.abrir')): ?>
                <a class="nav-link <?= Url::esActiva('caja') ? 'active' : '' ?>" href="<?= e(Url::to('caja')) ?>"><i class="bi bi-cash-stack"></i> Caja</a>
                <?php endif; ?>
                <?php if (Auth::puede('cuentas_cobrar.ver')): ?>
                <a class="nav-link <?= Url::esActiva('cuentas-por-cobrar') ? 'active' : '' ?>" href="<?= e(Url::to('cuentas-por-cobrar')) ?>"><i class="bi bi-wallet2"></i> Cuentas por cobrar</a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (Auth::puede('usuarios.ver') || Auth::puede('roles.ver') || Auth::puede('configuracion.ver')): ?>
            <div class="nav-section">Administracion</div>
                <?php if (Auth::puede('usuarios.ver')): ?>
                <a class="nav-link <?= Url::esActiva('usuarios') ? 'active' : '' ?>" href="<?= e(Url::to('usuarios')) ?>"><i class="bi bi-person-badge"></i> Usuarios</a>
                <?php endif; ?>
                <?php if (Auth::puede('roles.ver')): ?>
                <a class="nav-link <?= Url::esActiva('roles') ? 'active' : '' ?>" href="<?= e(Url::to('roles')) ?>"><i class="bi bi-shield-check"></i> Roles y permisos</a>
                <?php endif; ?>
            <?php endif; ?>
        </nav>

        <div class="sidebar-foot">
            v1.0 · Fase 1<br>© <?= date('Y') ?> <?= e(APP_NAME) ?>
        </div>
    </aside>

    <div class="app-main">
        <header class="app-topbar">
            <button id="btn-menu-movil" class="btn btn-sm btn-outline-secondary d-lg-none" type="button">
                <i class="bi bi-list"></i>
            </button>

            <h1 class="h5 mb-0 font-display flex-grow-1"><?= e($tituloPagina) ?></h1>

            <button id="btn-tema" class="theme-toggle" type="button" title="Cambiar tema">
                <i id="icono-tema" class="bi bi-moon-stars"></i>
            </button>

            <div class="dropdown">
                <button class="btn btn-sm d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown">
                    <span class="avatar-circle" style="width:34px;height:34px;font-size:.8rem;">
                        <?= e(mb_strtoupper(mb_substr(Auth::nombre(), 0, 1))) ?>
                    </span>
                    <span class="d-none d-md-inline small"><?= e(Auth::nombre()) ?></span>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><span class="dropdown-item-text small text-muted-soft"><?= e(Auth::rolNombre()) ?></span></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="<?= e(Url::to('cambiar-password')) ?>"><i class="bi bi-key me-2"></i>Cambiar contraseña</a></li>
                    <li><a class="dropdown-item text-danger" href="<?= e(Url::to('logout')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesion</a></li>
                </ul>
            </div>
        </header>

        <main class="app-content">
            <?php foreach (Session::consumirFlash() as $flash): ?>
                <div class="alert alert-flash alert-<?= $flash['tipo'] === 'exito' ? 'success' : 'danger' ?> d-flex align-items-center gap-2" role="alert">
                    <i class="bi <?= $flash['tipo'] === 'exito' ? 'bi-check-circle' : 'bi-exclamation-triangle' ?>"></i>
                    <div><?= e($flash['mensaje']) ?></div>
                </div>
            <?php endforeach; ?>

            <?= $contenido ?>
        </main>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(Url::asset('js/app.js')) ?>"></script>
</body>
</html>
