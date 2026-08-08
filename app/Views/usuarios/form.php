<?php
use App\Core\Csrf;
use App\Core\Url;
$esEdicion = !empty($usuario['id']);
$tituloPagina = $esEdicion ? 'Editar usuario' : 'Nuevo usuario';
$accion = $esEdicion ? Url::to('usuarios/actualizar/' . $usuario['id']) : Url::to('usuarios/guardar');
?>

<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="card p-4">
            <form method="POST" action="<?= e($accion) ?>" enctype="multipart/form-data">
                <?= Csrf::field() ?>

                <div class="form-section-title">Datos del usuario</div>
                <div class="row g-3 mb-3">
                    <div class="col-md-3 text-center">
                        <span class="avatar-circle mx-auto mb-2" style="width:72px;height:72px;font-size:1.5rem;">
                            <?= e(mb_strtoupper(mb_substr($usuario['nombre'] ?? 'U', 0, 1))) ?>
                        </span>
                        <input type="file" name="foto" class="form-control form-control-sm" accept="image/*">
                    </div>
                    <div class="col-md-9">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control <?= isset($errores['nombre']) ? 'is-invalid' : '' ?>" value="<?= e($usuario['nombre'] ?? '') ?>" required>
                                <?php if (isset($errores['nombre'])): ?><div class="invalid-feedback"><?= e($errores['nombre']) ?></div><?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Apellido</label>
                                <input type="text" name="apellido" class="form-control <?= isset($errores['apellido']) ? 'is-invalid' : '' ?>" value="<?= e($usuario['apellido'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Codigo</label>
                                <input type="text" class="form-control font-mono" value="<?= e($usuario['codigo'] ?? $siguienteCodigo) ?>" disabled>
                            </div>
                            <?php if (!$esEdicion): ?>
                            <div class="col-md-6">
                                <label class="form-label">Nombre de usuario</label>
                                <input type="text" name="username" class="form-control <?= isset($errores['username']) ? 'is-invalid' : '' ?>" value="<?= e($usuario['username'] ?? '') ?>" required minlength="4">
                                <?php if (isset($errores['username'])): ?><div class="invalid-feedback"><?= e($errores['username']) ?></div><?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Correo electronico</label>
                        <input type="email" name="email" class="form-control <?= isset($errores['email']) ? 'is-invalid' : '' ?>" value="<?= e($usuario['email'] ?? '') ?>" required>
                        <?php if (isset($errores['email'])): ?><div class="invalid-feedback"><?= e($errores['email']) ?></div><?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Telefono</label>
                        <input type="text" name="telefono" class="form-control" value="<?= e($usuario['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Rol</label>
                        <select name="rol_id" class="form-select <?= isset($errores['rol_id']) ? 'is-invalid' : '' ?>" required>
                            <option value="">Seleccione...</option>
                            <?php foreach ($roles as $r): ?>
                                <option value="<?= (int) $r['id'] ?>" <?= (int) ($usuario['rol_id'] ?? 0) === (int) $r['id'] ? 'selected' : '' ?>><?= e($r['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">No. de colegiatura / exequatur <span class="text-muted-soft fw-normal">(doctores/optometras)</span></label>
                        <input type="text" name="cmd_colegiado" class="form-control" value="<?= e($usuario['cmd_colegiado'] ?? '') ?>">
                    </div>
                    <?php if (!$esEdicion): ?>
                    <div class="col-md-6">
                        <label class="form-label">Contraseña temporal</label>
                        <input type="password" name="password" class="form-control <?= isset($errores['password']) ? 'is-invalid' : '' ?>" minlength="8" required>
                        <div class="form-text">El usuario debera cambiarla en su primer inicio de sesion.</div>
                    </div>
                    <?php else: ?>
                    <div class="col-md-6">
                        <label class="form-label">Estado</label>
                        <select name="estado" class="form-select">
                            <option value="activo" <?= ($usuario['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($usuario['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="<?= e(Url::to('usuarios')) ?>" class="btn btn-outline-secondary">Cancelar</a>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
