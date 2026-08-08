<?php use App\Core\Auth; use App\Core\Csrf; use App\Core\Url; $tituloPagina = 'Usuarios'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <p class="text-muted-soft mb-0">Cuentas del personal y su rol asignado.</p>
    <?php if (Auth::puede('usuarios.crear')): ?>
        <a href="<?= e(Url::to('usuarios/crear')) ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Nuevo usuario</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead>
                <tr><th>Usuario</th><th>Rol</th><th>Contacto</th><th>Ultimo acceso</th><th>Estado</th><th class="text-end">Acciones</th></tr>
            </thead>
            <tbody>
            <?php foreach ($usuarios as $u): ?>
                <tr>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar-circle"><?= e(mb_strtoupper(mb_substr($u['nombre'], 0, 1))) ?></span>
                            <div>
                                <div class="fw-semibold"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></div>
                                <div class="small text-muted-soft font-mono"><?= e($u['username']) ?> · <?= e($u['codigo']) ?></div>
                            </div>
                        </div>
                    </td>
                    <td><span class="badge badge-neutral"><?= e($u['rol_nombre']) ?></span></td>
                    <td class="small"><?= e($u['email']) ?><br><span class="text-muted-soft"><?= e($u['telefono'] ?? '—') ?></span></td>
                    <td class="small text-muted-soft"><?= $u['ultimo_login'] ? fecha_hora($u['ultimo_login']) : 'Nunca' ?></td>
                    <td><span class="<?= estado_badge($u['estado']) ?>"><?= e(ucfirst($u['estado'])) ?></span></td>
                    <td class="text-end">
                        <?php if (Auth::puede('usuarios.editar')): ?>
                            <a href="<?= e(Url::to('usuarios/editar/' . $u['id'])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="<?= e(Url::to('usuarios/restablecer-password/' . $u['id'])) ?>" method="POST" class="d-inline" data-confirmar="¿Generar una contraseña temporal para este usuario?">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-secondary" title="Restablecer contraseña"><i class="bi bi-key"></i></button>
                            </form>
                        <?php endif; ?>
                        <?php if (Auth::puede('usuarios.eliminar') && $u['id'] !== Auth::id()): ?>
                            <form action="<?= e(Url::to('usuarios/eliminar/' . $u['id'])) ?>" method="POST" class="d-inline" data-confirmar="¿Desactivar a este usuario?">
                                <?= Csrf::field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
